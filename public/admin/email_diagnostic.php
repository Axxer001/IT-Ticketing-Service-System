<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Check PHP extensions
$checks = [];

// 1. Check if OpenSSL is loaded
$checks['OpenSSL Extension'] = extension_loaded('openssl') ? '✅ Enabled' : '❌ MISSING - Required for SMTP';

// 2. Check if PHPMailer exists
$checks['PHPMailer Library'] = file_exists(__DIR__ . '/../../vendor/autoload.php') ? '✅ Found' : '❌ MISSING';

// 3. Check config file
$configExists = file_exists(__DIR__ . '/../../classes/email_config.php');
$checks['Config File'] = $configExists ? '✅ Found' : '❌ MISSING';

// 4. Load and check config
$configValid = false;
$configDetails = [];
if ($configExists) {
    try {
        $config = require __DIR__ . '/../../classes/email_config.php';
        $configValid = !empty($config['smtp_username']) && !empty($config['smtp_password']);
        $configDetails = [
            'Enabled' => $config['enabled'] ? '✅ Yes' : '❌ No',
            'SMTP Host' => $config['smtp_host'],
            'SMTP Port' => $config['smtp_port'],
            'Username' => !empty($config['smtp_username']) ? '✅ Set (' . substr($config['smtp_username'], 0, 5) . '...)' : '❌ Empty',
            'Password' => !empty($config['smtp_password']) ? '✅ Set (' . strlen($config['smtp_password']) . ' chars)' : '❌ Empty',
            'From Email' => $config['from_email']
        ];
        $checks['Config Valid'] = $configValid ? '✅ Valid' : '❌ Missing credentials';
    } catch (Exception $e) {
        $checks['Config Valid'] = '❌ Error: ' . $e->getMessage();
    }
}

// 5. Test SMTP connection (simple socket test)
$smtpConnectable = false;
$smtpError = '';
if ($configValid) {
    $config = require __DIR__ . '/../../classes/email_config.php';
    $errno = 0;
    $errstr = '';
    $socket = @fsockopen($config['smtp_host'], $config['smtp_port'], $errno, $errstr, 5);
    if ($socket) {
        $smtpConnectable = true;
        fclose($socket);
        $checks['SMTP Connection'] = '✅ Can connect to ' . $config['smtp_host'] . ':' . $config['smtp_port'];
    } else {
        $smtpError = "Error $errno: $errstr";
        $checks['SMTP Connection'] = '❌ Cannot connect - ' . $smtpError;
    }
}

// 6. Check error log
$errorLogPath = ini_get('error_log');
$checks['Error Log Path'] = $errorLogPath ?: 'C:\xampp\php\logs\php_error_log';

// Get recent error log entries
$recentErrors = [];
if ($errorLogPath && file_exists($errorLogPath)) {
    $logContent = file($errorLogPath);
    $recentErrors = array_slice(array_reverse($logContent), 0, 10);
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= $_SESSION['theme'] ?? 'light' ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Email Diagnostic Tool - Nexon</title>
<link rel="stylesheet" href="../../assets/css/theme.css">
<style>
:root {
    --primary: #667eea;
    --success: #10b981;
    --danger: #ef4444;
    --bg-main: #f8fafc;
    --bg-card: #ffffff;
    --text-primary: #1e293b;
    --text-secondary: #64748b;
    --border-color: #e2e8f0;
}

[data-theme="dark"] {
    --bg-main: #0f172a;
    --bg-card: #1e293b;
    --text-primary: #f1f5f9;
    --text-secondary: #cbd5e1;
    --border-color: #334155;
}

body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    background: var(--bg-main);
    color: var(--text-primary);
    padding: 24px;
}

.container {
    max-width: 900px;
    margin: 0 auto;
}

.card {
    background: var(--bg-card);
    border-radius: 16px;
    padding: 24px;
    margin-bottom: 24px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    border: 1px solid var(--border-color);
}

.page-title {
    font-size: 28px;
    font-weight: 700;
    margin-bottom: 8px;
}

.check-item {
    display: flex;
    justify-content: space-between;
    padding: 12px;
    border-bottom: 1px solid var(--border-color);
    align-items: center;
}

.check-item:last-child {
    border-bottom: none;
}

.check-label {
    font-weight: 600;
}

.check-status {
    font-family: monospace;
}

.btn {
    padding: 10px 20px;
    border-radius: 8px;
    border: none;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
}

