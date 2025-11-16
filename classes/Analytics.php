<?php
require_once "Database.php";

/**
 * Analytics and Reporting Class
 */
class Analytics {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    /**
     * Get dashboard statistics based on user type
     */
    public function getDashboardStats($userType, $userId) {
        $stats = [];
        
        if ($userType === 'admin') {
            // Total tickets
            $sql = "SELECT COUNT(*) as total FROM tickets";
            $stmt = $this->db->connect()->prepare($sql);
            $stmt->execute();
            $stats['total_tickets'] = $stmt->fetch()['total'];
            
            // Pending tickets
            $sql = "SELECT COUNT(*) as count FROM tickets WHERE status = 'pending'";
            $stmt = $this->db->connect()->prepare($sql);
            $stmt->execute();
            $stats['pending'] = $stmt->fetch()['count'];
            
            // Active tickets (assigned + in_progress)
            $sql = "SELECT COUNT(*) as count FROM tickets WHERE status IN ('assigned', 'in_progress')";
            $stmt = $this->db->connect()->prepare($sql);
            $stmt->execute();
            $stats['active'] = $stmt->fetch()['count'];
            
            // Resolved today
            $sql = "SELECT COUNT(*) as count FROM tickets WHERE status = 'resolved' AND DATE(resolved_at) = CURDATE()";
            $stmt = $this->db->connect()->prepare($sql);
            $stmt->execute();
            $stats['resolved_today'] = $stmt->fetch()['count'];
            
        } elseif ($userType === 'employee') {
            // Get employee ID
            $sql = "SELECT id FROM employees WHERE user_id = ?";
            $stmt = $this->db->connect()->prepare($sql);
            $stmt->execute([$userId]);
            $employee = $stmt->fetch();
            $employeeId = $employee['id'];
            
            // My tickets
            $sql = "SELECT COUNT(*) as count FROM tickets WHERE employee_id = ?";
            $stmt = $this->db->connect()->prepare($sql);
            $stmt->execute([$employeeId]);
            $stats['my_tickets'] = $stmt->fetch()['count'];
            
            // By status
            $sql = "SELECT COUNT(*) as count FROM tickets WHERE employee_id = ? AND status = 'pending'";
            $stmt = $this->db->connect()->prepare($sql);
            $stmt->execute([$employeeId]);
            $stats['pending'] = $stmt->fetch()['count'];
            
            $sql = "SELECT COUNT(*) as count FROM tickets WHERE employee_id = ? AND status = 'in_progress'";
            $stmt = $this->db->connect()->prepare($sql);
            $stmt->execute([$employeeId]);
            $stats['in_progress'] = $stmt->fetch()['count'];
            
            $sql = "SELECT COUNT(*) as count FROM tickets WHERE employee_id = ? AND status = 'resolved'";
            $stmt = $this->db->connect()->prepare($sql);
            $stmt->execute([$employeeId]);
            $stats['resolved'] = $stmt->fetch()['count'];
            
        } elseif ($userType === 'service_provider') {
            // Get provider ID
            $sql = "SELECT id FROM service_providers WHERE user_id = ?";
            $stmt = $this->db->connect()->prepare($sql);
            $stmt->execute([$userId]);
            $provider = $stmt->fetch();
            $providerId = $provider['id'];
            
            // Assigned tickets
            $sql = "SELECT COUNT(*) as count FROM tickets WHERE assigned_provider_id = ? AND status IN ('assigned', 'in_progress')";
            $stmt = $this->db->connect()->prepare($sql);
            $stmt->execute([$providerId]);
            $stats['assigned'] = $stmt->fetch()['count'];
            
            $sql = "SELECT COUNT(*) as count FROM tickets WHERE assigned_provider_id = ? AND status = 'in_progress'";
            $stmt = $this->db->connect()->prepare($sql);
            $stmt->execute([$providerId]);
            $stats['in_progress'] = $stmt->fetch()['count'];
            
            $sql = "SELECT COUNT(*) as count FROM tickets WHERE assigned_provider_id = ? AND status = 'resolved'";
            $stmt = $this->db->connect()->prepare($sql);
            $stmt->execute([$providerId]);
            $stats['resolved'] = $stmt->fetch()['count'];
            
            // Rating stats
            $sql = "SELECT rating_average, total_ratings FROM service_providers WHERE id = ?";
            $stmt = $this->db->connect()->prepare($sql);
            $stmt->execute([$providerId]);
            $rating = $stmt->fetch();
            $stats['avg_rating'] = $rating['rating_average'] ?? 0;
            $stats['total_ratings'] = $rating['total_ratings'] ?? 0;
        }
        
        return $stats;
    }
    
