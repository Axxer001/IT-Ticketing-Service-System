<?php
session_start();
require_once "../../classes/Analytics.php";

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$analyticsObj = new Analytics();
$dateFrom = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
$dateTo = $_GET['date_to'] ?? date('Y-m-d');

$analytics = $analyticsObj->getTicketAnalytics($dateFrom, $dateTo);
$providerPerformance = $analyticsObj->getProviderPerformance();
$departmentPerformance = $analyticsObj->getDepartmentPerformance();
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= $_SESSION['theme'] ?? 'light' ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Analytics - Nexon</title>
<link rel="stylesheet" href="../../assets/css/theme.css">
<script>
    const PHP_SESSION_THEME = <?= json_encode($_SESSION['theme'] ?? 'light') ?>;
</script>
<style>
:root {
    --primary: #667eea;
    --secondary: #764ba2;
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

.filters {
    background: var(--bg-card);
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 24px;
    border: 1px solid var(--border-color);
    display: flex;
    gap: 16px;
    align-items: flex-end;
}

.filter-group {
    flex: 1;
}

.filter-group label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    margin-bottom: 8px;
    color: var(--text-secondary);
}

.filter-group input {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    background: var(--bg-main);
    color: var(--text-primary);
}

.btn-primary {
    padding: 10px 20px;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
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
    padding: 20px;
    box-shadow: var(--shadow);
    border: 1px solid var(--border-color);
}

.stat-label {
    font-size: 14px;
    color: var(--text-secondary);
    margin-bottom: 8px;
}

.stat-value {
    font-size: 32px;
    font-weight: 700;
}

.card {
    background: var(--bg-card);
    border-radius: 16px;
    padding: 24px;
    box-shadow: var(--shadow);
    border: 1px solid var(--border-color);
    margin-bottom: 24px;
}

.card-title {
    font-size: 18px;
    font-weight: 700;
    margin-bottom: 16px;
    padding-bottom: 16px;
    border-bottom: 1px solid var(--border-color);
}

table {
    width: 100%;
    border-collapse: collapse;
}

th {
    text-align: left;
    padding: 12px;
    font-weight: 600;
    color: var(--text-secondary);
    font-size: 13px;
    border-bottom: 2px solid var(--border-color);
}

td {
    padding: 12px;
    border-bottom: 1px solid var(--border-color);
}

.grid-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
}

@media (max-width: 768px) {
    .grid-2 {
        grid-template-columns: 1fr;
    }
}
</style>
</head>
<body>

<nav class="navbar">
    <div class="navbar-brand">NEXON</div>
    <a href="../dashboard.php" class="back-btn">← Dashboard</a>
</nav>

<div class="container">
    <div class="page-header">
        <h1 class="page-title">Analytics & Reports</h1>
        <p class="page-subtitle">System performance and statistics</p>
    </div>

    <form class="filters" method="GET">
        <div class="filter-group">
            <label>From Date</label>
            <input type="date" name="date_from" value="<?= $dateFrom ?>">
        </div>
        <div class="filter-group">
            <label>To Date</label>
            <input type="date" name="date_to" value="<?= $dateTo ?>">
        </div>
        <button type="submit" class="btn-primary">Apply Filter</button>
    </form>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Average Resolution Time</div>
            <div class="stat-value"><?= $analytics['avg_resolution_hours'] ?>h</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Total Tickets (Period)</div>
            <div class="stat-value"><?= count($analytics['daily_creation']) ?></div>
        </div>
    </div>

    <div class="grid-2">
        <div class="card">
            <h2 class="card-title">Tickets by Status</h2>
            <table>
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>Count</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($analytics['by_status'] as $item): ?>
                    <tr>
                        <td style="text-transform:capitalize"><?= str_replace('_', ' ', $item['status']) ?></td>
                        <td><strong><?= $item['count'] ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="card">
            <h2 class="card-title">Tickets by Priority</h2>
            <table>
                <thead>
                    <tr>
                        <th>Priority</th>
                        <th>Count</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($analytics['by_priority'] as $item): ?>
                    <tr>
                        <td style="text-transform:capitalize"><?= $item['priority'] ?></td>
                        <td><strong><?= $item['count'] ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <h2 class="card-title">Top Departments by Ticket Volume</h2>
        <table>
            <thead>
                <tr>
                    <th>Department</th>
                    <th>Total Tickets</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($analytics['by_department'] as $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item['name']) ?></td>
                    <td><strong><?= $item['count'] ?></strong></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="card">
        <h2 class="card-title">Service Provider Performance</h2>
        <table>
            <thead>
                <tr>
                    <th>Provider</th>
                    <th>Total Tickets</th>
                    <th>Resolved</th>
                    <th>Avg Resolution (hrs)</th>
                    <th>Rating</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($providerPerformance as $provider): ?>
                <tr>
                    <td><?= htmlspecialchars($provider['provider_name']) ?></td>
                    <td><?= $provider['total_tickets'] ?></td>
                    <td><?= $provider['resolved_tickets'] ?></td>
                    <td><?= round($provider['avg_resolution_hours'] ?? 0, 1) ?></td>
                    <td><?= number_format($provider['rating_average'], 1) ?>⭐ (<?= $provider['total_ratings'] ?>)</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<script src="../../assets/js/theme.js"></script>
<script src="../../assets/js/notifications.js"></script>
</body>
</html>