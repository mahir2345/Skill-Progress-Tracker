<?php
/**
 * Authentication Controller for Smart Skill Progress Tracker
 * CSE470 Software Engineering Project
 * 
 * Handles user authentication, registration, and session management
 */

require_once __DIR__ . '/../config/config.php';

class AuthController {
    private $user;
    
    public function __construct() {
        $this->user = new User();
    }
    
    /**
     * Handle user registration
     */
    public function register() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            file_put_contents(__DIR__ . '/../logs/login_debug.log', date('Y-m-d H:i:s') . " - Register POST: " . print_r($_POST, true), FILE_APPEND);
            // Validate CSRF token
            if (!validateCSRFToken()) {
                setFlashMessage('error', 'Invalid security token. Please try again.');
                header('Location: ' . BASE_URL . '?page=register');
                exit;
            }
            
            // Sanitize input
            $userData = [
                'username' => sanitizeInput($_POST['username'] ?? ''),
                'email' => sanitizeInput($_POST['email'] ?? ''),
                'password' => $_POST['password'] ?? '',
                'confirm_password' => $_POST['confirm_password'] ?? '',
                'first_name' => sanitizeInput($_POST['first_name'] ?? ''),
                'last_name' => sanitizeInput($_POST['last_name'] ?? '')
            ];
            
            // Validate password confirmation
            if ($userData['password'] !== $userData['confirm_password']) {
                setFlashMessage('error', 'Passwords do not match.');
                header('Location: ' . BASE_URL . '?page=register');
                exit;
            }
            
            // Attempt to create user
            $result = $this->user->create($userData);
            file_put_contents(__DIR__ . '/../logs/login_debug.log', date('Y-m-d H:i:s') . " - Register result: " . print_r($result, true), FILE_APPEND);
            
