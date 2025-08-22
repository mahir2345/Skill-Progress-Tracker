<?php
/**
 * User Model for Smart Skill Progress Tracker
 * CSE470 Software Engineering Project
 * 
 * Handles user authentication, registration, and profile management
 */

class User {
    private $pdo;
    
    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }
    
    /**
     * Create a new user account
     * @param array $userData
     * @return array ['success' => bool, 'message' => string, 'user_id' => int]
     */
    public function create($userData) {
        try {
            // Validate input
            $validation = $this->validateUserData($userData);
            if (!$validation['valid']) {
                return ['success' => false, 'message' => implode(', ', $validation['errors'])];
            }
            
            // Check if username or email already exists
            if ($this->usernameExists($userData['username'])) {
                return ['success' => false, 'message' => 'Username already exists'];
            }
            
            if ($this->emailExists($userData['email'])) {
                return ['success' => false, 'message' => 'Email already exists'];
            }
            
            // Hash password
            $hashedPassword = hashPassword($userData['password']);
            file_put_contents(__DIR__ . '/../logs/login_debug.log', date('Y-m-d H:i:s') . " - Hashed password: $hashedPassword\n", FILE_APPEND);
            
            // Insert user
            $sql = "INSERT INTO users (username, email, password_hash, first_name, last_name) 
                    VALUES (:username, :email, :password_hash, :first_name, :last_name)";
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                ':username' => $userData['username'],
                ':email' => $userData['email'],
                ':password_hash' => $hashedPassword,
                ':first_name' => $userData['first_name'],
                ':last_name' => $userData['last_name']
            ]);
            
            if ($result) {
                $userId = $this->pdo->lastInsertId();
                logActivity('user_registered', 'New user account created', $userId);
                
                return [
                    'success' => true, 
                    'message' => 'User account created successfully',
                    'user_id' => $userId
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to create user account'];
            }
            
        } catch (PDOException $e) {
            error_log("User creation error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }
    
    /**
     * Authenticate user login
     * @param string $username
     * @param string $password
     * @return array ['success' => bool, 'message' => string, 'user' => array]
     */
    public function authenticate($username, $password) {
        try {
            // Find user by username or email
            $sql = "SELECT * FROM users WHERE username = :username OR email = :email";
            file_put_contents(__DIR__ . '/../logs/login_debug.log', date('Y-m-d H:i:s') . " - Authenticate: username=$username, password=$password\n", FILE_APPEND);
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':username' => $username, ':email' => $username]);
            $user = $stmt->fetch();
            file_put_contents(__DIR__ . '/../logs/login_debug.log', date('Y-m-d H:i:s') . " - DB user: " . print_r($user, true), FILE_APPEND);
            
            if (!$user) {
                return ['success' => false, 'message' => 'Invalid username or password'];
            }
            
            // Verify password
            $verify = verifyPassword($password, $user['password_hash']);
            file_put_contents(__DIR__ . '/../logs/login_debug.log', date('Y-m-d H:i:s') . " - Password verify: " . ($verify ? 'true' : 'false') . "\n", FILE_APPEND);
            if (!$verify) {
                return ['success' => false, 'message' => 'Invalid username or password'];
            }
            
            // Update last login
            $this->updateLastLogin($user['user_id']);
            
            // Remove password hash from user data
            unset($user['password_hash']);
            
            logActivity('user_login', 'User logged in', $user['user_id']);
            
            return [
                'success' => true,
                'message' => 'Login successful',
                'user' => $user
            ];
            
        } catch (PDOException $e) {
            error_log("Authentication error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Authentication failed'];
        }
    }
    
    /**
     * Find user by ID
     * @param int $userId
     * @return array|null
     */
    public function findById($userId) {
        try {
            $sql = "SELECT user_id, username, email, first_name, last_name, created_at, updated_at 
                    FROM users WHERE user_id = :user_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':user_id' => $userId]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Find user error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Update user profile
     * @param int $userId
     * @param array $userData
     * @return array ['success' => bool, 'message' => string]
     */
    public function updateProfile($userId, $userData) {
        try {
            // Validate input
            $validation = $this->validateProfileData($userData);
            if (!$validation['valid']) {
                return ['success' => false, 'message' => implode(', ', $validation['errors'])];
            }
            
            // Check if email is being changed and if it already exists
            if (isset($userData['email'])) {
                $currentUser = $this->findById($userId);
                if ($userData['email'] !== $currentUser['email'] && $this->emailExists($userData['email'])) {
                    return ['success' => false, 'message' => 'Email already exists'];
                }
            }
            
            $sql = "UPDATE users SET first_name = :first_name, last_name = :last_name, 
                    email = :email, updated_at = CURRENT_TIMESTAMP 
                    WHERE user_id = :user_id";
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                ':first_name' => $userData['first_name'],
                ':last_name' => $userData['last_name'],
                ':email' => $userData['email'],
                ':user_id' => $userId
            ]);
            
            if ($result) {
                logActivity('profile_updated', 'User profile updated', $userId);
                return ['success' => true, 'message' => 'Profile updated successfully'];
            } else {
                return ['success' => false, 'message' => 'Failed to update profile'];
            }
            
        } catch (PDOException $e) {
            error_log("Profile update error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }
    
    /**
     * Change user password
     * @param int $userId
     * @param string $currentPassword
     * @param string $newPassword
     * @return array ['success' => bool, 'message' => string]
     */
    public function changePassword($userId, $currentPassword, $newPassword) {
        try {
            // Get current password hash
            $sql = "SELECT password_hash FROM users WHERE user_id = :user_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':user_id' => $userId]);
            $user = $stmt->fetch();
            
            if (!$user) {
                return ['success' => false, 'message' => 'User not found'];
            }
            
            // Verify current password
            if (!verifyPassword($currentPassword, $user['password_hash'])) {
                return ['success' => false, 'message' => 'Current password is incorrect'];
            }
            
            // Validate new password
            $validation = validatePassword($newPassword);
            if (!$validation['valid']) {
                return ['success' => false, 'message' => implode(', ', $validation['errors'])];
            }
            
            // Update password
            $newHash = hashPassword($newPassword);
            $sql = "UPDATE users SET password_hash = :password_hash, updated_at = CURRENT_TIMESTAMP 
                    WHERE user_id = :user_id";
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                ':password_hash' => $newHash,
                ':user_id' => $userId
            ]);
            
            if ($result) {
                logActivity('password_changed', 'User password changed', $userId);
                return ['success' => true, 'message' => 'Password changed successfully'];
            } else {
                return ['success' => false, 'message' => 'Failed to change password'];
            }
            
        } catch (PDOException $e) {
            error_log("Password change error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }
    
    /**
     * Get user statistics
     * @param int $userId
     * @return array
     */
    public function getStatistics($userId) {
        try {
            $sql = "SELECT 
                        COUNT(DISTINCT s.skill_id) as total_skills,
                        COUNT(DISTINCT pe.entry_id) as total_entries,
                        COALESCE(SUM(pe.hours_spent), 0) as total_hours,
                        COALESCE(SUM(pe.tasks_completed), 0) as total_tasks,
                        COUNT(DISTINCT g.goal_id) as total_goals,
                        COUNT(DISTINCT CASE WHEN g.is_completed = 1 THEN g.goal_id END) as completed_goals
                    FROM users u
                    LEFT JOIN skills s ON u.user_id = s.user_id
                    LEFT JOIN progress_entries pe ON s.skill_id = pe.skill_id
                    LEFT JOIN goals g ON s.skill_id = g.skill_id
                    WHERE u.user_id = :user_id";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':user_id' => $userId]);
            return $stmt->fetch();
            
        } catch (PDOException $e) {
            error_log("User statistics error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Check if username exists
     * @param string $username
     * @return bool
     */
    private function usernameExists($username) {
        $sql = "SELECT COUNT(*) FROM users WHERE username = :username";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':username' => $username]);
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Check if email exists
     * @param string $email
     * @return bool
     */
    private function emailExists($email) {
        $sql = "SELECT COUNT(*) FROM users WHERE email = :email";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':email' => $email]);
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Update last login timestamp
     * @param int $userId
     */
    private function updateLastLogin($userId) {
        $sql = "UPDATE users SET updated_at = CURRENT_TIMESTAMP WHERE user_id = :user_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
    }
    
    /**
     * Validate user registration data
     * @param array $userData
     * @return array ['valid' => bool, 'errors' => array]
     */
    private function validateUserData($userData) {
        $errors = [];
        
        // Required fields
        $required = ['username', 'email', 'password', 'first_name', 'last_name'];
        foreach ($required as $field) {
            if (empty($userData[$field])) {
                $errors[] = ucfirst(str_replace('_', ' ', $field)) . " is required";
            }
        }
        
        // Username validation
        if (!empty($userData['username'])) {
            if (strlen($userData['username']) < 3) {
                $errors[] = "Username must be at least 3 characters long";
            }
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $userData['username'])) {
                $errors[] = "Username can only contain letters, numbers, and underscores";
            }
        }
        
        // Email validation
        if (!empty($userData['email']) && !validateEmail($userData['email'])) {
            $errors[] = "Invalid email address";
        }
        
        // Password validation
        if (!empty($userData['password'])) {
            $passwordValidation = validatePassword($userData['password']);
            if (!$passwordValidation['valid']) {
                $errors = array_merge($errors, $passwordValidation['errors']);
            }
        }
        
        // Name validation
        if (!empty($userData['first_name']) && strlen($userData['first_name']) < 2) {
            $errors[] = "First name must be at least 2 characters long";
        }
        
        if (!empty($userData['last_name']) && strlen($userData['last_name']) < 2) {
            $errors[] = "Last name must be at least 2 characters long";
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Validate profile update data
     * @param array $userData
     * @return array ['valid' => bool, 'errors' => array]
     */
    private function validateProfileData($userData) {
        $errors = [];
        
        // Required fields
        $required = ['email', 'first_name', 'last_name'];
        foreach ($required as $field) {
            if (empty($userData[$field])) {
                $errors[] = ucfirst(str_replace('_', ' ', $field)) . " is required";
            }
        }
        
        // Email validation
        if (!empty($userData['email']) && !validateEmail($userData['email'])) {
            $errors[] = "Invalid email address";
        }
        
        // Name validation
        if (!empty($userData['first_name']) && strlen($userData['first_name']) < 2) {
            $errors[] = "First name must be at least 2 characters long";
        }
        
        if (!empty($userData['last_name']) && strlen($userData['last_name']) < 2) {
            $errors[] = "Last name must be at least 2 characters long";
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}
?>

