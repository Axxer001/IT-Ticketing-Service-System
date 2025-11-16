<?php
session_start();
require_once "../../classes/User.php";
require_once "../../classes/Ticket.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$ticketObj = new Ticket();
$userObj = new User();
$userType = $_SESSION['user_type'];

// Build filters based on user type
$filters = [];
if ($userType === 'employee') {
    $profile = $userObj->getUserProfile($_SESSION['user_id']);
    $filters['employee_id'] = $profile['profile']['id'];
} elseif ($userType === 'service_provider') {
    $profile = $userObj->getUserProfile($_SESSION['user_id']);
    $filters['provider_id'] = $profile['profile']['id'];
}

// Apply search/filter from GET
if (!empty($_GET['status'])) {
    $filters['status'] = $_GET['status'];
}
if (!empty($_GET['priority'])) {
    $filters['priority'] = $_GET['priority'];
}
if (!empty($_GET['search'])) {
    $filters['search'] = $_GET['search'];
}

$tickets = $ticketObj->getTickets($filters, 50, 0);
$stats = $ticketObj->getStatistics($filters);
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= $_SESSION['theme'] ?? 'light' ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Tickets - Nexon</title>
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
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}

.page-title {
    font-size: 28px;
    font-weight: 700;
}

.filters {
    background: var(--bg-card);
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 24px;
    border: 1px solid var(--border-color);
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
}

.filter-group {
    flex: 1;
    min-width: 200px;
}

.filter-group label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    margin-bottom: 8px;
    color: var(--text-secondary);
}

.filter-group select,
.filter-group input {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    background: var(--bg-main);
    color: var(--text-primary);
    font-size: 14px;
}

.btn {
    padding: 10px 20px;
    border-radius: 10px;
    border: none;
    font-weight: 600;
    cursor: pointer;
    font-size: 14px;
    text-decoration: none;
    display: inline-block;
}

.btn-primary {
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    color: white;
}

.card {
    background: var(--bg-card);
    border-radius: 16px;
    padding: 24px;
    box-shadow: var(--shadow);
    border: 1px solid var(--border-color);
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

tr:hover {
    background: var(--bg-main);
}

.badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    display: inline-block;
}

.badge-pending { background: rgba(245, 158, 11, 0.15); color: #f59e0b; }
.badge-assigned { background: rgba(59, 130, 246, 0.15); color: #3b82f6; }
.badge-in-progress { background: rgba(139, 92, 246, 0.15); color: #8b5cf6; }
.badge-resolved { background: rgba(16, 185, 129, 0.15); color: #10b981; }
.badge-closed { background: rgba(100, 116, 139, 0.15); color: #64748b; }
.badge-low { background: rgba(59, 130, 246, 0.15); color: #3b82f6; }
.badge-medium { background: rgba(245, 158, 11, 0.15); color: #f59e0b; }
.badge-high { background: rgba(239, 68, 68, 0.15); color: #ef4444; }
.badge-critical { background: rgba(220, 38, 38, 0.2); color: #dc2626; }

.empty-state {
    text-align: center;
    padding: 48px 24px;
    color: var(--text-secondary);
}
</style>
</head>
<body>

<nav class="navbar">
    <div class="navbar-brand">NEXON</div>
    <div style="display:flex; gap:12px;">
        <a href="../printables/index.php" class="back-btn">📊 Reports</a>
        <a href="../dashboard.php" class="back-btn">← Dashboard</a>
    </div>
</nav>

<div class="container">
    <div class="page-header">
        <h1 class="page-title">My Tickets</h1>
        <?php if ($userType === 'employee'): ?>
            <a href="create.php" class="btn btn-primary">+ Create Ticket</a>
        <?php endif; ?>
    </div>

    <form class="filters" method="GET">
        <div class="filter-group">
            <label>Search</label>
            <input type="text" name="search" placeholder="Ticket #, description..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
        </div>
        <div class="filter-group">
            <label>Status</label>
            <select name="status">
                <option value="">All Status</option>
                <option value="pending" <?= ($_GET['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                <option value="assigned" <?= ($_GET['status'] ?? '') === 'assigned' ? 'selected' : '' ?>>Assigned</option>
                <option value="in_progress" <?= ($_GET['status'] ?? '') === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                <option value="resolved" <?= ($_GET['status'] ?? '') === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                <option value="closed" <?= ($_GET['status'] ?? '') === 'closed' ? 'selected' : '' ?>>Closed</option>
            </select>
        </div>
        <div class="filter-group">
            <label>Priority</label>
            <select name="priority">
                <option value="">All Priorities</option>
                <option value="low" <?= ($_GET['priority'] ?? '') === 'low' ? 'selected' : '' ?>>Low</option>
                <option value="medium" <?= ($_GET['priority'] ?? '') === 'medium' ? 'selected' : '' ?>>Medium</option>
                <option value="high" <?= ($_GET['priority'] ?? '') === 'high' ? 'selected' : '' ?>>High</option>
                <option value="critical" <?= ($_GET['priority'] ?? '') === 'critical' ? 'selected' : '' ?>>Critical</option>
            </select>
        </div>
        <div class="filter-group" style="display:flex; align-items:flex-end">
            <button type="submit" class="btn btn-primary" style="width:100%">Filter</button>
        </div>
    </form>

    <div class="card">
        <?php if (empty($tickets)): ?>
            <div class="empty-state">
                <div style="font-size:64px; margin-bottom:16px">📋</div>
                <p>No tickets found</p>
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Ticket #</th>
                        <?php if ($userType !== 'employee'): ?>
                            <th>Employee</th>
                        <?php endif; ?>
                        <?php if ($userType !== 'service_provider'): ?>
                            <th>Provider</th>
                        <?php endif; ?>
                        <th>Device</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tickets as $ticket): ?>
                        <tr onclick="location.href='view.php?id=<?= $ticket['id'] ?>'" style="cursor:pointer">
                            <td><strong><?= htmlspecialchars($ticket['ticket_number']) ?></strong></td>
                            <?php if ($userType !== 'employee'): ?>
                                <td><?= htmlspecialchars($ticket['first_name'] . ' ' . $ticket['last_name']) ?></td>
                            <?php endif; ?>
                            <?php if ($userType !== 'service_provider'): ?>
                                <td><?= htmlspecialchars($ticket['provider_name'] ?? 'Unassigned') ?></td>
                            <?php endif; ?>
                            <td><?= htmlspecialchars($ticket['device_type_name']) ?></td>
                            <td><span class="badge badge-<?= $ticket['priority'] ?>"><?= ucfirst($ticket['priority']) ?></span></td>
                            <td><span class="badge badge-<?= str_replace('_', '-', $ticket['status']) ?>"><?= ucfirst(str_replace('_', ' ', $ticket['status'])) ?></span></td>
                            <td><?= date('M j, Y', strtotime($ticket['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
<script src="../../assets/js/theme.js"></script>
<script src="../../assets/js/notifications.js"></script>
</body>
</html>