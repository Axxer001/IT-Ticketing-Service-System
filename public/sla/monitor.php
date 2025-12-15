<?php
session_start();
require_once "../../classes/SLA.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}
require_once "../includes/sidebar_component.php";

$slaObj = new SLA();
$atRiskTickets = $slaObj->getAtRiskTickets();
$complianceStats = $slaObj->getComplianceStats();
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= $_SESSION['theme'] ?? 'light' ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SLA Monitor - Nexon</title>
<link rel="stylesheet" href="../../assets/css/theme.css">
<script>
    const PHP_SESSION_THEME = <?= json_encode($_SESSION['theme'] ?? 'light') ?>;
</script>
<style>
:root {
    --primary: #667eea;
    --secondary: #764ba2;
    --success: #10b981;
    --warning: #f59e0b;
    --danger: #ef4444;
    --bg-main: #f8fafc;
    --bg-card: #ffffff;
    --text-primary: #1e293b;
    --text-secondary: #64748b;
    --border-color: #e2e8f0;
    --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

[data-theme="dark"] {
    --bg-main: #0f172a;
    --bg-card: #1e293b;
    --text-primary: #f1f5f9;
    --text-secondary: #cbd5e1;
    --border-color: #334155;
}

* { margin: 0; padding: 0; box-sizing: border-box; }

body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    background: var(--bg-main);
    color: var(--text-primary);
}

.navbar {
    background: var(--bg-card);
    border-bottom: 1px solid var(--border-color);
    padding: 16px 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: var(--shadow);
}

.navbar-brand {
    font-size: 24px;
    font-weight: 800;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.back-btn {
    padding: 8px 16px;
    background: var(--bg-main);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    text-decoration: none;
    color: var(--text-primary);
    font-weight: 600;
}

.container {
    max-width: 1400px;
    margin: 24px auto;
    padding: 0 24px;
}

.page-header {
    margin-bottom: 24px;
}

.page-title {
    font-size: 28px;
    font-weight: 700;
    margin-bottom: 8px;
}

.page-subtitle {
    color: var(--text-secondary);
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 32px;
}

.stat-card {
    background: var(--bg-card);
    border-radius: 12px;
    padding: 24px;
    box-shadow: var(--shadow);
    border: 1px solid var(--border-color);
}

.stat-value {
    font-size: 36px;
    font-weight: 700;
    color: var(--primary);
    margin-bottom: 8px;
}

.stat-label {
    font-size: 14px;
    color: var(--text-secondary);
}

.card {
    background: var(--bg-card);
    border-radius: 16px;
    padding: 24px;
    box-shadow: var(--shadow);
    border: 1px solid var(--border-color);
}

.card-title {
    font-size: 20px;
    font-weight: 700;
    margin-bottom: 20px;
    padding-bottom: 16px;
    border-bottom: 1px solid var(--border-color);
}

table {
    width: 100%;
    border-collapse: collapse;
}

thead tr {
    border-bottom: 2px solid var(--border-color);
}

th {
    text-align: left;
    padding: 12px;
    font-weight: 600;
    color: var(--text-secondary);
    font-size: 13px;
    text-transform: uppercase;
}

td {
    padding: 16px 12px;
    border-bottom: 1px solid var(--border-color);
}

tbody tr:hover {
    background: var(--bg-main);
}

.badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    display: inline-block;
}

.badge-on_track { background: rgba(16, 185, 129, 0.15); color: #10b981; }
.badge-at_risk { background: rgba(245, 158, 11, 0.15); color: #f59e0b; }
.badge-breached { background: rgba(239, 68, 68, 0.15); color: #ef4444; }

.sla-at-risk { background: rgba(245, 158, 11, 0.05); }
.sla-breached { background: rgba(239, 68, 68, 0.05); border-left: 4px solid #ef4444; }

.empty-state {
    text-align: center;
    padding: 40px 20px;
    color: var(--text-secondary);
}
</style>
</head>
<body>



<div class="main-content">
<div class="container">
    <div class="page-header">
        <h1 class="page-title">⏱️ SLA Monitor</h1>
        <p class="page-subtitle">Track service level agreement compliance</p>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value"><?= $complianceStats['response_compliance'] ?>%</div>
            <div class="stat-label">Response SLA Compliance</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= $complianceStats['resolution_compliance'] ?>%</div>
            <div class="stat-label">Resolution SLA Compliance</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= count($atRiskTickets) ?></div>
            <div class="stat-label">Tickets At Risk</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= $complianceStats['avg_response_time'] ?></div>
            <div class="stat-label">Avg Response Time</div>
        </div>
    </div>

    <div class="card">
        <h2 class="card-title">Tickets At Risk</h2>
        <?php if (count($atRiskTickets) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Ticket #</th>
                    <th>Description</th>
                    <th>Priority</th>
                    <th>Time Remaining</th>
                    <th>SLA Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($atRiskTickets as $ticket): ?>
                <tr class="<?= $ticket['sla_status'] == 'breached' ? 'sla-breached' : 'sla-at-risk' ?>">
                    <td><strong><?= htmlspecialchars($ticket['ticket_number']) ?></strong></td>
                    <td><?= htmlspecialchars(substr($ticket['issue_description'], 0, 50)) ?>...</td>
                    <td><span class="badge badge-<?= $ticket['priority'] ?>"><?= strtoupper($ticket['priority']) ?></span></td>
                    <td><?= htmlspecialchars($ticket['time_remaining']) ?></td>
                    <td><span class="badge badge-<?= $ticket['sla_status'] ?>"><?= strtoupper(str_replace('_', ' ', $ticket['sla_status'])) ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty-state">
            <p>✅ All tickets are within SLA compliance</p>
        </div>
        <?php endif; ?>
    </div>
</div>
</div>

<script>
    const PHP_SESSION_THEME = <?= json_encode($_SESSION['theme'] ?? 'light') ?>;
</script>
<script src="../../assets/js/theme.js?v=2"></script>
<script src="../../assets/js/notifications.js?v=2"></script>
</body>
</html>