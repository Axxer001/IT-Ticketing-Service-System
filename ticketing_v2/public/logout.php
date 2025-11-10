<?php
session_start();

// Log the logout action if user is logged in
if (isset($_SESSION['user_id'])) {
    require_once "../classes/AuditLog.php";
    $audit = new AuditLog();
    $audit->log($_SESSION['user_id'], 'user_logout', 'users', $_SESSION['user_id']);
}

// Destroy all session data
$_SESSION = array();

// Delete session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Destroy session
session_destroy();

// Redirect to login
header("Location: login.php");
exit;