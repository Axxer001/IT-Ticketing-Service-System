<?php
session_start();
require_once "../../classes/User.php";
require_once "../../classes/Printables.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$userType = $_SESSION['user_type'];
if ($userType !== 'service_provider' && $userType !== 'admin') {
    header("Location: index.php");
    exit;
}

$printablesObj = new Printables();
$userObj = new User();

// Get providers list for admin
$providers = [];
if ($userType === 'admin') {
    $providers = $userObj->getAllServiceProviders();
}

// Determine provider ID
$providerId = null;
if ($userType === 'service_provider') {
    $profile = $userObj->getUserProfile($_SESSION['user_id']);
    $providerId = $profile['profile']['id'];
} elseif (isset($_GET['provider_id'])) {
    $providerId = $_GET['provider_id'];
}

// Get date range
$dateFrom = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
$dateTo = $_GET['date_to'] ?? date('Y-m-d');

// Get logs
$logs = null;
$providerInfo = null;
if ($providerId) {
    $logs = $printablesObj->getServiceProviderLogs($providerId, $dateFrom, $dateTo);
    
    // Get provider info
    $sql = "SELECT sp.*, u.email FROM service_providers sp 
            JOIN users u ON sp.user_id = u.id 
            WHERE sp.id = ?";
    $db = new Database();
    $stmt = $db->connect()->prepare($sql);
    $stmt->execute([$providerId]);
    $providerInfo = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Service Provider Logs - Nexon</title>
<link rel="stylesheet" href="../../assets/css/theme.css">
<style>
@media print {
    .no-print { display: none !important; }
    body { background: white; color: black; }
}

* { margin: 0; padding: 0; box-sizing: border-box; }

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    padding: 24px;
    background: #f8fafc;
    color: #333;
}

.no-print {
    background: white;
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 24px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.header-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}

.filters {
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
}

.form-group {
    flex: 1;
    min-width: 200px;
}

label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    font-size: 14px;
    color: #666;
}

select, input {
    width: 100%;
    padding: 10px 12px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-family: inherit;
    font-size: 14px;
}

.btn {
    padding: 10px 20px;
    border-radius: 8px;
    border: none;
    font-weight: 600;
    cursor: pointer;
    font-size: 14px;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
}

.btn-secondary {
    background: #f1f5f9;
    color: #334155;
}

