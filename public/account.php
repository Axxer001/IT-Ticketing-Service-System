<?php
session_start();
require_once "../classes/User.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userObj = new User();
$profile = $userObj->getUserProfile($_SESSION['user_id']);
$stats = $userObj->getUserStatistics($_SESSION['user_id']);

$success = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_gmail'])) {
        $gmail = trim($_POST['gmail']);
        $result = $userObj->updateGmailBinding($_SESSION['user_id'], $gmail);
        
        if ($result['success']) {
            $success = $result['message'];
            $profile = $userObj->getUserProfile($_SESSION['user_id']); // Refresh
        } else {
            $error = $result['message'];
        }
    }
    
    if (isset($_POST['update_job_position']) && $_SESSION['user_type'] === 'employee') {
        $jobPosition = trim($_POST['job_position']);
        $result = $userObj->updateJobPosition($_SESSION['user_id'], $jobPosition);
        
        if ($result['success']) {
            $success = $result['message'];
            $profile = $userObj->getUserProfile($_SESSION['user_id']); // Refresh
        } else {
            $error = $result['message'];
        }
    }
}

// FIXED: Job position options (matching registration)
$jobPositions = [
    'Software Engineer',
    'Senior Software Engineer',
    'Lead Developer',
    'Project Manager',
    'Product Manager',
    'Business Analyst',
    'Quality Assurance Engineer',
    'DevOps Engineer',
    'System Administrator',
    'Network Administrator',
    'Database Administrator',
    'UI/UX Designer',
    'Graphic Designer',
    'HR Manager',
    'HR Specialist',
    'Recruiter',
    'Accountant',
    'Finance Manager',
    'Marketing Manager',
    'Marketing Specialist',
    'Sales Manager',
    'Sales Representative',
    'Customer Support Specialist',
    'Technical Support Engineer',
    'IT Support Specialist',
    'Game Designer',
    'Level Designer',
    'Game Developer',
    'Artist',
    'Animator',
    'Sound Designer',
    'Music Composer',
    'QA Tester',
    'Localization Specialist',
    'Producer',
    'Executive',
    'Department Head',
    'Team Lead',
    'Coordinator',
    'Assistant',
    'Intern',
    'Consultant',
    'Contractor',
    'Other'
];
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= $_SESSION['theme'] ?? 'light' ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Account Settings - Nexon</title>
<link rel="stylesheet" href="../assets/css/theme.css">
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
    max-width: 1200px;
    margin: 24px auto;
    padding: 0 24px;
}

.page-header {
    margin-bottom: 32px;
}

.page-title {
    font-size: 32px;
    font-weight: 700;
    margin-bottom: 8px;
}

.page-subtitle {
    color: var(--text-secondary);
}

.alert {
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 24px;
}

.alert-success {
    background: rgba(16, 185, 129, 0.1);
    color: var(--success);
    border-left: 4px solid var(--success);
}

.alert-danger {
    background: rgba(239, 68, 68, 0.1);
    color: #ef4444;
    border-left: 4px solid #ef4444;
}

.content-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
    margin-bottom: 24px;
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

.form-group {
    margin-bottom: 20px;
}

label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    font-size: 14px;
}

input, select {
    width: 100%;
    padding: 12px;
    border: 2px solid var(--border-color);
    border-radius: 10px;
    font-family: inherit;
    font-size: 14px;
    background: var(--bg-card);
    color: var(--text-primary);
}

input:focus, select:focus {
    outline: none;
    border-color: var(--primary);
}

.help-text {
    font-size: 12px;
    color: var(--text-secondary);
    margin-top: 6px;
}

.btn {
    padding: 12px 24px;
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
}

.btn-danger {
    background: #ef4444;
    color: white;
    width: 100%;
}

.btn-danger:hover {
    background: #dc2626;
}

.stat-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}

.stat-box {
    background: var(--bg-main);
    border-radius: 12px;
    padding: 20px;
    text-align: center;
}

.stat-value {
    font-size: 32px;
    font-weight: 700;
    color: var(--primary);
}

.stat-label {
    font-size: 12px;
    color: var(--text-secondary);
    margin-top: 4px;
}