    /**
     * Get ticket statistics for analytics dashboard
     */
    public function getTicketAnalytics($dateFrom = null, $dateTo = null) {
        if (!$dateFrom) $dateFrom = date('Y-m-d', strtotime('-30 days'));
        if (!$dateTo) $dateTo = date('Y-m-d');
        
        $analytics = [];
        
        // Tickets by status
        $sql = "SELECT status, COUNT(*) as count 
                FROM tickets 
                WHERE DATE(created_at) BETWEEN ? AND ?
                GROUP BY status";
        $stmt = $this->db->connect()->prepare($sql);
        $stmt->execute([$dateFrom, $dateTo]);
        $analytics['by_status'] = $stmt->fetchAll();
        
        // Tickets by priority
        $sql = "SELECT priority, COUNT(*) as count 
                FROM tickets 
                WHERE DATE(created_at) BETWEEN ? AND ?
                GROUP BY priority";
        $stmt = $this->db->connect()->prepare($sql);
        $stmt->execute([$dateFrom, $dateTo]);
        $analytics['by_priority'] = $stmt->fetchAll();
        
        // Tickets by department
        $sql = "SELECT d.name, COUNT(t.id) as count 
                FROM tickets t
                JOIN departments d ON t.department_id = d.id
                WHERE DATE(t.created_at) BETWEEN ? AND ?
                GROUP BY d.id
                ORDER BY count DESC
                LIMIT 10";
        $stmt = $this->db->connect()->prepare($sql);
        $stmt->execute([$dateFrom, $dateTo]);
        $analytics['by_department'] = $stmt->fetchAll();
        
        // Tickets by device type
        $sql = "SELECT dt.type_name, COUNT(t.id) as count 
                FROM tickets t
                JOIN device_types dt ON t.device_type_id = dt.id
                WHERE DATE(t.created_at) BETWEEN ? AND ?
                GROUP BY dt.id
                ORDER BY count DESC";
        $stmt = $this->db->connect()->prepare($sql);
        $stmt->execute([$dateFrom, $dateTo]);
        $analytics['by_device_type'] = $stmt->fetchAll();
        
        // Daily ticket creation
        $sql = "SELECT DATE(created_at) as date, COUNT(*) as count 
                FROM tickets 
                WHERE DATE(created_at) BETWEEN ? AND ?
                GROUP BY DATE(created_at)
                ORDER BY date ASC";
        $stmt = $this->db->connect()->prepare($sql);
        $stmt->execute([$dateFrom, $dateTo]);
        $analytics['daily_creation'] = $stmt->fetchAll();
        
        // Average resolution time
        $sql = "SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) as avg_hours
                FROM tickets 
                WHERE status = 'resolved' 
                AND DATE(resolved_at) BETWEEN ? AND ?";
        $stmt = $this->db->connect()->prepare($sql);
        $stmt->execute([$dateFrom, $dateTo]);
        $result = $stmt->fetch();
        $analytics['avg_resolution_hours'] = round($result['avg_hours'] ?? 0, 2);
        
        return $analytics;
    }
    
    /**
     * Get provider performance metrics
     */
    public function getProviderPerformance() {
        $sql = "SELECT 
                sp.provider_name,
                sp.rating_average,
                sp.total_ratings,
                COUNT(t.id) as total_tickets,
                SUM(CASE WHEN t.status = 'resolved' THEN 1 ELSE 0 END) as resolved_tickets,
                AVG(CASE WHEN t.status = 'resolved' THEN TIMESTAMPDIFF(HOUR, t.assigned_at, t.resolved_at) END) as avg_resolution_hours
                FROM service_providers sp
                LEFT JOIN tickets t ON sp.id = t.assigned_provider_id
                GROUP BY sp.id
                ORDER BY sp.rating_average DESC";
        
        $stmt = $this->db->connect()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Get department performance
     */
    public function getDepartmentPerformance() {
        $sql = "SELECT 
                d.name as department_name,
                d.category,
                COUNT(t.id) as total_tickets,
                SUM(CASE WHEN t.status = 'resolved' THEN 1 ELSE 0 END) as resolved_tickets,
                SUM(CASE WHEN t.status = 'pending' THEN 1 ELSE 0 END) as pending_tickets
                FROM departments d
                LEFT JOIN tickets t ON d.id = t.department_id
                GROUP BY d.id
                ORDER BY total_tickets DESC";
        
        $stmt = $this->db->connect()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}