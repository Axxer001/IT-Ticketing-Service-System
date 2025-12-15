
<?php
/**
 * Standardized Sidebar Navigation Component
 * Path: public/includes/sidebar_component.php
 * 
 * Usage in files:
 * - From /public/admin/*.php: require_once "../includes/sidebar_component.php";
 * - From /public/tickets/*.php: require_once "../includes/sidebar_component.php";
 * - From /public/search/*.php: require_once "../includes/sidebar_component.php";
 */

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// Determine user type and permissions
$userType = $_SESSION['user_type'] ?? 'guest';
$isAdmin = ($userType === 'admin');
$isEmployee = ($userType === 'employee');
$isProvider = ($userType === 'service_provider');

// Get current page for active state
$currentScript = basename($_SERVER['PHP_SELF']);
$currentPath = $_SERVER['REQUEST_URI'];

// Determine base path for links (adjust based on current location)
$inAdmin = strpos($currentPath, '/admin/') !== false;
$inTickets = strpos($currentPath, '/tickets/') !== false;
$inProvider = strpos($currentPath, '/provider/') !== false;
$inSearch = strpos($currentPath, '/search/') !== false;
$inCalendar = strpos($currentPath, '/calendar/') !== false;
$inAnalytics = strpos($currentPath, '/analytics/') !== false;
$inSLA = strpos($currentPath, '/sla/') !== false;
$inBatch = strpos($currentPath, '/batch/') !== false;

$inPrintables = strpos($currentPath, '/printables/') !== false;

// Set base path
if ($inAdmin || $inTickets || $inProvider || $inSearch || $inCalendar || 
    $inAnalytics || $inSLA || $inBatch || $inPrintables) {
    $basePath = '../';
} else {
    $basePath = '';
}
?>

<!-- Navbar with Sidebar Toggle -->
<nav class="navbar">
    <div class="navbar-left">
        <button class="sidebar-toggle-btn" id="sidebarToggle">☰</button>
        <div class="navbar-brand">NEXON</div>
    </div>
    
    <div class="navbar-actions">
        <button class="theme-toggle" id="themeToggle" data-theme-toggle>
            <?= ($_SESSION['theme'] ?? 'light') === 'light' ? '🌙' : '☀️' ?>
        </button>
        
        <button class="notification-btn">
            🔔
            <span class="notification-badge" style="display:none"></span>
        </button>
        
        <a href="<?= $basePath ?>account.php" class="user-menu">
            <div class="user-avatar">
                <?= strtoupper(substr($_SESSION['email'] ?? 'U', 0, 2)) ?>
            </div>
        </a>
    </div>
</nav>

