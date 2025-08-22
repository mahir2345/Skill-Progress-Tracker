<?php
/**
 * Dashboard Controller for Smart Skill Progress Tracker
 * CSE470 Software Engineering Project
 * 
 * Handles dashboard data aggregation and display
 */

require_once __DIR__ . '/../config/config.php';

class DashboardController {
    private $user;
    private $skill;
    private $progress;
    private $goal;
    private $category;
    
    public function __construct() {
        $this->user = new User();
        $this->skill = new Skill();
        $this->progress = new Progress();
        $this->goal = new Goal();
        $this->category = new Category();
    }
    
    /**
     * Get dashboard data
     */
    public function index() {
        requireLogin();
        
        $userId = getCurrentUserId();
        
        // Get user statistics
        $userStats = $this->user->getStatistics($userId);
        
        // Get recent progress entries
        $recentProgress = $this->progress->getRecentProgress($userId, 5);
        
        // Get recent skills
        $recentSkills = $this->skill->getRecentSkills($userId, 5);
        
        // Get progress statistics for the last 30 days
        $progressStats = $this->progress->getUserProgressStats($userId, 30);
        
        // Get goal statistics
        $goalStats = $this->goal->getUserGoalStats($userId);
        
        // Get upcoming goals (due in next 7 days)
        $upcomingGoals = $this->goal->getUpcomingGoals($userId, 7);
        
        // Get progress by category
        $categoryProgress = $this->progress->getProgressByCategory($userId, 30);
        
        // Get progress streaks
        $streaks = $this->progress->getProgressStreaks($userId);
        
        // Get daily progress data for chart (last 30 days)
        $dailyProgress = $this->progress->getDailyProgressData($userId, 30);
        
        // Auto-complete goals based on progress
        $autoCompletedGoals = $this->goal->autoCompleteGoals($userId);
        if ($autoCompletedGoals > 0) {
            setFlashMessage('success', "Congratulations! {$autoCompletedGoals} goal(s) have been automatically completed based on your progress.");
        }
        
        return [
            'user_stats' => $userStats,
            'recent_progress' => $recentProgress,
            'recent_skills' => $recentSkills,
            'progress_stats' => $progressStats,
            'goal_stats' => $goalStats,
            'upcoming_goals' => $upcomingGoals,
            'category_progress' => $categoryProgress,
            'streaks' => $streaks,
            'daily_progress' => $dailyProgress
        ];
    }
    
    /**
     * Get dashboard statistics (AJAX)
     */
    public function getStatistics() {
        requireLogin();
        
        $userId = getCurrentUserId();
        $period = $_GET['period'] ?? '30'; // days
        
        // Get statistics for the specified period
        $stats = [
            'user_stats' => $this->user->getStatistics($userId),
            'progress_stats' => $this->progress->getUserProgressStats($userId, (int)$period),
            'goal_stats' => $this->goal->getUserGoalStats($userId),
            'streaks' => $this->progress->getProgressStreaks($userId)
        ];
        
        sendJsonResponse($stats);
    }
    
    /**
     * Get chart data for dashboard (AJAX)
     */
    public function getChartData() {
        requireLogin();
        
        $userId = getCurrentUserId();
        $type = $_GET['type'] ?? 'daily';
        $days = (int)($_GET['days'] ?? 30);
        
        $data = [];
        
        switch ($type) {
            case 'daily':
                $data = $this->progress->getDailyProgressData($userId, $days);
                break;
            case 'category':
                $data = $this->progress->getProgressByCategory($userId, $days);
                break;
            case 'skills':
                // Get top skills by hours
                $skillsResult = $this->skill->getUserSkills($userId, [], 1, 10);
                $skills = $skillsResult['skills'];
                
                // Sort by total hours and take top 5
                usort($skills, function($a, $b) {
                    return $b['total_hours'] <=> $a['total_hours'];
                });
                
                $data = array_slice($skills, 0, 5);
                break;
            default:
                $data = [];
        }
        
        sendJsonResponse(['chart_data' => $data]);
    }
    
    /**
     * Get recent activities (AJAX)
     */
    public function getRecentActivities() {
        requireLogin();
        
        $userId = getCurrentUserId();
        $limit = (int)($_GET['limit'] ?? 10);
        
        // Get recent progress entries
        $recentProgress = $this->progress->getRecentProgress($userId, $limit);
        
        // Format activities for timeline display
        $activities = [];
        foreach ($recentProgress as $entry) {
            $activities[] = [
                'type' => 'progress',
                'title' => 'Progress logged for ' . $entry['skill_name'],
                'description' => $entry['hours_spent'] . ' hours, ' . $entry['tasks_completed'] . ' tasks',
                'date' => $entry['created_at'],
                'icon' => 'fa-chart-line',
                'color' => 'success'
            ];
        }
        
        // Sort by date (most recent first)
        usort($activities, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });
        
