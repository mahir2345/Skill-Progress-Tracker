<?php
/**
 * Main Entry Point for Smart Skill Progress Tracker
 * CSE470 Software Engineering Project
 * 
 * This file implements the front controller pattern and handles routing
 */

// Enable development mode (set to false in production)
define('DEVELOPMENT', true);

// Include configuration
require_once 'config/config.php';

// Get the requested page
$page = $_GET['page'] ?? 'dashboard';
$action = $_GET['action'] ?? 'index';

// Define allowed pages and their corresponding files
$allowedPages = [
    'login' => 'views/auth/login.php',
    'register' => 'views/auth/register.php',
    'logout' => 'controllers/AuthController.php',
    'dashboard' => 'views/dashboard/index.php',
    'skills' => 'views/skills/index.php',
    'skill-create' => 'views/skills/create.php',
    'skill-edit' => 'views/skills/edit.php',
    'progress' => 'views/progress/index.php',
    'progress-create' => 'views/progress/create.php',
    'goals' => 'views/goals/index.php',
    'goal-create' => 'views/goals/create.php',
    'profile' => 'views/profile/index.php',
    '404' => 'views/errors/404.php',
    '500' => 'views/errors/500.php'
];

// Handle API requests
if (strpos($_SERVER['REQUEST_URI'], '/api/') !== false) {
    $apiPath = str_replace('/skill_tracker/api/', '', $_SERVER['REQUEST_URI']);
    $apiPath = explode('?', $apiPath)[0]; // Remove query parameters
    
    switch ($apiPath) {
        case 'auth':
            require_once 'api/auth.php';
            break;
        case 'skills':
            require_once 'api/skills.php';
            break;
        case 'progress':
            require_once 'api/progress.php';
            break;
        case 'dashboard':
            require_once 'api/dashboard.php';
            break;
        default:
            http_response_code(404);
            sendJsonResponse(['error' => 'API endpoint not found']);
    }
    exit;
}

// Handle logout action
if ($page === 'logout') {
    session_destroy();
    header('Location: ' . BASE_URL . '?page=login');
    exit;
}

// Check if page exists
if (!array_key_exists($page, $allowedPages)) {
    $page = '404';
}

// Check authentication for protected pages
$publicPages = ['login', 'register', '404', '500'];
if (!in_array($page, $publicPages) && !isLoggedIn()) {
    header('Location: ' . BASE_URL . '?page=login');
    exit;
}

// Redirect logged-in users away from auth pages
if (in_array($page, ['login', 'register']) && isLoggedIn()) {
    header('Location: ' . BASE_URL . '?page=dashboard');
    exit;
}

// Set page title
$pageTitle = ucfirst(str_replace('-', ' ', $page));
if ($page === 'dashboard') {
    $pageTitle = 'Dashboard';
}

// Special handling: if POST to login, call AuthController directly
if ($page === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'controllers/AuthController.php';
    $controller = new AuthController();
    $controller->login();
    exit;
}

// Special handling: if POST to register, call AuthController directly
if ($page === 'register' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'controllers/AuthController.php';
    $controller = new AuthController();
    $controller->register();
    exit;
}

// Special handling for skill-create page
if ($page === 'skill-create') {
    require_once 'controllers/SkillController.php';
    $controller = new SkillController();
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $controller->store();
        exit;
    } else {
        // GET request - get data for the form
        $data = $controller->create();
        $categories = $data['categories'] ?? [];
        // Continue to include the view file
    }
}

// Special handling for goal-create page
if ($page === 'goal-create') {
    require_once 'controllers/GoalController.php';
    $controller = new GoalController();
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $controller->store();
        exit;
    } else {
        // GET request - get data for the form
        $data = $controller->create();
        $skills = $data['skills'] ?? [];
        // Continue to include the view file
    }
}

// Special handling for skills page
if ($page === 'skills') {
    require_once 'controllers/SkillController.php';
    $controller = new SkillController();
    $skillsData = $controller->index();
    
    // Debug: Log skills data
    if (isset($_GET['debug'])) {
        error_log("Skills data: " . print_r($skillsData, true));
        error_log("User ID: " . getCurrentUserId());
        error_log("Is logged in: " . (isLoggedIn() ? 'yes' : 'no'));
    }
    
    extract($skillsData);
    // Continue to include the view file
}

// Special handling for goals page
if ($page === 'goals') {
    require_once 'controllers/GoalController.php';
    $controller = new GoalController();
    $goalsData = $controller->index();
    extract($goalsData);
    // Continue to include the view file
}

// Remove skill-view page handling - now handled inline in skills page

// Special handling for skill-edit page
if ($page === 'skill-edit') {
    require_once 'controllers/SkillController.php';
    $controller = new SkillController();
    $skillData = $controller->edit();
    extract($skillData);
    // Continue to include the view file
}

$pageFile = $allowedPages[$page];

// Handle controller actions
if (strpos($pageFile, 'controllers/') === 0) {
    require_once $pageFile;
} else {
    // Include the page file without additional layout files
    // The page files now include their own header and footer
    include $pageFile;
}
?>