            if ($result['success']) {
                setFlashMessage('success', 'Account created successfully! Please log in.');
                header('Location: ' . BASE_URL . '?page=login');
            } else {
                setFlashMessage('error', $result['message']);
                header('Location: ' . BASE_URL . '?page=register');
            }
            exit;
        }
    }
    
    /**
     * Handle user login
     */
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // DEBUG: Log entry into login POST handler
            file_put_contents(__DIR__ . '/../logs/login_debug.log', date('Y-m-d H:i:s') . " - Entered login POST\n", FILE_APPEND);
            // Log all POST data for debugging
            file_put_contents(__DIR__ . '/../logs/login_debug.log', date('Y-m-d H:i:s') . " - POST data: " . print_r($_POST, true) . "\n", FILE_APPEND);
            // Log all SESSION data for debugging
            file_put_contents(__DIR__ . '/../logs/login_debug.log', date('Y-m-d H:i:s') . " - SESSION data: " . print_r($_SESSION, true) . "\n", FILE_APPEND);
            
            // Validate CSRF token
            $token = $_POST['csrf_token'] ?? 'no_token';
            $session_token = $_SESSION['csrf_token'] ?? 'no_session_token';
            file_put_contents(__DIR__ . '/../logs/login_debug.log', date('Y-m-d H:i:s') . " - POST token: $token\n", FILE_APPEND);
            file_put_contents(__DIR__ . '/../logs/login_debug.log', date('Y-m-d H:i:s') . " - Session token: $session_token\n", FILE_APPEND);
            
            if (!validateCSRFToken($token)) {
                setFlashMessage('error', 'Invalid security token. Please try again.');
                file_put_contents(__DIR__ . '/../logs/login_debug.log', date('Y-m-d H:i:s') . " - Invalid CSRF token - POST token and SESSION token don't match\n", FILE_APPEND);
                header('Location: ' . BASE_URL . '?page=login');
                exit;
            }
            
            // Sanitize input
            $username = sanitizeInput($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $rememberMe = isset($_POST['remember_me']);
            
            // Validate input
            if (empty($username) || empty($password)) {
                setFlashMessage('error', 'Please enter both username and password.');
                file_put_contents(__DIR__ . '/../logs/login_debug.log', date('Y-m-d H:i:s') . " - Missing username or password\n", FILE_APPEND);
                header('Location: ' . BASE_URL . '?page=login');
                exit;
            }
            
            // Attempt authentication
            $result = $this->user->authenticate($username, $password);
            file_put_contents(__DIR__ . '/../logs/login_debug.log', date('Y-m-d H:i:s') . " - Attempting authentication for user: $username\n", FILE_APPEND);
            if ($result['success']) {
                file_put_contents(__DIR__ . '/../logs/login_debug.log', date('Y-m-d H:i:s') . " - Authentication SUCCESS for user: $username\n", FILE_APPEND);
                // Set session data
                $_SESSION['user_id'] = $result['user']['user_id'];
                $_SESSION['username'] = $result['user']['username'];
                $_SESSION['first_name'] = $result['user']['first_name'];
                $_SESSION['last_name'] = $result['user']['last_name'];
                $_SESSION['email'] = $result['user']['email'];
                $_SESSION['login_time'] = time();
                
                // Set remember me cookie if requested
                if ($rememberMe) {
                    $token = generateRandomString(32);
                    setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/', '', false, true); // 30 days
                }
                
                // Regenerate session ID for security
                session_regenerate_id(true);
                
                setFlashMessage('success', 'Welcome back, ' . $result['user']['first_name'] . '!');
                
                // Always redirect to dashboard after login
                header('Location: ' . BASE_URL . '?page=dashboard');
                file_put_contents(__DIR__ . '/../logs/login_debug.log', date('Y-m-d H:i:s') . " - Redirecting to dashboard\n", FILE_APPEND);
            } else {
                setFlashMessage('error', $result['message']);
                header('Location: ' . BASE_URL . '?page=login');
            }
            exit;
        }
    }
    
    /**
     * Handle user logout
     */
    public function logout() {
        // Log the logout activity
        if (isLoggedIn()) {
            logActivity('user_logout', 'User logged out');
        }
        
        // Clear remember me cookie
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/', '', false, true);
        }
        
        // Destroy session
        session_destroy();
        
        setFlashMessage('success', 'You have been logged out successfully.');
        header('Location: ' . BASE_URL . '?page=login');
        exit;
    }
    
    /**
     * Handle password change
     */
    public function changePassword() {
        requireLogin();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validate CSRF token
            if (!validateCSRFToken()) {
                setFlashMessage('error', 'Invalid security token. Please try again.');
                header('Location: ' . BASE_URL . '?page=profile');
                exit;
            }
            
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            
            // Validate input
            if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                setFlashMessage('error', 'All password fields are required.');
                header('Location: ' . BASE_URL . '?page=profile');
                exit;
            }
            
            if ($newPassword !== $confirmPassword) {
                setFlashMessage('error', 'New passwords do not match.');
                header('Location: ' . BASE_URL . '?page=profile');
                exit;
            }
            
            // Attempt password change
            $result = $this->user->changePassword(getCurrentUserId(), $currentPassword, $newPassword);
            
            if ($result['success']) {
                setFlashMessage('success', $result['message']);
            } else {
                setFlashMessage('error', $result['message']);
            }
            
            header('Location: ' . BASE_URL . '?page=profile');
            exit;
        }
    }
    
    /**
     * Handle profile update
     */
    public function updateProfile() {
        requireLogin();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validate CSRF token
            if (!validateCSRFToken()) {
                setFlashMessage('error', 'Invalid security token. Please try again.');
                header('Location: ' . BASE_URL . '?page=profile');
                exit;
            }
            
            // Sanitize input
            $userData = [
                'first_name' => sanitizeInput($_POST['first_name'] ?? ''),
                'last_name' => sanitizeInput($_POST['last_name'] ?? ''),
                'email' => sanitizeInput($_POST['email'] ?? '')
            ];
            
            // Attempt profile update
            $result = $this->user->updateProfile(getCurrentUserId(), $userData);
            
            if ($result['success']) {
                // Update session data
                $_SESSION['first_name'] = $userData['first_name'];
                $_SESSION['last_name'] = $userData['last_name'];
                $_SESSION['email'] = $userData['email'];
                
                setFlashMessage('success', $result['message']);
            } else {
                setFlashMessage('error', $result['message']);
            }
            
            header('Location: ' . BASE_URL . '?page=profile');
            exit;
        }
    }
    
    /**
     * Check if user session is valid
     */
    public function validateSession() {
        if (!isLoggedIn()) {
            return false;
        }
        
        // Check session timeout
        $loginTime = $_SESSION['login_time'] ?? 0;
        if (time() - $loginTime > SESSION_LIFETIME) {
            $this->logout();
            return false;
        }
        
        // Update last activity time
        $_SESSION['last_activity'] = time();
        
        return true;
    }
    
    /**
     * Handle forgot password (basic implementation)
     */
    public function forgotPassword() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validate CSRF token
            if (!validateCSRFToken()) {
                setFlashMessage('error', 'Invalid security token. Please try again.');
                header('Location: ' . BASE_URL . '?page=login');
                exit;
            }
            
            $email = sanitizeInput($_POST['email'] ?? '');
            
            if (empty($email) || !validateEmail($email)) {
                setFlashMessage('error', 'Please enter a valid email address.');
                header('Location: ' . BASE_URL . '?page=login');
                exit;
            }
            
            // In a real application, you would:
            // 1. Check if email exists in database
            // 2. Generate a secure reset token
            // 3. Store token in database with expiration
            // 4. Send email with reset link
            
            // For this demo, we'll just show a success message
            setFlashMessage('success', 'If an account with that email exists, a password reset link has been sent.');
            header('Location: ' . BASE_URL . '?page=login');
            exit;
        }
    }
    
    /**
     * Get user dashboard data
     */
    public function getDashboardData() {
        requireLogin();
        
        $userId = getCurrentUserId();
        
        // Get user statistics
        $userStats = $this->user->getStatistics($userId);
        
        // Get recent activities (this would be implemented with an activity log)
        $recentActivities = [];
        
        return [
            'user_stats' => $userStats,
            'recent_activities' => $recentActivities
        ];
    }
}

// Handle direct access to this controller
if (basename($_SERVER['PHP_SELF']) === 'AuthController.php') {
    $controller = new AuthController();
    $action = $_GET['action'] ?? 'login';
    
    switch ($action) {
        case 'register':
            $controller->register();
            break;
        case 'login':
            $controller->login();
            break;
        case 'logout':
            $controller->logout();
            break;
        case 'change-password':
            $controller->changePassword();
            break;
        case 'update-profile':
            $controller->updateProfile();
            break;
        case 'forgot-password':
            $controller->forgotPassword();
            break;
        default:
            header('Location: ' . BASE_URL . '?page=login');
            exit;
    }
}
?>

