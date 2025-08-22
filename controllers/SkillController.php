<?php
/**
 * Skill Controller for Smart Skill Progress Tracker
 * CSE470 Software Engineering Project
 * 
 * Handles skill management operations
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Goal.php';
require_once __DIR__ . '/../models/Progress.php';

class SkillController {
    private $skill;
    private $category;
    
    public function __construct() {
        $this->skill = new Skill();
        $this->category = new Category();
    }
    
    /**
     * Display skills list
     */
    public function index() {
        requireLogin();
        
        $userId = getCurrentUserId();
        $page = (int)($_GET['page'] ?? 1);
        $filters = [
            'category_id' => $_GET['category'] ?? '',
            'proficiency' => $_GET['proficiency'] ?? '',
            'search' => $_GET['search'] ?? ''
        ];
        
        // Get skills with pagination
        $result = $this->skill->getUserSkills($userId, $filters, $page);
        $skills = $result['skills'];
        $pagination = $result['pagination'];
        
        // Get categories for filter dropdown
        $categories = $this->category->getAll();
        
        return [
            'skills' => $skills,
            'categories' => $categories,
            'pagination' => $pagination,
            'filters' => $filters
        ];
    }
    
    /**
     * Show skill creation form
     */
    public function create() {
        requireLogin();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->store();
        }
        
        // Get categories for dropdown
        $categories = $this->category->getAll();
        
        return ['categories' => $categories];
    }
    
    /**
     * Store new skill
     */
    public function store() {
        requireLogin();
        
        // Validate CSRF token
        if (!validateCSRFToken()) {
            setFlashMessage('error', 'Invalid security token. Please try again.');
            header('Location: ' . BASE_URL . '?page=skill-create');
            exit;
        }
        
        // Sanitize input
        $skillData = [
            'user_id' => getCurrentUserId(),
            'skill_name' => sanitizeInput($_POST['skill_name'] ?? ''),
            'description' => sanitizeInput($_POST['description'] ?? ''),
            'category_id' => (int)($_POST['category_id'] ?? 0),
            'current_proficiency' => sanitizeInput($_POST['current_proficiency'] ?? 'Beginner')
        ];
        
        // Attempt to create skill
        $result = $this->skill->create($skillData);
        
        if ($result['success']) {
            setFlashMessage('success', $result['message']);
            header('Location: ' . BASE_URL . '?page=skills');
        } else {
            setFlashMessage('error', $result['message']);
            header('Location: ' . BASE_URL . '?page=skill-create');
        }
        exit;
    }
    
    /**
     * Show skill details
     */
    public function show() {
        requireLogin();
        
        $skillId = (int)($_GET['id'] ?? 0);
        $userId = getCurrentUserId();
        
        if (!$skillId) {
            setFlashMessage('error', 'Invalid skill ID.');
            header('Location: ' . BASE_URL . '?page=skills');
            exit;
        }
        
        // Get skill details
        $skill = $this->skill->findById($skillId, $userId);
        
        if (!$skill) {
            setFlashMessage('error', 'Skill not found or access denied.');
            header('Location: ' . BASE_URL . '?page=skills');
            exit;
        }
        
        // Get progress summary
        $progressSummary = $this->skill->getProgressSummary($skillId, $userId, 30);
        
        // Get goals for this skill
        $goalModel = new Goal();
        $goals = $goalModel->getSkillGoals($skillId, $userId);
        
        // Get recent progress entries
        $progressModel = new Progress();
        $recentProgress = $progressModel->getSkillProgress($skillId, $userId, [], 1, 5);
        
        // Get all user skills for the bottom section
        $allSkillsData = $this->skill->getUserSkills($userId, [], 1, 50); // Get up to 50 skills
        
        return [
            'skill' => $skill,
            'progress_summary' => $progressSummary,
            'goals' => $goals,
            'recent_progress' => $recentProgress['entries'],
            'all_skills' => $allSkillsData['skills']
        ];
    }
    
    /**
     * View skill details (alias for show method)
     */
    public function view() {
        return $this->show();
    }
    
    /**
     * Show skill edit form
     */
    public function edit() {
        requireLogin();
        
        $skillId = (int)($_GET['id'] ?? 0);
        $userId = getCurrentUserId();
        
        if (!$skillId) {
            setFlashMessage('error', 'Invalid skill ID.');
            header('Location: ' . BASE_URL . '?page=skills');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->update($skillId);
        }
        
        // Get skill details
        $skill = $this->skill->findById($skillId, $userId);
        
        if (!$skill) {
            setFlashMessage('error', 'Skill not found or access denied.');
            header('Location: ' . BASE_URL . '?page=skills');
            exit;
        }
        
        // Get categories for dropdown
        $categories = $this->category->getAll();
        
        return [
            'skill' => $skill,
            'categories' => $categories
        ];
    }
    
    /**
     * Update skill
     */
    public function update($skillId) {
        requireLogin();
        
        // Validate CSRF token
        if (!validateCSRFToken()) {
            setFlashMessage('error', 'Invalid security token. Please try again.');
            header('Location: ' . BASE_URL . '?page=skill-edit&id=' . $skillId);
            exit;
        }
        
        $userId = getCurrentUserId();
        
        // Sanitize input
        $skillData = [
            'skill_name' => sanitizeInput($_POST['skill_name'] ?? ''),
            'description' => sanitizeInput($_POST['description'] ?? ''),
            'category_id' => (int)($_POST['category_id'] ?? 0),
            'current_proficiency' => sanitizeInput($_POST['current_proficiency'] ?? 'Beginner')
        ];
        
        // Attempt to update skill
        $result = $this->skill->update($skillId, $userId, $skillData);
        
        if ($result['success']) {
            setFlashMessage('success', $result['message']);
            header('Location: ' . BASE_URL . '?page=skill-view&id=' . $skillId);
        } else {
            setFlashMessage('error', $result['message']);
            header('Location: ' . BASE_URL . '?page=skill-edit&id=' . $skillId);
        }
        exit;
    }
    
    /**
     * Delete skill
     */
    public function delete() {
        requireLogin();
        
        // Validate CSRF token
        if (!validateCSRFToken()) {
            setFlashMessage('error', 'Invalid security token. Please try again.');
            header('Location: ' . BASE_URL . '?page=skills');
            exit;
        }
        
        $skillId = (int)($_POST['skill_id'] ?? 0);
        $userId = getCurrentUserId();
        
        if (!$skillId) {
            setFlashMessage('error', 'Invalid skill ID.');
            header('Location: ' . BASE_URL . '?page=skills');
            exit;
        }
        
        // Attempt to delete skill
        $result = $this->skill->delete($skillId, $userId);
        
        if ($result['success']) {
            setFlashMessage('success', $result['message']);
        } else {
            setFlashMessage('error', $result['message']);
        }
        
        header('Location: ' . BASE_URL . '?page=skills');
        exit;
    }
    
    /**
     * Search skills
     */
    public function search() {
        requireLogin();
        
        $userId = getCurrentUserId();
        $searchTerm = sanitizeInput($_GET['q'] ?? '');
        
        if (empty($searchTerm)) {
            return ['skills' => []];
        }
        
        $skills = $this->skill->search($userId, $searchTerm);
        
        return ['skills' => $skills, 'search_term' => $searchTerm];
    }
    
    /**
     * Get skills by category (AJAX)
     */
    public function getByCategory() {
        requireLogin();
        
        $categoryId = (int)($_GET['category_id'] ?? 0);
        $userId = getCurrentUserId();
        
        if (!$categoryId) {
            sendJsonResponse(['error' => 'Invalid category ID'], 400);
        }
        
        $skills = $this->skill->getByCategory($categoryId, $userId);
        sendJsonResponse(['skills' => $skills]);
    }
    
    /**
     * Get skill statistics (AJAX)
     */
    public function getStatistics() {
        requireLogin();
        
        $skillId = (int)($_GET['skill_id'] ?? 0);
        $userId = getCurrentUserId();
        
        if (!$skillId) {
            sendJsonResponse(['error' => 'Invalid skill ID'], 400);
        }
        
        // Verify skill ownership
        $skill = $this->skill->findById($skillId, $userId);
        if (!$skill) {
            sendJsonResponse(['error' => 'Skill not found or access denied'], 404);
        }
        
        // Get progress summary for different periods
        $stats = [
            'last_7_days' => $this->skill->getProgressSummary($skillId, $userId, 7),
            'last_30_days' => $this->skill->getProgressSummary($skillId, $userId, 30),
            'last_90_days' => $this->skill->getProgressSummary($skillId, $userId, 90),
            'all_time' => $this->skill->getProgressSummary($skillId, $userId, 365)
        ];
        
        sendJsonResponse(['statistics' => $stats]);
    }
    
    /**
     * Export skill data
     */
    public function export() {
        requireLogin();
        
        $skillId = (int)($_GET['skill_id'] ?? 0);
        $userId = getCurrentUserId();
        $format = $_GET['format'] ?? 'csv';
        
        if (!$skillId) {
            setFlashMessage('error', 'Invalid skill ID.');
            header('Location: ' . BASE_URL . '?page=skills');
            exit;
        }
        
        // Verify skill ownership
        $skill = $this->skill->findById($skillId, $userId);
        if (!$skill) {
            setFlashMessage('error', 'Skill not found or access denied.');
            header('Location: ' . BASE_URL . '?page=skills');
            exit;
        }
        
        // Get all progress entries for this skill
        $progressModel = new Progress();
        $progressData = $progressModel->getSkillProgress($skillId, $userId, [], 1, 1000);
        
        if ($format === 'csv') {
            $this->exportToCsv($skill, $progressData['entries']);
        } else {
            setFlashMessage('error', 'Unsupported export format.');
            header('Location: ' . BASE_URL . '?page=skill-view&id=' . $skillId);
            exit;
        }
    }
    
    /**
     * Export skill data to CSV
     */
    private function exportToCsv($skill, $progressEntries) {
        $filename = 'skill_' . $skill['skill_id'] . '_' . date('Y-m-d') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // Write skill information header
        fputcsv($output, ['Skill Export Report']);
        fputcsv($output, ['Skill Name', $skill['skill_name']]);
        fputcsv($output, ['Category', $skill['category_name']]);
        fputcsv($output, ['Current Proficiency', $skill['current_proficiency']]);
        fputcsv($output, ['Total Hours', $skill['total_hours']]);
        fputcsv($output, ['Total Tasks', $skill['total_tasks']]);
        fputcsv($output, ['Total Entries', $skill['total_entries']]);
        fputcsv($output, ['Export Date', date('Y-m-d H:i:s')]);
        fputcsv($output, []); // Empty row
        
        // Write progress entries header
        fputcsv($output, ['Progress Entries']);
        fputcsv($output, ['Date', 'Hours Spent', 'Tasks Completed', 'Proficiency Level', 'Notes']);
        
        // Write progress entries
        foreach ($progressEntries as $entry) {
            fputcsv($output, [
                $entry['entry_date'],
                $entry['hours_spent'],
                $entry['tasks_completed'],
                $entry['proficiency_level'],
                $entry['notes']
            ]);
        }
        
        fclose($output);
        exit;
    }
}

// Handle direct access to this controller
if (basename($_SERVER['PHP_SELF']) === 'SkillController.php') {
    $controller = new SkillController();
    $action = $_GET['action'] ?? 'index';
    
    switch ($action) {
        case 'index':
            $data = $controller->index();
            break;
        case 'create':
            $data = $controller->create();
            break;
        case 'show':
            $data = $controller->show();
            break;
        case 'edit':
            $data = $controller->edit();
            break;
        case 'delete':
            $controller->delete();
            break;
        case 'search':
            $data = $controller->search();
            break;
        case 'get-by-category':
            $controller->getByCategory();
            break;
        case 'get-statistics':
            $controller->getStatistics();
            break;
        case 'export':
            $controller->export();
            break;
        default:
            header('Location: ' . BASE_URL . '?page=skills');
            exit;
    }
}
?>

