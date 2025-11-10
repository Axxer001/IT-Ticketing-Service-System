<?php
session_start();
require_once "../classes/User.php";
require_once "../classes/Ticket.php";
require_once "../classes/Notification.php";
require_once "../classes/Analytics.php";

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userObj = new User();
$ticketObj = new Ticket();
$notificationObj = new Notification();
$analyticsObj = new Analytics();

$userId = $_SESSION['user_id'];
$userType = $_SESSION['user_type'];
$profile = $userObj->getUserProfile($userId);

// Get statistics
$stats = $analyticsObj->getDashboardStats($userType, $userId);

// Get notifications
$notifications = $notificationObj->getUserNotifications($userId, false, 10);
$unreadCount = $notificationObj->getUnreadCount($userId);

// Get recent tickets based on user type
$filters = [];
if ($userType === 'employee') {
    $filters['employee_id'] = $profile['profile']['id'];
} elseif ($userType === 'service_provider') {
    $filters['provider_id'] = $profile['profile']['id'];
}
$recentTickets = $ticketObj->getTickets($filters, 5, 0);
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= $_SESSION['theme'] ?? 'light' ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard - Nexon Ticketing</title>
<!-- Theme CSS -->
<link rel="stylesheet" href="../assets/css/theme.css">
<!-- OR adjust path based on file location -->
<link rel="stylesheet" href="../../assets/css/theme.css">
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
    --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3);
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
    background: var(--bg-main);
    color: var(--text-primary);
    transition: background 0.3s, color 0.3s;
}

/* Header/Navbar */
.navbar {
    background: var(--bg-card);
    border-bottom: 1px solid var(--border-color);
    padding: 16px 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: var(--shadow);
    position: sticky;
    top: 0;
    z-index: 100;
}

.navbar-brand {
    font-size: 24px;
    font-weight: 800;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    letter-spacing: 1px;
}

.navbar-actions {
    display: flex;
    align-items: center;
    gap: 16px;
}

.theme-toggle, .notification-btn {
    background: none;
    border: 2px solid var(--border-color);
    width: 40px;
    height: 40px;
    border-radius: 10px;
    cursor: pointer;
    font-size: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s;
    position: relative;
}

.theme-toggle:hover, .notification-btn:hover {
    border-color: var(--primary);
    transform: scale(1.05);
}

.notification-badge {
    position: absolute;
    top: -6px;
    right: -6px;
    background: var(--danger);
    color: white;
    font-size: 11px;
    font-weight: 700;
    padding: 2px 6px;
    border-radius: 10px;
    min-width: 18px;
    text-align: center;
}

.user-menu {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 8px 16px;
    background: var(--bg-main);
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.3s;
}

.user-menu:hover {
    background: var(--border-color);
}

.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 700;
}

.user-info {
    text-align: left;
}

.user-name {
    font-weight: 600;
    font-size: 14px;
    color: var(--text-primary);
}

.user-role {
    font-size: 12px;
    color: var(--text-secondary);
    text-transform: capitalize;
}

/* Main Container */
.container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 24px;
}

.page-header {
    margin-bottom: 32px;
}

.page-title {
    font-size: 32px;
    font-weight: 700;
    color: var(--text-primary);
    margin-bottom: 8px;
}

.page-subtitle {
    color: var(--text-secondary);
    font-size: 16px;
}

/* Stats Cards */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 32px;
}

.stat-card {
    background: var(--bg-card);
    border-radius: 16px;
    padding: 24px;
    box-shadow: var(--shadow);
    border: 1px solid var(--border-color);
    transition: transform 0.3s, box-shadow 0.3s;
}

.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 20px -5px rgba(0, 0, 0, 0.15);
}

.stat-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 16px;
}

.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
}

.stat-icon.primary { background: rgba(102, 126, 234, 0.1); }
.stat-icon.success { background: rgba(16, 185, 129, 0.1); }
.stat-icon.warning { background: rgba(245, 158, 11, 0.1); }
.stat-icon.danger { background: rgba(239, 68, 68, 0.1); }

