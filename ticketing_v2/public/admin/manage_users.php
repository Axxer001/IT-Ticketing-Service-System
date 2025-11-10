<?php
session_start();
require_once "../../classes/User.php";

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$userObj = new User();
$db = new Database();

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['toggle_status'])) {
        $userId = $_POST['user_id'];
        $currentStatus = $_POST['current_status'];
        $newStatus = $currentStatus ? 0 : 1;
        
        $sql = "UPDATE users SET is_active = ? WHERE id = ?";
        $stmt = $db->connect()->prepare($sql);
        $stmt->execute([$newStatus, $userId]);
        
        header("Location: manage_users.php?updated=1");
        exit;
    }
}

// Get all users with their profiles
$sql = "SELECT u.*, 
        CASE 
            WHEN u.user_type = 'employee' THEN CONCAT(e.first_name, ' ', e.last_name)
            WHEN u.user_type = 'service_provider' THEN sp.provider_name
            ELSE 'Admin'
        END as display_name,
        CASE
            WHEN u.user_type = 'employee' THEN d.name
            WHEN u.user_type = 'service_provider' THEN sp.specialization
            ELSE NULL
        END as additional_info
        FROM users u
        LEFT JOIN employees e ON u.id = e.user_id
        LEFT JOIN departments d ON e.department_id = d.id
        LEFT JOIN service_providers sp ON u.id = sp.user_id
        ORDER BY u.created_at DESC";

$stmt = $db->connect()->prepare($sql);
$stmt->execute();
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= $_SESSION['theme'] ?? 'light' ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Users - Nexon</title>
<link rel="stylesheet" href="../../assets/css/theme.css">
<script>
    const PHP_SESSION_THEME = <?= json_encode($_SESSION['theme'] ?? 'light') ?>;
</script>
<style>
:root {
    --primary: #667eea;
    --secondary: #764ba2;
    --success: #10b981;
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

th {
    text-align: left;
    padding: 12px;
    font-weight: 600;
    color: var(--text-secondary);
    font-size: 13px;
    text-transform: uppercase;
    border-bottom: 2px solid var(--border-color);
}

td {
    padding: 16px 12px;
    border-bottom: 1px solid var(--border-color);
}

.badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    display: inline-block;
}

.badge-admin { background: rgba(139, 92, 246, 0.15); color: #8b5cf6; }
.badge-employee { background: rgba(59, 130, 246, 0.15); color: #3b82f6; }
.badge-service_provider { background: rgba(16, 185, 129, 0.15); color: #10b981; }
.badge-active { background: rgba(16, 185, 129, 0.15); color: #10b981; }
.badge-inactive { background: rgba(239, 68, 68, 0.15); color: #ef4444; }

.btn-toggle {
    padding: 6px 12px;
    border: none;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-activate {
    background: rgba(16, 185, 129, 0.15);
    color: #10b981;
}

.btn-deactivate {
    background: rgba(239, 68, 68, 0.15);
    color: #ef4444;
}

.btn-toggle:hover {
    transform: translateY(-2px);
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
        <h1 class="page-title">User Management</h1>
        <p class="page-subtitle">Manage system users and their access</p>
    </div>

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Type</th>
                    <th>Additional Info</th>
                    <th>Status</th>
                    <th>Last Login</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($user['display_name']) ?></strong></td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td>
                        <span class="badge badge-<?= $user['user_type'] ?>">
                            <?= ucfirst(str_replace('_', ' ', $user['user_type'])) ?>
                        </span>
                    </td>
                    <td><?= htmlspecialchars($user['additional_info'] ?? '-') ?></td>
                    <td>
                        <span class="badge badge-<?= $user['is_active'] ? 'active' : 'inactive' ?>">
                            <?= $user['is_active'] ? 'Active' : 'Inactive' ?>
                        </span>
                    </td>
                    <td><?= $user['last_login'] ? date('M j, Y', strtotime($user['last_login'])) : 'Never' ?></td>
                    <td>
                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                            <input type="hidden" name="current_status" value="<?= $user['is_active'] ?>">
                            <button type="submit" name="toggle_status" 
                                    class="btn-toggle <?= $user['is_active'] ? 'btn-deactivate' : 'btn-activate' ?>"
                                    onclick="return confirm('Are you sure?')">
                                <?= $user['is_active'] ? 'Deactivate' : 'Activate' ?>
                            </button>
                        </form>
                        <?php else: ?>
                        <span style="color:var(--text-secondary); font-size:12px">Current User</span>
                        <?php endif; ?>
                    </td>
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