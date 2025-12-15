<?php
session_start();
require_once "../../classes/SLA.php";

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}
require_once "../includes/sidebar_component.php";

$slaObj = new SLA();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settings = [
        'low_response' => (int)$_POST['low_response'],
        'low_resolution' => (int)$_POST['low_resolution'],
        'medium_response' => (int)$_POST['medium_response'],
        'medium_resolution' => (int)$_POST['medium_resolution'],
        'high_response' => (int)$_POST['high_response'],
        'high_resolution' => (int)$_POST['high_resolution'],
        'critical_response' => (int)$_POST['critical_response'],
        'critical_resolution' => (int)$_POST['critical_resolution']
    ];
    
    if ($slaObj->updateSettings($settings)) {
        $success = "SLA settings updated successfully!";
    } else {
        $error = "Failed to update SLA settings.";
    }
}

$currentSettings = $slaObj->getSettings();
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= $_SESSION['theme'] ?? 'light' ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SLA Settings - Nexon</title>
<link rel="stylesheet" href="../../assets/css/theme.css">
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
    max-width: 900px;
    margin: 24px auto;
    padding: 0 24px;
}

.card {
    background: var(--bg-card);
    border-radius: 16px;
    padding: 32px;
    box-shadow: var(--shadow);
    border: 1px solid var(--border-color);
}

.card-title {
    font-size: 24px;
    font-weight: 700;
    margin-bottom: 24px;
}

.alert {
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.alert-success {
    background: rgba(16, 185, 129, 0.15);
    color: #10b981;
    border: 1px solid #10b981;
}

.alert-error {
    background: rgba(239, 68, 68, 0.15);
    color: #ef4444;
    border: 1px solid #ef4444;
}

.form-section {
    margin-bottom: 32px;
}

.section-title {
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 16px;
    color: var(--primary);
}

.form-group {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
    margin-bottom: 20px;
}

.input-group {
    display: flex;
    flex-direction: column;
}

label {
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 8px;
    color: var(--text-secondary);
}

input {
    padding: 10px 14px;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    background: var(--bg-main);
    color: var(--text-primary);
    font-size: 14px;
}

input:focus {
    outline: none;
    border-color: var(--primary);
}

.btn {
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    font-size: 14px;
    transition: all 0.2s;
}

.btn-primary {
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    color: white;
}

.btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 8px 16px rgba(102, 126, 234, 0.3);
}

.help-text {
    font-size: 12px;
    color: var(--text-secondary);
    margin-top: 4px;
}
</style>
</head>
<body>



<div class="main-content">
<div class="container">
    <div class="card">
        <h1 class="card-title">⚙️ SLA Configuration</h1>
        
        <?php if (isset($success)): ?>
        <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
        <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-section">
                <h2 class="section-title">🟢 Low Priority</h2>
                <div class="form-group">
                    <div class="input-group">
                        <label>Response Time (hours)</label>
                        <input type="number" name="low_response" value="<?= $currentSettings['low_response'] ?? 24 ?>" required>
                        <span class="help-text">Time to first response</span>
                    </div>
                    <div class="input-group">
                        <label>Resolution Time (hours)</label>
                        <input type="number" name="low_resolution" value="<?= $currentSettings['low_resolution'] ?? 120 ?>" required>
                        <span class="help-text">Time to resolve ticket</span>
                    </div>
                </div>
            </div>
            
            <div class="form-section">
                <h2 class="section-title">🟡 Medium Priority</h2>
                <div class="form-group">
                    <div class="input-group">
                        <label>Response Time (hours)</label>
                        <input type="number" name="medium_response" value="<?= $currentSettings['medium_response'] ?? 8 ?>" required>
                    </div>
                    <div class="input-group">
                        <label>Resolution Time (hours)</label>
                        <input type="number" name="medium_resolution" value="<?= $currentSettings['medium_resolution'] ?? 48 ?>" required>
                    </div>
                </div>
            </div>
            
            <div class="form-section">
                <h2 class="section-title">🟠 High Priority</h2>
                <div class="form-group">
                    <div class="input-group">
                        <label>Response Time (hours)</label>
                        <input type="number" name="high_response" value="<?= $currentSettings['high_response'] ?? 4 ?>" required>
                    </div>
                    <div class="input-group">
                        <label>Resolution Time (hours)</label>
                        <input type="number" name="high_resolution" value="<?= $currentSettings['high_resolution'] ?? 24 ?>" required>
                    </div>
                </div>
            </div>
            
            <div class="form-section">
                <h2 class="section-title">🔴 Critical Priority</h2>
                <div class="form-group">
                    <div class="input-group">
                        <label>Response Time (hours)</label>
                        <input type="number" name="critical_response" value="<?= $currentSettings['critical_response'] ?? 1 ?>" required>
                    </div>
                    <div class="input-group">
                        <label>Resolution Time (hours)</label>
                        <input type="number" name="critical_resolution" value="<?= $currentSettings['critical_resolution'] ?? 8 ?>" required>
                    </div>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary">💾 Save Settings</button>
        </form>
    </div>
</div>
</div>

</body>
</html>