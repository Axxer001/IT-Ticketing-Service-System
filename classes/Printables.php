<?php
require_once "Database.php";

/**
 * Printables Management Class
 * Handles generation of printable reports for tickets, service logs, and summaries
 */
class Printables {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    /**
     * Get ticket report data
     */
    public function getTicketReport($ticketId) {
        $sql = "SELECT t.*, 
                e.first_name, e.last_name, e.contact_number,
                u.email as employee_email,
                d.name as department_name, d.category as department_category,
                dt.type_name as device_type_name,
                sp.provider_name, sp.contact_number as provider_contact,
                spu.email as provider_email,
                tr.rating, tr.feedback as rating_feedback
                FROM tickets t
                JOIN employees e ON t.employee_id = e.id
                JOIN users u ON e.user_id = u.id
                JOIN departments d ON t.department_id = d.id
                JOIN device_types dt ON t.device_type_id = dt.id
                LEFT JOIN service_providers sp ON t.assigned_provider_id = sp.id
                LEFT JOIN users spu ON sp.user_id = spu.id
                LEFT JOIN ticket_ratings tr ON t.id = tr.ticket_id
                WHERE t.id = ?";
        
        $stmt = $this->db->connect()->prepare($sql);
        $stmt->execute([$ticketId]);
        $ticket = $stmt->fetch();
        
        if ($ticket) {
            // Get attachments
            $sql = "SELECT * FROM ticket_attachments WHERE ticket_id = ? ORDER BY uploaded_at ASC";
            $stmt = $this->db->connect()->prepare($sql);
            $stmt->execute([$ticketId]);
            $ticket['attachments'] = $stmt->fetchAll();
            
            // Get updates/timeline
            $sql = "SELECT tu.*, u.email, u.user_type 
                    FROM ticket_updates tu 
                    JOIN users u ON tu.user_id = u.id 
                    WHERE tu.ticket_id = ? 
                    ORDER BY tu.created_at ASC";
            $stmt = $this->db->connect()->prepare($sql);
            $stmt->execute([$ticketId]);
            $ticket['updates'] = $stmt->fetchAll();
        }
        
        return $ticket;
    }
    
