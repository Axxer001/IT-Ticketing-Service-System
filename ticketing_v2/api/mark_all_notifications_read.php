<?php
session_start();
require_once "../classes/Notification.php";

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$notificationObj = new Notification();
$result = $notificationObj->markAllAsRead($_SESSION['user_id']);

if ($result) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to mark all as read']);
}