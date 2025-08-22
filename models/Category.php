<?php
/**
 * Category Model for Smart Skill Progress Tracker
 * CSE470 Software Engineering Project
 * 
 * Handles skill categories for organization and filtering
 */

class Category {
    private $pdo;
    
    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }
    
    /**
     * Get all categories
     * @return array
     */
    public function getAll() {
        try {
            $sql = "SELECT * FROM categories ORDER BY category_name ASC";
            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get categories error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get category by ID
     * @param int $categoryId
     * @return array|null
     */
    public function findById($categoryId) {
        try {
            $sql = "SELECT * FROM categories WHERE category_id = :category_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':category_id' => $categoryId]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Find category error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Create a new category
     * @param array $categoryData
     * @return array ['success' => bool, 'message' => string, 'category_id' => int]
     */
    public function create($categoryData) {
        try {
            // Validate input
            $validation = $this->validateCategoryData($categoryData);
            if (!$validation['valid']) {
                return ['success' => false, 'message' => implode(', ', $validation['errors'])];
            }
            
            // Check if category name already exists
            if ($this->categoryNameExists($categoryData['category_name'])) {
                return ['success' => false, 'message' => 'Category name already exists'];
            }
            
            $sql = "INSERT INTO categories (category_name, description) VALUES (:category_name, :description)";
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                ':category_name' => $categoryData['category_name'],
                ':description' => $categoryData['description'] ?? ''
            ]);
            
            if ($result) {
                $categoryId = $this->pdo->lastInsertId();
                logActivity('category_created', 'New category created: ' . $categoryData['category_name']);
                
                return [
                    'success' => true,
                    'message' => 'Category created successfully',
                    'category_id' => $categoryId
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to create category'];
            }
            
        } catch (PDOException $e) {
            error_log("Category creation error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }
    
    /**
     * Update category
     * @param int $categoryId
     * @param array $categoryData
     * @return array ['success' => bool, 'message' => string]
     */
    public function update($categoryId, $categoryData) {
        try {
            // Validate input
            $validation = $this->validateCategoryData($categoryData);
            if (!$validation['valid']) {
                return ['success' => false, 'message' => implode(', ', $validation['errors'])];
            }
            
            // Check if category exists
            $category = $this->findById($categoryId);
            if (!$category) {
                return ['success' => false, 'message' => 'Category not found'];
            }
            
            // Check if new name already exists (if name is being changed)
            if ($categoryData['category_name'] !== $category['category_name'] && 
                $this->categoryNameExists($categoryData['category_name'])) {
                return ['success' => false, 'message' => 'Category name already exists'];
            }
            
            $sql = "UPDATE categories SET category_name = :category_name, description = :description 
                    WHERE category_id = :category_id";
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                ':category_name' => $categoryData['category_name'],
                ':description' => $categoryData['description'] ?? '',
                ':category_id' => $categoryId
            ]);
            
            if ($result) {
                logActivity('category_updated', 'Category updated: ' . $categoryData['category_name']);
                return ['success' => true, 'message' => 'Category updated successfully'];
            } else {
                return ['success' => false, 'message' => 'Failed to update category'];
            }
            
        } catch (PDOException $e) {
            error_log("Category update error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }
    
    /**
     * Delete category
     * @param int $categoryId
     * @return array ['success' => bool, 'message' => string]
     */
    public function delete($categoryId) {
        try {
            // Check if category exists
            $category = $this->findById($categoryId);
            if (!$category) {
                return ['success' => false, 'message' => 'Category not found'];
            }
            
            // Check if category has associated skills
            $skillCount = $this->getSkillCount($categoryId);
            if ($skillCount > 0) {
                return ['success' => false, 'message' => 'Cannot delete category with associated skills'];
            }
            
            $sql = "DELETE FROM categories WHERE category_id = :category_id";
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([':category_id' => $categoryId]);
            
            if ($result) {
                logActivity('category_deleted', 'Category deleted: ' . $category['category_name']);
                return ['success' => true, 'message' => 'Category deleted successfully'];
            } else {
                return ['success' => false, 'message' => 'Failed to delete category'];
            }
            
        } catch (PDOException $e) {
            error_log("Category deletion error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }
    
    /**
     * Get categories with skill counts
     * @param int $userId (optional) - filter by user
     * @return array
     */
    public function getCategoriesWithSkillCounts($userId = null) {
        try {
            $sql = "SELECT c.*, COUNT(s.skill_id) as skill_count 
                    FROM categories c 
                    LEFT JOIN skills s ON c.category_id = s.category_id";
            
            $params = [];
            if ($userId) {
                $sql .= " AND s.user_id = :user_id";
                $params[':user_id'] = $userId;
            }
            
            $sql .= " GROUP BY c.category_id, c.category_name, c.description, c.created_at 
                      ORDER BY c.category_name ASC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            error_log("Get categories with counts error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get category statistics
     * @param int $categoryId
     * @param int $userId (optional) - filter by user
     * @return array
     */
    public function getStatistics($categoryId, $userId = null) {
        try {
            $sql = "SELECT 
                        COUNT(DISTINCT s.skill_id) as total_skills,
                        COUNT(DISTINCT pe.entry_id) as total_entries,
                        COALESCE(SUM(pe.hours_spent), 0) as total_hours,
                        COALESCE(SUM(pe.tasks_completed), 0) as total_tasks,
                        COALESCE(AVG(pe.hours_spent), 0) as avg_hours_per_entry
                    FROM skills s
                    LEFT JOIN progress_entries pe ON s.skill_id = pe.skill_id
                    WHERE s.category_id = :category_id";
            
            $params = [':category_id' => $categoryId];
            if ($userId) {
                $sql .= " AND s.user_id = :user_id";
                $params[':user_id'] = $userId;
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch();
            
        } catch (PDOException $e) {
            error_log("Category statistics error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Search categories
     * @param string $searchTerm
     * @return array
     */
    public function search($searchTerm) {
        try {
            $sql = "SELECT * FROM categories 
                    WHERE category_name LIKE :search OR description LIKE :search 
                    ORDER BY category_name ASC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':search' => '%' . $searchTerm . '%']);
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            error_log("Category search error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Check if category name exists
     * @param string $categoryName
     * @return bool
     */
    private function categoryNameExists($categoryName) {
        $sql = "SELECT COUNT(*) FROM categories WHERE category_name = :category_name";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':category_name' => $categoryName]);
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Get skill count for category
     * @param int $categoryId
     * @return int
     */
    private function getSkillCount($categoryId) {
        $sql = "SELECT COUNT(*) FROM skills WHERE category_id = :category_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':category_id' => $categoryId]);
        return $stmt->fetchColumn();
    }
    
    /**
     * Validate category data
     * @param array $categoryData
     * @return array ['valid' => bool, 'errors' => array]
     */
    private function validateCategoryData($categoryData) {
        $errors = [];
        
        // Category name is required
        if (empty($categoryData['category_name'])) {
            $errors[] = "Category name is required";
        } else {
            // Category name length validation
            if (strlen($categoryData['category_name']) < 2) {
                $errors[] = "Category name must be at least 2 characters long";
            }
            
            if (strlen($categoryData['category_name']) > 100) {
                $errors[] = "Category name must not exceed 100 characters";
            }
            
            // Category name format validation
            if (!preg_match('/^[a-zA-Z0-9\s\-_&]+$/', $categoryData['category_name'])) {
                $errors[] = "Category name contains invalid characters";
            }
        }
        
        // Description validation (optional)
        if (isset($categoryData['description']) && strlen($categoryData['description']) > 500) {
            $errors[] = "Description must not exceed 500 characters";
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}
?>

