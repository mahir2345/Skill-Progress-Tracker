<?php
/**
 * Goal Controller for Smart Skill Progress Tracker
 * CSE470 Software Engineering Project
 * 
 * Handles goal creation, editing, and management
 */

require_once __DIR__ . '/../config/config.php';

class GoalController {
    private $goal;
    private $skill;
    
    public function __construct() {
        $this->goal = new Goal();
        $this->skill = new Skill();
    }
    
    /**
     * Show goals list
     */
    public function index() {
        requireLogin();
        
        $page = (int)($_GET['page'] ?? 1);
        $limit = 10;
        $filters = [];
        
        $goalsData = $this->goal->getUserGoals($_SESSION['user_id'], $filters, $page, $limit);
        
        return [
            'goals' => $goalsData['goals'],
            'pagination' => $goalsData['pagination']
        ];
    }
    
    /**
     * Show goal creation form
     */
    public function create() {
        requireLogin();
        
        // Get user's skills for dropdown
        $skillsData = $this->skill->getUserSkills($_SESSION['user_id']);
        
        return [
            'skills' => $skillsData['skills'] ?? []
        ];
    }

    /**
     * Store new goal
     */
    public function store() {
        requireLogin();
        
        // Validate CSRF token
        if (!validateCSRFToken()) {
            setFlashMessage('error', 'Invalid security token. Please try again.');
            header('Location: ' . BASE_URL . '?page=goal-create');
            exit;
        }
        
        $goalData = [
            'user_id' => $_SESSION['user_id'],
            'skill_id' => (int)$_POST['skill_id'],
            'target_proficiency' => $_POST['target_proficiency'],
            'target_date' => $_POST['target_date'],
            'description' => trim($_POST['description'] ?? '')
        ];
        
        $result = $this->goal->create($goalData);
        
        if ($result['success']) {
            setFlashMessage('success', $result['message']);
            header('Location: ' . BASE_URL . '?page=goals');
        } else {
            setFlashMessage('error', $result['message']);
            header('Location: ' . BASE_URL . '?page=goal-create');
        }
        exit;
    }
}

// Handle direct access to this controller
if (basename($_SERVER['PHP_SELF']) === 'GoalController.php') {
    $controller = new GoalController();
    $action = $_GET['action'] ?? 'index';
    
    switch ($action) {
        case 'index':
            $data = $controller->index();
            break;
        case 'create':
            $data = $controller->create();
            break;
        default:
            $data = $controller->index();
            break;
    }
    
    // Return JSON for API calls
    if (isset($_GET['api'])) {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}