.info-row {
    display: flex;
    justify-content: space-between;
    padding: 12px 0;
    border-bottom: 1px solid var(--border-color);
}

.info-row:last-child {
    border-bottom: none;
}

.info-label {
    font-weight: 600;
    color: var(--text-secondary);
}

.quick-links {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
}

.quick-link {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px;
    background: var(--bg-main);
    border-radius: 12px;
    text-decoration: none;
    color: var(--text-primary);
    transition: all 0.3s;
    border: 1px solid var(--border-color);
}

.quick-link:hover {
    border-color: var(--primary);
    transform: translateY(-2px);
}

.quick-link-icon {
    font-size: 24px;
}

.quick-link-text {
    font-weight: 600;
    font-size: 14px;
}

@media (max-width: 768px) {
    .content-grid {
        grid-template-columns: 1fr;
    }
}
</style>
</head>
<body>

<nav class="navbar">
    <div class="navbar-brand">NEXON</div>
    <a href="dashboard.php" class="back-btn">← Back to Dashboard</a>
</nav>

<div class="container">
    <div class="page-header">
        <h1 class="page-title">Account Settings</h1>
        <p class="page-subtitle">Manage your account credentials and preferences</p>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Account Statistics -->
    <?php if ($stats): ?>
    <div class="card" style="margin-bottom: 24px;">
        <h2 class="card-title">📊 Account Statistics</h2>
        <div class="stat-grid">
            <?php if ($_SESSION['user_type'] === 'employee'): ?>
                <div class="stat-box">
                    <div class="stat-value"><?= $stats['total_tickets'] ?? 0 ?></div>
                    <div class="stat-label">Total Tickets</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value"><?= $stats['pending'] ?? 0 ?></div>
                    <div class="stat-label">Pending</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value"><?= $stats['active'] ?? 0 ?></div>
                    <div class="stat-label">Active</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value"><?= $stats['resolved'] ?? 0 ?></div>
                    <div class="stat-label">Resolved</div>
                </div>
            <?php elseif ($_SESSION['user_type'] === 'service_provider'): ?>
                <div class="stat-box">
                    <div class="stat-value"><?= $stats['total_tickets'] ?? 0 ?></div>
                    <div class="stat-label">Total Tickets</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value"><?= $stats['active'] ?? 0 ?></div>
                    <div class="stat-label">Active</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value"><?= number_format($stats['avg_resolution_hours'] ?? 0, 1) ?>h</div>
                    <div class="stat-label">Avg Resolution</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value"><?= number_format($stats['avg_rating'] ?? 0, 1) ?>⭐</div>
                    <div class="stat-label">Rating</div>
                </div>
            <?php else: ?>
                <div class="stat-box">
                    <div class="stat-value"><?= $stats['total_tickets'] ?? 0 ?></div>
                    <div class="stat-label">Total Tickets</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value"><?= $stats['pending'] ?? 0 ?></div>
                    <div class="stat-label">Pending</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value"><?= $stats['active'] ?? 0 ?></div>
                    <div class="stat-label">Active</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value"><?= $stats['resolved'] ?? 0 ?></div>
                    <div class="stat-label">Resolved</div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Quick Links -->
    <div class="card" style="margin-bottom: 24px;">
        <h2 class="card-title">🔗 Quick Access</h2>
        <div class="quick-links">
            <a href="printables/index.php" class="quick-link">
                <span class="quick-link-icon">📊</span>
                <span class="quick-link-text">Reports & Printables</span>
            </a>
            <?php if ($_SESSION['user_type'] === 'admin'): ?>
            <a href="admin/analytics.php" class="quick-link">
                <span class="quick-link-icon">📈</span>
                <span class="quick-link-text">System Analytics</span>
            </a>
            <a href="admin/manage_tickets.php" class="quick-link">
                <span class="quick-link-icon">🎫</span>
                <span class="quick-link-text">Manage Tickets</span>
            </a>
            <a href="admin/manage_users.php" class="quick-link">
                <span class="quick-link-icon">👥</span>
                <span class="quick-link-text">Manage Users</span>
            </a>
            <?php elseif ($_SESSION['user_type'] === 'employee'): ?>
            <a href="tickets/list.php" class="quick-link">
                <span class="quick-link-icon">🎫</span>
                <span class="quick-link-text">My Tickets</span>
            </a>
            <a href="tickets/create.php" class="quick-link">
                <span class="quick-link-icon">➕</span>
                <span class="quick-link-text">Create Ticket</span>
            </a>
            <?php else: ?>
            <a href="provider/my_tickets.php" class="quick-link">
                <span class="quick-link-icon">🎫</span>
                <span class="quick-link-text">My Assignments</span>
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Account Information & Settings -->
    <div class="content-grid">
        <!-- Account Info -->
        <div class="card">
            <h2 class="card-title">ℹ️ Account Information</h2>
            <div class="info-row">
                <span class="info-label">Email:</span>
                <span><?= htmlspecialchars($profile['email']) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Account Type:</span>
                <span style="text-transform: capitalize"><?= str_replace('_', ' ', $_SESSION['user_type']) ?></span>
            </div>
            <?php if ($_SESSION['user_type'] === 'employee'): ?>
            <div class="info-row">
                <span class="info-label">Name:</span>
                <span><?= htmlspecialchars($profile['profile']['first_name'] . ' ' . $profile['profile']['last_name']) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Department:</span>
                <span><?= htmlspecialchars($profile['profile']['department_name']) ?></span>
            </div>
            <?php elseif ($_SESSION['user_type'] === 'service_provider'): ?>
            <div class="info-row">
                <span class="info-label">Provider Name:</span>
                <span><?= htmlspecialchars($profile['profile']['provider_name']) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Specialization:</span>
                <span><?= htmlspecialchars($profile['profile']['specialization'] ?? 'General') ?></span>
            </div>
            <?php endif; ?>
            <div class="info-row">
                <span class="info-label">Member Since:</span>
                <span><?= date('M j, Y', strtotime($profile['created_at'])) ?></span>
            </div>
        </div>

        <!-- Gmail Binding -->
        <div class="card">
            <h2 class="card-title">📧 Gmail Notifications</h2>
            <form method="POST">
                <div class="form-group">
                    <label>Bound Gmail Address</label>
                    <input type="email" 
                           name="gmail" 
                           placeholder="your.email@gmail.com"
                           value="<?= htmlspecialchars($profile['bound_gmail'] ?? '') ?>">
                    <div class="help-text">
                        <?php if ($_SESSION['user_type'] === 'admin'): ?>
                            ⚠️ Admin accounts do not receive Gmail notifications
                        <?php else: ?>
                            ✉️ Receive system notifications at this Gmail address
                        <?php endif; ?>
                    </div>
                </div>
                <button type="submit" name="update_gmail" class="btn btn-primary">
                    Update Gmail Binding
                </button>
            </form>
        </div>
    </div>

    <!-- FIXED: Job Position (Employee Only) with Dropdown -->
    <?php if ($_SESSION['user_type'] === 'employee'): ?>
    <div class="card" style="margin-bottom: 24px;">
        <h2 class="card-title">💼 Job Position</h2>
        <form method="POST">
            <div class="form-group">
                <label>Current Position</label>
                <select name="job_position" required>
                    <option value="">-- Select Position --</option>
                    <?php foreach ($jobPositions as $position): ?>
                        <option value="<?= htmlspecialchars($position) ?>" 
                                <?= ($profile['job_position'] ?? '') === $position ? 'selected' : '' ?>>
                            <?= htmlspecialchars($position) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="help-text">
                    Your role or position in the company
                </div>
            </div>
            <button type="submit" name="update_job_position" class="btn btn-primary">
                Update Job Position
            </button>
        </form>
    </div>
    <?php endif; ?>

    <!-- Sign Out -->
    <div class="card">
        <h2 class="card-title">🚪 Sign Out</h2>
        <p style="margin-bottom: 16px; color: var(--text-secondary);">
            End your current session and return to the login page
        </p>
        <a href="logout.php" class="btn btn-danger" onclick="return confirm('Are you sure you want to sign out?')">
            Sign Out
        </a>
    </div>
</div>

<script src="../assets/js/theme.js"></script>
<script src="../assets/js/notifications.js"></script>
</body>
</html>