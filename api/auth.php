<?php
/**
 * Authentication API Endpoints for Smart Skill Progress Tracker
 * CSE470 Software Engineering Project
 * 
 * Handles authentication-related API requests
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../config/config.php';

// Get request method and path
$method = $_SERVER['REQUEST_METHOD'];
$path = $_SERVER['PATH_INFO'] ?? '';
$path = trim($path, '/');

// Initialize response
$response = ['success' => false, 'message' => '', 'data' => null];

try {
    $user = new User();
    
    switch ($method) {
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            
            if ($path === 'login') {
                // User login
                if (empty($input['username']) || empty($input['password'])) {
                    $response['message'] = 'Username and password are required';
                    http_response_code(400);
                    break;
                }
                
                $result = $user->authenticate($input['username'], $input['password']);
                
                if ($result['success']) {
                    // Set session data
                    $_SESSION['user_id'] = $result['user']['user_id'];
                    $_SESSION['username'] = $result['user']['username'];
                    $_SESSION['first_name'] = $result['user']['first_name'];
                    $_SESSION['last_name'] = $result['user']['last_name'];
                    $_SESSION['email'] = $result['user']['email'];
                    $_SESSION['login_time'] = time();
                    
                    session_regenerate_id(true);
                    
                    $response['success'] = true;
                    $response['message'] = $result['message'];
                    $response['data'] = [
                        'user' => $result['user'],
                        'session_id' => session_id()
                    ];
                } else {
                    $response['message'] = $result['message'];
                    http_response_code(401);
                }
                
            } elseif ($path === 'register') {
                // User registration
                $required = ['username', 'email', 'password', 'first_name', 'last_name'];
                foreach ($required as $field) {
                    if (empty($input[$field])) {
                        $response['message'] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
                        http_response_code(400);
                        break 2;
                    }
                }
                
                $result = $user->create($input);
                
                if ($result['success']) {
                    $response['success'] = true;
                    $response['message'] = $result['message'];
                    $response['data'] = ['user_id' => $result['user_id']];
                    http_response_code(201);
                } else {
                    $response['message'] = $result['message'];
                    http_response_code(400);
                }
                
            } elseif ($path === 'logout') {
                // User logout
                if (isLoggedIn()) {
                    logActivity('user_logout', 'User logged out via API');
                    session_destroy();
                    $response['success'] = true;
                    $response['message'] = 'Logged out successfully';
                } else {
                    $response['message'] = 'Not logged in';
                    http_response_code(400);
                }
                
            } elseif ($path === 'change-password') {
                // Change password
                if (!isLoggedIn()) {
                    $response['message'] = 'Authentication required';
                    http_response_code(401);
                    break;
                }
                
                if (empty($input['current_password']) || empty($input['new_password'])) {
                    $response['message'] = 'Current password and new password are required';
                    http_response_code(400);
                    break;
                }
                
                $result = $user->changePassword(
                    getCurrentUserId(),
                    $input['current_password'],
                    $input['new_password']
                );
                
                if ($result['success']) {
                    $response['success'] = true;
                    $response['message'] = $result['message'];
                } else {
                    $response['message'] = $result['message'];
                    http_response_code(400);
                }
                
            } else {
                $response['message'] = 'Endpoint not found';
                http_response_code(404);
            }
            break;
            
        case 'GET':
            if ($path === 'me') {
                // Get current user info
                if (!isLoggedIn()) {
                    $response['message'] = 'Authentication required';
                    http_response_code(401);
                    break;
                }
                
                $userData = $user->findById(getCurrentUserId());
                if ($userData) {
                    $response['success'] = true;
                    $response['data'] = $userData;
                } else {
                    $response['message'] = 'User not found';
                    http_response_code(404);
                }
                
            } elseif ($path === 'status') {
                // Check authentication status
                $response['success'] = true;
                $response['data'] = [
                    'authenticated' => isLoggedIn(),
                    'user_id' => getCurrentUserId(),
                    'session_id' => session_id()
                ];
                
            } elseif ($path === 'statistics') {
                // Get user statistics
                if (!isLoggedIn()) {
                    $response['message'] = 'Authentication required';
                    http_response_code(401);
                    break;
                }
                
                $stats = $user->getStatistics(getCurrentUserId());
                $response['success'] = true;
                $response['data'] = $stats;
                
            } else {
                $response['message'] = 'Endpoint not found';
                http_response_code(404);
            }
            break;
            
        case 'PUT':
            if ($path === 'profile') {
                // Update user profile
                if (!isLoggedIn()) {
                    $response['message'] = 'Authentication required';
                    http_response_code(401);
                    break;
                }
                
                $input = json_decode(file_get_contents('php://input'), true);
                
                $required = ['first_name', 'last_name', 'email'];
                foreach ($required as $field) {
                    if (empty($input[$field])) {
                        $response['message'] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
                        http_response_code(400);
                        break 2;
                    }
                }
                
                $result = $user->updateProfile(getCurrentUserId(), $input);
                
                if ($result['success']) {
                    // Update session data
                    $_SESSION['first_name'] = $input['first_name'];
                    $_SESSION['last_name'] = $input['last_name'];
                    $_SESSION['email'] = $input['email'];
                    
                    $response['success'] = true;
                    $response['message'] = $result['message'];
                } else {
                    $response['message'] = $result['message'];
                    http_response_code(400);
                }
                
            } else {
                $response['message'] = 'Endpoint not found';
                http_response_code(404);
            }
            break;
            
        default:
            $response['message'] = 'Method not allowed';
            http_response_code(405);
            break;
    }
    
} catch (Exception $e) {
    error_log("Auth API error: " . $e->getMessage());
    $response['message'] = 'Internal server error';
    http_response_code(500);
}

echo json_encode($response);
?>

