<?php
/**
 * Generate a CSRF token field for forms
 * @return string
 */
function generateCSRFField() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    $token = htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8');
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}
/**
 * Utility Functions for Smart Skill Progress Tracker
 * CSE470 Software Engineering Project
 */

/**
 * Validate email address
 * @param string $email
 * @return bool
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate password strength
 * @param string $password
 * @return array ['valid' => bool, 'errors' => array]
 */
function validatePassword($password) {
    $errors = [];
    
    if (strlen($password) < PASSWORD_MIN_LENGTH) {
        $errors[] = "Password must be at least " . PASSWORD_MIN_LENGTH . " characters long";
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter";
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must contain at least one lowercase letter";
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number";
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * Hash password securely
 * @param string $password
 * @return string
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verify password against hash
 * @param string $password
 * @param string $hash
 * @return bool
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Generate random string
 * @param int $length
 * @return string
 */
function generateRandomString($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Sanitize input data
 * @param mixed $data
 * @return mixed
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    
    return trim(htmlspecialchars($data, ENT_QUOTES, 'UTF-8'));
}

/**
 * Validate CSRF token from request
 * @return bool
 */
function validateCSRFToken() {
    $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
    return verifyCSRFToken($token);
}

/**
 * Send JSON response
 * @param array $data
 * @param int $statusCode
 */
function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Log activity
 * @param string $action
 * @param string $details
 * @param int $userId
 */
function logActivity($action, $details = '', $userId = null) {
    if ($userId === null) {
        $userId = getCurrentUserId();
    }
    
    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'user_id' => $userId,
        'action' => $action,
        'details' => $details,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ];
    
    // Log to file (in production, consider using a proper logging library)
    $logFile = __DIR__ . '/../logs/activity.log';
    if (!file_exists(dirname($logFile))) {
        mkdir(dirname($logFile), 0755, true);
    }
    
    file_put_contents($logFile, json_encode($logEntry) . "\n", FILE_APPEND | LOCK_EX);
}

/**
 * Format file size
 * @param int $bytes
 * @return string
 */
function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= pow(1024, $pow);
    
    return round($bytes, 2) . ' ' . $units[$pow];
}

/**
 * Validate file upload
 * @param array $file $_FILES array element
 * @return array ['valid' => bool, 'error' => string]
 */
function validateFileUpload($file) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['valid' => false, 'error' => 'File upload failed'];
    }
    
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['valid' => false, 'error' => 'File size exceeds limit'];
    }
    
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, ALLOWED_FILE_TYPES)) {
        return ['valid' => false, 'error' => 'File type not allowed'];
    }
    
    return ['valid' => true, 'error' => ''];
}

/**
 * Calculate progress percentage
 * @param float $current
 * @param float $target
 * @return float
 */
function calculateProgress($current, $target) {
    if ($target <= 0) {
        return 0;
    }
    
    return min(100, ($current / $target) * 100);
}

/**
 * Get proficiency level color
 * @param string $level
 * @return string
 */
function getProficiencyColor($level) {
    switch (strtolower($level)) {
        case 'beginner':
            return '#dc3545'; // Red
        case 'intermediate':
            return '#ffc107'; // Yellow
        case 'advanced':
            return '#17a2b8'; // Blue
        case 'expert':
            return '#28a745'; // Green
        default:
            return '#6c757d'; // Gray
    }
}

/**
 * Get proficiency level badge HTML
 * @param string $level
 * @return string
 */
function getProficiencyBadge($level) {
    $color = getProficiencyColor($level);
    return "<span class='badge' style='background-color: {$color}; color: white;'>" . ucfirst($level) . "</span>";
}

/**
 * Paginate results
 * @param int $totalItems
 * @param int $currentPage
 * @param int $itemsPerPage
 * @return array
 */
function paginate($totalItems, $currentPage = 1, $itemsPerPage = ITEMS_PER_PAGE) {
    $totalPages = ceil($totalItems / $itemsPerPage);
    $currentPage = max(1, min($currentPage, $totalPages));
    $offset = ($currentPage - 1) * $itemsPerPage;
    
    return [
        'total_items' => $totalItems,
        'total_pages' => $totalPages,
        'current_page' => $currentPage,
        'items_per_page' => $itemsPerPage,
        'offset' => $offset,
        'has_previous' => $currentPage > 1,
        'has_next' => $currentPage < $totalPages,
        'previous_page' => $currentPage - 1,
        'next_page' => $currentPage + 1
    ];
}

/**
 * Generate pagination HTML
 * @param array $pagination
 * @param string $baseUrl
 * @return string
 */
function generatePaginationHTML($pagination, $baseUrl) {
    if ($pagination['total_pages'] <= 1) {
        return '';
    }
    
    $html = '<nav aria-label="Page navigation"><ul class="pagination">';
    
    // Previous button
    if ($pagination['has_previous']) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '&page=' . $pagination['previous_page'] . '">Previous</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><span class="page-link">Previous</span></li>';
    }
    
    // Page numbers
    $start = max(1, $pagination['current_page'] - 2);
    $end = min($pagination['total_pages'], $pagination['current_page'] + 2);
    
    for ($i = $start; $i <= $end; $i++) {
        if ($i == $pagination['current_page']) {
            $html .= '<li class="page-item active"><span class="page-link">' . $i . '</span></li>';
        } else {
            $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '&page=' . $i . '">' . $i . '</a></li>';
        }
    }
    
    // Next button
    if ($pagination['has_next']) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '&page=' . $pagination['next_page'] . '">Next</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><span class="page-link">Next</span></li>';
    }
    
    $html .= '</ul></nav>';
    
    return $html;
}

/**
 * Debug function (only works in development)
 * @param mixed $data
 * @param bool $die
 */
function debug($data, $die = false) {
    if (defined('DEVELOPMENT') && DEVELOPMENT) {
        echo '<pre>';
        print_r($data);
        echo '</pre>';
        
        if ($die) {
            die();
        }
    }
}

/**
 * Check if a flash message exists in the session
 * @return bool
 */
function hasFlashMessage() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return !empty($_SESSION['flash_messages']);
}

/**
 * Get and clear all flash messages from the session
 * @return array
 */


