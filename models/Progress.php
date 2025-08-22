<?php
/**
 * Progress Model for Smart Skill Progress Tracker
 * CSE470 Software Engineering Project
 * 
 * Handles progress tracking and related operations
 */

class Progress {
    private $pdo;
    
    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }
    
    /**
     * Create a new progress entry
     * @param array $progressData
     * @return array ['success' => bool, 'message' => string, 'entry_id' => int]
     */
    public function create($progressData) {
        try {
            // Validate input
            $validation = $this->validateProgressData($progressData);
            if (!$validation['valid']) {
                return ['success' => false, 'message' => implode(', ', $validation['errors'])];
            }
            
            // Verify skill ownership
            $skill = new Skill();
            $skillData = $skill->findById($progressData['skill_id'], $progressData['user_id']);
            if (!$skillData) {
                return ['success' => false, 'message' => 'Skill not found or access denied'];
            }
            
            $sql = "INSERT INTO progress_entries (skill_id, hours_spent, tasks_completed, proficiency_level, notes, entry_date) 
                    VALUES (:skill_id, :hours_spent, :tasks_completed, :proficiency_level, :notes, :entry_date)";
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                ':skill_id' => $progressData['skill_id'],
                ':hours_spent' => $progressData['hours_spent'] ?? 0,
                ':tasks_completed' => $progressData['tasks_completed'] ?? 0,
                ':proficiency_level' => $progressData['proficiency_level'],
                ':notes' => $progressData['notes'] ?? '',
                ':entry_date' => $progressData['entry_date'] ?? date('Y-m-d')
            ]);
            
            if ($result) {
                $entryId = $this->pdo->lastInsertId();
                
                // Update skill's current proficiency if this is the latest entry
                $this->updateSkillProficiency($progressData['skill_id']);
                
                logActivity('progress_logged', 'Progress logged for skill: ' . $skillData['skill_name'], $progressData['user_id']);
                
                return [
                    'success' => true,
                    'message' => 'Progress logged successfully',
                    'entry_id' => $entryId
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to log progress'];
            }
            
        } catch (PDOException $e) {
            error_log("Progress creation error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }
    
    /**
     * Get progress entries for a skill
     * @param int $skillId
     * @param int $userId
     * @param array $filters (optional)
     * @param int $page (optional)
     * @param int $limit (optional)
     * @return array
     */
    public function getSkillProgress($skillId, $userId, $filters = [], $page = 1, $limit = ITEMS_PER_PAGE) {
        try {
            // Verify skill ownership
            $skill = new Skill();
            $skillData = $skill->findById($skillId, $userId);
            if (!$skillData) {
                return ['entries' => [], 'pagination' => []];
            }
            
            $offset = ($page - 1) * $limit;
            
            // Build WHERE clause
            $whereConditions = ['pe.skill_id = :skill_id'];
            $params = [':skill_id' => $skillId];
            
            if (!empty($filters['start_date'])) {
                $whereConditions[] = 'pe.entry_date >= :start_date';
                $params[':start_date'] = $filters['start_date'];
            }
            
            if (!empty($filters['end_date'])) {
                $whereConditions[] = 'pe.entry_date <= :end_date';
                $params[':end_date'] = $filters['end_date'];
            }
            
            if (!empty($filters['proficiency'])) {
                $whereConditions[] = 'pe.proficiency_level = :proficiency';
                $params[':proficiency'] = $filters['proficiency'];
            }
            
            $whereClause = implode(' AND ', $whereConditions);
            
            // Get total count
            $countSql = "SELECT COUNT(*) FROM progress_entries pe WHERE {$whereClause}";
            $countStmt = $this->pdo->prepare($countSql);
            $countStmt->execute($params);
            $totalCount = $countStmt->fetchColumn();
            
            // Get progress entries
            $sql = "SELECT pe.*, s.skill_name 
                    FROM progress_entries pe
                    JOIN skills s ON pe.skill_id = s.skill_id
                    WHERE {$whereClause}
                    ORDER BY pe.entry_date DESC, pe.created_at DESC
                    LIMIT :limit OFFSET :offset";
            
            $params[':limit'] = $limit;
            $params[':offset'] = $offset;
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $entries = $stmt->fetchAll();
            
            return [
                'entries' => $entries,
                'pagination' => paginate($totalCount, $page, $limit)
            ];
            
        } catch (PDOException $e) {
            error_log("Get skill progress error: " . $e->getMessage());
            return ['entries' => [], 'pagination' => []];
        }
    }
    
    /**
     * Get progress entry by ID
     * @param int $entryId
     * @param int $userId
     * @return array|null
     */
    public function findById($entryId, $userId) {
        try {
            $sql = "SELECT pe.*, s.skill_name, s.user_id 
                    FROM progress_entries pe
                    JOIN skills s ON pe.skill_id = s.skill_id
                    WHERE pe.entry_id = :entry_id AND s.user_id = :user_id";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':entry_id' => $entryId,
                ':user_id' => $userId
            ]);
            
            return $stmt->fetch();
            
        } catch (PDOException $e) {
            error_log("Find progress entry error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Update progress entry
     * @param int $entryId
     * @param int $userId
     * @param array $progressData
     * @return array ['success' => bool, 'message' => string]
     */
    public function update($entryId, $userId, $progressData) {
        try {
            // Validate input
            $validation = $this->validateProgressData($progressData, $entryId);
            if (!$validation['valid']) {
                return ['success' => false, 'message' => implode(', ', $validation['errors'])];
            }
            
            // Check if entry exists and belongs to user
            $entry = $this->findById($entryId, $userId);
            if (!$entry) {
                return ['success' => false, 'message' => 'Progress entry not found or access denied'];
            }
            
            $sql = "UPDATE progress_entries SET hours_spent = :hours_spent, tasks_completed = :tasks_completed,
                    proficiency_level = :proficiency_level, notes = :notes, entry_date = :entry_date
                    WHERE entry_id = :entry_id";
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                ':hours_spent' => $progressData['hours_spent'] ?? 0,
                ':tasks_completed' => $progressData['tasks_completed'] ?? 0,
                ':proficiency_level' => $progressData['proficiency_level'],
                ':notes' => $progressData['notes'] ?? '',
                ':entry_date' => $progressData['entry_date'] ?? $entry['entry_date'],
                ':entry_id' => $entryId
            ]);
            
            if ($result) {
                // Update skill's current proficiency
                $this->updateSkillProficiency($entry['skill_id']);
                
                logActivity('progress_updated', 'Progress entry updated for skill: ' . $entry['skill_name'], $userId);
                return ['success' => true, 'message' => 'Progress updated successfully'];
            } else {
                return ['success' => false, 'message' => 'Failed to update progress'];
            }
            
        } catch (PDOException $e) {
            error_log("Progress update error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }
    
    /**
     * Delete progress entry
     * @param int $entryId
     * @param int $userId
     * @return array ['success' => bool, 'message' => string]
     */
    public function delete($entryId, $userId) {
        try {
            // Check if entry exists and belongs to user
            $entry = $this->findById($entryId, $userId);
            if (!$entry) {
                return ['success' => false, 'message' => 'Progress entry not found or access denied'];
            }
            
            $sql = "DELETE FROM progress_entries WHERE entry_id = :entry_id";
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([':entry_id' => $entryId]);
            
            if ($result) {
                // Update skill's current proficiency
                $this->updateSkillProficiency($entry['skill_id']);
                
                logActivity('progress_deleted', 'Progress entry deleted for skill: ' . $entry['skill_name'], $userId);
                return ['success' => true, 'message' => 'Progress entry deleted successfully'];
            } else {
                return ['success' => false, 'message' => 'Failed to delete progress entry'];
            }
            
        } catch (PDOException $e) {
            error_log("Progress deletion error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }
    
    /**
     * Get user's recent progress entries
     * @param int $userId
     * @param int $limit
     * @return array
     */
    public function getRecentProgress($userId, $limit = 10) {
        try {
            $sql = "SELECT pe.*, s.skill_name, c.category_name
                    FROM progress_entries pe
                    JOIN skills s ON pe.skill_id = s.skill_id
                    JOIN categories c ON s.category_id = c.category_id
                    WHERE s.user_id = :user_id
                    ORDER BY pe.created_at DESC
                    LIMIT :limit";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':user_id' => $userId,
                ':limit' => $limit
            ]);
            
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            error_log("Get recent progress error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get progress statistics for user
     * @param int $userId
     * @param int $days (optional)
     * @return array
     */
    public function getUserProgressStats($userId, $days = 30) {
        try {
            $sql = "SELECT 
                        COUNT(pe.entry_id) as total_entries,
                        COALESCE(SUM(pe.hours_spent), 0) as total_hours,
                        COALESCE(SUM(pe.tasks_completed), 0) as total_tasks,
                        COALESCE(AVG(pe.hours_spent), 0) as avg_hours_per_entry,
                        COUNT(DISTINCT pe.skill_id) as skills_with_progress,
                        COUNT(DISTINCT DATE(pe.entry_date)) as active_days
                    FROM progress_entries pe
                    JOIN skills s ON pe.skill_id = s.skill_id
                    WHERE s.user_id = :user_id 
                    AND pe.entry_date >= DATE_SUB(CURDATE(), INTERVAL :days DAY)";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':user_id' => $userId,
                ':days' => $days
            ]);
            
            return $stmt->fetch();
            
        } catch (PDOException $e) {
            error_log("User progress stats error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get daily progress data for charts
     * @param int $userId
     * @param int $days
     * @return array
     */
    public function getDailyProgressData($userId, $days = 30) {
        try {
            $sql = "SELECT 
                        DATE(pe.entry_date) as date,
                        SUM(pe.hours_spent) as daily_hours,
                        SUM(pe.tasks_completed) as daily_tasks,
                        COUNT(pe.entry_id) as daily_entries
                    FROM progress_entries pe
                    JOIN skills s ON pe.skill_id = s.skill_id
                    WHERE s.user_id = :user_id 
                    AND pe.entry_date >= DATE_SUB(CURDATE(), INTERVAL :days DAY)
                    GROUP BY DATE(pe.entry_date)
                    ORDER BY pe.entry_date ASC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':user_id' => $userId,
                ':days' => $days
            ]);
            
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            error_log("Daily progress data error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get progress by category
     * @param int $userId
     * @param int $days (optional)
     * @return array
     */
    public function getProgressByCategory($userId, $days = 30) {
        try {
            $sql = "SELECT 
                        c.category_name,
                        c.category_id,
                        COUNT(pe.entry_id) as total_entries,
                        COALESCE(SUM(pe.hours_spent), 0) as total_hours,
                        COALESCE(SUM(pe.tasks_completed), 0) as total_tasks
                    FROM categories c
                    LEFT JOIN skills s ON c.category_id = s.category_id AND s.user_id = :user_id
                    LEFT JOIN progress_entries pe ON s.skill_id = pe.skill_id 
                        AND pe.entry_date >= DATE_SUB(CURDATE(), INTERVAL :days DAY)
                    GROUP BY c.category_id, c.category_name
                    HAVING total_entries > 0
                    ORDER BY total_hours DESC, c.category_name ASC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':user_id' => $userId,
                ':days' => $days
            ]);
            
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            error_log("Progress by category error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get progress streaks
     * @param int $userId
     * @return array
     */
    public function getProgressStreaks($userId) {
        try {
            // Get current streak
            $sql = "SELECT COUNT(*) as current_streak
                    FROM (
                        SELECT DISTINCT DATE(pe.entry_date) as entry_date
                        FROM progress_entries pe
                        JOIN skills s ON pe.skill_id = s.skill_id
                        WHERE s.user_id = :user_id
                        AND pe.entry_date >= (
                            SELECT MIN(date_val)
                            FROM (
                                SELECT CURDATE() - INTERVAL (a.a + (10 * b.a)) DAY as date_val
                                FROM (SELECT 0 as a UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) as a
                                CROSS JOIN (SELECT 0 as a UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) as b
                                ORDER BY date_val DESC
                                LIMIT 100
                            ) dates
                            WHERE date_val NOT IN (
                                SELECT DISTINCT DATE(pe2.entry_date)
                                FROM progress_entries pe2
                                JOIN skills s2 ON pe2.skill_id = s2.skill_id
                                WHERE s2.user_id = :user_id
                                AND DATE(pe2.entry_date) = date_val
                            )
                            LIMIT 1
                        )
                        ORDER BY entry_date DESC
                    ) streak_dates";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':user_id' => $userId]);
            $currentStreak = $stmt->fetchColumn();
            
            // Get longest streak (simplified version)
            $sql = "SELECT COUNT(DISTINCT DATE(pe.entry_date)) as longest_streak
                    FROM progress_entries pe
                    JOIN skills s ON pe.skill_id = s.skill_id
                    WHERE s.user_id = :user_id
                    AND pe.entry_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':user_id' => $userId]);
            $longestStreak = $stmt->fetchColumn();
            
            return [
                'current_streak' => $currentStreak ?: 0,
                'longest_streak' => $longestStreak ?: 0
            ];
            
        } catch (PDOException $e) {
            error_log("Progress streaks error: " . $e->getMessage());
            return ['current_streak' => 0, 'longest_streak' => 0];
        }
    }
    
    /**
     * Update skill's current proficiency based on latest progress
     * @param int $skillId
     */
    private function updateSkillProficiency($skillId) {
        try {
            $sql = "SELECT proficiency_level FROM progress_entries 
                    WHERE skill_id = :skill_id 
                    ORDER BY entry_date DESC, created_at DESC 
                    LIMIT 1";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':skill_id' => $skillId]);
            $latestProficiency = $stmt->fetchColumn();
            
            if ($latestProficiency) {
                $sql = "UPDATE skills SET current_proficiency = :proficiency, updated_at = CURRENT_TIMESTAMP 
                        WHERE skill_id = :skill_id";
                
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([
                    ':proficiency' => $latestProficiency,
                    ':skill_id' => $skillId
                ]);
            }
            
        } catch (PDOException $e) {
            error_log("Update skill proficiency error: " . $e->getMessage());
        }
    }
    
    /**
     * Validate progress data
     * @param array $progressData
     * @param int $excludeEntryId (optional)
     * @return array ['valid' => bool, 'errors' => array]
     */
    private function validateProgressData($progressData, $excludeEntryId = null) {
        $errors = [];
        
        // Required fields
        $required = ['skill_id', 'proficiency_level'];
        foreach ($required as $field) {
            if (empty($progressData[$field])) {
                $errors[] = ucfirst(str_replace('_', ' ', $field)) . " is required";
            }
        }
        
        // Hours validation
        if (isset($progressData['hours_spent'])) {
            if (!is_numeric($progressData['hours_spent']) || $progressData['hours_spent'] < 0) {
                $errors[] = "Hours spent must be a positive number";
            }
            if ($progressData['hours_spent'] > 24) {
                $errors[] = "Hours spent cannot exceed 24 hours per day";
            }
        }
        
        // Tasks validation
        if (isset($progressData['tasks_completed'])) {
            if (!is_numeric($progressData['tasks_completed']) || $progressData['tasks_completed'] < 0) {
                $errors[] = "Tasks completed must be a positive number";
            }
            if ($progressData['tasks_completed'] > 1000) {
                $errors[] = "Tasks completed seems unreasonably high";
            }
        }
        
        // Proficiency validation
        if (!empty($progressData['proficiency_level'])) {
            $validProficiencies = ['Beginner', 'Intermediate', 'Advanced', 'Expert'];
            if (!in_array($progressData['proficiency_level'], $validProficiencies)) {
                $errors[] = "Invalid proficiency level";
            }
        }
        
        // Date validation
        if (isset($progressData['entry_date'])) {
            if (!strtotime($progressData['entry_date'])) {
                $errors[] = "Invalid entry date";
            } else {
                $entryDate = new DateTime($progressData['entry_date']);
                $today = new DateTime();
                if ($entryDate > $today) {
                    $errors[] = "Entry date cannot be in the future";
                }
            }
        }
        
        // Notes validation (optional)
        if (isset($progressData['notes']) && strlen($progressData['notes']) > 1000) {
            $errors[] = "Notes must not exceed 1000 characters";
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}
?>

