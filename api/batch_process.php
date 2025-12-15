<?php
header('Content-Type: application/json');
require_once "../classes/BatchOperations.php";

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden - Admin access required']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$batchOps = new BatchOperations();
$input = json_decode(file_get_contents('php://input'), true);

$action = $input['action'] ?? '';
$ticketIds = $input['ticket_ids'] ?? [];

if (empty($ticketIds) || !is_array($ticketIds)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid or empty ticket IDs']);
    exit;
}

$result = false;
$message = '';

switch ($action) {
    case 'assign':
        if (empty($input['provider_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Provider ID required']);
            exit;
        }
        
        $result = $batchOps->batchAssign(
            $ticketIds,
            (int)$input['provider_id'],
            $_SESSION['user_id']
        );
        
        $message = $result ? 
            count($ticketIds) . ' tickets assigned successfully' : 
            'Failed to assign tickets';
        break;
        
    case 'update_status':
        if (empty($input['status'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Status required']);
            exit;
        }
        
        $validStatuses = ['pending', 'assigned', 'in_progress', 'resolved', 'closed', 'cancelled'];
        if (!in_array($input['status'], $validStatuses)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid status']);
            exit;
        }
        
        $result = $batchOps->batchUpdateStatus(
            $ticketIds,
            $input['status'],
            $_SESSION['user_id']
        );
        
        $message = $result ? 
            count($ticketIds) . ' tickets updated successfully' : 
            'Failed to update tickets';
        break;
        
    case 'update_priority':
        if (empty($input['priority'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Priority required']);
            exit;
        }
        
        $validPriorities = ['low', 'medium', 'high', 'critical'];
        if (!in_array($input['priority'], $validPriorities)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid priority']);
            exit;
        }
        
        $result = $batchOps->batchUpdatePriority(
            $ticketIds,
            $input['priority'],
            $_SESSION['user_id']
        );
        
        $message = $result ? 
            count($ticketIds) . ' tickets updated successfully' : 
            'Failed to update tickets';
        break;
        
    case 'add_tags':
        if (empty($input['tag_ids']) || !is_array($input['tag_ids'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Tag IDs required']);
            exit;
        }
        
        $result = $batchOps->batchAddTags(
            $ticketIds,
            $input['tag_ids'],
            $_SESSION['user_id']
        );
        
        $message = $result ? 
            'Tags added to ' . count($ticketIds) . ' tickets successfully' : 
            'Failed to add tags';
        break;
        
    case 'close':
        $result = $batchOps->batchClose(
            $ticketIds,
            $_SESSION['user_id']
        );
        
        $message = $result ? 
            count($ticketIds) . ' tickets closed successfully' : 
            'Failed to close tickets';
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        exit;
}

echo json_encode([
    'success' => $result,
    'message' => $message,
    'affected_count' => $result ? count($ticketIds) : 0
]);
?>