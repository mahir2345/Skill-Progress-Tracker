<?php
/**
 * Dashboard API Endpoints for Smart Skill Progress Tracker
 * CSE470 Software Engineering Project
 * 
 * Handles dashboard-related API requests
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

// Check authentication
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

// Get request method and path
$method = $_SERVER['REQUEST_METHOD'];
$path = $_SERVER['PATH_INFO'] ?? '';
$path = trim($path, '/');
$pathParts = explode('/', $path);

// Initialize response
$response = ['success' => false, 'message' => '', 'data' => null];

try {
    $dashboardController = new DashboardController();
    $userId = getCurrentUserId();
    
    switch ($method) {
        case 'GET':
            if (empty($path)) {
                // Get dashboard data
                $data = $dashboardController->index();
                
                $response['success'] = true;
                $response['data'] = $data;
                
            } elseif ($pathParts[0] === 'statistics') {
                // Get dashboard statistics
                $period = $_GET['period'] ?? '30';
                $stats = $dashboardController->getStatistics();
                
                $response['success'] = true;
                $response['data'] = $stats;
                
            } elseif ($pathParts[0] === 'chart-data') {
                // Get chart data
                $type = $_GET['type'] ?? 'daily';
                $days = (int)($_GET['days'] ?? 30);
                
                $data = $dashboardController->getChartData();
                
                $response['success'] = true;
                $response['data'] = $data;
                
            } elseif ($pathParts[0] === 'activities') {
                // Get recent activities
                $limit = (int)($_GET['limit'] ?? 10);
                
                $data = $dashboardController->getRecentActivities();
                
                $response['success'] = true;
                $response['data'] = $data;
                
            } elseif ($pathParts[0] === 'skill-progress') {
                // Get skill progress summary
                $skillId = (int)($_GET['skill_id'] ?? 0);
                $days = (int)($_GET['days'] ?? 30);
                
                if (!$skillId) {
                    $response['message'] = 'Skill ID is required';
                    http_response_code(400);
                } else {
                    $data = $dashboardController->getSkillProgressSummary();
                    
                    $response['success'] = true;
                    $response['data'] = $data;
                }
                
            } elseif ($pathParts[0] === 'insights') {
                // Get productivity insights
                $days = (int)($_GET['days'] ?? 30);
                
                $data = $dashboardController->getProductivityInsights();
                
                $response['success'] = true;
                $response['data'] = $data;
                
            } elseif ($pathParts[0] === 'recommendations') {
                // Get skill recommendations
                $data = $dashboardController->getSkillRecommendations();
                
                $response['success'] = true;
                $response['data'] = $data;
                
            } elseif ($pathParts[0] === 'preferences') {
                // Get dashboard preferences
                $data = $dashboardController->getPreferences();
                
                $response['success'] = true;
                $response['data'] = $data;
                
            } else {
                $response['message'] = 'Endpoint not found';
                http_response_code(404);
            }
            break;
            
        case 'POST':
            if ($pathParts[0] === 'preferences') {
                // Update dashboard preferences
                $data = $dashboardController->updatePreferences();
                
                $response['success'] = true;
                $response['data'] = $data;
                
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
    error_log("Dashboard API error: " . $e->getMessage());
    $response['message'] = 'Internal server error';
    http_response_code(500);
}

echo json_encode($response);
?>

