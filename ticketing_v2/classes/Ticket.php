<?php
require_once "Database.php";
require_once "AuditLog.php";
require_once "Notification.php";

/**
 * Enhanced Ticket Management Class
 */
class Ticket {
    private $db;
    private $audit;
    private $notification;
    
    public function __construct() {
        $this->db = new Database();
        $this->audit = new AuditLog();
        $this->notification = new Notification();
    }
    
    /**
     * Generate unique ticket number
     */
    private function generateTicketNumber() {
        $prefix = "TKT";
        $date = date('Ymd');
        $random = strtoupper(substr(md5(uniqid(rand(), true)), 0, 4));
        return "{$prefix}-{$date}-{$random}";
    }
    
    /**
     * Create new ticket
     */
    public function create($employeeId, $data, $attachments = []) {
        try {
            $this->db->beginTransaction();
            
            $ticketNumber = $this->generateTicketNumber();
            
            // Get employee's department
            $deptSql = "SELECT department_id FROM employees WHERE id = ?";
            $deptStmt = $this->db->connect()->prepare($deptSql);
            $deptStmt->execute([$employeeId]);
            $employee = $deptStmt->fetch();
            
            // Insert ticket
            $sql = "INSERT INTO tickets 
                    (ticket_number, employee_id, department_id, device_type_id, 
                     device_name, issue_description, priority) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->connect()->prepare($sql);
            $stmt->execute([
                $ticketNumber,
                $employeeId,
                $employee['department_id'],
                $data['device_type_id'],
                $data['device_name'],
                $data['issue_description'],
                $data['priority'] ?? 'medium'
            ]);
            
            $ticketId = $this->db->lastInsertId();
            
            // Handle attachments
            if (!empty($attachments)) {
                $this->saveAttachments($ticketId, $attachments);
            }
            
            // Log initial creation
            $this->logTicketUpdate($ticketId, $employeeId, 'comment', 'Ticket created');
            
            // Notify admins
            $this->notification->notifyAdminNewTicket($ticketId, $ticketNumber);
            
            // Audit log
            $this->audit->log($employeeId, 'ticket_created', 'tickets', $ticketId, 
                null, json_encode($data));
            
            $this->db->commit();
            
            return ['success' => true, 'ticket_id' => $ticketId, 'ticket_number' => $ticketNumber];
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Ticket creation error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Get ticket by ID with all details
     */
    public function getById($ticketId) {
        $sql = "SELECT t.*, 
                e.first_name, e.last_name, e.contact_number, e.user_id as employee_user_id,
                u.email as employee_email,
                d.name as department_name, d.category as department_category,
                dt.type_name as device_type_name,
                sp.provider_name, sp.user_id as provider_user_id,
                spu.email as provider_email
                FROM tickets t
                JOIN employees e ON t.employee_id = e.id
                JOIN users u ON e.user_id = u.id
                JOIN departments d ON t.department_id = d.id
                JOIN device_types dt ON t.device_type_id = dt.id
                LEFT JOIN service_providers sp ON t.assigned_provider_id = sp.id
                LEFT JOIN users spu ON sp.user_id = spu.id
                WHERE t.id = ?";
        
        $stmt = $this->db->connect()->prepare($sql);
        $stmt->execute([$ticketId]);
        $ticket = $stmt->fetch();
        
        if ($ticket) {
            // Get attachments
            $ticket['attachments'] = $this->getAttachments($ticketId);
            
            // Get updates/comments
            $ticket['updates'] = $this->getTicketUpdates($ticketId);
            
            // Get rating if exists
            $ticket['rating'] = $this->getTicketRating($ticketId);
        }
        
        return $ticket;
    }
    
    /**
     * Get tickets with filters
     */
    public function getTickets($filters = [], $limit = 50, $offset = 0) {
        $sql = "SELECT t.*, 
                e.first_name, e.last_name,
                d.name as department_name,
                dt.type_name as device_type_name,
                sp.provider_name
                FROM tickets t
                JOIN employees e ON t.employee_id = e.id
                JOIN departments d ON t.department_id = d.id
                JOIN device_types dt ON t.device_type_id = dt.id
                LEFT JOIN service_providers sp ON t.assigned_provider_id = sp.id
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($filters['employee_id'])) {
            $sql .= " AND t.employee_id = ?";
            $params[] = $filters['employee_id'];
        }
        
        if (!empty($filters['provider_id'])) {
            $sql .= " AND t.assigned_provider_id = ?";
            $params[] = $filters['provider_id'];
        }
        
        if (!empty($filters['status'])) {
            $sql .= " AND t.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['priority'])) {
            $sql .= " AND t.priority = ?";
            $params[] = $filters['priority'];
        }
        
        if (!empty($filters['search'])) {
            $sql .= " AND (t.ticket_number LIKE ? OR t.issue_description LIKE ? OR e.first_name LIKE ? OR e.last_name LIKE ?)";
            $searchTerm = "%{$filters['search']}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        $sql .= " ORDER BY t.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $this->db->connect()->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Assign ticket to service provider (Admin only)
     */
    public function assign($ticketId, $providerId, $adminUserId) {
        try {
            $this->db->beginTransaction();
            
            // Get current ticket info
            $ticket = $this->getById($ticketId);
            
            if (!$ticket) {
                throw new Exception("Ticket not found");
            }
            
            // Update ticket
            $sql = "UPDATE tickets 
                    SET assigned_provider_id = ?, status = 'assigned', 
                        assigned_at = NOW(), updated_at = NOW() 
                    WHERE id = ?";
            
            $stmt = $this->db->connect()->prepare($sql);
            $stmt->execute([$providerId, $ticketId]);
            
            // Log the assignment
            $this->logTicketUpdate($ticketId, $adminUserId, 'assignment', 
                "Ticket assigned to service provider");
            
            // Notify provider
            $this->notification->notifyTicketAssignment($ticketId, $providerId, $ticket['ticket_number']);
            
            // Notify employee
            $this->notification->notifyTicketStatusChange($ticketId, $ticket['employee_user_id'], 
                $ticket['ticket_number'], 'assigned');
            
            // Audit log
            $this->audit->log($adminUserId, 'ticket_assigned', 'tickets', $ticketId, 
                json_encode(['old_provider' => $ticket['assigned_provider_id']]),
                json_encode(['new_provider' => $providerId]));
            
            $this->db->commit();
            
            return ['success' => true];
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Ticket assignment error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Update ticket status (Service Provider)
     */
    public function updateStatus($ticketId, $status, $userId, $comment = null) {
        try {
            $this->db->beginTransaction();
            
            $ticket = $this->getById($ticketId);
            $oldStatus = $ticket['status'];
            
            $sql = "UPDATE tickets SET status = ?, updated_at = NOW()";
            $params = [$status];
            
            if ($status === 'resolved') {
                $sql .= ", resolved_at = NOW()";
            } elseif ($status === 'closed') {
                $sql .= ", closed_at = NOW()";
            }
            
            $sql .= " WHERE id = ?";
            $params[] = $ticketId;
            
            $stmt = $this->db->connect()->prepare($sql);
            $stmt->execute($params);
            
            // Log the status change
            $message = $comment ?? "Status changed from {$oldStatus} to {$status}";
            $this->logTicketUpdate($ticketId, $userId, 'status_change', $message, $oldStatus, $status);
            
            // Notify employee
            $this->notification->notifyTicketStatusChange($ticketId, $ticket['employee_user_id'], 
                $ticket['ticket_number'], $status);
            
            // Audit log
            $this->audit->log($userId, 'ticket_status_updated', 'tickets', $ticketId, 
                json_encode(['status' => $oldStatus]),
                json_encode(['status' => $status]));
            
            $this->db->commit();
            
            return ['success' => true];
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Ticket status update error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Add comment to ticket
     */
    public function addComment($ticketId, $userId, $comment) {
        return $this->logTicketUpdate($ticketId, $userId, 'comment', $comment);
    }
    
    /**
     * Log ticket update/activity
     */
    private function logTicketUpdate($ticketId, $userId, $type, $message, $oldValue = null, $newValue = null) {
        $sql = "INSERT INTO ticket_updates 
                (ticket_id, user_id, update_type, message, old_value, new_value) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->connect()->prepare($sql);
        return $stmt->execute([$ticketId, $userId, $type, $message, $oldValue, $newValue]);
    }
    
    /**
     * Get ticket updates
     */
    public function getTicketUpdates($ticketId) {
        $sql = "SELECT tu.*, u.email, u.user_type 
                FROM ticket_updates tu 
                JOIN users u ON tu.user_id = u.id 
                WHERE tu.ticket_id = ? 
                ORDER BY tu.created_at ASC";
        
        $stmt = $this->db->connect()->prepare($sql);
        $stmt->execute([$ticketId]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Save ticket attachments
     */
    private function saveAttachments($ticketId, $files) {
        $uploadDir = "../uploads/tickets/";
        
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        foreach ($files as $file) {
            if ($file['error'] === UPLOAD_ERR_OK) {
                $fileName = uniqid() . '_' . basename($file['name']);
                $filePath = $uploadDir . $fileName;
                
                if (move_uploaded_file($file['tmp_name'], $filePath)) {
                    $sql = "INSERT INTO ticket_attachments 
                            (ticket_id, file_name, file_path, file_type, file_size) 
                            VALUES (?, ?, ?, ?, ?)";
                    
                    $stmt = $this->db->connect()->prepare($sql);
                    $stmt->execute([
                        $ticketId,
                        $file['name'],
                        $filePath,
                        $file['type'],
                        $file['size']
                    ]);
                }
            }
        }
    }
    
    /**
     * Get ticket attachments
     */
    public function getAttachments($ticketId) {
        $sql = "SELECT * FROM ticket_attachments WHERE ticket_id = ? ORDER BY uploaded_at ASC";
        $stmt = $this->db->connect()->prepare($sql);
        $stmt->execute([$ticketId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Submit rating for resolved ticket
     */
    public function submitRating($ticketId, $employeeId, $providerId, $rating, $feedback = null) {
        try {
            $this->db->beginTransaction();
            
            // Insert rating
            $sql = "INSERT INTO ticket_ratings (ticket_id, provider_id, employee_id, rating, feedback) 
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->db->connect()->prepare($sql);
            $stmt->execute([$ticketId, $providerId, $employeeId, $rating, $feedback]);
            
            // Update provider's average rating
            $this->updateProviderRating($providerId);
            
            $this->db->commit();
            
            return ['success' => true];
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Rating submission error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Update service provider average rating
     */
    private function updateProviderRating($providerId) {
        $sql = "UPDATE service_providers SET 
                rating_average = (SELECT AVG(rating) FROM ticket_ratings WHERE provider_id = ?),
                total_ratings = (SELECT COUNT(*) FROM ticket_ratings WHERE provider_id = ?)
                WHERE id = ?";
        
        $stmt = $this->db->connect()->prepare($sql);
        $stmt->execute([$providerId, $providerId, $providerId]);
    }
    
    /**
     * Get ticket rating
     */
    public function getTicketRating($ticketId) {
        $sql = "SELECT * FROM ticket_ratings WHERE ticket_id = ?";
        $stmt = $this->db->connect()->prepare($sql);
        $stmt->execute([$ticketId]);
        return $stmt->fetch();
    }
    
    /**
     * Get all device types
     */
    public function getDeviceTypes() {
        $sql = "SELECT * FROM device_types ORDER BY type_name";
        $stmt = $this->db->connect()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Get ticket statistics
     */
    public function getStatistics($filters = []) {
        $stats = [];
        
        // Total tickets
        $sql = "SELECT COUNT(*) as total FROM tickets WHERE 1=1";
        $params = [];
        
        if (!empty($filters['employee_id'])) {
            $sql .= " AND employee_id = ?";
            $params[] = $filters['employee_id'];
        }
        
        if (!empty($filters['provider_id'])) {
            $sql .= " AND assigned_provider_id = ?";
            $params[] = $filters['provider_id'];
        }
        
        $stmt = $this->db->connect()->prepare($sql);
        $stmt->execute($params);
        $stats['total'] = $stmt->fetch()['total'];
        
        // By status
        $sql = "SELECT status, COUNT(*) as count FROM tickets WHERE 1=1";
        if (!empty($params)) {
            if (!empty($filters['employee_id'])) {
                $sql .= " AND employee_id = ?";
            }
            if (!empty($filters['provider_id'])) {
                $sql .= " AND assigned_provider_id = ?";
            }
        }
        $sql .= " GROUP BY status";
        
        $stmt = $this->db->connect()->prepare($sql);
        $stmt->execute($params);
        $stats['by_status'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        // By priority
        $sql = "SELECT priority, COUNT(*) as count FROM tickets WHERE 1=1";
        if (!empty($params)) {
            if (!empty($filters['employee_id'])) {
                $sql .= " AND employee_id = ?";
            }
            if (!empty($filters['provider_id'])) {
                $sql .= " AND assigned_provider_id = ?";
            }
        }
        $sql .= " GROUP BY priority";
        
        $stmt = $this->db->connect()->prepare($sql);
        $stmt->execute($params);
        $stats['by_priority'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        return $stats;
    }
}