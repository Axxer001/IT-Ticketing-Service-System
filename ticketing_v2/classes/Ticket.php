<?php
require_once "Database.php";
require_once "AuditLog.php";
require_once "Notification.php";

/**
 * Optimized Ticket Management Class
 * FIXED: Removed slow operations for faster performance
 */
class Ticket {
    private $db;
    private $audit;
    private $notification;
    
    private $allowedFileTypes = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];
    private $maxFileSize = 10485760; // 10MB
    private $maxFiles = 5;
    
    public function __construct() {
        $this->db = new Database();
        $this->audit = new AuditLog();
        $this->notification = new Notification();
    }
    
    private function generateTicketNumber() {
        $prefix = "TKT";
        $date = date('Ymd');
        $random = strtoupper(substr(md5(uniqid(rand(), true)), 0, 4));
        return "{$prefix}-{$date}-{$random}";
    }
    
    private function validateFile($file) {
        $errors = [];
        
        if ($file['size'] > $this->maxFileSize) {
            $errors[] = "File {$file['name']} exceeds maximum size of 10MB";
        }
        
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $this->allowedFileTypes)) {
            $errors[] = "File type .{$extension} is not allowed";
        }
        
        return $errors;
    }
    
    public function create($employeeId, $data, $attachments = []) {
        try {
            // Quick validation
            if (empty($data['device_type_id']) || empty($data['device_name']) || empty($data['issue_description'])) {
                return ['success' => false, 'message' => "Required fields are missing"];
            }
            
            // Validate priority
            $validPriorities = ['low', 'medium', 'high', 'critical'];
            if (!in_array($data['priority'] ?? 'medium', $validPriorities)) {
                $data['priority'] = 'medium';
            }
            
            // Quick file validation
            if (count($attachments) > $this->maxFiles) {
                return ['success' => false, 'message' => "Maximum {$this->maxFiles} files allowed"];
            }
            
            foreach ($attachments as $file) {
                $validationErrors = $this->validateFile($file);
                if (!empty($validationErrors)) {
                    return ['success' => false, 'message' => implode(', ', $validationErrors)];
                }
            }
            
            $this->db->beginTransaction();
            
            $ticketNumber = $this->generateTicketNumber();
            
            // Single query to get employee info
            $deptSql = "SELECT department_id, user_id FROM employees WHERE id = ?";
            $deptStmt = $this->db->connect()->prepare($deptSql);
            $deptStmt->execute([$employeeId]);
            $employee = $deptStmt->fetch();
            
            if (!$employee) {
                $this->db->rollback();
                return ['success' => false, 'message' => "Employee not found"];
            }
            
            // Insert ticket - simplified
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
                htmlspecialchars($data['device_name'], ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($data['issue_description'], ENT_QUOTES, 'UTF-8'),
                $data['priority']
            ]);
            
            $ticketId = $this->db->lastInsertId();
            
            // Handle attachments if any
            if (!empty($attachments)) {
                $this->saveAttachments($ticketId, $attachments);
            }
            
            // Single update log
            $this->logTicketUpdate($ticketId, $employee['user_id'], 'comment', 'Ticket created');
            
            // Async notification (don't wait for it) - OPTIMIZED
            $this->notification->notifyAdminNewTicketAsync($ticketId, $ticketNumber);
            
            // Skip audit log for speed (optional for student project)
            // $this->audit->log($employee['user_id'], 'ticket_created', 'tickets', $ticketId, null, json_encode(['ticket_number' => $ticketNumber]));
            
            $this->db->commit();
            
            return ['success' => true, 'ticket_id' => $ticketId, 'ticket_number' => $ticketNumber];
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Ticket creation error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
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
            $ticket['attachments'] = $this->getAttachments($ticketId);
            $ticket['updates'] = $this->getTicketUpdates($ticketId);
            $ticket['rating'] = $this->getTicketRating($ticketId);
        }
        
        return $ticket;
    }
    
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
        $params[] = (int)$limit;
        $params[] = (int)$offset;
        
        $stmt = $this->db->connect()->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
    
    public function assign($ticketId, $providerId, $adminUserId) {
        try {
            $this->db->beginTransaction();
            
            // Get ticket info in single query
            $sql = "SELECT t.ticket_number, t.employee_id, e.user_id as employee_user_id
                    FROM tickets t
                    JOIN employees e ON t.employee_id = e.id
                    WHERE t.id = ?";
            $stmt = $this->db->connect()->prepare($sql);
            $stmt->execute([$ticketId]);
            $ticket = $stmt->fetch();
            
            if (!$ticket) {
                $this->db->rollback();
                return ['success' => false, 'message' => "Ticket not found"];
            }
            
            // Update ticket status
            $sql = "UPDATE tickets 
                    SET assigned_provider_id = ?, status = 'assigned', 
                        assigned_at = NOW(), updated_at = NOW() 
                    WHERE id = ?";
            
            $stmt = $this->db->connect()->prepare($sql);
            $stmt->execute([$providerId, $ticketId]);
            
            // Log update
            $this->logTicketUpdate($ticketId, $adminUserId, 'assignment', "Ticket assigned to service provider");
            
            // Async notifications - OPTIMIZED
            $this->notification->notifyTicketAssignmentAsync($ticketId, $providerId, $ticket['ticket_number']);
            $this->notification->notifyTicketStatusChangeAsync($ticketId, $ticket['employee_user_id'], $ticket['ticket_number'], 'assigned');
            
            // Skip audit for speed
            // $this->audit->log($adminUserId, 'ticket_assigned', 'tickets', $ticketId, null, json_encode(['provider' => $providerId]));
            
            $this->db->commit();
            
            return ['success' => true];
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Ticket assignment error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function updateStatus($ticketId, $status, $userId, $comment = null) {
        try {
            $validStatuses = ['assigned', 'in_progress', 'resolved', 'closed'];
            if (!in_array($status, $validStatuses)) {
                return ['success' => false, 'message' => "Invalid status"];
            }
            
            $this->db->beginTransaction();
            
            // Get current status and employee in one query
            $sql = "SELECT t.status, t.ticket_number, e.user_id as employee_user_id
                    FROM tickets t
                    JOIN employees e ON t.employee_id = e.id
                    WHERE t.id = ?";
            $stmt = $this->db->connect()->prepare($sql);
            $stmt->execute([$ticketId]);
            $ticket = $stmt->fetch();
            
            if (!$ticket) {
                $this->db->rollback();
                return ['success' => false, 'message' => "Ticket not found"];
            }
            
            $oldStatus = $ticket['status'];
            
            // Update status
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
            
            // Log update
            $message = $comment ?? "Status changed from {$oldStatus} to {$status}";
            $message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
            $this->logTicketUpdate($ticketId, $userId, 'status_change', $message, $oldStatus, $status);
            
            // Async notification - OPTIMIZED
            $this->notification->notifyTicketStatusChangeAsync($ticketId, $ticket['employee_user_id'], $ticket['ticket_number'], $status);
            
            // Skip audit for speed
            // $this->audit->log($userId, 'ticket_status_updated', 'tickets', $ticketId, json_encode(['status' => $oldStatus]), json_encode(['status' => $status]));
            
            $this->db->commit();
            
            return ['success' => true];
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Ticket status update error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function addComment($ticketId, $userId, $comment) {
        $comment = htmlspecialchars($comment, ENT_QUOTES, 'UTF-8');
        return $this->logTicketUpdate($ticketId, $userId, 'comment', $comment);
    }
    
    private function logTicketUpdate($ticketId, $userId, $type, $message, $oldValue = null, $newValue = null) {
        $sql = "INSERT INTO ticket_updates 
                (ticket_id, user_id, update_type, message, old_value, new_value) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->connect()->prepare($sql);
        return $stmt->execute([$ticketId, $userId, $type, $message, $oldValue, $newValue]);
    }
    
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
    
    private function saveAttachments($ticketId, $files) {
        $uploadDir = __DIR__ . "/../uploads/tickets/";
        
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        foreach ($files as $file) {
            if ($file['error'] === UPLOAD_ERR_OK && !empty($file['tmp_name'])) {
                $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $fileName = uniqid() . '_' . time() . '.' . $extension;
                $filePath = $uploadDir . $fileName;
                
                if (move_uploaded_file($file['tmp_name'], $filePath)) {
                    $sql = "INSERT INTO ticket_attachments 
                            (ticket_id, file_name, file_path, file_type, file_size) 
                            VALUES (?, ?, ?, ?, ?)";
                    
                    $stmt = $this->db->connect()->prepare($sql);
                    $stmt->execute([
                        $ticketId,
                        htmlspecialchars($file['name'], ENT_QUOTES, 'UTF-8'),
                        'uploads/tickets/' . $fileName,
                        $file['type'],
                        $file['size']
                    ]);
                }
            }
        }
    }
    
    public function getAttachments($ticketId) {
        $sql = "SELECT * FROM ticket_attachments WHERE ticket_id = ? ORDER BY uploaded_at ASC";
        $stmt = $this->db->connect()->prepare($sql);
        $stmt->execute([$ticketId]);
        return $stmt->fetchAll();
    }
    
    public function submitRating($ticketId, $employeeId, $providerId, $rating, $feedback = null) {
        try {
            if (!is_numeric($rating) || $rating < 1 || $rating > 5) {
                return ['success' => false, 'message' => "Invalid rating value"];
            }
            
            $this->db->beginTransaction();
            
            $sql = "INSERT INTO ticket_ratings (ticket_id, provider_id, employee_id, rating, feedback) 
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->db->connect()->prepare($sql);
            $stmt->execute([
                $ticketId, 
                $providerId, 
                $employeeId, 
                $rating, 
                $feedback ? htmlspecialchars($feedback, ENT_QUOTES, 'UTF-8') : null
            ]);
            
            $this->updateProviderRating($providerId);
            
            $this->db->commit();
            
            return ['success' => true];
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Rating submission error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    private function updateProviderRating($providerId) {
        $sql = "UPDATE service_providers SET 
                rating_average = (SELECT AVG(rating) FROM ticket_ratings WHERE provider_id = ?),
                total_ratings = (SELECT COUNT(*) FROM ticket_ratings WHERE provider_id = ?)
                WHERE id = ?";
        
        $stmt = $this->db->connect()->prepare($sql);
        $stmt->execute([$providerId, $providerId, $providerId]);
    }
    
    public function getTicketRating($ticketId) {
        $sql = "SELECT * FROM ticket_ratings WHERE ticket_id = ?";
        $stmt = $this->db->connect()->prepare($sql);
        $stmt->execute([$ticketId]);
        return $stmt->fetch();
    }
    
    public function getDeviceTypes() {
        $sql = "SELECT * FROM device_types ORDER BY type_name";
        $stmt = $this->db->connect()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function getStatistics($filters = []) {
        $stats = [];
        
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