.report-content {
    background: white;
    border-radius: 12px;
    padding: 40px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.report-header {
    text-align: center;
    border-bottom: 3px solid #667eea;
    padding-bottom: 24px;
    margin-bottom: 32px;
}

.company-name {
    font-size: 28px;
    font-weight: 700;
    color: #667eea;
    margin-bottom: 8px;
}

.report-title {
    font-size: 20px;
    color: #333;
}

.report-period {
    font-size: 14px;
    color: #666;
    margin-top: 8px;
}

.provider-info {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 32px;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.info-label {
    font-size: 12px;
    color: #666;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.info-value {
    font-size: 16px;
    font-weight: 600;
    color: #333;
}

.stats-row {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 32px;
}

.stat-box {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 16px;
    text-align: center;
}

.stat-value {
    font-size: 28px;
    font-weight: 700;
    color: #667eea;
    margin-bottom: 4px;
}

.stat-label {
    font-size: 12px;
    color: #666;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 32px;
}

thead {
    background: #f8f9fa;
}

th {
    text-align: left;
    padding: 12px;
    font-weight: 600;
    font-size: 12px;
    color: #666;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 2px solid #667eea;
}

td {
    padding: 12px;
    border-bottom: 1px solid #e0e0e0;
    font-size: 13px;
}

tr:hover {
    background: #f8f9fa;
}

.badge {
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    display: inline-block;
}

.badge-resolved { background: #d1fae5; color: #059669; }
.badge-in-progress { background: #ddd6fe; color: #7c3aed; }
.badge-assigned { background: #dbeafe; color: #2563eb; }
.badge-pending { background: #fef3c7; color: #d97706; }

.report-footer {
    margin-top: 48px;
    padding-top: 24px;
    border-top: 2px solid #e0e0e0;
    text-align: center;
    font-size: 11px;
    color: #999;
}

.empty-state {
    text-align: center;
    padding: 48px;
    color: #999;
}

@media print {
    @page { margin: 20mm; }
    body { padding: 0; }
    .stats-row { grid-template-columns: repeat(2, 1fr); }
}
</style>
</head>
<body>

<div class="no-print">
    <div class="header-actions">
        <h2 style="color:#667eea">📋 Service Provider Logs</h2>
        <div style="display:flex; gap:12px">
            <?php if ($logs): ?>
            <button class="btn btn-primary" onclick="window.print()">🖨️ Print Report</button>
            <?php endif; ?>
            <a href="index.php" class="btn btn-secondary">← Back</a>
        </div>
    </div>

    <form class="filters" method="GET">
        <?php if ($userType === 'admin'): ?>
        <div class="form-group">
            <label>Service Provider</label>
            <select name="provider_id" required>
                <option value="">-- Select Provider --</option>
                <?php foreach ($providers as $provider): ?>
                    <option value="<?= $provider['id'] ?>" <?= $providerId == $provider['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($provider['provider_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>
        
        <div class="form-group">
            <label>From Date</label>
            <input type="date" name="date_from" value="<?= $dateFrom ?>" required>
        </div>
        
        <div class="form-group">
            <label>To Date</label>
            <input type="date" name="date_to" value="<?= $dateTo ?>" required>
        </div>
        
        <div class="form-group" style="display:flex; align-items:flex-end">
            <button type="submit" class="btn btn-primary" style="width:100%">Generate Report</button>
        </div>
    </form>
</div>

<?php if ($logs && $providerInfo): ?>
<div class="report-content">
    <div class="report-header">
        <div class="company-name">NEXON IT TICKETING SYSTEM</div>
        <div class="report-title">Service Provider Activity Log</div>
        <div class="report-period">
            Period: <?= date('F j, Y', strtotime($dateFrom)) ?> - <?= date('F j, Y', strtotime($dateTo)) ?>
        </div>
    </div>

    <div class="provider-info">
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Provider Name</span>
                <span class="info-value"><?= htmlspecialchars($providerInfo['provider_name']) ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Email</span>
                <span class="info-value"><?= htmlspecialchars($providerInfo['email']) ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Specialization</span>
                <span class="info-value"><?= htmlspecialchars($providerInfo['specialization'] ?? 'General') ?></span>
            </div>
        </div>
    </div>

    <?php
    $totalTickets = count($logs);
    $resolvedTickets = count(array_filter($logs, fn($t) => $t['status'] === 'resolved'));
    $inProgressTickets = count(array_filter($logs, fn($t) => $t['status'] === 'in_progress'));
    $avgRating = 0;
    $ratedTickets = array_filter($logs, fn($t) => $t['rating'] !== null);
    if (!empty($ratedTickets)) {
        $avgRating = array_sum(array_column($ratedTickets, 'rating')) / count($ratedTickets);
    }
    ?>

    <div class="stats-row">
        <div class="stat-box">
            <div class="stat-value"><?= $totalTickets ?></div>
            <div class="stat-label">Total Tickets</div>
        </div>
        <div class="stat-box">
            <div class="stat-value"><?= $resolvedTickets ?></div>
            <div class="stat-label">Resolved</div>
        </div>
        <div class="stat-box">
            <div class="stat-value"><?= $inProgressTickets ?></div>
            <div class="stat-label">In Progress</div>
        </div>
        <div class="stat-box">
            <div class="stat-value"><?= number_format($avgRating, 1) ?>★</div>
            <div class="stat-label">Avg Rating</div>
        </div>
    </div>

    <h3 style="font-size:16px; margin-bottom:16px; color:#667eea">Ticket Details</h3>
    
    <?php if (empty($logs)): ?>
    <div class="empty-state">
        <p>No tickets found for the selected period</p>
    </div>
    <?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Ticket #</th>
                <th>Employee</th>
                <th>Department</th>
                <th>Device Type</th>
                <th>Status</th>
                <th>Created</th>
                <th>Rating</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($logs as $log): ?>
            <tr>
                <td><strong><?= htmlspecialchars($log['ticket_number']) ?></strong></td>
                <td><?= htmlspecialchars($log['first_name'] . ' ' . $log['last_name']) ?></td>
                <td><?= htmlspecialchars($log['department_name']) ?></td>
                <td><?= htmlspecialchars($log['device_type_name']) ?></td>
                <td>
                    <span class="badge badge-<?= str_replace('_', '-', $log['status']) ?>">
                        <?= ucfirst(str_replace('_', ' ', $log['status'])) ?>
                    </span>
                </td>
                <td><?= date('M j, Y', strtotime($log['created_at'])) ?></td>
                <td><?= $log['rating'] ? str_repeat('⭐', $log['rating']) : '-' ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <div class="report-footer">
        <p><strong>Nexon IT Ticketing System</strong></p>
        <p>Report Generated: <?= date('F j, Y g:i A') ?></p>
    </div>
</div>
<?php elseif (isset($_GET['provider_id']) || $userType === 'service_provider'): ?>
<div class="report-content">
    <div class="empty-state">
        <div style="font-size:64px; margin-bottom:16px">📋</div>
        <p>No data available for the selected criteria</p>
    </div>
</div>
<?php else: ?>
<div class="report-content">
    <div class="empty-state">
        <div style="font-size:64px; margin-bottom:16px">📋</div>
        <p>Please select a service provider and date range</p>
    </div>
</div>
<?php endif; ?>

<script src="../../assets/js/theme.js"></script>
</body>
</html>