.btn-primary {
    background: linear-gradient(135deg, var(--primary), #764ba2);
    color: white;
}

.alert {
    padding: 16px;
    border-radius: 8px;
    margin-bottom: 16px;
}

.alert-warning {
    background: rgba(245, 158, 11, 0.1);
    border-left: 4px solid #f59e0b;
    color: #f59e0b;
}

.code-block {
    background: #1e293b;
    color: #f1f5f9;
    padding: 16px;
    border-radius: 8px;
    overflow-x: auto;
    font-family: 'Courier New', monospace;
    font-size: 12px;
    line-height: 1.5;
}

pre {
    margin: 0;
    white-space: pre-wrap;
    word-wrap: break-word;
}
</style>
</head>
<body>

<div class="container">
    <h1 class="page-title">🔍 Email Diagnostic Tool</h1>
    <p style="color: var(--text-secondary); margin-bottom: 24px;">
        This tool checks your email configuration and helps identify issues
    </p>

    <div class="card">
        <h2 style="font-size: 18px; margin-bottom: 16px; color: var(--primary);">System Checks</h2>
        <?php foreach ($checks as $label => $status): ?>
        <div class="check-item">
            <span class="check-label"><?= $label ?>:</span>
            <span class="check-status"><?= $status ?></span>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if (!empty($configDetails)): ?>
    <div class="card">
        <h2 style="font-size: 18px; margin-bottom: 16px; color: var(--primary);">Configuration Details</h2>
        <?php foreach ($configDetails as $key => $value): ?>
        <div class="check-item">
            <span class="check-label"><?= $key ?>:</span>
            <span class="check-status"><?= $value ?></span>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (!$configValid): ?>
    <div class="alert alert-warning">
        <strong>⚠️ Configuration Issue Detected</strong><br>
        Your email configuration is incomplete. Please update <code>classes/email_config.php</code> with your Gmail credentials.
        <br><br>
        <strong>Steps to fix:</strong><br>
        1. Generate Gmail App Password: <a href="https://myaccount.google.com/apppasswords" target="_blank" style="color:#667eea">myaccount.google.com/apppasswords</a><br>
        2. Edit <code>classes/email_config.php</code><br>
        3. Add your Gmail address and App Password (16 characters, no spaces)
    </div>
    <?php endif; ?>

    <?php if (!extension_loaded('openssl')): ?>
    <div class="alert alert-warning">
        <strong>⚠️ OpenSSL Missing</strong><br>
        OpenSSL extension is required for SMTP connections. To enable it:
        <br><br>
        1. Open <code>C:\xampp\php\php.ini</code><br>
        2. Find the line: <code>;extension=openssl</code><br>
        3. Remove the semicolon: <code>extension=openssl</code><br>
        4. Save and restart Apache
    </div>
    <?php endif; ?>

    <?php if (!empty($recentErrors)): ?>
    <div class="card">
        <h2 style="font-size: 18px; margin-bottom: 16px; color: var(--primary);">Recent Error Log (Last 10 entries)</h2>
        <div class="code-block">
            <pre><?php foreach ($recentErrors as $error): ?><?= htmlspecialchars(trim($error)) ?>
<?php endforeach; ?></pre>
        </div>
    </div>
    <?php endif; ?>

    <div style="display: flex; gap: 12px; margin-top: 24px;">
        <a href="test_email.php" class="btn btn-primary">Go to Email Test Tool</a>
        <a href="../dashboard.php" class="btn" style="background: var(--bg-main); color: var(--text-primary);">← Back to Dashboard</a>
    </div>

    <div class="card" style="margin-top: 24px;">
        <h2 style="font-size: 18px; margin-bottom: 16px; color: var(--primary);">Quick Troubleshooting Guide</h2>
        <div style="font-size: 14px; line-height: 1.8;">
            <strong>If OpenSSL is missing:</strong><br>
            • Edit <code>C:\xampp\php\php.ini</code><br>
            • Uncomment: <code>extension=openssl</code><br>
            • Restart Apache<br>
            <br>
            <strong>If SMTP connection fails:</strong><br>
            • Check your internet connection<br>
            • Make sure port 587 is not blocked by firewall<br>
            • Temporarily disable antivirus/firewall to test<br>
            <br>
            <strong>If credentials are invalid:</strong><br>
            • Use Gmail App Password (NOT regular password)<br>
            • Enable 2-Step Verification first<br>
            • Generate new App Password at: <a href="https://myaccount.google.com/apppasswords" target="_blank" style="color: var(--primary);">Google Account</a><br>
            • App Password should be 16 characters with NO spaces<br>
            <br>
            <strong>Test connection manually:</strong><br>
            • Open Command Prompt<br>
            • Run: <code>telnet smtp.gmail.com 587</code><br>
            • If it fails, your firewall is blocking the port
        </div>
    </div>
</div>

<script src="../../assets/js/theme.js"></script>
</body>
</html>