    /**
     * Get service provider logs
     */
    public function getServiceProviderLogs($providerId, $dateFrom = null, $dateTo = null) {
        if (!$dateFrom) $dateFrom = date('Y-m-d', strtotime('-30 days'));
        if (!$dateTo) $dateTo = date('Y-m-d');
        
        $sql = "SELECT t.*, 
                e.first_name, e.last_name,
                d.name as department_name,
                dt.type_name as device_type_name,
                tr.rating
                FROM tickets t
                JOIN employees e ON t.employee_id = e.id
                JOIN departments d ON t.department_id = d.id
                JOIN device_types dt ON t.device_type_id = dt.id
                LEFT JOIN ticket_ratings tr ON t.id = tr.ticket_id
                WHERE t.assigned_provider_id = ?
                AND DATE(t.created_at) BETWEEN ? AND ?
                ORDER BY t.created_at DESC";
        
        $stmt = $this->db->connect()->prepare($sql);
        $stmt->execute([$providerId, $dateFrom, $dateTo]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Get summary report data
     */
    public function getSummaryReport($dateFrom = null, $dateTo = null, $filters = []) {
        if (!$dateFrom) $dateFrom = date('Y-m-d', strtotime('-30 days'));
        if (!$dateTo) $dateTo = date('Y-m-d');
        
        $report = [
            'period' => [
                'from' => $dateFrom,
                'to' => $dateTo
            ],
            'stats' => [],
            'tickets' => [],
            'by_status' => [],
            'by_priority' => [],
            'by_department' => [],
            'providers' => []
        ];
        
        // Overall statistics
        $sql = "SELECT 
                COUNT(*) as total_tickets,
                SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_tickets,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_tickets,
                SUM(CASE WHEN status IN ('assigned', 'in_progress') THEN 1 ELSE 0 END) as active_tickets,
                AVG(CASE WHEN status = 'resolved' THEN TIMESTAMPDIFF(HOUR, created_at, resolved_at) END) as avg_resolution_hours
                FROM tickets 
                WHERE DATE(created_at) BETWEEN ? AND ?";
        
        $stmt = $this->db->connect()->prepare($sql);
        $stmt->execute([$dateFrom, $dateTo]);
        $report['stats'] = $stmt->fetch();
        
        // Tickets by status
        $sql = "SELECT status, COUNT(*) as count 
                FROM tickets 
                WHERE DATE(created_at) BETWEEN ? AND ?
                GROUP BY status";
        $stmt = $this->db->connect()->prepare($sql);
        $stmt->execute([$dateFrom, $dateTo]);
        $report['by_status'] = $stmt->fetchAll();
        
        // Tickets by priority
        $sql = "SELECT priority, COUNT(*) as count 
                FROM tickets 
                WHERE DATE(created_at) BETWEEN ? AND ?
                GROUP BY priority";
        $stmt = $this->db->connect()->prepare($sql);
        $stmt->execute([$dateFrom, $dateTo]);
        $report['by_priority'] = $stmt->fetchAll();
        
        // Tickets by department
        $sql = "SELECT d.name, d.category, COUNT(t.id) as count 
                FROM tickets t
                JOIN departments d ON t.department_id = d.id
                WHERE DATE(t.created_at) BETWEEN ? AND ?
                GROUP BY d.id
                ORDER BY count DESC
                LIMIT 10";
        $stmt = $this->db->connect()->prepare($sql);
        $stmt->execute([$dateFrom, $dateTo]);
        $report['by_department'] = $stmt->fetchAll();
        
        // Provider performance
        $sql = "SELECT 
                sp.provider_name,
                COUNT(t.id) as total_tickets,
                SUM(CASE WHEN t.status = 'resolved' THEN 1 ELSE 0 END) as resolved_tickets,
                AVG(CASE WHEN t.status = 'resolved' THEN TIMESTAMPDIFF(HOUR, t.assigned_at, t.resolved_at) END) as avg_resolution_hours,
                AVG(tr.rating) as avg_rating,
                COUNT(tr.id) as total_ratings
                FROM service_providers sp
                LEFT JOIN tickets t ON sp.id = t.assigned_provider_id 
                    AND DATE(t.created_at) BETWEEN ? AND ?
                LEFT JOIN ticket_ratings tr ON t.id = tr.ticket_id
                GROUP BY sp.id
                HAVING total_tickets > 0
                ORDER BY total_tickets DESC";
        $stmt = $this->db->connect()->prepare($sql);
        $stmt->execute([$dateFrom, $dateTo]);
        $report['providers'] = $stmt->fetchAll();
        
        // All tickets for detailed list
        $sql = "SELECT t.ticket_number, t.status, t.priority, t.created_at, t.resolved_at,
                e.first_name, e.last_name,
                d.name as department_name,
                dt.type_name as device_type_name,
                sp.provider_name
                FROM tickets t
                JOIN employees e ON t.employee_id = e.id
                JOIN departments d ON t.department_id = d.id
                JOIN device_types dt ON t.device_type_id = dt.id
                LEFT JOIN service_providers sp ON t.assigned_provider_id = sp.id
                WHERE DATE(t.created_at) BETWEEN ? AND ?
                ORDER BY t.created_at DESC";
        $stmt = $this->db->connect()->prepare($sql);
        $stmt->execute([$dateFrom, $dateTo]);
        $report['tickets'] = $stmt->fetchAll();
        
        return $report;
    }
    
    /**
     * Get tickets for export (with filters)
     */
    public function getTicketsForExport($filters = []) {
        $sql = "SELECT 
                t.ticket_number,
                t.status,
                t.priority,
                t.created_at,
                t.assigned_at,
                t.resolved_at,
                CONCAT(e.first_name, ' ', e.last_name) as employee_name,
                e.contact_number as employee_contact,
                d.name as department,
                dt.type_name as device_type,
                t.device_name,
                t.issue_description,
                sp.provider_name,
                tr.rating
                FROM tickets t
                JOIN employees e ON t.employee_id = e.id
                JOIN departments d ON t.department_id = d.id
                JOIN device_types dt ON t.device_type_id = dt.id
                LEFT JOIN service_providers sp ON t.assigned_provider_id = sp.id
                LEFT JOIN ticket_ratings tr ON t.id = tr.ticket_id
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(t.created_at) >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(t.created_at) <= ?";
            $params[] = $filters['date_to'];
        }
        
        if (!empty($filters['status'])) {
            $sql .= " AND t.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['priority'])) {
            $sql .= " AND t.priority = ?";
            $params[] = $filters['priority'];
        }
        
        $sql .= " ORDER BY t.created_at DESC";
        
        $stmt = $this->db->connect()->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
}