<?php
/**
 * Goals API Endpoints for Smart Skill Progress Tracker
 * CSE470 Software Engineering Project
 * 
 * Handles goal-related API requests
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
    $goal = new Goal();
    $userId = getCurrentUserId();
    
    switch ($method) {
        case 'GET':
            if (empty($path)) {
                // Get all goals for user
                $page = (int)($_GET['page'] ?? 1);
                $filters = [
                    'is_completed' => $_GET['status'] ?? '',
                    'skill_id' => $_GET['skill_id'] ?? '',
                    'category_id' => $_GET['category_id'] ?? ''
                ];
                
                $result = $goal->getUserGoals($userId, $filters, $page);
                
                $response['success'] = true;
                $response['data'] = [
                    'goals' => $result['goals'],
                    'pagination' => $result['pagination'],
                    'filters' => $filters
                ];
                
            } elseif (is_numeric($pathParts[0])) {
                // Get specific goal
                $goalId = (int)$pathParts[0];
                $goalData = $goal->findById($goalId, $userId);
                
                if ($goalData) {
                    $response['success'] = true;
                    $response['data'] = $goalData;
                } else {
                    $response['message'] = 'Goal not found or access denied';
                    http_response_code(404);
                }
                
            } elseif ($pathParts[0] === 'statistics') {
                // Get goal statistics
                $stats = $goal->getUserGoalStats($userId);
                
                $response['success'] = true;
                $response['data'] = $stats;
                
            } elseif ($pathParts[0] === 'upcoming') {
                // Get upcoming goals
                $days = (int)($_GET['days'] ?? 7);
                $goals = $goal->getUpcomingGoals($userId, $days);
                
                $response['success'] = true;
                $response['data'] = ['goals' => $goals];
                
            } else {
                $response['message'] = 'Endpoint not found';
                http_response_code(404);
            }
            break;
            
        case 'POST':
            if (empty($path)) {
                // Create new goal
                $input = json_decode(file_get_contents('php://input'), true);
                
                $required = ['skill_id', 'target_proficiency'];
                foreach ($required as $field) {
                    if (empty($input[$field])) {
                        $response['message'] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
                        http_response_code(400);
                        break 2;
                    }
                }
                
                $goalData = [
                    'user_id' => $userId,
                    'skill_id' => (int)$input['skill_id'],
                    'target_proficiency' => $input['target_proficiency'],
                    'target_date' => $input['target_date'] ?? null,
                    'target_hours' => $input['target_hours'] ?? null,
                    'description' => $input['description'] ?? ''
                ];
                
                $result = $goal->create($goalData);
                
                if ($result['success']) {
                    $response['success'] = true;
                    $response['message'] = $result['message'];
                    $response['data'] = ['goal_id' => $result['goal_id']];
                    http_response_code(201);
                } else {
                    $response['message'] = $result['message'];
                    http_response_code(400);
                }
                
            } else {
                $response['message'] = 'Endpoint not found';
                http_response_code(404);
            }
            break;
            
        case 'PUT':
            if (is_numeric($pathParts[0])) {
                $goalId = (int)$pathParts[0];
                
                if (isset($pathParts[1]) && $pathParts[1] === 'complete') {
                    // Mark goal as completed
                    $result = $goal->markCompleted($goalId, $userId);
                } elseif (isset($pathParts[1]) && $pathParts[1] === 'incomplete') {
                    // Mark goal as incomplete
                    $result = $goal->markIncomplete($goalId, $userId);
                } else {
                    // Update goal
                    $input = json_decode(file_get_contents('php://input'), true);
                    
                    $required = ['target_proficiency'];
                    foreach ($required as $field) {
                        if (empty($input[$field])) {
                            $response['message'] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
                            http_response_code(400);
                            break 2;
                        }
                    }
                    
                    $goalData = [
                        'target_proficiency' => $input['target_proficiency'],
                        'target_date' => $input['target_date'] ?? null,
                        'target_hours' => $input['target_hours'] ?? null,
                        'description' => $input['description'] ?? ''
                    ];
                    
                    $result = $goal->update($goalId, $userId, $goalData);
                }
                
                if ($result['success']) {
                    $response['success'] = true;
                    $response['message'] = $result['message'];
                } else {
                    $response['message'] = $result['message'];
                    http_response_code(400);
                }
                
            } else {
                $response['message'] = 'Goal ID is required';
                http_response_code(400);
            }
            break;
            
        case 'DELETE':
            if (is_numeric($pathParts[0])) {
                // Delete goal
                $goalId = (int)$pathParts[0];
                
                $result = $goal->delete($goalId, $userId);
                
                if ($result['success']) {
                    $response['success'] = true;
                    $response['message'] = $result['message'];
                } else {
                    $response['message'] = $result['message'];
                    http_response_code(400);
                }
                
            } else {
                $response['message'] = 'Goal ID is required';
                http_response_code(400);
            }
            break;
            
        default:
            $response['message'] = 'Method not allowed';
            http_response_code(405);
            break;
    }
    
} catch (Exception $e) {
    error_log("Goals API error: " . $e->getMessage());
    $response['message'] = 'Internal server error';
    http_response_code(500);
}

echo json_encode($response);
?>
