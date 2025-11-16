<?php
session_start();
require_once "../../classes/User.php";
require_once "../../classes/Ticket.php";

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'employee') {
    header("Location: ../login.php");
    exit;
}

$userObj = new User();
$ticketObj = new Ticket();
$profile = $userObj->getUserProfile($_SESSION['user_id']);

$filters = ['employee_id' => $profile['profile']['id']];
$tickets = $ticketObj->getTickets($filters, 100, 0);
$stats = $ticketObj->getStatistics($filters);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Tickets Report - Nexon</title>
<style>
@media print {
    .no-print { display: none !important; }
    @page { margin: 20mm; }
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
    display: flex;
    justify-content: space-between;
    align-items: center;
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
    margin-left: 12px;
}

.report-content {
    background: white;
    padding: 40px;
    border-radius: 12px;
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
}

.report-title {
    font-size: 20px;
    margin-top: 8px;
}

.employee-info {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 32px;
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
}

.info-value {
    font-size: 16px;
    font-weight: 600;
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
    padding: 20px;
    text-align: center;
}

.stat-value {
    font-size: 32px;
    font-weight: 700;
    color: #667eea;
}

.stat-label {
    font-size: 13px;
    color: #666;
    margin-top: 8px;
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

.badge-pending { background: #fef3c7; color: #d97706; }
.badge-assigned { background: #dbeafe; color: #2563eb; }
.badge-in-progress { background: #ddd6fe; color: #7c3aed; }
.badge-resolved { background: #d1fae5; color: #059669; }
.badge-closed { background: #e5e7eb; color: #6b7280; }

.report-footer {
    margin-top: 48px;
    padding-top: 24px;
    border-top: 2px solid #e0e0e0;
    text-align: center;
    font-size: 11px;
    color: #999;
}

@media print {
    body { padding: 0; }
    .stats-row { grid-template-columns: repeat(2, 1fr); }
    .employee-info { grid-template-columns: 1fr 1fr; }
}
</style>
</head>
<body>

<div class="no-print">
    <h2 style="color:#667eea">📝 My Tickets Report</h2>
    <div>
        <button class="btn btn-primary" onclick="window.print()">🖨️ Print Report</button>
        <a href="index.php" class="btn btn-secondary">← Back</a>
    </div>
</div>

<div class="report-content">
    <div class="report-header">
        <div class="company-name">NEXON IT TICKETING SYSTEM</div>
        <div class="report-title">Personal Ticket Report</div>
    </div>

    <div class="employee-info">
        <div class="info-item">
            <span class="info-label">Employee Name</span>
            <span class="info-value">
                <?= htmlspecialchars($profile['profile']['first_name'] . ' ' . $profile['profile']['last_name']) ?>
            </span>
        </div>
        <div class="info-item">
            <span class="info-label">Department</span>
            <span class="info-value"><?= htmlspecialchars($profile['profile']['department_name']) ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">Email</span>
            <span class="info-value"><?= htmlspecialchars($profile['email']) ?></span>
        </div>
    </div>

    <div class="stats-row">
        <div class="stat-box">
            <div class="stat-value"><?= $stats['total'] ?></div>
            <div class="stat-label">Total Tickets</div>
        </div>
        <div class="stat-box">
            <div class="stat-value"><?= $stats['by_status']['pending'] ?? 0 ?></div>
            <div class="stat-label">Pending</div>
        </div>
        <div class="stat-box">
            <div class="stat-value"><?= $stats['by_status']['in_progress'] ?? 0 ?></div>
            <div class="stat-label">In Progress</div>
        </div>
        <div class="stat-box">
            <div class="stat-value"><?= $stats['by_status']['resolved'] ?? 0 ?></div>
            <div class="stat-label">Resolved</div>
        </div>
    </div>

    <h3 style="font-size:16px; margin-bottom:16px; color:#667eea">All Tickets</h3>
    
    <?php if (empty($tickets)): ?>
    <div style="text-align:center; padding:48px; color:#999">
        <p>No tickets found</p>
    </div>
    <?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Ticket #</th>
                <th>Device</th>
                <th>Issue</th>
                <th>Priority</th>
                <th>Status</th>
                <th>Provider</th>
                <th>Created</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($tickets as $ticket): ?>
            <tr>
                <td><strong><?= htmlspecialchars($ticket['ticket_number']) ?></strong></td>
                <td><?= htmlspecialchars($ticket['device_type_name']) ?></td>
                <td style="max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap">
                    <?= htmlspecialchars(substr($ticket['device_name'], 0, 40)) ?>
                </td>
                <td style="text-transform:capitalize"><?= $ticket['priority'] ?></td>
                <td>
                    <span class="badge badge-<?= str_replace('_', '-', $ticket['status']) ?>">
                        <?= ucfirst(str_replace('_', ' ', $ticket['status'])) ?>
                    </span>
                </td>
                <td><?= htmlspecialchars($ticket['provider_name'] ?? '-') ?></td>
                <td><?= date('M j, Y', strtotime($ticket['created_at'])) ?></td>
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

<script src="../../assets/js/theme.js"></script>
</body>
</html>