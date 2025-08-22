<?php
/**
 * Goal Model for Smart Skill Progress Tracker
 * CSE470 Software Engineering Project
 * 
 * Handles goal setting and tracking for skills
 */

class Goal {
    private $pdo;
    
    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }
    
    /**
     * Create a new goal
     * @param array $goalData
     * @return array ['success' => bool, 'message' => string, 'goal_id' => int]
     */
    public function create($goalData) {
        try {
            // Validate input
            $validation = $this->validateGoalData($goalData);
            if (!$validation['valid']) {
                return ['success' => false, 'message' => implode(', ', $validation['errors'])];
            }
            
            // Verify skill ownership
            $skill = new Skill();
            $skillData = $skill->findById($goalData['skill_id'], $goalData['user_id']);
            if (!$skillData) {
                return ['success' => false, 'message' => 'Skill not found or access denied'];
            }
            
            $sql = "INSERT INTO goals (skill_id, target_proficiency, target_date, target_hours, description) 
                    VALUES (:skill_id, :target_proficiency, :target_date, :target_hours, :description)";
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                ':skill_id' => $goalData['skill_id'],
                ':target_proficiency' => $goalData['target_proficiency'],
                ':target_date' => $goalData['target_date'] ?? null,
                ':target_hours' => $goalData['target_hours'] ?? null,
                ':description' => $goalData['description'] ?? ''
            ]);
            
            if ($result) {
                $goalId = $this->pdo->lastInsertId();
                logActivity('goal_created', 'New goal created for skill: ' . $skillData['skill_name'], $goalData['user_id']);
                
                return [
                    'success' => true,
                    'message' => 'Goal created successfully',
                    'goal_id' => $goalId
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to create goal'];
            }
            
        } catch (PDOException $e) {
            error_log("Goal creation error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }
    
    /**
     * Get goals for a user
     * @param int $userId
     * @param array $filters (optional)
     * @param int $page (optional)
     * @param int $limit (optional)
     * @return array
     */
    public function getUserGoals($userId, $filters = [], $page = 1, $limit = ITEMS_PER_PAGE) {
        try {
            $offset = ($page - 1) * $limit;
            
            // Build WHERE clause
            $whereConditions = ['s.user_id = :user_id'];
            $params = [':user_id' => $userId];
            
            if (isset($filters['is_completed'])) {
                $whereConditions[] = 'g.is_completed = :is_completed';
                $params[':is_completed'] = $filters['is_completed'] ? 1 : 0;
            }
            
            if (!empty($filters['skill_id'])) {
                $whereConditions[] = 'g.skill_id = :skill_id';
                $params[':skill_id'] = $filters['skill_id'];
            }
            
            if (!empty($filters['category_id'])) {
                $whereConditions[] = 's.category_id = :category_id';
                $params[':category_id'] = $filters['category_id'];
            }
            
            $whereClause = implode(' AND ', $whereConditions);
            
            // Get total count
            $countSql = "SELECT COUNT(*) FROM goals g 
                         JOIN skills s ON g.skill_id = s.skill_id 
                         WHERE {$whereClause}";
            $countStmt = $this->pdo->prepare($countSql);
            $countStmt->execute($params);
            $totalCount = $countStmt->fetchColumn();
            
            // Get goals with progress data
            $sql = "SELECT g.*, s.skill_name, c.category_name,
                           COALESCE(SUM(pe.hours_spent), 0) as current_hours,
                           CASE 
                               WHEN g.target_hours > 0 THEN (COALESCE(SUM(pe.hours_spent), 0) / g.target_hours) * 100
                               ELSE 0
                           END as hours_progress_percentage,
                           s.current_proficiency
                    FROM goals g
                    JOIN skills s ON g.skill_id = s.skill_id
                    JOIN categories c ON s.category_id = c.category_id
                    LEFT JOIN progress_entries pe ON g.skill_id = pe.skill_id 
                        AND pe.entry_date >= DATE(g.created_at)
                    WHERE {$whereClause}
                    GROUP BY g.goal_id, g.skill_id, g.target_proficiency, g.target_date, g.target_hours, 
                             g.description, g.is_completed, g.created_at, g.completed_at, 
                             s.skill_name, s.current_proficiency, c.category_name
                    ORDER BY g.is_completed ASC, g.target_date ASC, g.created_at DESC
                    LIMIT :limit OFFSET :offset";
            
            $params[':limit'] = $limit;
            $params[':offset'] = $offset;
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $goals = $stmt->fetchAll();
            
            return [
                'goals' => $goals,
                'pagination' => paginate($totalCount, $page, $limit)
            ];
            
        } catch (PDOException $e) {
            error_log("Get user goals error: " . $e->getMessage());
            return ['goals' => [], 'pagination' => []];
        }
    }
    
    /**
     * Get goal by ID
     * @param int $goalId
     * @param int $userId
     * @return array|null
     */
    public function findById($goalId, $userId) {
        try {
            $sql = "SELECT g.*, s.skill_name, s.user_id, c.category_name,
                           COALESCE(SUM(pe.hours_spent), 0) as current_hours,
                           CASE 
                               WHEN g.target_hours > 0 THEN (COALESCE(SUM(pe.hours_spent), 0) / g.target_hours) * 100
                               ELSE 0
                           END as hours_progress_percentage,
                           s.current_proficiency
                    FROM goals g
                    JOIN skills s ON g.skill_id = s.skill_id
                    JOIN categories c ON s.category_id = c.category_id
                    LEFT JOIN progress_entries pe ON g.skill_id = pe.skill_id 
                        AND pe.entry_date >= DATE(g.created_at)
                    WHERE g.goal_id = :goal_id AND s.user_id = :user_id
                    GROUP BY g.goal_id, g.skill_id, g.target_proficiency, g.target_date, g.target_hours, 
                             g.description, g.is_completed, g.created_at, g.completed_at, 
                             s.skill_name, s.user_id, s.current_proficiency, c.category_name";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':goal_id' => $goalId,
                ':user_id' => $userId
            ]);
            
            return $stmt->fetch();
            
        } catch (PDOException $e) {
            error_log("Find goal error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Update goal
     * @param int $goalId
     * @param int $userId
     * @param array $goalData
     * @return array ['success' => bool, 'message' => string]
     */
    public function update($goalId, $userId, $goalData) {
        try {
            // Validate input
            $validation = $this->validateGoalData($goalData, $goalId);
            if (!$validation['valid']) {
                return ['success' => false, 'message' => implode(', ', $validation['errors'])];
            }
            
            // Check if goal exists and belongs to user
            $goal = $this->findById($goalId, $userId);
            if (!$goal) {
                return ['success' => false, 'message' => 'Goal not found or access denied'];
            }
            
            $sql = "UPDATE goals SET target_proficiency = :target_proficiency, target_date = :target_date,
                    target_hours = :target_hours, description = :description
                    WHERE goal_id = :goal_id";
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                ':target_proficiency' => $goalData['target_proficiency'],
                ':target_date' => $goalData['target_date'] ?? null,
                ':target_hours' => $goalData['target_hours'] ?? null,
                ':description' => $goalData['description'] ?? '',
                ':goal_id' => $goalId
            ]);
            
            if ($result) {
                logActivity('goal_updated', 'Goal updated for skill: ' . $goal['skill_name'], $userId);
                return ['success' => true, 'message' => 'Goal updated successfully'];
            } else {
                return ['success' => false, 'message' => 'Failed to update goal'];
            }
            
        } catch (PDOException $e) {
            error_log("Goal update error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }
    
    /**
     * Mark goal as completed
     * @param int $goalId
     * @param int $userId
     * @return array ['success' => bool, 'message' => string]
     */
    public function markCompleted($goalId, $userId) {
        try {
            // Check if goal exists and belongs to user
            $goal = $this->findById($goalId, $userId);
            if (!$goal) {
                return ['success' => false, 'message' => 'Goal not found or access denied'];
            }
            
            if ($goal['is_completed']) {
                return ['success' => false, 'message' => 'Goal is already completed'];
            }
            
            $sql = "UPDATE goals SET is_completed = 1, completed_at = CURRENT_TIMESTAMP 
                    WHERE goal_id = :goal_id";
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([':goal_id' => $goalId]);
            
            if ($result) {
                logActivity('goal_completed', 'Goal completed for skill: ' . $goal['skill_name'], $userId);
                return ['success' => true, 'message' => 'Goal marked as completed'];
            } else {
                return ['success' => false, 'message' => 'Failed to mark goal as completed'];
            }
            
        } catch (PDOException $e) {
            error_log("Goal completion error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }
    
    /**
     * Mark goal as incomplete
     * @param int $goalId
     * @param int $userId
     * @return array ['success' => bool, 'message' => string]
     */
    public function markIncomplete($goalId, $userId) {
        try {
            // Check if goal exists and belongs to user
            $goal = $this->findById($goalId, $userId);
            if (!$goal) {
                return ['success' => false, 'message' => 'Goal not found or access denied'];
            }
            
            if (!$goal['is_completed']) {
                return ['success' => false, 'message' => 'Goal is already incomplete'];
            }
            
            $sql = "UPDATE goals SET is_completed = 0, completed_at = NULL 
                    WHERE goal_id = :goal_id";
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([':goal_id' => $goalId]);
            
            if ($result) {
                logActivity('goal_reopened', 'Goal reopened for skill: ' . $goal['skill_name'], $userId);
                return ['success' => true, 'message' => 'Goal marked as incomplete'];
            } else {
                return ['success' => false, 'message' => 'Failed to mark goal as incomplete'];
            }
            
        } catch (PDOException $e) {
            error_log("Goal reopen error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }
    
    /**
     * Delete goal
     * @param int $goalId
     * @param int $userId
     * @return array ['success' => bool, 'message' => string]
     */
    public function delete($goalId, $userId) {
        try {
            // Check if goal exists and belongs to user
            $goal = $this->findById($goalId, $userId);
            if (!$goal) {
                return ['success' => false, 'message' => 'Goal not found or access denied'];
            }
            
            $sql = "DELETE FROM goals WHERE goal_id = :goal_id";
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([':goal_id' => $goalId]);
            
            if ($result) {
                logActivity('goal_deleted', 'Goal deleted for skill: ' . $goal['skill_name'], $userId);
                return ['success' => true, 'message' => 'Goal deleted successfully'];
            } else {
                return ['success' => false, 'message' => 'Failed to delete goal'];
            }
            
        } catch (PDOException $e) {
            error_log("Goal deletion error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }
    
    /**
     * Get goals for a specific skill
     * @param int $skillId
     * @param int $userId
     * @return array
     */
    public function getSkillGoals($skillId, $userId) {
        try {
            // Verify skill ownership
            $skill = new Skill();
            $skillData = $skill->findById($skillId, $userId);
            if (!$skillData) {
                return [];
            }
            
            $sql = "SELECT g.*,
                           COALESCE(SUM(pe.hours_spent), 0) as current_hours,
                           CASE 
                               WHEN g.target_hours > 0 THEN (COALESCE(SUM(pe.hours_spent), 0) / g.target_hours) * 100
                               ELSE 0
                           END as hours_progress_percentage
                    FROM goals g
                    LEFT JOIN progress_entries pe ON g.skill_id = pe.skill_id 
                        AND pe.entry_date >= DATE(g.created_at)
                    WHERE g.skill_id = :skill_id
                    GROUP BY g.goal_id, g.skill_id, g.target_proficiency, g.target_date, g.target_hours, 
                             g.description, g.is_completed, g.created_at, g.completed_at
                    ORDER BY g.is_completed ASC, g.target_date ASC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':skill_id' => $skillId]);
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            error_log("Get skill goals error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get goal statistics for user
     * @param int $userId
     * @return array
     */
    public function getUserGoalStats($userId) {
        try {
            $sql = "SELECT 
                        COUNT(*) as total_goals,
                        COUNT(CASE WHEN is_completed = 1 THEN 1 END) as completed_goals,
                        COUNT(CASE WHEN is_completed = 0 THEN 1 END) as active_goals,
                        COUNT(CASE WHEN is_completed = 0 AND target_date < CURDATE() THEN 1 END) as overdue_goals,
                        COUNT(CASE WHEN is_completed = 0 AND target_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as due_soon_goals
                    FROM goals g
                    JOIN skills s ON g.skill_id = s.skill_id
                    WHERE s.user_id = :user_id";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':user_id' => $userId]);
            return $stmt->fetch();
            
        } catch (PDOException $e) {
            error_log("Goal statistics error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get upcoming goals (due soon)
     * @param int $userId
     * @param int $days
     * @return array
     */
    public function getUpcomingGoals($userId, $days = 7) {
        try {
            $sql = "SELECT g.*, s.skill_name, c.category_name
                    FROM goals g
                    JOIN skills s ON g.skill_id = s.skill_id
                    JOIN categories c ON s.category_id = c.category_id
                    WHERE s.user_id = :user_id 
                    AND g.is_completed = 0
                    AND g.target_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :days DAY)
                    ORDER BY g.target_date ASC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':user_id' => $userId,
                ':days' => $days
            ]);
            
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            error_log("Upcoming goals error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Check and auto-complete goals based on progress
     * @param int $userId
     * @return int Number of goals auto-completed
     */
    public function autoCompleteGoals($userId) {
        try {
            $autoCompleted = 0;
            
            // Get active goals that might be completed
            $sql = "SELECT g.goal_id, g.target_proficiency, g.target_hours, s.current_proficiency,
                           COALESCE(SUM(pe.hours_spent), 0) as current_hours
                    FROM goals g
                    JOIN skills s ON g.skill_id = s.skill_id
                    LEFT JOIN progress_entries pe ON g.skill_id = pe.skill_id 
                        AND pe.entry_date >= DATE(g.created_at)
                    WHERE s.user_id = :user_id AND g.is_completed = 0
                    GROUP BY g.goal_id, g.target_proficiency, g.target_hours, s.current_proficiency";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':user_id' => $userId]);
            $goals = $stmt->fetchAll();
            
            foreach ($goals as $goal) {
                $shouldComplete = false;
                
                // Check proficiency goal
                $proficiencyLevels = ['Beginner' => 1, 'Intermediate' => 2, 'Advanced' => 3, 'Expert' => 4];
                $targetLevel = $proficiencyLevels[$goal['target_proficiency']] ?? 1;
                $currentLevel = $proficiencyLevels[$goal['current_proficiency']] ?? 1;
                
                if ($currentLevel >= $targetLevel) {
                    $shouldComplete = true;
                }
                
                // Check hours goal
                if ($goal['target_hours'] && $goal['current_hours'] >= $goal['target_hours']) {
                    $shouldComplete = true;
                }
                
                if ($shouldComplete) {
                    $this->markCompleted($goal['goal_id'], $userId);
                    $autoCompleted++;
                }
            }
            
            return $autoCompleted;
            
        } catch (PDOException $e) {
            error_log("Auto complete goals error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Validate goal data
     * @param array $goalData
     * @param int $excludeGoalId (optional)
     * @return array ['valid' => bool, 'errors' => array]
     */
    private function validateGoalData($goalData, $excludeGoalId = null) {
        $errors = [];
        
        // Required fields
        $required = ['skill_id', 'target_proficiency'];
        foreach ($required as $field) {
            if (empty($goalData[$field])) {
                $errors[] = ucfirst(str_replace('_', ' ', $field)) . " is required";
            }
        }
        
        // Proficiency validation
        if (!empty($goalData['target_proficiency'])) {
            $validProficiencies = ['Beginner', 'Intermediate', 'Advanced', 'Expert'];
            if (!in_array($goalData['target_proficiency'], $validProficiencies)) {
                $errors[] = "Invalid target proficiency level";
            }
        }
        
        // Target hours validation
        if (isset($goalData['target_hours'])) {
            if ($goalData['target_hours'] !== null && $goalData['target_hours'] !== '') {
                if (!is_numeric($goalData['target_hours']) || $goalData['target_hours'] <= 0) {
                    $errors[] = "Target hours must be a positive number";
                }
                if ($goalData['target_hours'] > 10000) {
                    $errors[] = "Target hours seems unreasonably high";
                }
            }
        }
        
        // Target date validation
        if (isset($goalData['target_date'])) {
            if ($goalData['target_date'] !== null && $goalData['target_date'] !== '') {
                if (!strtotime($goalData['target_date'])) {
                    $errors[] = "Invalid target date";
                } else {
                    $targetDate = new DateTime($goalData['target_date']);
                    $today = new DateTime();
                    if ($targetDate <= $today) {
                        $errors[] = "Target date must be in the future";
                    }
                }
            }
        }
        
        // Description validation (optional)
        if (isset($goalData['description']) && strlen($goalData['description']) > 1000) {
            $errors[] = "Description must not exceed 1000 characters";
        }
        
        // At least one target must be specified
        if (empty($goalData['target_hours']) && empty($goalData['target_date'])) {
            $errors[] = "Please specify at least a target date or target hours";
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}
?>

