<?php
session_start();
require_once "../classes/Notification.php";

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$notificationObj = new Notification();
$count = $notificationObj->getUnreadCount($_SESSION['user_id']);

echo json_encode(['success' => true, 'count' => $count]);