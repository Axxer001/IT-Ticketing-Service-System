<?php
session_start();
require_once "../classes/User.php";

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$theme = $data['theme'] ?? 'light';

if (!in_array($theme, ['light', 'dark'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid theme']);
    exit;
}

$userObj = new User();
$result = $userObj->updatePreferences($_SESSION['user_id'], $theme);

if ($result) {
    $_SESSION['theme'] = $theme;
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update theme']);
}