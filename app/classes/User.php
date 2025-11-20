<?php
/**
 * User Class - Multi-User Support
 * Blog Advanced v2.0
 * 
 * Handles user management, authentication, roles and permissions
 */

class User {
    private $db;
    private $id;
    private $username;
    private $email;
    private $role;
    private $displayName;
    private $avatar;
    private $status;
    
    // Role constants
    const ROLE_SUPER_ADMIN = 'super_admin';
    const ROLE_ADMIN = 'admin';
    const ROLE_EDITOR = 'editor';
    const ROLE_VIEWER = 'viewer';
    
    // Status constants
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_BANNED = 'banned';
    
    /**
     * Constructor
     */
    public function __construct($db = null) {
        $this->db = $db;
    }
    
    /**
     * Authenticate user
     */
    public function login($username, $password) {
        if (!$this->db) {
            return ['success' => false, 'message' => 'Database not connected'];
        }
        
        try {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE username = ? AND status = ? LIMIT 1");
            $stmt->execute([$username, self::STATUS_ACTIVE]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
                $this->logLoginAttempt($username, false);
                return ['success' => false, 'message' => 'Invalid username or password'];
            }
            
            // Check if account is locked due to too many failed attempts
            if ($this->isAccountLocked($username)) {
                return ['success' => false, 'message' => 'Account temporarily locked. Try again later.'];
            }
            
            // Verify password
            if (!password_verify($password, $user['password_hash'])) {
            
            // Success
            $this->logLoginAttempt($username, true);
            $this->updateLastLogin($user['id']);
            
            // Set session
            $_SESSION['display_name'] = $user['display_name'];
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error'];
        }
     * Logout user
     */
    public function logout() {
        session_destroy();
    /**
     * Check if user is logged in
     */
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    /**
     * Get current user ID
     */
    public static function getCurrentUserId() {
        return $_SESSION['user_id'] ?? null;
    }
    
    /**
     * Get current user role
     */
    public static function getCurrentUserRole() {
        return $_SESSION['role'] ?? null;
    }
    
    /**
     * Check if user has permission
     */
    public static function hasPermission($requiredRole) {
        $currentRole = self::getCurrentUserRole();
        
        $hierarchy = [
            self::ROLE_SUPER_ADMIN => 4,
            self::ROLE_ADMIN => 3,
            self::ROLE_EDITOR => 2,
            self::ROLE_VIEWER => 1
        ];
        
        $currentLevel = $hierarchy[$currentRole] ?? 0;
        $requiredLevel = $hierarchy[$requiredRole] ?? 0;
        
        return $currentLevel >= $requiredLevel;
    }
    
    /**
     * Create new user
     */
    public function create($data) {
        if (!$this->db) {
            return ['success' => false, 'message' => 'Database not connected'];
        }
        
        // Validate required fields
        if (empty($data['username']) || empty($data['email']) || empty($data['password'])) {
            return ['success' => false, 'message' => 'Missing required fields'];
        }
        
        // Check if username exists
        if ($this->usernameExists($data['username'])) {
            return ['success' => false, 'message' => 'Username already exists'];
        }
        
        // Check if email exists
        if ($this->emailExists($data['email'])) {
            return ['success' => false, 'message' => 'Email already exists'];
        }
        
        try {
            $passwordHash = password_hash($data['password'], PASSWORD_BCRYPT);
            
            $stmt = $this->db->prepare("
        return ['success' => true];
    }
    
                INSERT INTO users (username, email, password_hash, role, display_name, status)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['username'],
                $data['email'],
                $passwordHash,
                $data['role'] ?? self::ROLE_VIEWER,
                $data['display_name'] ?? $data['username'],
                $data['status'] ?? self::STATUS_ACTIVE
            ]);
            
    }
    
    /**
            $userId = $this->db->lastInsertId();
            
            // Log action
            $this->logAudit('user_created', 'user', $userId);
            
            return ['success' => true, 'user_id' => $userId];
            
        } catch (PDOException $e) {
            error_log("User creation error: " . $e->getMessage());
            
            return ['success' => true, 'user' => $user];
            
            return ['success' => false, 'message' => 'Database error'];
        }
    }
    
    /**
     * Update user
     */
    public function update($userId, $data) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
        if (!$this->db) {
            return ['success' => false, 'message' => 'Database not connected'];
        }
        
        $fields = [];
        $values = [];
        
        if (isset($data['email'])) {
            $fields[] = 'email = ?';
            $values[] = $data['email'];
        }
        
        if (isset($data['role'])) {
            $fields[] = 'role = ?';
            $values[] = $data['role'];
        }
        
        if (isset($data['display_name'])) {
            $fields[] = 'display_name = ?';
            $values[] = $data['display_name'];
        }
        
        if (isset($data['status'])) {
            $fields[] = 'status = ?';
            $values[] = $data['status'];
        }
        
        if (isset($data['password']) && !empty($data['password'])) {
            $fields[] = 'password_hash = ?';
            $values[] = password_hash($data['password'], PASSWORD_BCRYPT);
        }
        
        if (empty($fields)) {
            return ['success' => false, 'message' => 'No fields to update'];
        }
        
        $values[] = $userId;
        
        try {
            $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($values);
            
            // Log action
            $this->logAudit('user_updated', 'user', $userId);
            
            return ['success' => true];
            
        } catch (PDOException $e) {
            error_log("User update error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error'];
        }
    }
    
    /**
     * Delete user
     */
    public function delete($userId) {
        if (!$this->db) {
            return ['success' => false, 'message' => 'Database not connected'];
        }
        
        // Don't allow deleting super admin
        $user = $this->getById($userId);
        if ($user['role'] === self::ROLE_SUPER_ADMIN) {
            return ['success' => false, 'message' => 'Cannot delete super admin'];
        }
        
        try {
            $stmt = $this->db->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            
            // Log action
            $this->logAudit('user_deleted', 'user', $userId);
            
            return ['success' => true];
            
        } catch (PDOException $e) {
            error_log("User deletion error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error'];
        }
    }
    
    /**
     * Get user by ID
     */
    public function getById($userId) {
        if (!$this->db) return null;
        
        try {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get user error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get all users
     */
    public function getAll($filters = []) {
        if (!$this->db) return [];
        
        try {
            $sql = "SELECT * FROM users WHERE 1=1";
            $params = [];
            
            if (!empty($filters['role'])) {
                $sql .= " AND role = ?";
                $params[] = $filters['role'];
            }
            
            if (!empty($filters['status'])) {
                $sql .= " AND status = ?";
                $params[] = $filters['status'];
            }
            
            $sql .= " ORDER BY created_at DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Get users error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Check if username exists
     */
    private function usernameExists($username) {
        $stmt = $this->db->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetch() !== false;
    }
    
    /**
     * Check if email exists
     */
    private function emailExists($email) {
        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch() !== false;
    }
    
    /**
     * Log login attempt
     */
    private function logLoginAttempt($username, $success) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO login_attempts (username, ip_address, success)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$username, $_SERVER['REMOTE_ADDR'], $success ? 1 : 0]);
        } catch (PDOException $e) {
            error_log("Login attempt log error: " . $e->getMessage());
        }
    }
    
    /**
     * Check if account is locked
     */
    private function isAccountLocked($username) {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as attempts 
                FROM login_attempts 
                WHERE username = ? 
                AND success = 0 
                AND attempted_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
            ");
            $stmt->execute([$username]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result['attempts'] >= 5;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Update last login time
     */
    private function updateLastLogin($userId) {
        try {
            $stmt = $this->db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$userId]);
        } catch (PDOException $e) {
            error_log("Update last login error: " . $e->getMessage());
        }
    }
    
    /**
     * Log audit action
     */
    private function logAudit($action, $entityType, $entityId, $oldValue = null, $newValue = null) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO audit_log (user_id, action, entity_type, entity_id, old_value, new_value, ip_address, user_agent)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                self::getCurrentUserId(),
                $action,
                $entityType,
                $entityId,
                $oldValue,
                $newValue,
                $_SERVER['REMOTE_ADDR'],
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]);
        } catch (PDOException $e) {
            error_log("Audit log error: " . $e->getMessage());
        }
    }
}                $this->logLoginAttempt($username, false);
                return ['success' => false, 'message' => 'Invalid username or password'];
            }