<!-- Collapsible Sidebar -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div style="font-weight: 700; font-size: 16px;">Menu</div>
    </div>
    
    <div class="sidebar-menu">
        <!-- Main Navigation -->
        <div class="menu-section">
            <div class="menu-section-title">Main</div>
            
            <a href="<?= $basePath ?>dashboard.php" 
               class="menu-item <?= $currentScript === 'dashboard.php' && !($inAdmin || $inAnalytics || $inQA) ? 'active' : '' ?>">
                <span class="menu-icon">🏠</span>
                <span class="menu-text">Dashboard</span>
            </a>
            
            <a href="<?= $basePath ?>search/advanced.php" 
               class="menu-item <?= $inSearch ? 'active' : '' ?>">
                <span class="menu-icon">🔍</span>
                <span class="menu-text">Advanced Search</span>
            </a>
            
            <a href="<?= $basePath ?>calendar/view.php" 
               class="menu-item <?= $inCalendar ? 'active' : '' ?>">
                <span class="menu-icon">📅</span>
                <span class="menu-text">Calendar</span>
            </a>
            
            <?php 
            $analyticsLink = $isAdmin ? 'admin/analytics.php' : 'analytics/dashboard.php';
            $isAnalyticsActive = ($isAdmin && strpos($currentPath, 'admin/analytics.php') !== false) || (!$isAdmin && $inAnalytics);
            ?>
            <a href="<?= $basePath . $analyticsLink ?>" 
               class="menu-item <?= $isAnalyticsActive ? 'active' : '' ?>">
                <span class="menu-icon">📈</span>
                <span class="menu-text">Analytics</span>
            </a>
        </div>
        
        <!-- Employee-Specific Navigation -->
        <?php if ($isEmployee): ?>
        <div class="menu-section">
            <div class="menu-section-title">Tickets</div>
            
            <a href="<?= $basePath ?>tickets/create.php" 
               class="menu-item <?= $currentScript === 'create.php' ? 'active' : '' ?>">
                <span class="menu-icon">➕</span>
                <span class="menu-text">Create Ticket</span>
            </a>
            
            <a href="<?= $basePath ?>tickets/list.php" 
               class="menu-item <?= $currentScript === 'list.php' && $inTickets ? 'active' : '' ?>">
                <span class="menu-icon">📋</span>
                <span class="menu-text">My Tickets</span>
            </a>
        </div>
        <?php endif; ?>
        
        <!-- Service Provider Navigation -->
        <?php if ($isProvider): ?>
        <div class="menu-section">
            <div class="menu-section-title">Assignments</div>
            
            <a href="<?= $basePath ?>provider/my_tickets.php" 
               class="menu-item <?= $inProvider ? 'active' : '' ?>">
                <span class="menu-icon">🎫</span>
                <span class="menu-text">My Assignments</span>
            </a>
        </div>
        <?php endif; ?>
        
        <!-- Admin Navigation -->
        <?php if ($isAdmin): ?>
        <div class="menu-section">
            <div class="menu-section-title">Administration</div>
            
            <a href="<?= $basePath ?>admin/manage_tickets.php" 
               class="menu-item <?= $currentScript === 'manage_tickets.php' ? 'active' : '' ?>">
                <span class="menu-icon">🎫</span>
                <span class="menu-text">Manage All Tickets</span>
            </a>
            
            <a href="<?= $basePath ?>admin/manage_users.php" 
               class="menu-item <?= $currentScript === 'manage_users.php' ? 'active' : '' ?>">
                <span class="menu-icon">👥</span>
                <span class="menu-text">Manage Users</span>
            </a>
            
            <a href="<?= $basePath ?>admin/account_verifications.php" 
               class="menu-item <?= $currentScript === 'account_verifications.php' ? 'active' : '' ?>">
                <span class="menu-icon">✅</span>
                <span class="menu-text">Account Verifications</span>
            </a>
            

            
            <a href="<?= $basePath ?>admin/audit_logs.php" 
               class="menu-item <?= $currentScript === 'audit_logs.php' ? 'active' : '' ?>">
                <span class="menu-icon">📜</span>
                <span class="menu-text">Audit Logs</span>
            </a>
            
            <a href="<?= $basePath ?>sla/monitor.php" 
               class="menu-item <?= $inSLA ? 'active' : '' ?>">
                <span class="menu-icon">⏱️</span>
                <span class="menu-text">SLA Monitor</span>
            </a>
            
            <a href="<?= $basePath ?>batch/operations.php" 
               class="menu-item <?= $inBatch ? 'active' : '' ?>">
                <span class="menu-icon">⚡</span>
                <span class="menu-text">Batch Operations</span>
            </a>
            

        </div>
        <?php endif; ?>
        
        <!-- Reports Section (All Users) -->
        <div class="menu-section">
            <div class="menu-section-title">Reports</div>
            
            <a href="<?= $basePath ?>printables/index.php" 
               class="menu-item <?= $inPrintables ? 'active' : '' ?>">
                <span class="menu-icon">📊</span>
                <span class="menu-text">Reports & Printables</span>
            </a>
        </div>
        
        <!-- System Tools (Admin Only) -->
        <?php if ($isAdmin): ?>
        <div class="menu-section">
            <div class="menu-section-title">System</div>
            
            <a href="<?= $basePath ?>admin/test_email.php" 
               class="menu-item <?= $currentScript === 'test_email.php' ? 'active' : '' ?>">
                <span class="menu-icon">📧</span>
                <span class="menu-text">Email Test</span>
            </a>
            
            <a href="<?= $basePath ?>admin/email_diagnostic.php" 
               class="menu-item <?= $currentScript === 'email_diagnostic.php' ? 'active' : '' ?>">
                <span class="menu-icon">🔍</span>
                <span class="menu-text">Email Diagnostic</span>
            </a>
        </div>
        <?php endif; ?>
    </div>
