<?php
/**
 * Progress Controller for Smart Skill Progress Tracker
 * CSE470 Software Engineering Project
 * 
 * Handles progress tracking operations
 */

require_once __DIR__ . '/../config/config.php';

class ProgressController {
    private $progress;
    private $skill;
    
    public function __construct() {
        $this->progress = new Progress();
        $this->skill = new Skill();
    }
    
    /**
     * Display progress entries
     */
    public function index() {
        requireLogin();
        
        $userId = getCurrentUserId();
        $skillId = (int)($_GET['skill_id'] ?? 0);
        $page = (int)($_GET['page'] ?? 1);
        
        $filters = [
            'start_date' => $_GET['start_date'] ?? '',
            'end_date' => $_GET['end_date'] ?? '',
            'proficiency' => $_GET['proficiency'] ?? ''
        ];
        
        if ($skillId) {
            // Show progress for specific skill
            $skill = $this->skill->findById($skillId, $userId);
            if (!$skill) {
                setFlashMessage('error', 'Skill not found or access denied.');
                header('Location: ' . BASE_URL . '?page=skills');
                exit;
            }
            
            $result = $this->progress->getSkillProgress($skillId, $userId, $filters, $page);
            $progressEntries = $result['entries'];
            $pagination = $result['pagination'];
            
            return [
                'progress_entries' => $progressEntries,
                'pagination' => $pagination,
                'filters' => $filters,
                'skill' => $skill,
                'skill_id' => $skillId
            ];
        } else {
            // Show recent progress for all skills
            $recentProgress = $this->progress->getRecentProgress($userId, 20);
            $userSkills = $this->skill->getUserSkills($userId, [], 1, 100);
            
            return [
                'recent_progress' => $recentProgress,
                'skills' => $userSkills['skills'],
                'filters' => $filters
            ];
        }
    }
    
    /**
     * Show progress creation form
     */
    public function create() {
        requireLogin();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->store();
        }
        
        $userId = getCurrentUserId();
        $skillId = (int)($_GET['skill_id'] ?? 0);
        
        // Get user's skills for dropdown
        $userSkills = $this->skill->getUserSkills($userId, [], 1, 100);
        $skills = $userSkills['skills'];
        
        // If skill_id is provided, get skill details
        $selectedSkill = null;
        if ($skillId) {
            $selectedSkill = $this->skill->findById($skillId, $userId);
            if (!$selectedSkill) {
                setFlashMessage('error', 'Skill not found or access denied.');
                header('Location: ' . BASE_URL . '?page=skills');
                exit;
            }
        }
        
