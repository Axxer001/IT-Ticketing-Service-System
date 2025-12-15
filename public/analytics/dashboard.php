<?php
session_start();
require_once "../includes/sidebar_component.php";
require_once "../../classes/Database.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$database = new Database();
$db = $database->connect();

// Get date range from query params or default to last 30 days
$startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$endDate = $_GET['end_date'] ?? date('Y-m-d');

// Ticket Status Distribution
$query = "SELECT status, COUNT(*) as count FROM tickets 
          WHERE created_at BETWEEN :start AND :end GROUP BY status";
$stmt = $db->prepare($query);
$stmt->execute(['start' => $startDate, 'end' => $endDate]);
$statusData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Priority Distribution
$query = "SELECT priority, COUNT(*) as count FROM tickets 
          WHERE created_at BETWEEN :start AND :end GROUP BY priority";
$stmt = $db->prepare($query);
$stmt->execute(['start' => $startDate, 'end' => $endDate]);
$priorityData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Department Statistics
$query = "SELECT d.name, COUNT(t.id) as ticket_count 
          FROM tickets t 
          JOIN departments d ON t.department_id = d.id 
          WHERE t.created_at BETWEEN :start AND :end 
          GROUP BY d.id ORDER BY ticket_count DESC LIMIT 5";
$stmt = $db->prepare($query);
$stmt->execute(['start' => $startDate, 'end' => $endDate]);
$deptData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Daily Ticket Trend (Last 7 days)
$query = "SELECT DATE(created_at) as date, COUNT(*) as count 
          FROM tickets 
          WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
          GROUP BY DATE(created_at) ORDER BY date ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$trendData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Average Resolution Time
$query = "SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) as avg_hours 
          FROM tickets WHERE resolved_at IS NOT NULL 
          AND created_at BETWEEN :start AND :end";
$stmt = $db->prepare($query);
$stmt->execute(['start' => $startDate, 'end' => $endDate]);
$avgResolution = $stmt->fetch(PDO::FETCH_ASSOC);

// Provider Performance
$query = "SELECT sp.provider_name, 
          COUNT(t.id) as total_tickets,
          AVG(sp.rating_average) as avg_rating,
          SUM(CASE WHEN t.status = 'resolved' THEN 1 ELSE 0 END) as resolved_tickets
          FROM service_providers sp
          LEFT JOIN tickets t ON t.assigned_provider_id = sp.id
          WHERE t.created_at BETWEEN :start AND :end OR t.created_at IS NULL
          GROUP BY sp.id";
$stmt = $db->prepare($query);
$stmt->execute(['start' => $startDate, 'end' => $endDate]);
$providerStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= $_SESSION['theme'] ?? 'light' ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Analytics Dashboard - Nexon</title>
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

.nav-actions {
    display: flex;
    gap: 12px;
    align-items: center;
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

.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}

.page-title {
    font-size: 28px;
    font-weight: 700;
}

.date-filter {
    display: flex;
    gap: 12px;
    align-items: center;
}

.date-filter input {
    padding: 8px 12px;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    background: var(--bg-card);
    color: var(--text-primary);
}

.btn {
    padding: 8px 16px;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    color: white;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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
    font-size: 32px;
    font-weight: 700;
    color: var(--primary);
    margin-bottom: 8px;
}

.stat-label {
    font-size: 14px;
    color: var(--text-secondary);
}

.charts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
    gap: 24px;
    margin-bottom: 32px;
}

.chart-card {
    background: var(--bg-card);
    border-radius: 16px;
    padding: 24px;
    box-shadow: var(--shadow);
    border: 1px solid var(--border-color);
}

.chart-title {
    font-size: 18px;
    font-weight: 700;
    margin-bottom: 20px;
}

.table-card {
    background: var(--bg-card);
    border-radius: 16px;
    padding: 24px;
    box-shadow: var(--shadow);
    border: 1px solid var(--border-color);
    margin-bottom: 24px;
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
}

td {
    padding: 16px 12px;
    border-bottom: 1px solid var(--border-color);
}
</style>
</head>
<body>



<div class="main-content">
<div class="container">
    <div class="header">
        <h1 class="page-title">📊 Analytics Dashboard</h1>
        <form class="date-filter" method="GET">
            <input type="date" name="start_date" value="<?= $startDate ?>" required>
            <span>to</span>
            <input type="date" name="end_date" value="<?= $endDate ?>" required>
            <button type="submit" class="btn">Apply</button>
        </form>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value"><?= array_sum(array_column($statusData, 'count')) ?></div>
            <div class="stat-label">Total Tickets</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= round($avgResolution['avg_hours'] ?? 0, 1) ?>h</div>
            <div class="stat-label">Avg Resolution Time</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= count(array_filter($statusData, fn($s) => $s['status'] === 'resolved')) ?></div>
            <div class="stat-label">Resolved Tickets</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= count(array_filter($statusData, fn($s) => $s['status'] === 'pending')) ?></div>
            <div class="stat-label">Pending Tickets</div>
        </div>
    </div>

    <div class="table-card">
        <h2 class="chart-title">Provider Performance</h2>
        <table>
            <thead>
                <tr>
                    <th>Provider</th>
                    <th>Total Tickets</th>
                    <th>Resolved</th>
                    <th>Avg Rating</th>
                    <th>Resolution Rate</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($providerStats as $provider): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($provider['provider_name']) ?></strong></td>
                    <td><?= $provider['total_tickets'] ?></td>
                    <td><?= $provider['resolved_tickets'] ?></td>
                    <td><?= number_format($provider['avg_rating'], 2) ?> ⭐</td>
                    <td><?= $provider['total_tickets'] > 0 ? round(($provider['resolved_tickets'] / $provider['total_tickets']) * 100) : 0 ?>%</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
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