.stat-value {
    font-size: 36px;
    font-weight: 700;
    color: var(--text-primary);
    line-height: 1;
}

.stat-label {
    color: var(--text-secondary);
    font-size: 14px;
    margin-top: 8px;
}

/* Content Grid */
.content-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 24px;
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
    color: var(--text-primary);
}

.btn {
    padding: 10px 20px;
    border-radius: 10px;
    border: none;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    text-decoration: none;
    display: inline-block;
    font-size: 14px;
}

.btn-primary {
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(102, 126, 234, 0.3);
}

/* Table */
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
    letter-spacing: 0.5px;
}

td {
    padding: 16px 12px;
    border-bottom: 1px solid var(--border-color);
    color: var(--text-primary);
}

tr:last-child td {
    border-bottom: none;
}

/* Status Badge */
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

/* Priority Badge */
.badge-low { background: rgba(59, 130, 246, 0.15); color: #3b82f6; }
.badge-medium { background: rgba(245, 158, 11, 0.15); color: #f59e0b; }
.badge-high { background: rgba(239, 68, 68, 0.15); color: #ef4444; }
.badge-critical { background: rgba(220, 38, 38, 0.2); color: #dc2626; }

/* Empty State */
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

/* Responsive */
@media (max-width: 768px) {
    .navbar {
        padding: 12px 16px;
    }
    
    .navbar-brand {
        font-size: 20px;
    }
    
    .user-info {
        display: none;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .page-title {
        font-size: 24px;
    }
    
    .container {
        padding: 16px;
    }
    
    .card {
        padding: 16px;
    }
    
    th, td {
        padding: 8px;
        font-size: 13px;
    }
}

/* Notification Dropdown */
.notification-dropdown {
    position: absolute;
    top: 60px;
    right: 24px;
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.15);
    width: 360px;
    max-height: 500px;
    overflow-y: auto;
    display: none;
    z-index: 1000;
}

.notification-dropdown.show {
    display: block;
}

.notification-header {
    padding: 16px;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.notification-item {
    padding: 16px;
    border-bottom: 1px solid var(--border-color);
    cursor: pointer;
    transition: background 0.2s;
}

.notification-item:hover {
    background: var(--bg-main);
}

.notification-item.unread {
    background: rgba(102, 126, 234, 0.05);
}

.notification-title {
    font-weight: 600;
    font-size: 14px;
    margin-bottom: 4px;
}

.notification-message {
    font-size: 13px;
    color: var(--text-secondary);
    margin-bottom: 4px;
}

.notification-time {
    font-size: 11px;
    color: var(--text-secondary);
}
</style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar">
    <div class="navbar-brand">NEXON</div>
    
    <div class="navbar-actions">
        <button class="theme-toggle" onclick="toggleTheme()" id="themeToggle">
            <?= ($_SESSION['theme'] ?? 'light') === 'light' ? '🌙' : '☀️' ?>
        </button>
        
        <button class="notification-btn" onclick="toggleNotifications()">
            🔔
            <?php if ($unreadCount > 0): ?>
                <span class="notification-badge"><?= $unreadCount ?></span>
            <?php endif; ?>
        </button>
        
        <div class="user-menu" onclick="location.href='logout.php'">
            <div class="user-avatar">
                <?= strtoupper(substr($profile['email'], 0, 2)) ?>
            </div>
            <div class="user-info">
                <div class="user-name">
                    <?php
                    if ($userType === 'employee') {
                        echo htmlspecialchars($profile['profile']['first_name'] . ' ' . $profile['profile']['last_name']);
                    } elseif ($userType === 'service_provider') {
                        echo htmlspecialchars($profile['profile']['provider_name']);
                    } else {
                        echo 'Admin';
                    }
                    ?>
                </div>
                <div class="user-role"><?= htmlspecialchars($userType) ?></div>
            </div>
        </div>
    </div>
</nav>

<!-- Notification Dropdown -->
<div class="notification-dropdown" id="notificationDropdown">
    <div class="notification-header">
        <strong>Notifications</strong>
        <?php if ($unreadCount > 0): ?>
            <a href="#" onclick="markAllRead(); return false;" style="font-size:12px; color:var(--primary)">Mark all read</a>
        <?php endif; ?>
    </div>
    <?php if (empty($notifications)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">🔔</div>
            <p>No notifications</p>
        </div>
    <?php else: ?>
        <?php foreach ($notifications as $notif): ?>
            <div class="notification-item <?= $notif['is_read'] ? '' : 'unread' ?>" 
                 onclick="markAsRead(<?= $notif['id'] ?>)">
                <div class="notification-title"><?= htmlspecialchars($notif['title']) ?></div>
                <div class="notification-message"><?= htmlspecialchars($notif['message']) ?></div>
                <div class="notification-time"><?= date('M j, g:i A', strtotime($notif['created_at'])) ?></div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Main Container -->
<div class="container">
    <div class="page-header">
        <h1 class="page-title">Dashboard</h1>
        <p class="page-subtitle">Welcome back! Here's what's happening today.</p>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <?php if ($userType === 'admin'): ?>
            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <div class="stat-value"><?= $stats['total_tickets'] ?></div>
                        <div class="stat-label">Total Tickets</div>
                    </div>
                    <div class="stat-icon primary">📋</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <div class="stat-value"><?= $stats['pending'] ?></div>
                        <div class="stat-label">Pending Assignment</div>
                    </div>
                    <div class="stat-icon warning">⏳</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <div class="stat-value"><?= $stats['active'] ?></div>
                        <div class="stat-label">Active Tickets</div>
                    </div>
                    <div class="stat-icon info">🔄</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <div class="stat-value"><?= $stats['resolved_today'] ?></div>
                        <div class="stat-label">Resolved Today</div>
                    </div>
                    <div class="stat-icon success">✅</div>
                </div>
            </div>
            
        <?php elseif ($userType === 'employee'): ?>
            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <div class="stat-value"><?= $stats['my_tickets'] ?></div>
                        <div class="stat-label">My Tickets</div>
                    </div>
                    <div class="stat-icon primary">📋</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <div class="stat-value"><?= $stats['pending'] ?></div>
                        <div class="stat-label">Pending</div>
                    </div>
                    <div class="stat-icon warning">⏳</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <div class="stat-value"><?= $stats['in_progress'] ?></div>
                        <div class="stat-label">In Progress</div>
                    </div>
                    <div class="stat-icon info">🔄</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <div class="stat-value"><?= $stats['resolved'] ?></div>
                        <div class="stat-label">Resolved</div>
                    </div>
                    <div class="stat-icon success">✅</div>
                </div>
            </div>
            
        <?php elseif ($userType === 'service_provider'): ?>
            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <div class="stat-value"><?= $stats['assigned'] ?></div>
                        <div class="stat-label">Assigned to Me</div>
                    </div>
                    <div class="stat-icon primary">📋</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <div class="stat-value"><?= $stats['in_progress'] ?></div>
                        <div class="stat-label">In Progress</div>
                    </div>
                    <div class="stat-icon warning">🔄</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <div class="stat-value"><?= $stats['resolved'] ?></div>
                        <div class="stat-label">Resolved</div>
                    </div>
                    <div class="stat-icon success">✅</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <div class="stat-value"><?= number_format($stats['avg_rating'], 1) ?>★</div>
                        <div class="stat-label">Average Rating (<?= $stats['total_ratings'] ?> ratings)</div>
                    </div>
                    <div class="stat-icon warning">⭐</div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Recent Tickets -->
    <div class="content-grid">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Recent Tickets</h2>
                <?php if ($userType === 'employee'): ?>
                    <a href="tickets/create.php" class="btn btn-primary">+ Create Ticket</a>
                <?php elseif ($userType === 'admin'): ?>
                    <a href="admin/manage_tickets.php" class="btn btn-primary">Manage All</a>
                <?php elseif ($userType === 'service_provider'): ?>
                    <a href="provider/my_tickets.php" class="btn btn-primary">View All</a>
                <?php endif; ?>
            </div>
            
            <?php if (empty($recentTickets)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📋</div>
                    <p>No tickets found</p>
                    <?php if ($userType === 'employee'): ?>
                        <a href="tickets/create.php" class="btn btn-primary" style="margin-top:16px">Create Your First Ticket</a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Ticket #</th>
                                <?php if ($userType !== 'employee'): ?>
                                    <th>Employee</th>
                                <?php endif; ?>
                                <th>Device</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <?php if ($userType === 'service_provider'): ?>
                                    <th>Action</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentTickets as $ticket): ?>
                                <tr onclick="location.href='tickets/view.php?id=<?= $ticket['id'] ?>'" style="cursor:pointer">
                                    <td><strong><?= htmlspecialchars($ticket['ticket_number']) ?></strong></td>
                                    <?php if ($userType !== 'employee'): ?>
                                        <td><?= htmlspecialchars($ticket['first_name'] . ' ' . $ticket['last_name']) ?></td>
                                    <?php endif; ?>
                                    <td><?= htmlspecialchars($ticket['device_type_name']) ?></td>
                                    <td><span class="badge badge-<?= $ticket['priority'] ?>"><?= ucfirst($ticket['priority']) ?></span></td>
                                    <td><span class="badge badge-<?= str_replace('_', '-', $ticket['status']) ?>"><?= ucfirst(str_replace('_', ' ', $ticket['status'])) ?></span></td>
                                    <?php if ($userType === 'service_provider'): ?>
                                        <td><a href="provider/update_ticket.php?id=<?= $ticket['id'] ?>" class="btn btn-primary" style="font-size:12px; padding:6px 12px" onclick="event.stopPropagation()">Update</a></td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Theme management
function toggleTheme() {
    const html = document.documentElement;
    const toggle = document.getElementById('themeToggle');
    const currentTheme = html.getAttribute('data-theme') || 'light';
    const newTheme = currentTheme === 'light' ? 'dark' : 'light';
    
    html.setAttribute('data-theme', newTheme);
    toggle.textContent = newTheme === 'light' ? '🌙' : '☀️';
    
    // Save to server
    fetch('api/update_theme.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({theme: newTheme})
    });
}

// Notification dropdown
function toggleNotifications() {
    const dropdown = document.getElementById('notificationDropdown');
    dropdown.classList.toggle('show');
}

// Close dropdown when clicking outside
document.addEventListener('click', function(e) {
    const dropdown = document.getElementById('notificationDropdown');
    const btn = document.querySelector('.notification-btn');
    if (!dropdown.contains(e.target) && !btn.contains(e.target)) {
        dropdown.classList.remove('show');
    }
});

function markAsRead(notifId) {
    fetch('api/mark_notification_read.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({notification_id: notifId})
    }).then(() => location.reload());
}

function markAllRead() {
    fetch('api/mark_all_notifications_read.php', {
        method: 'POST'
    }).then(() => location.reload());
}

// Auto-refresh notifications every 30 seconds
setInterval(function() {
    fetch('api/get_unread_count.php')
        .then(r => r.json())
        .then(data => {
            const badge = document.querySelector('.notification-badge');
            if (data.count > 0) {
                if (badge) {
                    badge.textContent = data.count;
                } else {
                    const newBadge = document.createElement('span');
                    newBadge.className = 'notification-badge';
                    newBadge.textContent = data.count;
                    document.querySelector('.notification-btn').appendChild(newBadge);
                }
            } else if (badge) {
                badge.remove();
            }
        });
}, 30000);
</script>
<!-- Theme Switcher -->
<script src="../assets/js/theme.js"></script>

<!-- Notifications (only on authenticated pages) -->
<script src="../assets/js/notifications.js"></script>
</body>
</html>