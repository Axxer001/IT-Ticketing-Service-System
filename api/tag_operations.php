<?php
header('Content-Type: application/json');
require_once "../classes/TicketTag.php";

session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$tagObj = new TicketTag();

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'get_all_tags':
        $tags = $tagObj->getAllTags();
        echo json_encode([
            'success' => true,
            'data' => $tags
        ]);
        break;
        
    case 'get_ticket_tags':
        if (!isset($_GET['ticket_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Ticket ID required']);
            exit;
        }
        
        $ticketId = (int)$_GET['ticket_id'];
        $tags = $tagObj->getTicketTags($ticketId);
        
        echo json_encode([
            'success' => true,
            'data' => $tags
        ]);
        break;
        
    case 'add_tag':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            exit;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (empty($input['ticket_id']) || empty($input['tag_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Ticket ID and Tag ID required']);
            exit;
        }
        
        $result = $tagObj->addTagToTicket(
            (int)$input['ticket_id'],
            (int)$input['tag_id'],
            $_SESSION['user_id']
        );
        
        echo json_encode([
            'success' => $result,
            'message' => $result ? 'Tag added successfully' : 'Failed to add tag'
        ]);
        break;
        
    case 'remove_tag':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            exit;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (empty($input['ticket_id']) || empty($input['tag_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Ticket ID and Tag ID required']);
            exit;
        }
        
        $result = $tagObj->removeTagFromTicket(
            (int)$input['ticket_id'],
            (int)$input['tag_id']
        );
        
        echo json_encode([
            'success' => $result,
            'message' => $result ? 'Tag removed successfully' : 'Failed to remove tag'
        ]);
        break;
        
    case 'create_tag':
        if ($_SESSION['user_type'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            exit;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (empty($input['name'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Tag name required']);
            exit;
        }
        
        $tagData = [
            'name' => $input['name'],
            'color' => $input['color'] ?? '#667eea',
            'description' => $input['description'] ?? null
        ];
        
        $result = $tagObj->createTag($tagData);
        
        echo json_encode([
            'success' => $result !== false,
            'tag_id' => $result,
            'message' => $result !== false ? 'Tag created successfully' : 'Failed to create tag'
        ]);
        break;
        
    case 'search_by_tag':
        if (!isset($_GET['tag_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Tag ID required']);
            exit;
        }
        
        $tagId = (int)$_GET['tag_id'];
        $tickets = $tagObj->getTicketsByTag($tagId);
        
        echo json_encode([
            'success' => true,
            'count' => count($tickets),
            'data' => $tickets
        ]);
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        break;
}
?>