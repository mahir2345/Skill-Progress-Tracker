<?php
// Prevent accidental output before headers
ob_start();

// Error reporting for development
if (!headers_sent()) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}


// Session configuration (must be set before session_start)
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 for HTTPS

// Always start session before any output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
/**
 * Main Configuration File for Smart Skill Progress Tracker
 * CSE470 Software Engineering Project
 */

// Application settings
define('APP_NAME', 'Smart Skill Progress Tracker');
define('APP_VERSION', '1.0.0');
define('BASE_URL', 'http://localhost/skill_tracker/');
define('ASSETS_URL', BASE_URL . 'assets/');
define('API_URL', BASE_URL . 'api/');

// Security settings
define('SESSION_LIFETIME', 3600); // 1 hour in seconds
define('PASSWORD_MIN_LENGTH', 8);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes

// Application settings
define('ITEMS_PER_PAGE', 10);
define('MAX_FILE_SIZE', 5242880); // 5MB
define('ALLOWED_FILE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'pdf']);

// Timezone setting
date_default_timezone_set('UTC');

// Error reporting (disable in production)
if (defined('DEVELOPMENT') && DEVELOPMENT) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}



// Include database connection
require_once __DIR__ . '/database.php';

// Include utility functions
require_once __DIR__ . '/../includes/functions.php';

/**
 * Autoloader for model classes
 */
spl_autoload_register(function ($class) {
    $modelFile = __DIR__ . '/../models/' . $class . '.php';
    if (file_exists($modelFile)) {
        require_once $modelFile;
    }
});

/**
 * Check if user is logged in
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get current user ID
 * @return int|null
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user data
 * @return array|null
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $user = new User();
    return $user->findById(getCurrentUserId());
}

/**
 * Redirect to login page if not authenticated
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '?page=login');
        exit;
    }
}

/**
 * Generate CSRF token
 * @return string
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 * @param string $token
 * @return bool
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Set flash message
 * @param string $type (success, error, warning, info)
 * @param string $message
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash_messages'][] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Get and clear flash messages
 * @return array
 */
function getFlashMessages() {
    $messages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);
    return $messages;
}

/**
 * Sanitize output for HTML display
 * @param string $string
 * @return string
 */
function escape($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Format date for display
 * @param string $date
 * @param string $format
 * @return string
 */
function formatDate($date, $format = 'Y-m-d H:i:s') {
    return date($format, strtotime($date));
}

/**
 * Calculate time ago
 * @param string $datetime
 * @return string
 */
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    if ($time < 31536000) return floor($time/2592000) . ' months ago';
    
    return floor($time/31536000) . ' years ago';
}
?>

