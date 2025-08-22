<?php
/**
 * Progress API Endpoints for Smart Skill Progress Tracker
 * CSE470 Software Engineering Project
 * 
 * Handles progress-related API requests
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
    $progress = new Progress();
    $userId = getCurrentUserId();
    
    switch ($method) {
        case 'GET':
            if (empty($path)) {
                // Get all progress entries for user
                $page = (int)($_GET['page'] ?? 1);
                $filters = [
                    'skill_id' => $_GET['skill_id'] ?? '',
                    'start_date' => $_GET['start_date'] ?? '',
                    'end_date' => $_GET['end_date'] ?? '',
                    'proficiency' => $_GET['proficiency'] ?? ''
                ];
                
                
                $response['success'] = true;
                $response['data'] = [
                    'entries' => $result['entries'],
                    'pagination' => $result['pagination'],
                    'filters' => $filters
                ];
                
            } elseif (is_numeric($pathParts[0])) {
                // Get specific progress entry
                $entryId = (int)$pathParts[0];
                $entry = $progress->findById($entryId, $userId);
                
                if ($entry) {
                    $response['success'] = true;
                    $response['data'] = $entry;
                } else {
                    $response['message'] = 'Progress entry not found or access denied';
                    http_response_code(404);
                }
                
            } elseif ($pathParts[0] === 'statistics') {
                // Get progress statistics
                $days = (int)($_GET['days'] ?? 30);
                $stats = $progress->getUserProgressStats($userId, $days);
                
                $response['success'] = true;
                $response['data'] = $stats;
                
            } elseif ($pathParts[0] === 'chart-data') {
                // Get chart data
                $type = $_GET['type'] ?? 'daily';
                $days = (int)($_GET['days'] ?? 30);
                
                switch ($type) {
                    case 'daily':
                        $data = $progress->getDailyProgressData($userId, $days);
                        break;
                    case 'category':
                        $data = $progress->getProgressByCategory($userId, $days);
                        break;
                    default:
                        $data = [];
                }
                
                $response['success'] = true;
                $response['data'] = ['chart_data' => $data];
                
            } else {
                $response['message'] = 'Endpoint not found';
                http_response_code(404);
            }
            break;
            
        case 'POST':
            if (empty($path)) {
                // Create new progress entry
                $input = json_decode(file_get_contents('php://input'), true);
                
                $required = ['skill_id', 'proficiency_level'];
                foreach ($required as $field) {
                    if (empty($input[$field])) {
                        $response['message'] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
                        http_response_code(400);
                        break 2;
                    }
                }
                
                $progressData = [
                    'user_id' => $userId,
                    'skill_id' => (int)$input['skill_id'],
                    'hours_spent' => $input['hours_spent'] ?? 0,
                    'tasks_completed' => $input['tasks_completed'] ?? 0,
                    'proficiency_level' => $input['proficiency_level'],
                    'notes' => $input['notes'] ?? '',
                    'entry_date' => $input['entry_date'] ?? date('Y-m-d')
                ];
                
                $result = $progress->create($progressData);
                
                if ($result['success']) {
                    $response['success'] = true;
                    $response['message'] = $result['message'];
                    $response['data'] = ['entry_id' => $result['entry_id']];
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
                // Update progress entry
                $entryId = (int)$pathParts[0];
                $input = json_decode(file_get_contents('php://input'), true);
                
                $required = ['proficiency_level'];
                foreach ($required as $field) {
                    if (empty($input[$field])) {
                        $response['message'] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
                        http_response_code(400);
                        break 2;
                    }
                }
                
                $progressData = [
                    'hours_spent' => $input['hours_spent'] ?? 0,
                    'tasks_completed' => $input['tasks_completed'] ?? 0,
                    'proficiency_level' => $input['proficiency_level'],
                    'notes' => $input['notes'] ?? '',
                    'entry_date' => $input['entry_date'] ?? date('Y-m-d')
                ];
                
                $result = $progress->update($entryId, $userId, $progressData);
                
                if ($result['success']) {
                    $response['success'] = true;
                    $response['message'] = $result['message'];
                } else {
                    $response['message'] = $result['message'];
                    http_response_code(400);
                }
                
            } else {
                $response['message'] = 'Entry ID is required';
                http_response_code(400);
            }
            break;
            
        case 'DELETE':
            if (is_numeric($pathParts[0])) {
                // Delete progress entry
                $entryId = (int)$pathParts[0];
                
                $result = $progress->delete($entryId, $userId);
                
                if ($result['success']) {
                    $response['success'] = true;
                    $response['message'] = $result['message'];
                } else {
                    $response['message'] = $result['message'];
                    http_response_code(400);
                }
                
            } else {
                $response['message'] = 'Entry ID is required';
                http_response_code(400);
            }
            break;
            
        default:
            $response['message'] = 'Method not allowed';
            http_response_code(405);
            break;
    }
    
} catch (Exception $e) {
    error_log("Progress API error: " . $e->getMessage());
    $response['message'] = 'Internal server error';
    http_response_code(500);
}

echo json_encode($response);
?>

