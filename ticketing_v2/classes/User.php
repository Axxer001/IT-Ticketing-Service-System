<?php
require_once "Database.php";
require_once "AuditLog.php";

/**
 * User Authentication and Management Class
 */
class User {
    private $db;
    private $audit;
    
    public function __construct() {
        $this->db = new Database();
        $this->audit = new AuditLog();
    }
    
    /**
     * Register new user
     */
    public function register($email, $password, $userType, $additionalData = []) {
        try {
            $this->db->beginTransaction();
            
            // Check if email exists
            if ($this->emailExists($email)) {
                throw new Exception("Email already exists");
            }
            
            // Hash password
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user
            $sql = "INSERT INTO users (email, password_hash, user_type) VALUES (?, ?, ?)";
            $stmt = $this->db->connect()->prepare($sql);
            $stmt->execute([$email, $passwordHash, $userType]);
            
            $userId = $this->db->lastInsertId();
            
            // Create profile based on user type
            if ($userType === 'employee' && !empty($additionalData)) {
                $this->createEmployeeProfile($userId, $additionalData);
            } elseif ($userType === 'service_provider' && !empty($additionalData)) {
                $this->createServiceProviderProfile($userId, $additionalData);
            }
            
            // Create default preferences
            $prefSql = "INSERT INTO user_preferences (user_id) VALUES (?)";
            $this->db->connect()->prepare($prefSql)->execute([$userId]);
            
            $this->db->commit();
            
            // Log audit
            $this->audit->log($userId, 'user_registered', 'users', $userId, null, 
                json_encode(['email' => $email, 'type' => $userType]));
            
            return $userId;
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Registration error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * User login
     */
    public function login($email, $password) {
        $sql = "SELECT u.*, up.theme 
                FROM users u 
                LEFT JOIN user_preferences up ON u.id = up.user_id 
                WHERE u.email = ? AND u.is_active = 1";
        
        $stmt = $this->db->connect()->prepare($sql);
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password_hash'])) {
            // Update last login
            $updateSql = "UPDATE users SET last_login = NOW() WHERE id = ?";
            $this->db->connect()->prepare($updateSql)->execute([$user['id']]);
            
            // Log audit
            $this->audit->log($user['id'], 'user_login', 'users', $user['id']);
            
            return $user;
        }
        
        return false;
    }
    
    /**
     * Get user profile with details
     */
    public function getUserProfile($userId) {
        $sql = "SELECT u.*, up.theme, up.notifications_enabled 
                FROM users u 
                LEFT JOIN user_preferences up ON u.id = up.user_id 
                WHERE u.id = ?";
        
        $stmt = $this->db->connect()->prepare($sql);
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user) return null;
        
        // Get additional profile data based on user type
        if ($user['user_type'] === 'employee') {
            $empSql = "SELECT e.*, d.name as department_name, d.category as department_category 
                       FROM employees e 
                       JOIN departments d ON e.department_id = d.id 
                       WHERE e.user_id = ?";
            $empStmt = $this->db->connect()->prepare($empSql);
            $empStmt->execute([$userId]);
            $user['profile'] = $empStmt->fetch();
            
        } elseif ($user['user_type'] === 'service_provider') {
            $spSql = "SELECT * FROM service_providers WHERE user_id = ?";
            $spStmt = $this->db->connect()->prepare($spSql);
            $spStmt->execute([$userId]);
            $user['profile'] = $spStmt->fetch();
        }
        
        return $user;
    }
    
    /**
     * Create employee profile
     */
    private function createEmployeeProfile($userId, $data) {
        $sql = "INSERT INTO employees (user_id, first_name, last_name, department_id, contact_number) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->db->connect()->prepare($sql);
        $stmt->execute([
            $userId,
            $data['first_name'],
            $data['last_name'],
            $data['department_id'],
            $data['contact_number'] ?? null
        ]);
    }
    
    /**
     * Create service provider profile
     */
    private function createServiceProviderProfile($userId, $data) {
        $sql = "INSERT INTO service_providers (user_id, provider_name, specialization, contact_number) 
                VALUES (?, ?, ?, ?)";
        $stmt = $this->db->connect()->prepare($sql);
        $stmt->execute([
            $userId,
            $data['provider_name'],
            $data['specialization'] ?? null,
            $data['contact_number'] ?? null
        ]);
    }
    
    /**
     * Check if email exists
     */
    public function emailExists($email) {
        $sql = "SELECT id FROM users WHERE email = ?";
        $stmt = $this->db->connect()->prepare($sql);
        $stmt->execute([$email]);
        return $stmt->fetch() !== false;
    }
    
    /**
     * Update user preferences
     */
    public function updatePreferences($userId, $theme, $notificationsEnabled = true) {
        $sql = "UPDATE user_preferences 
                SET theme = ?, notifications_enabled = ?, updated_at = NOW() 
                WHERE user_id = ?";
        $stmt = $this->db->connect()->prepare($sql);
        return $stmt->execute([$theme, $notificationsEnabled, $userId]);
    }
    
    /**
     * Get all service providers
     */
    public function getAllServiceProviders() {
        $sql = "SELECT sp.*, u.email, u.is_active,
                COUNT(t.id) as current_assignments
                FROM service_providers sp
                JOIN users u ON sp.user_id = u.id
                LEFT JOIN tickets t ON sp.id = t.assigned_provider_id 
                    AND t.status IN ('assigned', 'in_progress')
                WHERE u.is_active = 1
                GROUP BY sp.id
                ORDER BY sp.provider_name";
        
        $stmt = $this->db->connect()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Get available service providers (not at max capacity)
     */
    public function getAvailableServiceProviders() {
        $sql = "SELECT sp.*, u.email,
                COUNT(t.id) as current_assignments
                FROM service_providers sp
                JOIN users u ON sp.user_id = u.id
                LEFT JOIN tickets t ON sp.id = t.assigned_provider_id 
                    AND t.status IN ('assigned', 'in_progress')
                WHERE u.is_active = 1 AND sp.is_available = 1
                GROUP BY sp.id
                HAVING current_assignments < sp.max_concurrent_tickets
                ORDER BY current_assignments ASC, sp.rating_average DESC";
        
        $stmt = $this->db->connect()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Get all departments
     */
    public function getAllDepartments() {
        $sql = "SELECT * FROM departments ORDER BY category, name";
        $stmt = $this->db->connect()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Get departments grouped by category
     */
    public function getDepartmentsByCategory() {
        $departments = $this->getAllDepartments();
        $grouped = [];
        
        foreach ($departments as $dept) {
            $category = $dept['category'];
            if (!isset($grouped[$category])) {
                $grouped[$category] = [];
            }
            $grouped[$category][] = $dept;
        }
        
        return $grouped;
    }
}