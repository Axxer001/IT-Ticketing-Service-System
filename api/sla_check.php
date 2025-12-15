<?php
header('Content-Type: application/json');
require_once "../classes/SLA.php";

session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$slaObj = new SLA();

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'check_ticket':
        if (!isset($_GET['ticket_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Ticket ID required']);
            exit;
        }
        
        $ticketId = (int)$_GET['ticket_id'];
        $slaStatus = $slaObj->checkTicketSLA($ticketId);
        
        echo json_encode([
            'success' => true,
            'data' => $slaStatus
        ]);
        break;
        
    case 'at_risk_tickets':
        $atRiskTickets = $slaObj->getAtRiskTickets();
        
        echo json_encode([
            'success' => true,
            'count' => count($atRiskTickets),
            'data' => $atRiskTickets
        ]);
        break;
        
    case 'compliance_stats':
        $stats = $slaObj->getComplianceStats();
        
        echo json_encode([
            'success' => true,
            'data' => $stats
        ]);
        break;
        
    case 'update_settings':
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
        
        if (empty($input)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid input']);
            exit;
        }
        
        $result = $slaObj->updateSettings($input);
        
        echo json_encode([
            'success' => $result,
            'message' => $result ? 'Settings updated successfully' : 'Failed to update settings'
        ]);
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        break;
}
?>