<?php
session_start();
require_once "../classes/Notification.php";

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$notificationId = $data['notification_id'] ?? 0;

if (!$notificationId) {
    echo json_encode(['success' => false, 'message' => 'Invalid notification ID']);
    exit;
}

$notificationObj = new Notification();
$result = $notificationObj->markAsRead($notificationId, $_SESSION['user_id']);

if ($result) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to mark as read']);
}