        return [
            'skills' => $skills,
            'selected_skill' => $selectedSkill,
            'skill_id' => $skillId
        ];
    }
    
    /**
     * Store new progress entry
     */
    public function store() {
        requireLogin();
        
        // Validate CSRF token
        if (!validateCSRFToken()) {
            setFlashMessage('error', 'Invalid security token. Please try again.');
            header('Location: ' . BASE_URL . '?page=progress-create');
            exit;
        }
        
        // Sanitize input
        $progressData = [
            'user_id' => getCurrentUserId(),
            'skill_id' => (int)($_POST['skill_id'] ?? 0),
            'hours_spent' => (float)($_POST['hours_spent'] ?? 0),
            'tasks_completed' => (int)($_POST['tasks_completed'] ?? 0),
            'proficiency_level' => sanitizeInput($_POST['proficiency_level'] ?? 'Beginner'),
            'notes' => sanitizeInput($_POST['notes'] ?? ''),
            'entry_date' => sanitizeInput($_POST['entry_date'] ?? date('Y-m-d'))
        ];
        
        // Attempt to create progress entry
        $result = $this->progress->create($progressData);
        
        if ($result['success']) {
            setFlashMessage('success', $result['message']);
            
            // Redirect to skill view or progress list
            $redirectTo = $_POST['redirect_to'] ?? 'progress';
            if ($redirectTo === 'skill' && $progressData['skill_id']) {
                header('Location: ' . BASE_URL . '?page=skill-view&id=' . $progressData['skill_id']);
            } else {
                header('Location: ' . BASE_URL . '?page=progress');
            }
        } else {
            setFlashMessage('error', $result['message']);
            header('Location: ' . BASE_URL . '?page=progress-create&skill_id=' . $progressData['skill_id']);
        }
        exit;
    }
    
    /**
     * Show progress entry details
     */
    public function show() {
        requireLogin();
        
        $entryId = (int)($_GET['id'] ?? 0);
        $userId = getCurrentUserId();
        
        if (!$entryId) {
            setFlashMessage('error', 'Invalid progress entry ID.');
            header('Location: ' . BASE_URL . '?page=progress');
            exit;
        }
        
        // Get progress entry details
        $entry = $this->progress->findById($entryId, $userId);
        
        if (!$entry) {
            setFlashMessage('error', 'Progress entry not found or access denied.');
            header('Location: ' . BASE_URL . '?page=progress');
            exit;
        }
        
        return ['entry' => $entry];
    }
    
    /**
     * Show progress entry edit form
     */
    public function edit() {
        requireLogin();
        
        $entryId = (int)($_GET['id'] ?? 0);
        $userId = getCurrentUserId();
        
        if (!$entryId) {
            setFlashMessage('error', 'Invalid progress entry ID.');
            header('Location: ' . BASE_URL . '?page=progress');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->update($entryId);
        }
        
        // Get progress entry details
        $entry = $this->progress->findById($entryId, $userId);
        
        if (!$entry) {
            setFlashMessage('error', 'Progress entry not found or access denied.');
            header('Location: ' . BASE_URL . '?page=progress');
            exit;
        }
        
        return ['entry' => $entry];
    }
    
    /**
     * Update progress entry
     */
    public function update($entryId) {
        requireLogin();
        
        // Validate CSRF token
        if (!validateCSRFToken()) {
            setFlashMessage('error', 'Invalid security token. Please try again.');
            header('Location: ' . BASE_URL . '?page=progress-edit&id=' . $entryId);
            exit;
        }
        
        $userId = getCurrentUserId();
        
        // Sanitize input
        $progressData = [
            'hours_spent' => (float)($_POST['hours_spent'] ?? 0),
            'tasks_completed' => (int)($_POST['tasks_completed'] ?? 0),
            'proficiency_level' => sanitizeInput($_POST['proficiency_level'] ?? 'Beginner'),
            'notes' => sanitizeInput($_POST['notes'] ?? ''),
            'entry_date' => sanitizeInput($_POST['entry_date'] ?? date('Y-m-d'))
        ];
        
        // Attempt to update progress entry
        $result = $this->progress->update($entryId, $userId, $progressData);
        
        if ($result['success']) {
            setFlashMessage('success', $result['message']);
            header('Location: ' . BASE_URL . '?page=progress');
        } else {
            setFlashMessage('error', $result['message']);
            header('Location: ' . BASE_URL . '?page=progress-edit&id=' . $entryId);
        }
        exit;
    }
    
    /**
     * Delete progress entry
     */
    public function delete() {
        requireLogin();
        
        // Validate CSRF token
        if (!validateCSRFToken()) {
            setFlashMessage('error', 'Invalid security token. Please try again.');
            header('Location: ' . BASE_URL . '?page=progress');
            exit;
        }
        
        $entryId = (int)($_POST['entry_id'] ?? 0);
        $userId = getCurrentUserId();
        
        if (!$entryId) {
            setFlashMessage('error', 'Invalid progress entry ID.');
            header('Location: ' . BASE_URL . '?page=progress');
            exit;
        }
        
        // Attempt to delete progress entry
        $result = $this->progress->delete($entryId, $userId);
        
        if ($result['success']) {
            setFlashMessage('success', $result['message']);
        } else {
            setFlashMessage('error', $result['message']);
        }
        
        header('Location: ' . BASE_URL . '?page=progress');
        exit;
    }
    
    /**
     * Get progress statistics (AJAX)
     */
    public function getStatistics() {
        requireLogin();
        
        $userId = getCurrentUserId();
        $days = (int)($_GET['days'] ?? 30);
        
        // Get user progress statistics
        $stats = $this->progress->getUserProgressStats($userId, $days);
        
        // Get daily progress data for charts
        $dailyData = $this->progress->getDailyProgressData($userId, $days);
        
        // Get progress by category
        $categoryData = $this->progress->getProgressByCategory($userId, $days);
        
        // Get progress streaks
        $streaks = $this->progress->getProgressStreaks($userId);
        
        sendJsonResponse([
            'statistics' => $stats,
            'daily_data' => $dailyData,
            'category_data' => $categoryData,
            'streaks' => $streaks
        ]);
    }
    
    /**
     * Get progress chart data (AJAX)
     */
    public function getChartData() {
        requireLogin();
        
        $userId = getCurrentUserId();
        $skillId = (int)($_GET['skill_id'] ?? 0);
        $days = (int)($_GET['days'] ?? 30);
        $type = $_GET['type'] ?? 'daily';
        
        if ($skillId) {
            // Verify skill ownership
            $skill = $this->skill->findById($skillId, $userId);
            if (!$skill) {
                sendJsonResponse(['error' => 'Skill not found or access denied'], 404);
            }
            
            // Get skill-specific progress data
            $data = $this->skill->getProgressSummary($skillId, $userId, $days);
        } else {
            // Get user's overall progress data
            if ($type === 'daily') {
                $data = $this->progress->getDailyProgressData($userId, $days);
            } elseif ($type === 'category') {
                $data = $this->progress->getProgressByCategory($userId, $days);
            } else {
                $data = [];
            }
        }
        
        sendJsonResponse(['chart_data' => $data]);
    }
    
    /**
     * Bulk import progress entries
     */
    public function bulkImport() {
        requireLogin();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validate CSRF token
            if (!validateCSRFToken()) {
                setFlashMessage('error', 'Invalid security token. Please try again.');
                header('Location: ' . BASE_URL . '?page=progress');
                exit;
            }
            
            $userId = getCurrentUserId();
            
            // Handle file upload
            if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
                setFlashMessage('error', 'Please select a valid CSV file to import.');
                header('Location: ' . BASE_URL . '?page=progress');
                exit;
            }
            
            $file = $_FILES['import_file'];
            
            // Validate file type
            $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if ($fileExtension !== 'csv') {
                setFlashMessage('error', 'Only CSV files are supported for import.');
                header('Location: ' . BASE_URL . '?page=progress');
                exit;
            }
            
            // Process CSV file
            $result = $this->processCsvImport($file['tmp_name'], $userId);
            
            if ($result['success']) {
                setFlashMessage('success', $result['message']);
            } else {
                setFlashMessage('error', $result['message']);
            }
            
            header('Location: ' . BASE_URL . '?page=progress');
            exit;
        }
        
        // Show import form
        $userSkills = $this->skill->getUserSkills(getCurrentUserId(), [], 1, 100);
        return ['skills' => $userSkills['skills']];
    }
    
    /**
     * Process CSV import
     */
    private function processCsvImport($filePath, $userId) {
        try {
            $handle = fopen($filePath, 'r');
            if (!$handle) {
                return ['success' => false, 'message' => 'Could not read the uploaded file.'];
            }
            
            $imported = 0;
            $errors = [];
            $lineNumber = 0;
            
            // Skip header row
            fgetcsv($handle);
            $lineNumber++;
            
            while (($data = fgetcsv($handle)) !== false) {
                $lineNumber++;
                
                // Expected format: skill_name, entry_date, hours_spent, tasks_completed, proficiency_level, notes
                if (count($data) < 5) {
                    $errors[] = "Line {$lineNumber}: Insufficient data columns";
                    continue;
                }
                
                $skillName = trim($data[0]);
                $entryDate = trim($data[1]);
                $hoursSpent = (float)trim($data[2]);
                $tasksCompleted = (int)trim($data[3]);
                $proficiencyLevel = trim($data[4]);
                $notes = trim($data[5] ?? '');
                
                // Find skill by name
                $skills = $this->skill->search($userId, $skillName);
                $skill = null;
                foreach ($skills as $s) {
                    if (strtolower($s['skill_name']) === strtolower($skillName)) {
                        $skill = $s;
                        break;
                    }
                }
                
                if (!$skill) {
                    $errors[] = "Line {$lineNumber}: Skill '{$skillName}' not found";
                    continue;
                }
                
                // Validate date
                if (!strtotime($entryDate)) {
                    $errors[] = "Line {$lineNumber}: Invalid date format";
                    continue;
                }
                
                // Create progress entry
                $progressData = [
                    'user_id' => $userId,
                    'skill_id' => $skill['skill_id'],
                    'hours_spent' => $hoursSpent,
                    'tasks_completed' => $tasksCompleted,
                    'proficiency_level' => $proficiencyLevel,
                    'notes' => $notes,
                    'entry_date' => $entryDate
                ];
                
                $result = $this->progress->create($progressData);
                if ($result['success']) {
                    $imported++;
                } else {
                    $errors[] = "Line {$lineNumber}: " . $result['message'];
                }
            }
            
            fclose($handle);
            
            $message = "Successfully imported {$imported} progress entries.";
            if (!empty($errors)) {
                $message .= " Errors: " . implode(', ', array_slice($errors, 0, 5));
                if (count($errors) > 5) {
                    $message .= " and " . (count($errors) - 5) . " more.";
                }
            }
            
            return ['success' => true, 'message' => $message];
            
        } catch (Exception $e) {
            error_log("CSV import error: " . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred while processing the file.'];
        }
    }
    
    /**
     * Export progress data
     */
    public function export() {
        requireLogin();
        
        $userId = getCurrentUserId();
        $skillId = (int)($_GET['skill_id'] ?? 0);
        $format = $_GET['format'] ?? 'csv';
        $startDate = $_GET['start_date'] ?? '';
        $endDate = $_GET['end_date'] ?? '';
        
        $filters = [];
        if ($startDate) $filters['start_date'] = $startDate;
        if ($endDate) $filters['end_date'] = $endDate;
        
        if ($skillId) {
            // Export specific skill progress
            $skill = $this->skill->findById($skillId, $userId);
            if (!$skill) {
                setFlashMessage('error', 'Skill not found or access denied.');
                header('Location: ' . BASE_URL . '?page=progress');
                exit;
            }
            
            $result = $this->progress->getSkillProgress($skillId, $userId, $filters, 1, 1000);
            $progressEntries = $result['entries'];
            $filename = 'progress_' . $skill['skill_name'] . '_' . date('Y-m-d') . '.csv';
        } else {
            // Export all progress
            $progressEntries = $this->progress->getRecentProgress($userId, 1000);
            $filename = 'all_progress_' . date('Y-m-d') . '.csv';
        }
        
        if ($format === 'csv') {
            $this->exportToCsv($progressEntries, $filename);
        } else {
            setFlashMessage('error', 'Unsupported export format.');
            header('Location: ' . BASE_URL . '?page=progress');
            exit;
        }
    }
    
    /**
     * Export progress data to CSV
     */
    private function exportToCsv($progressEntries, $filename) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // Write header
        fputcsv($output, ['Skill Name', 'Category', 'Entry Date', 'Hours Spent', 'Tasks Completed', 'Proficiency Level', 'Notes', 'Created At']);
        
        // Write data
        foreach ($progressEntries as $entry) {
            fputcsv($output, [
                $entry['skill_name'],
                $entry['category_name'] ?? '',
                $entry['entry_date'],
                $entry['hours_spent'],
                $entry['tasks_completed'],
                $entry['proficiency_level'],
                $entry['notes'],
                $entry['created_at']
            ]);
        }
        
        fclose($output);
        exit;
    }
}

// Handle direct access to this controller
if (basename($_SERVER['PHP_SELF']) === 'ProgressController.php') {
    $controller = new ProgressController();
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
        case 'get-statistics':
            $controller->getStatistics();
            break;
        case 'get-chart-data':
            $controller->getChartData();
            break;
        case 'bulk-import':
            $data = $controller->bulkImport();
            break;
        case 'export':
            $controller->export();
            break;
        default:
            header('Location: ' . BASE_URL . '?page=progress');
            exit;
    }
}
?>

