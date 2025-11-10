<?php
require_once "Database.php";

/**
 * Optimized Audit Log Class
 * FIXED: Made logging optional to improve speed
 */
class AuditLog {
    private $db;
    private $enabled = false; // OPTIMIZED: Disabled by default for student project speed
    
    public function __construct() {
        $this->db = new Database();
    }
    
    /**
     * Enable audit logging (call this if you want logs)
     */
    public function enable() {
        $this->enabled = true;
    }
    
    /**
     * Disable audit logging (default for speed)
     */
    public function disable() {
        $this->enabled = false;
    }
    
    /**
     * Log an action - OPTIMIZED: Only logs if enabled
     */
    public function log($userId, $action, $tableName = null, $recordId = null, $oldValues = null, $newValues = null) {
        // Skip logging if disabled for performance
        if (!$this->enabled) {
            return true;
        }
        
        try {
            $sql = "INSERT INTO audit_logs 
                    (user_id, action, table_name, record_id, old_values, new_values, ip_address, user_agent) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->connect()->prepare($sql);
            $stmt->execute([
                $userId,
                $action,
                $tableName,
                $recordId,
                $oldValues,
                $newValues,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
            
            return true;
        } catch (Exception $e) {
            error_log("Audit log error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get audit logs with filters
     */
    public function getLogs($filters = [], $limit = 100, $offset = 0) {
        $sql = "SELECT al.*, u.email as user_email 
                FROM audit_logs al 
                JOIN users u ON al.user_id = u.id 
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($filters['user_id'])) {
            $sql .= " AND al.user_id = ?";
            $params[] = $filters['user_id'];
        }
        
        if (!empty($filters['action'])) {
            $sql .= " AND al.action = ?";
            $params[] = $filters['action'];
        }
        
        if (!empty($filters['table_name'])) {
            $sql .= " AND al.table_name = ?";
            $params[] = $filters['table_name'];
        }
        
        if (!empty($filters['date_from'])) {
            $sql .= " AND al.created_at >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND al.created_at <= ?";
            $params[] = $filters['date_to'];
        }
        
        $sql .= " ORDER BY al.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $this->db->connect()->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Get total count of logs
     */
    public function getLogsCount($filters = []) {
        $sql = "SELECT COUNT(*) as total FROM audit_logs WHERE 1=1";
        $params = [];
        
        if (!empty($filters['user_id'])) {
            $sql .= " AND user_id = ?";
            $params[] = $filters['user_id'];
        }
        
        if (!empty($filters['action'])) {
            $sql .= " AND action = ?";
            $params[] = $filters['action'];
        }
        
        $stmt = $this->db->connect()->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        
        return $result['total'];
    }
    
    /**
     * Get recent activities
     */
    public function getRecentActivities($limit = 20) {
        $sql = "SELECT al.*, u.email as user_email 
                FROM audit_logs al 
                JOIN users u ON al.user_id = u.id 
                ORDER BY al.created_at DESC 
                LIMIT ?";
        
        $stmt = $this->db->connect()->prepare($sql);
        $stmt->execute([$limit]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Get user activity summary
     */
    public function getUserActivitySummary($userId, $days = 30) {
        $sql = "SELECT 
                    action,
                    COUNT(*) as count,
                    DATE(created_at) as date
                FROM audit_logs 
                WHERE user_id = ? 
                AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY action, DATE(created_at)
                ORDER BY created_at DESC";
        
        $stmt = $this->db->connect()->prepare($sql);
        $stmt->execute([$userId, $days]);
        
        return $stmt->fetchAll();
    }
}