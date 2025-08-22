<?php
/**
 * Skill Model for Smart Skill Progress Tracker
 * CSE470 Software Engineering Project
 * 
 * Handles skill management and related operations
 */

class Skill {
    private $pdo;
    
    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }
    
    /**
     * Create a new skill
     * @param array $skillData
     * @return array ['success' => bool, 'message' => string, 'skill_id' => int]
     */
    public function create($skillData) {
        try {
            // Validate input
            $validation = $this->validateSkillData($skillData);
            if (!$validation['valid']) {
                return ['success' => false, 'message' => implode(', ', $validation['errors'])];
            }
            
            // Check if skill name already exists for this user
            if ($this->skillNameExists($skillData['user_id'], $skillData['skill_name'])) {
                return ['success' => false, 'message' => 'You already have a skill with this name'];
            }
            
            $sql = "INSERT INTO skills (user_id, category_id, skill_name, description, current_proficiency) 
                    VALUES (:user_id, :category_id, :skill_name, :description, :current_proficiency)";
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                ':user_id' => $skillData['user_id'],
                ':category_id' => $skillData['category_id'],
                ':skill_name' => $skillData['skill_name'],
                ':description' => $skillData['description'] ?? '',
                ':current_proficiency' => $skillData['current_proficiency'] ?? 'Beginner'
            ]);
            
            if ($result) {
                $skillId = $this->pdo->lastInsertId();
                logActivity('skill_created', 'New skill created: ' . $skillData['skill_name'], $skillData['user_id']);
                
                return [
                    'success' => true,
                    'message' => 'Skill created successfully',
                    'skill_id' => $skillId
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to create skill'];
            }
            
        } catch (PDOException $e) {
            error_log("Skill creation error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }
    
    /**
     * Get skills for a user
     * @param int $userId
     * @param array $filters (optional)
     * @param int $page (optional)
     * @param int $limit (optional)
     * @return array
     */
    public function getUserSkills($userId, $filters = [], $page = 1, $limit = ITEMS_PER_PAGE) {
        try {
            $offset = ($page - 1) * $limit;
            
            // Build WHERE clause
            $whereConditions = ['s.user_id = :user_id'];
            $params = [':user_id' => $userId];
            
            if (!empty($filters['category_id'])) {
                $whereConditions[] = 's.category_id = :category_id';
                $params[':category_id'] = $filters['category_id'];
            }
            
            if (!empty($filters['proficiency'])) {
                $whereConditions[] = 's.current_proficiency = :proficiency';
                $params[':proficiency'] = $filters['proficiency'];
            }
            
            if (!empty($filters['search'])) {
                $whereConditions[] = '(s.skill_name LIKE :search OR s.description LIKE :search)';
                $params[':search'] = '%' . $filters['search'] . '%';
            }
            
            $whereClause = implode(' AND ', $whereConditions);
            
            // Get total count
            $countSql = "SELECT COUNT(*) FROM skills s WHERE {$whereClause}";
            $countStmt = $this->pdo->prepare($countSql);
            $countStmt->execute($params);
            $totalCount = $countStmt->fetchColumn();
            
            // Get skills with summary data
            $sql = "SELECT s.*, c.category_name,
                           COALESCE(SUM(pe.hours_spent), 0) as total_hours,
                           COALESCE(SUM(pe.tasks_completed), 0) as total_tasks,
                           COUNT(pe.entry_id) as total_entries,
                           MAX(pe.entry_date) as last_progress_date
                    FROM skills s
                    LEFT JOIN categories c ON s.category_id = c.category_id
                    LEFT JOIN progress_entries pe ON s.skill_id = pe.skill_id
                    WHERE {$whereClause}
                    GROUP BY s.skill_id, s.user_id, s.category_id, s.skill_name, s.description, 
                             s.current_proficiency, s.created_at, s.updated_at, c.category_name
                    ORDER BY s.updated_at DESC, s.skill_name ASC
                    LIMIT :limit OFFSET :offset";
            
            $params[':limit'] = $limit;
            $params[':offset'] = $offset;
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $skills = $stmt->fetchAll();
            
            return [
                'skills' => $skills,
                'pagination' => paginate($totalCount, $page, $limit)
            ];
            
        } catch (PDOException $e) {
            error_log("Get user skills error: " . $e->getMessage());
            return ['skills' => [], 'pagination' => []];
        }
    }
    
    /**
     * Get skill by ID
     * @param int $skillId
     * @param int $userId (optional) - for ownership verification
     * @return array|null
     */
    public function findById($skillId, $userId = null) {
        try {
            $sql = "SELECT s.*, c.category_name,
                           COALESCE(SUM(pe.hours_spent), 0) as total_hours,
                           COALESCE(SUM(pe.tasks_completed), 0) as total_tasks,
                           COUNT(pe.entry_id) as total_entries,
                           MAX(pe.entry_date) as last_progress_date,
                           MIN(pe.entry_date) as first_progress_date
                    FROM skills s
                    LEFT JOIN categories c ON s.category_id = c.category_id
                    LEFT JOIN progress_entries pe ON s.skill_id = pe.skill_id
                    WHERE s.skill_id = :skill_id";
            
            $params = [':skill_id' => $skillId];
            
            if ($userId) {
                $sql .= " AND s.user_id = :user_id";
                $params[':user_id'] = $userId;
            }
            
            $sql .= " GROUP BY s.skill_id, s.user_id, s.category_id, s.skill_name, s.description, 
                              s.current_proficiency, s.created_at, s.updated_at, c.category_name";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch();
            
        } catch (PDOException $e) {
            error_log("Find skill error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Update skill
     * @param int $skillId
     * @param int $userId
     * @param array $skillData
     * @return array ['success' => bool, 'message' => string]
     */
    public function update($skillId, $userId, $skillData) {
        try {
            // Validate input
            $validation = $this->validateSkillData($skillData, $skillId);
            if (!$validation['valid']) {
                return ['success' => false, 'message' => implode(', ', $validation['errors'])];
            }
            
            // Check if skill exists and belongs to user
            $skill = $this->findById($skillId, $userId);
            if (!$skill) {
                return ['success' => false, 'message' => 'Skill not found or access denied'];
            }
            
            // Check if new name already exists for this user (if name is being changed)
            if ($skillData['skill_name'] !== $skill['skill_name'] && 
                $this->skillNameExists($userId, $skillData['skill_name'])) {
                return ['success' => false, 'message' => 'You already have a skill with this name'];
            }
            
            $sql = "UPDATE skills SET category_id = :category_id, skill_name = :skill_name, 
                    description = :description, current_proficiency = :current_proficiency,
                    updated_at = CURRENT_TIMESTAMP
                    WHERE skill_id = :skill_id AND user_id = :user_id";
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                ':category_id' => $skillData['category_id'],
                ':skill_name' => $skillData['skill_name'],
                ':description' => $skillData['description'] ?? '',
                ':current_proficiency' => $skillData['current_proficiency'] ?? 'Beginner',
                ':skill_id' => $skillId,
                ':user_id' => $userId
            ]);
            
            if ($result) {
                logActivity('skill_updated', 'Skill updated: ' . $skillData['skill_name'], $userId);
                return ['success' => true, 'message' => 'Skill updated successfully'];
            } else {
                return ['success' => false, 'message' => 'Failed to update skill'];
            }
            
        } catch (PDOException $e) {
            error_log("Skill update error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }
    
    /**
     * Delete skill
     * @param int $skillId
     * @param int $userId
     * @return array ['success' => bool, 'message' => string]
     */
    public function delete($skillId, $userId) {
        try {
            // Check if skill exists and belongs to user
            $skill = $this->findById($skillId, $userId);
            if (!$skill) {
                return ['success' => false, 'message' => 'Skill not found or access denied'];
            }
            
            // Begin transaction to delete skill and related data
            $this->pdo->beginTransaction();
            
            try {
                // Delete related progress entries and goals (handled by foreign key constraints)
                $sql = "DELETE FROM skills WHERE skill_id = :skill_id AND user_id = :user_id";
                $stmt = $this->pdo->prepare($sql);
                $result = $stmt->execute([
                    ':skill_id' => $skillId,
                    ':user_id' => $userId
                ]);
                
                if ($result) {
                    $this->pdo->commit();
                    logActivity('skill_deleted', 'Skill deleted: ' . $skill['skill_name'], $userId);
                    return ['success' => true, 'message' => 'Skill deleted successfully'];
                } else {
                    $this->pdo->rollBack();
                    return ['success' => false, 'message' => 'Failed to delete skill'];
                }
                
            } catch (Exception $e) {
                $this->pdo->rollBack();
                throw $e;
            }
            
        } catch (PDOException $e) {
            error_log("Skill deletion error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }
    
    /**
     * Get skill progress summary
     * @param int $skillId
     * @param int $userId
     * @param int $days (optional) - number of days to look back
     * @return array
     */
    public function getProgressSummary($skillId, $userId, $days = 30) {
        try {
            // Verify skill ownership
            $skill = $this->findById($skillId, $userId);
            if (!$skill) {
                return [];
            }
            
            $sql = "SELECT 
                        DATE(pe.entry_date) as date,
                        SUM(pe.hours_spent) as daily_hours,
                        SUM(pe.tasks_completed) as daily_tasks,
                        COUNT(pe.entry_id) as daily_entries
                    FROM progress_entries pe
                    WHERE pe.skill_id = :skill_id 
                    AND pe.entry_date >= DATE_SUB(CURDATE(), INTERVAL :days DAY)
                    GROUP BY DATE(pe.entry_date)
                    ORDER BY pe.entry_date ASC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':skill_id' => $skillId,
                ':days' => $days
            ]);
            
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            error_log("Skill progress summary error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get skills by category
     * @param int $categoryId
     * @param int $userId (optional)
     * @return array
     */
    public function getByCategory($categoryId, $userId = null) {
        try {
            $sql = "SELECT s.*, c.category_name FROM skills s
                    LEFT JOIN categories c ON s.category_id = c.category_id
                    WHERE s.category_id = :category_id";
            
            $params = [':category_id' => $categoryId];
            
            if ($userId) {
                $sql .= " AND s.user_id = :user_id";
                $params[':user_id'] = $userId;
            }
            
            $sql .= " ORDER BY s.skill_name ASC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            error_log("Get skills by category error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Search skills
     * @param int $userId
     * @param string $searchTerm
     * @return array
     */
    public function search($userId, $searchTerm) {
        try {
            $sql = "SELECT s.*, c.category_name FROM skills s
                    LEFT JOIN categories c ON s.category_id = c.category_id
                    WHERE s.user_id = :user_id 
                    AND (s.skill_name LIKE :search OR s.description LIKE :search)
                    ORDER BY s.skill_name ASC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':user_id' => $userId,
                ':search' => '%' . $searchTerm . '%'
            ]);
            
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            error_log("Skill search error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get recent skills for user
     * @param int $userId
     * @param int $limit
     * @return array
     */
    public function getRecentSkills($userId, $limit = 5) {
        try {
            $sql = "SELECT s.*, c.category_name FROM skills s
                    LEFT JOIN categories c ON s.category_id = c.category_id
                    WHERE s.user_id = :user_id
                    ORDER BY s.updated_at DESC
                    LIMIT :limit";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':user_id' => $userId,
                ':limit' => $limit
            ]);
            
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            error_log("Get recent skills error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Check if skill name exists for user
     * @param int $userId
     * @param string $skillName
     * @param int $excludeSkillId (optional)
     * @return bool
     */
    private function skillNameExists($userId, $skillName, $excludeSkillId = null) {
        $sql = "SELECT COUNT(*) FROM skills WHERE user_id = :user_id AND skill_name = :skill_name";
        $params = [':user_id' => $userId, ':skill_name' => $skillName];
        
        if ($excludeSkillId) {
            $sql .= " AND skill_id != :exclude_id";
            $params[':exclude_id'] = $excludeSkillId;
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Validate skill data
     * @param array $skillData
     * @param int $excludeSkillId (optional)
     * @return array ['valid' => bool, 'errors' => array]
     */
    private function validateSkillData($skillData, $excludeSkillId = null) {
        $errors = [];
        
        // Required fields
        $required = ['skill_name', 'category_id'];
        foreach ($required as $field) {
            if (empty($skillData[$field])) {
                $errors[] = ucfirst(str_replace('_', ' ', $field)) . " is required";
            }
        }
        
        // Skill name validation
        if (!empty($skillData['skill_name'])) {
            if (strlen($skillData['skill_name']) < 2) {
                $errors[] = "Skill name must be at least 2 characters long";
            }
            
            if (strlen($skillData['skill_name']) > 100) {
                $errors[] = "Skill name must not exceed 100 characters";
            }
        }
        
        // Category validation
        if (!empty($skillData['category_id'])) {
            $category = new Category();
            if (!$category->findById($skillData['category_id'])) {
                $errors[] = "Invalid category selected";
            }
        }
        
        // Proficiency validation
        if (isset($skillData['current_proficiency'])) {
            $validProficiencies = ['Beginner', 'Intermediate', 'Advanced', 'Expert'];
            if (!in_array($skillData['current_proficiency'], $validProficiencies)) {
                $errors[] = "Invalid proficiency level";
            }
        }
        
        // Description validation (optional)
        if (isset($skillData['description']) && strlen($skillData['description']) > 1000) {
            $errors[] = "Description must not exceed 1000 characters";
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}
?>

