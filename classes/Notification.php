<?php
require_once __DIR__ . "/Database.php";

/**
 * Notification Management Class
 * FIXED: Matches database schema (notification_type, title, message columns)
 */
class Notification {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    /**
     * Create a new notification - FIXED to match database columns
     */
    public function create($userId, $title, $message, $ticketId = null, $type = 'info') {
        try {
            // Use correct column names: notification_type instead of type
            $sql = "INSERT INTO notifications (user_id, ticket_id, notification_type, title, message, is_read, created_at) 
                    VALUES (?, ?, ?, ?, ?, 0, NOW())";
            
            $stmt = $this->db->connect()->prepare($sql);
            $stmt->execute([$userId, $ticketId, $type, $title, $message]);
            
            return $this->db->lastInsertId();
        } catch (Exception $e) {
            error_log("Notification creation error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get user notifications
     */
    public function getUserNotifications($userId, $unreadOnly = false, $limit = 50) {
        try {
            $sql = "SELECT * FROM notifications WHERE user_id = ?";
            
            if ($unreadOnly) {
                $sql .= " AND is_read = 0";
            }
            
            $sql .= " ORDER BY created_at DESC LIMIT ?";
            
            $stmt = $this->db->connect()->prepare($sql);
            $stmt->bindValue(1, $userId, PDO::PARAM_INT);
            $stmt->bindValue(2, $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Get notifications error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get unread notification count
     */
    public function getUnreadCount($userId) {
        try {
            $sql = "SELECT COUNT(*) as count FROM notifications 
                    WHERE user_id = ? AND is_read = 0";
            
            $stmt = $this->db->connect()->prepare($sql);
            $stmt->execute([$userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result['count'] ?? 0;
        } catch (Exception $e) {
            error_log("Get unread count error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Mark notification as read
     */
    public function markAsRead($notificationId, $userId) {
        try {
            $sql = "UPDATE notifications SET is_read = 1, read_at = NOW() 
                    WHERE id = ? AND user_id = ?";
            
            $stmt = $this->db->connect()->prepare($sql);
            $stmt->execute([$notificationId, $userId]);
            
            return true;
        } catch (Exception $e) {
            error_log("Mark as read error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Mark all notifications as read
     */
    public function markAllAsRead($userId) {
        try {
            $sql = "UPDATE notifications SET is_read = 1, read_at = NOW() WHERE user_id = ? AND is_read = 0";
            $stmt = $this->db->connect()->prepare($sql);
            $stmt->execute([$userId]);
            
            return true;
        } catch (Exception $e) {
            error_log("Mark all as read error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete old notifications (cleanup)
     */
    public function deleteOldNotifications($days = 30) {
        try {
            $sql = "DELETE FROM notifications 
                    WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
            
            $stmt = $this->db->connect()->prepare($sql);
            $stmt->execute([$days]);
            
            return true;
        } catch (Exception $e) {
            error_log("Delete old notifications error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Notify admin about new ticket (async)
     */
    public function notifyAdminNewTicketAsync($ticketId, $ticketNumber) {
        try {
            // Get all admin users
            $sql = "SELECT id FROM users WHERE user_type = 'admin' AND is_active = 1";
            $stmt = $this->db->connect()->prepare($sql);
            $stmt->execute();
            $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($admins as $admin) {
                $this->create(
                    $admin['id'],
                    'New Ticket Submitted',
                    "A new ticket #{$ticketNumber} has been submitted and requires assignment",
                    $ticketId,
                    'new_ticket'
                );
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Notify admin error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Notify about ticket assignment
     */
    public function notifyTicketAssignmentAsync($ticketId, $providerId, $ticketNumber) {
        try {
            // Get provider user ID
            $sql = "SELECT user_id FROM service_providers WHERE id = ?";
            $stmt = $this->db->connect()->prepare($sql);
            $stmt->execute([$providerId]);
            $provider = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($provider) {
                $this->create(
                    $provider['user_id'],
                    'New Ticket Assigned',
                    "You have been assigned to ticket #{$ticketNumber}",
                    $ticketId,
                    'ticket_assigned'
                );
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Notify assignment error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Notify about ticket status change
     */
    public function notifyTicketStatusChangeAsync($ticketId, $employeeUserId, $ticketNumber, $newStatus) {
        try {
            $statusMessages = [
                'assigned' => 'Your ticket has been assigned to a service provider',
                'in_progress' => 'Work has started on your ticket',
                'resolved' => 'Your ticket has been resolved',
                'closed' => 'Your ticket has been closed'
            ];
            
            $message = $statusMessages[$newStatus] ?? "Ticket status updated to {$newStatus}";
            
            $this->create(
                $employeeUserId,
                'Ticket Status Updated',
                "Ticket #{$ticketNumber}: {$message}",
                $ticketId,
                'ticket_status_change'
            );
            
            return true;
        } catch (Exception $e) {
            error_log("Notify status change error: " . $e->getMessage());
            return false;
        }
    }
}