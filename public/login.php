<?php
session_start();
require_once "../classes/User.php";

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $userObj = new User();
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    
    $user = $userObj->login($email, $password);
    
    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_type'] = $user['user_type'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['theme'] = $user['theme'] ?? 'light';
        
        // Get profile details
        $profile = $userObj->getUserProfile($user['id']);
        $_SESSION['profile'] = $profile['profile'] ?? [];
        
        header("Location: dashboard.php");
        exit;
    } else {
        $error = "Invalid email or password";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - Nexon Ticketing System</title>
<link rel="stylesheet" href="../assets/css/theme.css">
<style>
:root {
    --primary: #667eea;
    --secondary: #764ba2;
    --bg-main: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    --text-dark: #333;
    --text-light: #666;
}

[data-theme="dark"] {
    --primary: #7c3aed;
    --secondary: #a78bfa;
    --bg-main: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%);
    --text-dark: #e5e7eb;
    --text-light: #9ca3af;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
    background: var(--bg-main);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    transition: background 0.3s;
}

[data-theme="dark"] body {
    background: #0f172a;
}

.login-container {
    background: white;
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    max-width: 450px;
    width: 100%;
    padding: 50px 40px;
    animation: slideUp 0.5s ease;
    position: relative;
}

[data-theme="dark"] .login-container {
    background: #1e293b;
    box-shadow: 0 20px 60px rgba(0,0,0,0.6);
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.theme-toggle {
    position: absolute;
    top: 20px;
    right: 20px;
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    opacity: 0.7;
    transition: opacity 0.3s;
}

.theme-toggle:hover {
    opacity: 1;
}

.logo {
    text-align: center;
    margin-bottom: 40px;
}

.logo h1 {
    font-size: 40px;
    background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    font-weight: 800;
    letter-spacing: 3px;
}

.logo p {
    color: var(--text-light);
    margin-top: 8px;
    font-size: 14px;
}

.alert-danger {
    padding: 12px 16px;
    background: #fee;
    color: #c33;
    border-left: 4px solid #c33;
    border-radius: 8px;
    margin-bottom: 20px;
    font-size: 14px;
}

[data-theme="dark"] .alert-danger {
    background: #7f1d1d;
    color: #fca5a5;
    border-left-color: #ef4444;
}

.form-group {
    margin-bottom: 24px;
}

label {
    display: block;
    margin-bottom: 8px;
    color: var(--text-dark);
    font-weight: 600;
    font-size: 14px;
}

input {
    width: 100%;
    padding: 14px 16px;
    border: 2px solid #e0e0e0;
    border-radius: 10px;
    font-size: 15px;
    transition: all 0.3s;
    font-family: inherit;
    background: white;
    color: var(--text-dark);
}

[data-theme="dark"] input {
    background: #0f172a;
    border-color: #334155;
    color: #e5e7eb;
}

input:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

[data-theme="dark"] input:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.2);
}

.btn {
    width: 100%;
    padding: 14px;
    background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
    color: white;
    border: none;
    border-radius: 10px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: transform 0.2s, box-shadow 0.2s;
    margin-top: 10px;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
}

.btn:active {
    transform: translateY(0);
}

.text-center {
    text-align: center;
    margin-top: 24px;
    color: var(--text-light);
    font-size: 14px;
}

.text-center a {
    color: var(--primary);
    text-decoration: none;
    font-weight: 600;
}

.text-center a:hover {
    text-decoration: underline;
}

.divider {
    text-align: center;
    margin: 30px 0;
    position: relative;
}

.divider::before {
    content: '';
    position: absolute;
    left: 0;
    top: 50%;
    width: 100%;
    height: 1px;
    background: #e0e0e0;
}

[data-theme="dark"] .divider::before {
    background: #334155;
}

.divider span {
    position: relative;
    background: white;
    padding: 0 15px;
    color: var(--text-light);
    font-size: 13px;
}

[data-theme="dark"] .divider span {
    background: #1e293b;
}

@media (max-width: 500px) {
    .login-container {
        padding: 40px 30px;
    }
}
</style>
</head>
<body>

<div class="login-container">
    <button class="theme-toggle" id="themeToggle" data-theme-toggle>🌙</button>
    
    <div class="logo">
        <h1>NEXON</h1>
        <p>IT Ticketing System</p>
    </div>

    <?php if ($error): ?>
        <div class="alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label>Email Address</label>
            <input type="email" name="email" placeholder="your.email@nexon.com" required autofocus>
        </div>

        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" placeholder="Enter your password" required>
        </div>

        <button type="submit" class="btn">Sign In</button>
    </form>

    <div class="divider">
        <span>OR</span>
    </div>

    <div class="text-center">
        Don't have an account? <a href="register.php">Create Account</a>
    </div>
</div>

<script src="../assets/js/theme.js"></script>
</body>
</html>