</aside>

<!-- Shared Styles for Sidebar -->
<style>
:root {
    --sidebar-width: 260px;
}

.navbar {
    background: var(--bg-card);
    border-bottom: 1px solid var(--border-color);
    padding: 16px 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: var(--shadow);
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 100;
    height: 64px;
}

.navbar-left {
    display: flex;
    align-items: center;
    gap: 16px;
}

.sidebar-toggle-btn {
    width: 40px;
    height: 40px;
    background: var(--bg-main);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 20px;
    transition: all 0.3s;
    color: var(--text-primary);
}

.sidebar-toggle-btn:hover {
    border-color: var(--primary);
    transform: scale(1.05);
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
    color: var(--text-primary);
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
    text-decoration: none;
    color: var(--text-primary);
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

.sidebar {
    position: fixed;
    left: 0;
    top: 64px;
    width: var(--sidebar-width);
    height: calc(100vh - 64px);
    background: var(--bg-card);
    border-right: 1px solid var(--border-color);
    overflow-y: auto;
    transition: transform 0.3s ease;
    z-index: 90;
}

.sidebar.collapsed {
    transform: translateX(calc(-1 * var(--sidebar-width)));
}

.sidebar-header {
    padding: 20px;
    border-bottom: 1px solid var(--border-color);
}

.sidebar-menu {
    padding: 8px;
}

.menu-section {
    margin-bottom: 24px;
}

.menu-section-title {
    font-size: 11px;
    text-transform: uppercase;
    color: var(--text-secondary);
    font-weight: 700;
    padding: 8px 12px;
    letter-spacing: 0.5px;
}

.menu-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    border-radius: 8px;
    text-decoration: none;
    color: var(--text-primary);
    transition: all 0.2s;
    margin-bottom: 4px;
}

.menu-item:hover {
    background: var(--bg-main);
}

.menu-item.active {
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    color: white;
}

.menu-icon {
    font-size: 20px;
    width: 24px;
    text-align: center;
}

.menu-text {
    font-weight: 600;
    font-size: 14px;
}

.main-content {
    margin-left: var(--sidebar-width);
    margin-top: 64px;
    padding: 24px;
    transition: margin-left 0.3s ease;
    min-height: calc(100vh - 64px);
}

.sidebar.collapsed ~ .main-content {
    margin-left: 0;
}

@media (max-width: 768px) {
    .sidebar {
        transform: translateX(calc(-1 * var(--sidebar-width)));
    }
    
    .sidebar.show {
        transform: translateX(0);
    }
    
    .main-content {
        margin-left: 0;
    }
}

/* Notification Styles */
.notification-dropdown {
    position: fixed;
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

<!-- Sidebar Toggle Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
        });
        
        // Close sidebar on mobile when clicking outside
        document.addEventListener('click', (e) => {
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
                    if (sidebar.classList.contains('show')) {
                        sidebar.classList.remove('show');
                    }
                }
            }
        });
        
        // Mobile: toggle with 'show' class
        if (window.innerWidth <= 768) {
            const originalClickHandler = sidebarToggle.onclick;
            sidebarToggle.addEventListener('click', (e) => {
                e.stopPropagation();
                sidebar.classList.toggle('show');
            });
        }
    }
});
</script>
