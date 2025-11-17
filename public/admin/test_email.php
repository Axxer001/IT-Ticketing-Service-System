<?php
session_start();
require_once "../../classes/EmailNotification.php";

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$result = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_email'])) {
    $testEmail = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
    
    if ($testEmail) {
        $emailNotification = new EmailNotification();
        $result = $emailNotification->testEmailConfig($testEmail);
    } else {
        $result = [
            'success' => false,
            'message' => 'Please enter a valid email address'
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= $_SESSION['theme'] ?? 'light' ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Test Email Configuration - Nexon</title>
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
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
}

.container {
    max-width: 600px;
    width: 100%;
}

.card {
    background: var(--bg-card);
    border-radius: 16px;
    padding: 40px;
    box-shadow: var(--shadow);
    border: 1px solid var(--border-color);
}

.page-title {
    font-size: 24px;
    font-weight: 700;
    margin-bottom: 8px;
    text-align: center;
}

.page-subtitle {
    color: var(--text-secondary);
    text-align: center;
    margin-bottom: 32px;
    font-size: 14px;
}

.alert {
    padding: 16px;
    border-radius: 10px;
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    gap: 12px;
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

.alert-icon {
    font-size: 24px;
}

.form-group {
    margin-bottom: 24px;
}

label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    font-size: 14px;
}

input {
    width: 100%;
    padding: 12px;
    border: 2px solid var(--border-color);
    border-radius: 10px;
    font-family: inherit;
    font-size: 14px;
    background: var(--bg-card);
    color: var(--text-primary);
}

input:focus {
    outline: none;
    border-color: var(--primary);
}

.btn {
    width: 100%;
    padding: 14px;
    border-radius: 10px;
    border: none;
    font-weight: 600;
    cursor: pointer;
    font-size: 16px;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    color: white;
    transition: transform 0.2s;
}

.btn:hover {
    transform: translateY(-2px);
}

.btn-secondary {
    background: var(--bg-main);
    color: var(--text-primary);
    border: 1px solid var(--border-color);
    margin-top: 12px;
}

.info-box {
    background: rgba(102, 126, 234, 0.1);
    border-left: 4px solid var(--primary);
    border-radius: 8px;
    padding: 16px;
    margin-top: 24px;
    font-size: 13px;
    line-height: 1.6;
}

.config-status {
    margin-bottom: 24px;
    padding: 16px;
    background: var(--bg-main);
    border-radius: 10px;
}

.config-item {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid var(--border-color);
}

.config-item:last-child {
    border-bottom: none;
}

.config-label {
    font-weight: 600;
    color: var(--text-secondary);
    font-size: 13px;
}

.config-value {
    font-size: 13px;
}

.status-badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
}

.status-enabled {
    background: rgba(16, 185, 129, 0.15);
    color: var(--success);
}

.status-disabled {
    background: rgba(239, 68, 68, 0.15);
    color: var(--danger);
}
</style>
</head>
<body>

<div class="container">
    <div class="card">
        <h1 class="page-title">📧 Test Email Configuration</h1>
        <p class="page-subtitle">Send a test email to verify your SMTP settings</p>

        <?php if ($result): ?>
            <div class="alert <?= $result['success'] ? 'alert-success' : 'alert-danger' ?>">
                <span class="alert-icon"><?= $result['success'] ? '✅' : '❌' ?></span>
                <span><?= htmlspecialchars($result['message']) ?></span>
            </div>
        <?php endif; ?>

        <?php
        // Display current configuration
        $config = require '../../classes/email_config.php';
        ?>
        
        <div class="config-status">
            <h3 style="font-size:16px; margin-bottom:12px; color:var(--primary)">Current Configuration</h3>
            <div class="config-item">
                <span class="config-label">Email Notifications:</span>
                <span class="status-badge <?= $config['enabled'] ? 'status-enabled' : 'status-disabled' ?>">
                    <?= $config['enabled'] ? 'ENABLED' : 'DISABLED' ?>
                </span>
            </div>
            <div class="config-item">
                <span class="config-label">SMTP Host:</span>
                <span class="config-value"><?= htmlspecialchars($config['smtp_host']) ?></span>
            </div>
            <div class="config-item">
                <span class="config-label">SMTP Port:</span>
                <span class="config-value"><?= htmlspecialchars($config['smtp_port']) ?></span>
            </div>
            <div class="config-item">
                <span class="config-label">From Email:</span>
                <span class="config-value"><?= htmlspecialchars($config['from_email']) ?></span>
            </div>
            <div class="config-item">
                <span class="config-label">Username Set:</span>
                <span class="config-value"><?= !empty($config['smtp_username']) ? 'Yes ✓' : 'No ✗' ?></span>
            </div>
            <div class="config-item">
                <span class="config-label">Password Set:</span>
                <span class="config-value"><?= !empty($config['smtp_password']) ? 'Yes ✓' : 'No ✗' ?></span>
            </div>
        </div>

        <form method="POST">
            <div class="form-group">
                <label>Send Test Email To:</label>
                <input type="email" 
                       name="email" 
                       placeholder="your-email@gmail.com" 
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                       required>
            </div>

            <button type="submit" name="test_email" class="btn">
                Send Test Email
            </button>
            
            <a href="../dashboard.php" class="btn btn-secondary" style="display:block; text-align:center; text-decoration:none">
                ← Back to Dashboard
            </a>
        </form>

        <div class="info-box">
            <strong>📝 Before Testing:</strong><br>
            1. Make sure you've updated <code>classes/email_config.php</code> with your Gmail credentials<br>
            2. Use a Gmail App Password (not your regular password)<br>
            3. Enable 2-Step Verification in your Google Account<br>
            4. Generate App Password at: <a href="https://myaccount.google.com/apppasswords" target="_blank" style="color:var(--primary)">myaccount.google.com/apppasswords</a><br>
            <br>
            <strong>📧 What Happens:</strong><br>
            A test email will be sent to the address you provide. Check your inbox (and spam folder) for the test message.
        </div>
    </div>
</div>

<script src="../../assets/js/theme.js"></script>
</body>
</html>