        sendJsonResponse(['activities' => $activities]);
    }
    
    /**
     * Get skill progress summary (AJAX)
     */
    public function getSkillProgressSummary() {
        requireLogin();
        
        $userId = getCurrentUserId();
        $skillId = (int)($_GET['skill_id'] ?? 0);
        $days = (int)($_GET['days'] ?? 30);
        
        if (!$skillId) {
            sendJsonResponse(['error' => 'Skill ID is required'], 400);
        }
        
        // Verify skill ownership
        $skill = $this->skill->findById($skillId, $userId);
        if (!$skill) {
            sendJsonResponse(['error' => 'Skill not found or access denied'], 404);
        }
        
        // Get progress summary
        $progressSummary = $this->skill->getProgressSummary($skillId, $userId, $days);
        
        // Get goals for this skill
        $goals = $this->goal->getSkillGoals($skillId, $userId);
        
        sendJsonResponse([
            'skill' => $skill,
            'progress_summary' => $progressSummary,
            'goals' => $goals
        ]);
    }
    
    /**
     * Get productivity insights (AJAX)
     */
    public function getProductivityInsights() {
        requireLogin();
        
        $userId = getCurrentUserId();
        $days = (int)($_GET['days'] ?? 30);
        
        // Get daily progress data
        $dailyData = $this->progress->getDailyProgressData($userId, $days);
        
        // Calculate insights
        $insights = [];
        
        if (!empty($dailyData)) {
            // Most productive day of week
            $dayOfWeekHours = [];
            foreach ($dailyData as $entry) {
                $dayOfWeek = date('w', strtotime($entry['date'])); // 0 = Sunday
                $dayOfWeekHours[$dayOfWeek] = ($dayOfWeekHours[$dayOfWeek] ?? 0) + $entry['daily_hours'];
            }
            
            if (!empty($dayOfWeekHours)) {
                $maxDay = array_keys($dayOfWeekHours, max($dayOfWeekHours))[0];
                $dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                $insights['most_productive_day'] = $dayNames[$maxDay];
            }
            
            // Average daily hours
            $totalHours = array_sum(array_column($dailyData, 'daily_hours'));
            $insights['avg_daily_hours'] = round($totalHours / count($dailyData), 2);
            
            // Most active week
            $weeklyData = [];
            foreach ($dailyData as $entry) {
                $week = date('Y-W', strtotime($entry['date']));
                $weeklyData[$week] = ($weeklyData[$week] ?? 0) + $entry['daily_hours'];
            }
            
            if (!empty($weeklyData)) {
                $maxWeek = array_keys($weeklyData, max($weeklyData))[0];
                $insights['most_active_week'] = $maxWeek;
                $insights['most_active_week_hours'] = max($weeklyData);
            }
        }
        
        // Get category distribution
        $categoryData = $this->progress->getProgressByCategory($userId, $days);
        if (!empty($categoryData)) {
            $topCategory = $categoryData[0];
            $insights['top_category'] = $topCategory['category_name'];
            $insights['top_category_hours'] = $topCategory['total_hours'];
        }
        
        // Get goal completion rate
        $goalStats = $this->goal->getUserGoalStats($userId);
        if ($goalStats['total_goals'] > 0) {
            $insights['goal_completion_rate'] = round(($goalStats['completed_goals'] / $goalStats['total_goals']) * 100, 1);
        }
        
        sendJsonResponse(['insights' => $insights]);
    }
    
    /**
     * Get skill recommendations (AJAX)
     */
    public function getSkillRecommendations() {
        requireLogin();
        
        $userId = getCurrentUserId();
        
        // Get user's current skills and categories
        $userSkills = $this->skill->getUserSkills($userId, [], 1, 100);
        $skills = $userSkills['skills'];
        
        // Get categories with skill counts
        $categories = $this->category->getCategoriesWithSkillCounts($userId);
        
        $recommendations = [];
        
        // Recommend categories with few or no skills
        foreach ($categories as $category) {
            if ($category['skill_count'] == 0) {
                $recommendations[] = [
                    'type' => 'new_category',
                    'title' => 'Explore ' . $category['category_name'],
                    'description' => 'You haven\'t added any skills in this category yet.',
                    'action' => 'Add a skill in ' . $category['category_name'],
                    'priority' => 'medium'
                ];
            } elseif ($category['skill_count'] == 1) {
                $recommendations[] = [
                    'type' => 'expand_category',
                    'title' => 'Expand ' . $category['category_name'],
                    'description' => 'You only have one skill in this category.',
                    'action' => 'Add more skills in ' . $category['category_name'],
                    'priority' => 'low'
                ];
            }
        }
        
        // Recommend skills that haven't been practiced recently
        $cutoffDate = date('Y-m-d', strtotime('-7 days'));
        foreach ($skills as $skill) {
            if (!$skill['last_progress_date'] || $skill['last_progress_date'] < $cutoffDate) {
                $recommendations[] = [
                    'type' => 'practice_skill',
                    'title' => 'Practice ' . $skill['skill_name'],
                    'description' => 'You haven\'t logged progress for this skill recently.',
                    'action' => 'Log progress for ' . $skill['skill_name'],
                    'priority' => 'high',
                    'skill_id' => $skill['skill_id']
                ];
            }
        }
        
        // Recommend setting goals for skills without goals
        $goalModel = new Goal();
        foreach ($skills as $skill) {
            $skillGoals = $goalModel->getSkillGoals($skill['skill_id'], $userId);
            if (empty($skillGoals)) {
                $recommendations[] = [
                    'type' => 'set_goal',
                    'title' => 'Set a goal for ' . $skill['skill_name'],
                    'description' => 'Setting goals helps track your progress more effectively.',
                    'action' => 'Create a goal for ' . $skill['skill_name'],
                    'priority' => 'medium',
                    'skill_id' => $skill['skill_id']
                ];
            }
        }
        
        // Sort by priority and limit results
        $priorityOrder = ['high' => 3, 'medium' => 2, 'low' => 1];
        usort($recommendations, function($a, $b) use ($priorityOrder) {
            return $priorityOrder[$b['priority']] - $priorityOrder[$a['priority']];
        });
        
        $recommendations = array_slice($recommendations, 0, 5);
        
        sendJsonResponse(['recommendations' => $recommendations]);
    }
    
    /**
     * Update dashboard preferences (AJAX)
     */
    public function updatePreferences() {
        requireLogin();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            sendJsonResponse(['error' => 'Method not allowed'], 405);
        }
        
        // Validate CSRF token
        if (!validateCSRFToken()) {
            sendJsonResponse(['error' => 'Invalid security token'], 403);
        }
        
        $userId = getCurrentUserId();
        $preferences = json_decode(file_get_contents('php://input'), true);
        
        // In a real application, you would store these preferences in the database
        // For now, we'll just store them in the session
        $_SESSION['dashboard_preferences'] = $preferences;
        
        logActivity('dashboard_preferences_updated', 'Dashboard preferences updated', $userId);
        
        sendJsonResponse(['success' => true, 'message' => 'Preferences updated successfully']);
    }
    
    /**
     * Get dashboard preferences (AJAX)
     */
    public function getPreferences() {
        requireLogin();
        
        // Get preferences from session (in a real app, from database)
        $preferences = $_SESSION['dashboard_preferences'] ?? [
            'show_recent_progress' => true,
            'show_upcoming_goals' => true,
            'show_category_chart' => true,
            'show_daily_chart' => true,
            'show_recommendations' => true,
            'chart_period' => 30
        ];
        
        sendJsonResponse(['preferences' => $preferences]);
    }
}

// Handle direct access to this controller
if (basename($_SERVER['PHP_SELF']) === 'DashboardController.php') {
    $controller = new DashboardController();
    $action = $_GET['action'] ?? 'index';
    
    switch ($action) {
        case 'index':
            $data = $controller->index();
            break;
        case 'get-statistics':
            $controller->getStatistics();
            break;
        case 'get-chart-data':
            $controller->getChartData();
            break;
        case 'get-recent-activities':
            $controller->getRecentActivities();
            break;
        case 'get-skill-progress-summary':
            $controller->getSkillProgressSummary();
            break;
        case 'get-productivity-insights':
            $controller->getProductivityInsights();
            break;
        case 'get-skill-recommendations':
            $controller->getSkillRecommendations();
            break;
        case 'update-preferences':
            $controller->updatePreferences();
            break;
        case 'get-preferences':
            $controller->getPreferences();
            break;
        default:
            header('Location: ' . BASE_URL . '?page=dashboard');
            exit;
    }
}
?>

