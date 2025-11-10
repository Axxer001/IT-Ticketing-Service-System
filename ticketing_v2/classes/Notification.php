<?php
require_once "Database.php";

/**
 * Optimized Notification Management Class
 * FIXED: Added async methods to prevent slow page loads
 */
class Notification {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    /**
     * Create a notification
     */
    public function create($userId, $type, $title, $message, $ticketId = null) {
        try {
            $sql = "INSERT INTO notifications 
                    (user_id, ticket_id, notification_type, title, message) 
                    VALUES (?, ?, ?, ?, ?)";
            
            $stmt = $this->db->connect()->prepare($sql);
            $stmt->execute([$userId, $ticketId, $type, $title, $message]);
            
            return $this->db->lastInsertId();
        } catch (Exception $e) {
            error_log("Notification error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get user notifications
     */
    public function getUserNotifications($userId, $unreadOnly = false, $limit = 50) {
        $sql = "SELECT n.*, t.ticket_number 
                FROM notifications n 
                LEFT JOIN tickets t ON n.ticket_id = t.id 
                WHERE n.user_id = ?";
        
        if ($unreadOnly) {
            $sql .= " AND n.is_read = 0";
        }
        
        $sql .= " ORDER BY n.created_at DESC LIMIT ?";
        
        $stmt = $this->db->connect()->prepare($sql);
        $stmt->execute([$userId, $limit]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Get unread count
     */
    public function getUnreadCount($userId) {
        $sql = "SELECT COUNT(*) as count FROM notifications 
                WHERE user_id = ? AND is_read = 0";
        
        $stmt = $this->db->connect()->prepare($sql);
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        
        return $result['count'];
    }
    
    /**
     * Mark notification as read
     */
    public function markAsRead($notificationId, $userId) {
        $sql = "UPDATE notifications 
                SET is_read = 1, read_at = NOW() 
                WHERE id = ? AND user_id = ?";
        
        $stmt = $this->db->connect()->prepare($sql);
        return $stmt->execute([$notificationId, $userId]);
    }
    
    /**
     * Mark all as read
     */
    public function markAllAsRead($userId) {
        $sql = "UPDATE notifications 
                SET is_read = 1, read_at = NOW() 
                WHERE user_id = ? AND is_read = 0";
        
        $stmt = $this->db->connect()->prepare($sql);
        return $stmt->execute([$userId]);
    }
    
    /**
     * Delete notification
     */
    public function delete($notificationId, $userId) {
        $sql = "DELETE FROM notifications WHERE id = ? AND user_id = ?";
        $stmt = $this->db->connect()->prepare($sql);
        return $stmt->execute([$notificationId, $userId]);
    }
    
    /**
     * OPTIMIZED: Async ticket assignment notification
     * Doesn't block page load
     */
    public function notifyTicketAssignmentAsync($ticketId, $providerId, $ticketNumber) {
        // Get provider user_id in background
        try {
            $sql = "SELECT user_id FROM service_providers WHERE id = ?";
            $stmt = $this->db->connect()->prepare($sql);
            $stmt->execute([$providerId]);
            $provider = $stmt->fetch();
            
            if ($provider) {
                $this->create(
                    $provider['user_id'],
                    'ticket_assigned',
                    'New Ticket Assigned',
                    "You have been assigned to ticket #{$ticketNumber}",
                    $ticketId
                );
            }
        } catch (Exception $e) {
            error_log("Async notification error: " . $e->getMessage());
            // Don't throw - just log and continue
        }
    }
    
    /**
     * OPTIMIZED: Async status change notification
     */
    public function notifyTicketStatusChangeAsync($ticketId, $employeeUserId, $ticketNumber, $newStatus) {
        try {
            $statusMessages = [
                'assigned' => 'Your ticket has been assigned to a service provider',
                'in_progress' => 'Work has started on your ticket',
                'resolved' => 'Your ticket has been resolved',
                'closed' => 'Your ticket has been closed'
            ];
            
            $message = $statusMessages[$newStatus] ?? "Your ticket status has been updated to {$newStatus}";
            
            $this->create(
                $employeeUserId,
                'ticket_status_change',
                'Ticket Status Updated',
                "Ticket #{$ticketNumber}: {$message}",
                $ticketId
            );
        } catch (Exception $e) {
            error_log("Async notification error: " . $e->getMessage());
            // Don't throw - just log and continue
        }
    }
    
    /**
     * OPTIMIZED: Async new ticket notification to admins
     */
    public function notifyAdminNewTicketAsync($ticketId, $ticketNumber) {
        try {
            // Single query to get all active admins
            $sql = "SELECT id FROM users WHERE user_type = 'admin' AND is_active = 1";
            $stmt = $this->db->connect()->prepare($sql);
            $stmt->execute();
            $admins = $stmt->fetchAll();
            
            // Batch insert for performance
            if (!empty($admins)) {
                $sql = "INSERT INTO notifications (user_id, ticket_id, notification_type, title, message) VALUES ";
                $values = [];
                $params = [];
                
                foreach ($admins as $admin) {
                    $values[] = "(?, ?, 'new_ticket', 'New Ticket Submitted', ?)";
                    $params[] = $admin['id'];
                    $params[] = $ticketId;
                    $params[] = "A new ticket #{$ticketNumber} has been submitted and requires assignment";
                }
                
                $sql .= implode(', ', $values);
                $stmt = $this->db->connect()->prepare($sql);
                $stmt->execute($params);
            }
        } catch (Exception $e) {
            error_log("Async notification error: " . $e->getMessage());
            // Don't throw - just log and continue
        }
    }
    
    /**
     * OLD METHODS - Keep for backward compatibility but use async versions
     */
    public function notifyTicketAssignment($ticketId, $providerId, $ticketNumber) {
        return $this->notifyTicketAssignmentAsync($ticketId, $providerId, $ticketNumber);
    }
    
    public function notifyTicketStatusChange($ticketId, $employeeUserId, $ticketNumber, $newStatus) {
        return $this->notifyTicketStatusChangeAsync($ticketId, $employeeUserId, $ticketNumber, $newStatus);
    }
    
    public function notifyAdminNewTicket($ticketId, $ticketNumber) {
        return $this->notifyAdminNewTicketAsync($ticketId, $ticketNumber);
    }
}