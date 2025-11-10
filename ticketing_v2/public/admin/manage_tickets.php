<?php
session_start();
require_once "../../classes/User.php";
require_once "../../classes/Ticket.php";

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$ticketObj = new Ticket();
$userObj = new User();

// Build filters
$filters = [];
if (!empty($_GET['status'])) {
    $filters['status'] = $_GET['status'];
}
if (!empty($_GET['priority'])) {
    $filters['priority'] = $_GET['priority'];
}
if (!empty($_GET['search'])) {
    $filters['search'] = $_GET['search'];
}

// Get tickets with filters
$tickets = $ticketObj->getTickets($filters, 100, 0);

// Get available service providers for quick assignment
$providers = $userObj->getAvailableServiceProviders();
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= $_SESSION['theme'] ?? 'light' ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Tickets - Nexon Admin</title>
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
    --info: #3b82f6;
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

.navbar-actions {
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
    transition: all 0.3s;
}

.back-btn:hover {
    border-color: var(--primary);
    color: var(--primary);
}

.btn-analytics {
    padding: 8px 16px;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    color: white;
    border: none;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s;
}

.btn-analytics:hover {
    transform: translateY(-2px);
}

.container {
    max-width: 1600px;
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

.stats-bar {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}

.stat-box {
    background: var(--bg-card);
    border-radius: 12px;
    padding: 16px;
    border: 1px solid var(--border-color);
    text-align: center;
}

.stat-value {
    font-size: 32px;
    font-weight: 700;
    color: var(--primary);
}

.stat-label {
    font-size: 13px;
    color: var(--text-secondary);
    margin-top: 4px;
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

.filter-group input,
.filter-group select {
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
    transition: all 0.3s;
}

.btn-primary {
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(102, 126, 234, 0.3);
}

.card {
    background: var(--bg-card);
    border-radius: 16px;
    padding: 24px;
    box-shadow: var(--shadow);
    border: 1px solid var(--border-color);
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 16px;
    border-bottom: 1px solid var(--border-color);
}

.card-title {
    font-size: 20px;
    font-weight: 700;
}

.table-responsive {
    overflow-x: auto;
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

tbody tr {
    transition: background 0.2s;
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

.badge-pending { background: rgba(245, 158, 11, 0.15); color: #f59e0b; }
.badge-assigned { background: rgba(59, 130, 246, 0.15); color: #3b82f6; }
.badge-in-progress { background: rgba(139, 92, 246, 0.15); color: #8b5cf6; }
.badge-resolved { background: rgba(16, 185, 129, 0.15); color: #10b981; }
.badge-closed { background: rgba(100, 116, 139, 0.15); color: #64748b; }
.badge-cancelled { background: rgba(239, 68, 68, 0.15); color: #ef4444; }

.badge-low { background: rgba(59, 130, 246, 0.15); color: #3b82f6; }
.badge-medium { background: rgba(245, 158, 11, 0.15); color: #f59e0b; }
.badge-high { background: rgba(239, 68, 68, 0.15); color: #ef4444; }
.badge-critical { 
    background: rgba(220, 38, 38, 0.2); 
    color: #dc2626;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}

.action-btn {
    padding: 6px 12px;
    border-radius: 6px;
    border: 1px solid var(--primary);
    background: transparent;
    color: var(--primary);
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    text-decoration: none;
    display: inline-block;
}

.action-btn:hover {
    background: var(--primary);
    color: white;
}

.action-btn.success {
    border-color: var(--success);
    color: var(--success);
}

.action-btn.success:hover {
    background: var(--success);
    color: white;
}

.empty-state {
    text-align: center;
    padding: 48px 24px;
    color: var(--text-secondary);
}

.empty-state-icon {
    font-size: 64px;
    margin-bottom: 16px;
    opacity: 0.5;
}

@media (max-width: 768px) {
    .stats-bar {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .filters {
        flex-direction: column;
    }
    
    .filter-group {
        min-width: 100%;
    }
    
    th, td {
        padding: 8px;
        font-size: 13px;
    }
}
</style>
</head>
<body>

<nav class="navbar">
    <div class="navbar-brand">NEXON ADMIN</div>
<div class="navbar-actions">
    <a href="../printables/index.php" class="btn-analytics">📊 Reports</a>
    <a href="analytics.php" class="btn-analytics">📊 Analytics</a>
    <a href="../dashboard.php" class="back-btn">← Dashboard</a>
</div>
</nav>

<div class="container">
    <div class="page-header">
        <h1 class="page-title">Manage All Tickets</h1>
        <p class="page-subtitle">View, assign, and manage all support tickets in the system</p>
    </div>

    <?php
    // Calculate statistics
    $totalTickets = count($tickets);
    $pendingCount = count(array_filter($tickets, fn($t) => $t['status'] === 'pending'));
    $assignedCount = count(array_filter($tickets, fn($t) => $t['status'] === 'assigned'));
    $inProgressCount = count(array_filter($tickets, fn($t) => $t['status'] === 'in_progress'));
    $resolvedCount = count(array_filter($tickets, fn($t) => $t['status'] === 'resolved'));
    $criticalCount = count(array_filter($tickets, fn($t) => $t['priority'] === 'critical'));
    ?>

    <div class="stats-bar">
        <div class="stat-box">
            <div class="stat-value"><?= $totalTickets ?></div>
            <div class="stat-label">Total Tickets</div>
        </div>
        <div class="stat-box">
            <div class="stat-value" style="color: var(--warning)"><?= $pendingCount ?></div>
            <div class="stat-label">Pending</div>
        </div>
        <div class="stat-box">
            <div class="stat-value" style="color: var(--info)"><?= $assignedCount + $inProgressCount ?></div>
            <div class="stat-label">Active</div>
        </div>
        <div class="stat-box">
            <div class="stat-value" style="color: var(--success)"><?= $resolvedCount ?></div>
            <div class="stat-label">Resolved</div>
        </div>
        <div class="stat-box">
            <div class="stat-value" style="color: var(--danger)"><?= $criticalCount ?></div>
            <div class="stat-label">Critical</div>
        </div>
    </div>

    <form class="filters" method="GET">
        <div class="filter-group">
            <label>Search</label>
            <input type="text" name="search" placeholder="Ticket #, employee name..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
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
            <button type="submit" class="btn btn-primary" style="width:100%">Apply Filters</button>
        </div>
    </form>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">All Tickets (<?= $totalTickets ?>)</h2>
            <div>
                <a href="audit_logs.php" style="margin-right:12px; color: var(--text-secondary); font-size:14px; text-decoration:none">
                    📋 Audit Logs
                </a>
                <a href="manage_users.php" style="color: var(--text-secondary); font-size:14px; text-decoration:none">
                    👥 Manage Users
                </a>
            </div>
        </div>

        <?php if (empty($tickets)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">🎫</div>
                <p>No tickets found</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Ticket #</th>
                            <th>Employee</th>
                            <th>Department</th>
                            <th>Device</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Provider</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tickets as $ticket): ?>
                            <tr>
                                <td>
                                    <strong>
                                        <a href="../tickets/view.php?id=<?= $ticket['id'] ?>" 
                                           style="color:var(--primary); text-decoration:none">
                                            <?= htmlspecialchars($ticket['ticket_number']) ?>
                                        </a>
                                    </strong>
                                </td>
                                <td><?= htmlspecialchars($ticket['first_name'] . ' ' . $ticket['last_name']) ?></td>
                                <td style="font-size:12px"><?= htmlspecialchars($ticket['department_name']) ?></td>
                                <td><?= htmlspecialchars($ticket['device_type_name']) ?></td>
                                <td>
                                    <span class="badge badge-<?= $ticket['priority'] ?>">
                                        <?= ucfirst($ticket['priority']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-<?= str_replace('_', '-', $ticket['status']) ?>">
                                        <?= ucfirst(str_replace('_', ' ', $ticket['status'])) ?>
                                    </span>
                                </td>
                                <td style="font-size:13px">
                                    <?php if ($ticket['provider_name']): ?>
                                        <?= htmlspecialchars($ticket['provider_name']) ?>
                                    <?php else: ?>
                                        <span style="color:var(--text-secondary)">Unassigned</span>
                                    <?php endif; ?>
                                </td>
                                <td style="font-size:13px; color:var(--text-secondary)">
                                    <?= date('M j, Y', strtotime($ticket['created_at'])) ?>
                                </td>
                                <td>
                                    <?php if ($ticket['status'] === 'pending'): ?>
                                        <a href="assign_ticket.php?id=<?= $ticket['id'] ?>" class="action-btn success">
                                            Assign
                                        </a>
                                    <?php else: ?>
                                        <a href="../tickets/view.php?id=<?= $ticket['id'] ?>" class="action-btn">
                                            View
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="../../assets/js/theme.js"></script>
<script src="../../assets/js/notifications.js"></script>
</body>
</html>