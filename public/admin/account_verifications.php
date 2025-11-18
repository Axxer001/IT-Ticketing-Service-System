<?php
session_start();
require_once "../../classes/User.php";

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$userObj = new User();

$success = '';
$error = '';

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['approve'])) {
        $verificationId = $_POST['verification_id'];
        $comment = $_POST['admin_comment'] ?? null;
        
        $result = $userObj->approveVerification($verificationId, $_SESSION['user_id'], $comment);
        
        if ($result['success']) {
            $success = "Account approved successfully! User has been notified via email.";
        } else {
            $error = $result['message'];
        }
    } elseif (isset($_POST['reject'])) {
        $verificationId = $_POST['verification_id'];
        $comment = $_POST['admin_comment'];
        
        if (empty($comment)) {
            $error = "Please provide a reason for rejection.";
        } else {
            $result = $userObj->rejectVerification($verificationId, $_SESSION['user_id'], $comment);
            
            if ($result['success']) {
                $success = "Account rejected. User has been notified via email.";
            } else {
                $error = $result['message'];
            }
        }
    }
}

$pendingVerifications = $userObj->getPendingVerifications();
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= $_SESSION['theme'] ?? 'light' ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Account Verifications - Nexon</title>
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
    color: var(--danger);
    border-left: 4px solid var(--danger);
}

.verification-card {
    background: var(--bg-card);
    border-radius: 16px;
    padding: 24px;
    box-shadow: var(--shadow);
    border: 1px solid var(--border-color);
    margin-bottom: 20px;
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 20px;
    padding-bottom: 16px;
    border-bottom: 1px solid var(--border-color);
}

.user-info h3 {
    font-size: 20px;
    margin-bottom: 4px;
}

.user-type {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    background: rgba(102, 126, 234, 0.15);
    color: var(--primary);
}

.request-date {
    font-size: 13px;
    color: var(--text-secondary);
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
    margin-bottom: 20px;
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.info-label {
    font-size: 12px;
    color: var(--text-secondary);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.info-value {
    font-size: 15px;
    font-weight: 600;
}

.form-group {
    margin-bottom: 16px;
}

label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    font-size: 14px;
}

textarea {
    width: 100%;
    padding: 12px;
    border: 2px solid var(--border-color);
    border-radius: 10px;
    font-family: inherit;
    font-size: 14px;
    background: var(--bg-card);
    color: var(--text-primary);
    resize: vertical;
    min-height: 80px;
}

textarea:focus {
    outline: none;
    border-color: var(--primary);
}

.action-buttons {
    display: flex;
    gap: 12px;
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

.btn-approve {
    background: var(--success);
    color: white;
    flex: 1;
}

.btn-approve:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(16, 185, 129, 0.3);
}

.btn-reject {
    background: var(--danger);
    color: white;
    flex: 1;
}

.btn-reject:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(239, 68, 68, 0.3);
}

.empty-state {
    text-align: center;
    padding: 64px 24px;
    color: var(--text-secondary);
}

.empty-state-icon {
    font-size: 64px;
    margin-bottom: 16px;
    opacity: 0.5;
}

@media (max-width: 768px) {
    .info-grid {
        grid-template-columns: 1fr;
    }
}
</style>
</head>
<body>

<nav class="navbar">
    <div class="navbar-brand">NEXON ADMIN</div>
    <a href="../dashboard.php" class="back-btn">← Dashboard</a>
</nav>

<div class="container">
    <div class="page-header">
        <h1 class="page-title">Account Verifications</h1>
        <p class="page-subtitle">Review and approve pending account requests</p>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (empty($pendingVerifications)): ?>
        <div class="verification-card">
            <div class="empty-state">
                <div class="empty-state-icon">✅</div>
                <h3>No Pending Verifications</h3>
                <p>All account requests have been processed</p>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($pendingVerifications as $verification): ?>
        <div class="verification-card">
            <div class="card-header">
                <div class="user-info">
                    <h3>
                        <?php if ($verification['user_type'] === 'employee'): ?>
                            <?= htmlspecialchars($verification['first_name'] . ' ' . $verification['last_name']) ?>
                        <?php else: ?>
                            <?= htmlspecialchars($verification['provider_name']) ?>
                        <?php endif; ?>
                    </h3>
                    <span class="user-type"><?= ucfirst(str_replace('_', ' ', $verification['user_type'])) ?></span>
                </div>
                <div class="request-date">
                    Requested: <?= date('M j, Y g:i A', strtotime($verification['created_at'])) ?>
                </div>
            </div>

            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Email</span>
                    <span class="info-value"><?= htmlspecialchars($verification['email']) ?></span>
                </div>
                
                <div class="info-item">
                    <span class="info-label">Gmail Address (for notifications)</span>
                    <span class="info-value"><?= htmlspecialchars($verification['bound_gmail'] ?? 'Not provided') ?></span>
                </div>

                <?php if ($verification['user_type'] === 'employee'): ?>
                    <div class="info-item">
                        <span class="info-label">Department</span>
                        <span class="info-value"><?= htmlspecialchars($verification['department_name']) ?></span>
                    </div>
                    
                    <div class="info-item">
                        <span class="info-label">Contact Number</span>
                        <span class="info-value"><?= htmlspecialchars($verification['contact_number'] ?? 'Not provided') ?></span>
                    </div>
                <?php else: ?>
                    <div class="info-item">
                        <span class="info-label">Specialization</span>
                        <span class="info-value"><?= htmlspecialchars($verification['specialization'] ?? 'General') ?></span>
                    </div>
                    
                    <div class="info-item">
                        <span class="info-label">Contact Number</span>
                        <span class="info-value"><?= htmlspecialchars($verification['contact_number'] ?? 'Not provided') ?></span>
                    </div>
                <?php endif; ?>
            </div>

            <form method="POST">
                <input type="hidden" name="verification_id" value="<?= $verification['id'] ?>">
                
                <div class="form-group">
                    <label>Admin Comment (Optional for approval, Required for rejection)</label>
                    <textarea name="admin_comment" placeholder="Add a note about this decision..."></textarea>
                </div>

                <div class="action-buttons">
                    <button type="submit" name="approve" class="btn btn-approve" onclick="return confirm('Approve this account? The user will be notified via email.')">
                        ✅ Approve Account
                    </button>
                    <button type="submit" name="reject" class="btn btn-reject" onclick="return confirm('Reject this account? The user will be notified via email.')">
                        ❌ Reject Account
                    </button>
                </div>
            </form>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script src="../../assets/js/theme.js"></script>
<script src="../../assets/js/notifications.js"></script>
</body>
</html>