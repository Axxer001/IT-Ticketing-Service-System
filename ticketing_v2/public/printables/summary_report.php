<?php
session_start();
require_once "../../classes/Printables.php";

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$printablesObj = new Printables();

$dateFrom = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
$dateTo = $_GET['date_to'] ?? date('Y-m-d');

$report = $printablesObj->getSummaryReport($dateFrom, $dateTo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Summary Report - Nexon</title>
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
}

.header-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.filters {
    display: flex;
    gap: 16px;
}

.form-group {
    flex: 1;
}

label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    font-size: 14px;
    color: #666;
}

input {
    width: 100%;
    padding: 10px 12px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-family: inherit;
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
    font-size: 32px;
    font-weight: 700;
    color: #667eea;
}

.report-title {
    font-size: 24px;
    margin-top: 8px;
}

.report-period {
    font-size: 14px;
    color: #666;
    margin-top: 8px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 40px;
}

.stat-box {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 24px;
    text-align: center;
}

.stat-value {
    font-size: 36px;
    font-weight: 700;
    color: #667eea;
}

.stat-label {
    font-size: 13px;
    color: #666;
    margin-top: 8px;
}

.section {
    margin-bottom: 40px;
}

.section-title {
    font-size: 18px;
    font-weight: 700;
    color: #667eea;
    margin-bottom: 16px;
    padding-bottom: 8px;
    border-bottom: 2px solid #e0e0e0;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
}

thead {
    background: #f8f9fa;
}

th {
    text-align: left;
    padding: 12px;
    font-weight: 600;
    font-size: 13px;
    color: #666;
    text-transform: uppercase;
    border-bottom: 2px solid #667eea;
}

td {
    padding: 12px;
    border-bottom: 1px solid #e0e0e0;
    font-size: 14px;
}

tr:hover {
    background: #f8f9fa;
}

.report-footer {
    margin-top: 48px;
    padding-top: 24px;
    border-top: 2px solid #e0e0e0;
    text-align: center;
    font-size: 11px;
    color: #999;
}

@media print {
    .stats-grid { grid-template-columns: repeat(2, 1fr); }
    body { padding: 0; }
}
</style>
</head>
<body>

<div class="no-print">
    <div class="header-actions">
        <h2 style="color:#667eea">📊 Summary Report Generator</h2>
        <div style="display:flex; gap:12px">
            <button class="btn btn-primary" onclick="window.print()">🖨️ Print</button>
            <a href="index.php" class="btn btn-secondary">← Back</a>
        </div>
    </div>

    <form class="filters" method="GET">
        <div class="form-group">
            <label>From Date</label>
            <input type="date" name="date_from" value="<?= $dateFrom ?>" required>
        </div>
        <div class="form-group">
            <label>To Date</label>
            <input type="date" name="date_to" value="<?= $dateTo ?>" required>
        </div>
        <div class="form-group" style="display:flex; align-items:flex-end">
            <button type="submit" class="btn btn-primary" style="width:100%">Generate</button>
        </div>
    </form>
</div>

<div class="report-content">
    <div class="report-header">
        <div class="company-name">NEXON IT TICKETING SYSTEM</div>
        <div class="report-title">Summary Report</div>
        <div class="report-period">
            Period: <?= date('F j, Y', strtotime($dateFrom)) ?> - <?= date('F j, Y', strtotime($dateTo)) ?>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-box">
            <div class="stat-value"><?= $report['stats']['total_tickets'] ?></div>
            <div class="stat-label">Total Tickets</div>
        </div>
        <div class="stat-box">
            <div class="stat-value"><?= $report['stats']['resolved_tickets'] ?></div>
            <div class="stat-label">Resolved</div>
        </div>
        <div class="stat-box">
            <div class="stat-value"><?= $report['stats']['active_tickets'] ?></div>
            <div class="stat-label">Active</div>
        </div>
        <div class="stat-box">
            <div class="stat-value"><?= round($report['stats']['avg_resolution_hours'] ?? 0, 1) ?>h</div>
            <div class="stat-label">Avg Resolution</div>
        </div>
    </div>

    <div class="section">
        <h2 class="section-title">Tickets by Status</h2>
        <table>
            <thead>
                <tr>
                    <th>Status</th>
                    <th>Count</th>
                    <th>Percentage</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $total = $report['stats']['total_tickets'];
                foreach ($report['by_status'] as $item): 
                    $pct = $total > 0 ? ($item['count'] / $total) * 100 : 0;
                ?>
                <tr>
                    <td style="text-transform:capitalize"><strong><?= str_replace('_', ' ', $item['status']) ?></strong></td>
                    <td><?= $item['count'] ?></td>
                    <td><?= number_format($pct, 1) ?>%</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="section">
        <h2 class="section-title">Tickets by Priority</h2>
        <table>
            <thead>
                <tr>
                    <th>Priority</th>
                    <th>Count</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($report['by_priority'] as $item): ?>
                <tr>
                    <td style="text-transform:capitalize"><strong><?= $item['priority'] ?></strong></td>
                    <td><?= $item['count'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="section">
        <h2 class="section-title">Top Departments</h2>
        <table>
            <thead>
                <tr>
                    <th>Department</th>
                    <th>Category</th>
                    <th>Tickets</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($report['by_department'] as $dept): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($dept['name']) ?></strong></td>
                    <td><?= htmlspecialchars($dept['category']) ?></td>
                    <td><?= $dept['count'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="section">
        <h2 class="section-title">Service Provider Performance</h2>
        <table>
            <thead>
                <tr>
                    <th>Provider</th>
                    <th>Total</th>
                    <th>Resolved</th>
                    <th>Avg Time (hrs)</th>
                    <th>Rating</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($report['providers'] as $provider): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($provider['provider_name']) ?></strong></td>
                    <td><?= $provider['total_tickets'] ?></td>
                    <td><?= $provider['resolved_tickets'] ?></td>
                    <td><?= round($provider['avg_resolution_hours'] ?? 0, 1) ?></td>
                    <td><?= number_format($provider['avg_rating'] ?? 0, 1) ?>★</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="report-footer">
        <p><strong>Nexon IT Ticketing System</strong></p>
        <p>Report Generated: <?= date('F j, Y g:i A') ?></p>
        <p>Confidential - For Internal Use Only</p>
    </div>
</div>

<script src="../../assets/js/theme.js"></script>
</body>
</html>