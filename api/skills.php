<?php
/**
 * Skills API Endpoints for Smart Skill Progress Tracker
 * CSE470 Software Engineering Project
 * 
 * Handles skill-related API requests
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
    $skill = new Skill();
    $category = new Category();
    $userId = getCurrentUserId();
    
    switch ($method) {
        case 'GET':
            if (empty($path)) {
                // Get all skills for user
                $page = (int)($_GET['page'] ?? 1);
                $filters = [
                    'category_id' => $_GET['category'] ?? '',
                    'proficiency' => $_GET['proficiency'] ?? '',
                    'search' => $_GET['search'] ?? ''
                ];
                
                $result = $skill->getUserSkills($userId, $filters, $page);
                
                $response['success'] = true;
                $response['data'] = [
                    'skills' => $result['skills'],
                    'pagination' => $result['pagination'],
                    'filters' => $filters
                ];
                
            } elseif (is_numeric($pathParts[0])) {
                // Get specific skill
                $skillId = (int)$pathParts[0];
                $skillData = $skill->findById($skillId, $userId);
                
                if ($skillData) {
                    // Get additional data if requested
                    if (isset($_GET['include'])) {
                        $includes = explode(',', $_GET['include']);
                        
                        if (in_array('progress', $includes)) {
                            $skillData['progress_summary'] = $skill->getProgressSummary($skillId, $userId, 30);
                        }
                        
                        if (in_array('goals', $includes)) {
                            $goalModel = new Goal();
                            $skillData['goals'] = $goalModel->getSkillGoals($skillId, $userId);
                        }
                    }
                    
                    $response['success'] = true;
                    $response['data'] = $skillData;
                } else {
                    $response['message'] = 'Skill not found or access denied';
                    http_response_code(404);
                }
                
            } elseif ($pathParts[0] === 'search') {
                // Search skills
                $searchTerm = $_GET['q'] ?? '';
                if (empty($searchTerm)) {
                    $response['message'] = 'Search term is required';
                    http_response_code(400);
                } else {
                    $skills = $skill->search($userId, $searchTerm);
                    $response['success'] = true;
                    $response['data'] = ['skills' => $skills];
                }
                
            } elseif ($pathParts[0] === 'categories') {
                // Get skills by category
                if (empty($pathParts[1]) || !is_numeric($pathParts[1])) {
                    $response['message'] = 'Category ID is required';
                    http_response_code(400);
                } else {
                    $categoryId = (int)$pathParts[1];
                    $skills = $skill->getByCategory($categoryId, $userId);
                    $response['success'] = true;
                    $response['data'] = ['skills' => $skills];
                }
                
            } elseif ($pathParts[0] === 'recent') {
                // Get recent skills
                $limit = (int)($_GET['limit'] ?? 5);
                $skills = $skill->getRecentSkills($userId, $limit);
                $response['success'] = true;
                $response['data'] = ['skills' => $skills];
                
            } elseif (is_numeric($pathParts[0]) && $pathParts[1] === 'statistics') {
                // Get skill statistics
                $skillId = (int)$pathParts[0];
                $skillData = $skill->findById($skillId, $userId);
                
                if (!$skillData) {
                    $response['message'] = 'Skill not found or access denied';
                    http_response_code(404);
                } else {
                    $days = (int)($_GET['days'] ?? 30);
                    $stats = [
                        'progress_summary' => $skill->getProgressSummary($skillId, $userId, $days),
                        'skill_data' => $skillData
                    ];
                    
                    $response['success'] = true;
                    $response['data'] = $stats;
                }
                
            } else {
                $response['message'] = 'Endpoint not found';
                http_response_code(404);
            }
            break;
            
        case 'POST':
            if (empty($path)) {
                // Create new skill
                $input = json_decode(file_get_contents('php://input'), true);
                
                $required = ['skill_name', 'category_id'];
                foreach ($required as $field) {
                    if (empty($input[$field])) {
                        $response['message'] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
                        http_response_code(400);
                        break 2;
                    }
                }
                
                $skillData = [
                    'user_id' => $userId,
                    'skill_name' => $input['skill_name'],
                    'description' => $input['description'] ?? '',
                    'category_id' => (int)$input['category_id'],
                    'current_proficiency' => $input['current_proficiency'] ?? 'Beginner'
                ];
                
                $result = $skill->create($skillData);
                
                if ($result['success']) {
                    $response['success'] = true;
                    $response['message'] = $result['message'];
                    $response['data'] = ['skill_id' => $result['skill_id']];
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
                // Update skill
                $skillId = (int)$pathParts[0];
                $input = json_decode(file_get_contents('php://input'), true);
                
                $required = ['skill_name', 'category_id'];
                foreach ($required as $field) {
                    if (empty($input[$field])) {
                        $response['message'] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
                        http_response_code(400);
                        break 2;
                    }
                }
                
                $skillData = [
                    'skill_name' => $input['skill_name'],
                    'description' => $input['description'] ?? '',
                    'category_id' => (int)$input['category_id'],
                    'current_proficiency' => $input['current_proficiency'] ?? 'Beginner'
                ];
                
                $result = $skill->update($skillId, $userId, $skillData);
                
                if ($result['success']) {
                    $response['success'] = true;
                    $response['message'] = $result['message'];
                } else {
                    $response['message'] = $result['message'];
                    http_response_code(400);
                }
                
            } else {
                $response['message'] = 'Skill ID is required';
                http_response_code(400);
            }
            break;
            
        case 'DELETE':
            if (is_numeric($pathParts[0])) {
                // Delete skill
                $skillId = (int)$pathParts[0];
                
                $result = $skill->delete($skillId, $userId);
                
                if ($result['success']) {
                    $response['success'] = true;
                    $response['message'] = $result['message'];
                } else {
                    $response['message'] = $result['message'];
                    http_response_code(400);
                }
                
            } else {
                $response['message'] = 'Skill ID is required';
                http_response_code(400);
            }
            break;
            
        default:
            $response['message'] = 'Method not allowed';
            http_response_code(405);
            break;
    }
    
} catch (Exception $e) {
    error_log("Skills API error: " . $e->getMessage());
    $response['message'] = 'Internal server error';
    http_response_code(500);
}

echo json_encode($response);
?>

