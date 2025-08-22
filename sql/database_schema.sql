-- Smart Skill Progress Tracker Database Schema
-- Created for CSE470 Software Engineering Project
-- Database: skill_tracker

-- Create database
CREATE DATABASE IF NOT EXISTS skill_tracker;
USE skill_tracker;

-- Drop tables if they exist (for clean setup)
DROP TABLE IF EXISTS goals;
DROP TABLE IF EXISTS progress_entries;
DROP TABLE IF EXISTS skills;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;

-- Create Users table
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes for performance
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_created_at (created_at)
);

-- Create Categories table
CREATE TABLE categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Index for performance
    INDEX idx_category_name (category_name)
);

-- Create Skills table
CREATE TABLE skills (
    skill_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category_id INT NOT NULL,
    skill_name VARCHAR(100) NOT NULL,
    description TEXT,
    current_proficiency ENUM('Beginner', 'Intermediate', 'Advanced', 'Expert') DEFAULT 'Beginner',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign key constraints
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE RESTRICT,
    
    -- Indexes for performance
    INDEX idx_user_id (user_id),
    INDEX idx_category_id (category_id),
    INDEX idx_skill_name (skill_name),
    INDEX idx_user_created (user_id, created_at)
);

-- Create Progress Entries table
CREATE TABLE progress_entries (
    entry_id INT AUTO_INCREMENT PRIMARY KEY,
    skill_id INT NOT NULL,
    hours_spent DECIMAL(5,2) DEFAULT 0.00,
    tasks_completed INT DEFAULT 0,
    proficiency_level ENUM('Beginner', 'Intermediate', 'Advanced', 'Expert') NOT NULL,
    notes TEXT,
    entry_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign key constraints
    FOREIGN KEY (skill_id) REFERENCES skills(skill_id) ON DELETE CASCADE,
    
    -- Indexes for performance
    INDEX idx_skill_id (skill_id),
    INDEX idx_entry_date (entry_date),
    INDEX idx_skill_date (skill_id, entry_date),
    INDEX idx_created_at (created_at)
);

-- Create Goals table
CREATE TABLE goals (
    goal_id INT AUTO_INCREMENT PRIMARY KEY,
    skill_id INT NOT NULL,
    target_proficiency ENUM('Beginner', 'Intermediate', 'Advanced', 'Expert') NOT NULL,
    target_date DATE,
    target_hours DECIMAL(6,2),
    description TEXT,
    is_completed BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    
    -- Foreign key constraints
    FOREIGN KEY (skill_id) REFERENCES skills(skill_id) ON DELETE CASCADE,
    
    -- Indexes for performance
    INDEX idx_skill_id (skill_id),
    INDEX idx_target_date (target_date),
    INDEX idx_is_completed (is_completed),
    INDEX idx_skill_completed (skill_id, is_completed)
);

-- Insert default categories
INSERT INTO categories (category_name, description) VALUES
('Programming', 'Programming languages and software development skills'),
('Design', 'Graphic design, UI/UX, and creative skills'),
('Soft Skills', 'Communication, leadership, and interpersonal skills'),
('Languages', 'Foreign languages and communication skills'),
('Technical', 'Technical skills and tools'),
('Business', 'Business and entrepreneurship skills'),
('Academic', 'Academic subjects and research skills'),
('Creative', 'Creative arts and hobbies'),
('Health & Fitness', 'Physical fitness and wellness skills'),
('Other', 'Miscellaneous skills and interests');

-- Create views for common queries

-- View for skill summary with progress statistics
CREATE VIEW skill_summary AS
SELECT 
    s.skill_id,
    s.user_id,
    s.skill_name,
    s.description,
    s.current_proficiency,
    c.category_name,
    s.created_at,
    s.updated_at,
    COALESCE(SUM(pe.hours_spent), 0) as total_hours,
    COALESCE(SUM(pe.tasks_completed), 0) as total_tasks,
    COUNT(pe.entry_id) as total_entries,
    MAX(pe.entry_date) as last_progress_date
FROM skills s
LEFT JOIN categories c ON s.category_id = c.category_id
LEFT JOIN progress_entries pe ON s.skill_id = pe.skill_id
GROUP BY s.skill_id, s.user_id, s.skill_name, s.description, s.current_proficiency, c.category_name, s.created_at, s.updated_at;

-- View for recent progress entries
CREATE VIEW recent_progress AS
SELECT 
    pe.entry_id,
    pe.skill_id,
    s.skill_name,
    s.user_id,
    pe.hours_spent,
    pe.tasks_completed,
    pe.proficiency_level,
    pe.notes,
    pe.entry_date,
    pe.created_at,
    c.category_name
FROM progress_entries pe
JOIN skills s ON pe.skill_id = s.skill_id
JOIN categories c ON s.category_id = c.category_id
ORDER BY pe.created_at DESC;

-- View for goal progress tracking
CREATE VIEW goal_progress AS
SELECT 
    g.goal_id,
    g.skill_id,
    s.skill_name,
    s.user_id,
    g.target_proficiency,
    g.target_date,
    g.target_hours,
    g.description,
    g.is_completed,
    g.created_at,
    g.completed_at,
    COALESCE(SUM(pe.hours_spent), 0) as current_hours,
    CASE 
        WHEN g.target_hours > 0 THEN (COALESCE(SUM(pe.hours_spent), 0) / g.target_hours) * 100
        ELSE 0
    END as hours_progress_percentage,
    s.current_proficiency,
    c.category_name
FROM goals g
JOIN skills s ON g.skill_id = s.skill_id
JOIN categories c ON s.category_id = c.category_id
LEFT JOIN progress_entries pe ON g.skill_id = pe.skill_id AND pe.entry_date >= DATE(g.created_at)
GROUP BY g.goal_id, g.skill_id, s.skill_name, s.user_id, g.target_proficiency, g.target_date, 
         g.target_hours, g.description, g.is_completed, g.created_at, g.completed_at, 
         s.current_proficiency, c.category_name;

-- Create stored procedures for common operations

DELIMITER //

-- Procedure to get user dashboard data
CREATE PROCEDURE GetUserDashboard(IN p_user_id INT)
BEGIN
    -- Get user's skill count by category
    SELECT 
        c.category_name,
        COUNT(s.skill_id) as skill_count,
        COALESCE(SUM(pe.hours_spent), 0) as total_hours
    FROM categories c
    LEFT JOIN skills s ON c.category_id = s.category_id AND s.user_id = p_user_id
    LEFT JOIN progress_entries pe ON s.skill_id = pe.skill_id
    GROUP BY c.category_id, c.category_name
    ORDER BY skill_count DESC, c.category_name;
    
    -- Get recent progress entries
    SELECT * FROM recent_progress 
    WHERE user_id = p_user_id 
    ORDER BY created_at DESC 
    LIMIT 10;
    
    -- Get active goals
    SELECT * FROM goal_progress 
    WHERE user_id = p_user_id AND is_completed = FALSE
    ORDER BY target_date ASC;
END //

-- Procedure to calculate skill statistics
CREATE PROCEDURE GetSkillStatistics(IN p_skill_id INT)
BEGIN
    SELECT 
        COUNT(*) as total_entries,
        COALESCE(SUM(hours_spent), 0) as total_hours,
        COALESCE(SUM(tasks_completed), 0) as total_tasks,
        COALESCE(AVG(hours_spent), 0) as avg_hours_per_entry,
        MIN(entry_date) as first_entry_date,
        MAX(entry_date) as last_entry_date,
        DATEDIFF(MAX(entry_date), MIN(entry_date)) + 1 as days_tracked
    FROM progress_entries 
    WHERE skill_id = p_skill_id;
END //

-- Procedure to update skill proficiency based on progress
CREATE PROCEDURE UpdateSkillProficiency(IN p_skill_id INT)
BEGIN
    DECLARE total_hours DECIMAL(6,2);
    DECLARE latest_proficiency VARCHAR(20);
    
    -- Get total hours and latest proficiency level
    SELECT 
        COALESCE(SUM(hours_spent), 0),
        proficiency_level
    INTO total_hours, latest_proficiency
    FROM progress_entries 
    WHERE skill_id = p_skill_id 
    ORDER BY entry_date DESC, created_at DESC 
    LIMIT 1;
    
    -- Update skill's current proficiency if we have progress data
    IF latest_proficiency IS NOT NULL THEN
        UPDATE skills 
        SET current_proficiency = latest_proficiency,
            updated_at = CURRENT_TIMESTAMP
        WHERE skill_id = p_skill_id;
    END IF;
END //

DELIMITER ;

-- Create triggers for automatic updates

-- Trigger to update skill proficiency when progress is added
DELIMITER //
CREATE TRIGGER update_skill_proficiency_after_progress_insert
    AFTER INSERT ON progress_entries
    FOR EACH ROW
BEGIN
    CALL UpdateSkillProficiency(NEW.skill_id);
END //
DELIMITER ;

-- Trigger to update skill proficiency when progress is updated
DELIMITER //
CREATE TRIGGER update_skill_proficiency_after_progress_update
    AFTER UPDATE ON progress_entries
    FOR EACH ROW
BEGIN
    CALL UpdateSkillProficiency(NEW.skill_id);
END //
DELIMITER ;

-- Insert sample data for testing (optional - can be removed for production)
-- This data will help with initial testing and demonstration

-- Sample user (password is 'password123' hashed)
INSERT INTO users (username, email, password_hash, first_name, last_name) VALUES
('testuser', 'test@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Test', 'User');

-- Sample skills for the test user
INSERT INTO skills (user_id, category_id, skill_name, description, current_proficiency) VALUES
(1, 1, 'PHP Programming', 'Server-side web development with PHP', 'Intermediate'),
(1, 1, 'JavaScript', 'Client-side scripting and web development', 'Intermediate'),
(1, 2, 'UI/UX Design', 'User interface and user experience design', 'Beginner'),
(1, 3, 'Public Speaking', 'Presentation and communication skills', 'Beginner');

-- Sample progress entries
INSERT INTO progress_entries (skill_id, hours_spent, tasks_completed, proficiency_level, notes, entry_date) VALUES
(1, 2.5, 3, 'Intermediate', 'Worked on MVC architecture implementation', CURDATE() - INTERVAL 1 DAY),
(1, 1.5, 2, 'Intermediate', 'Database design and SQL queries', CURDATE() - INTERVAL 2 DAY),
(2, 3.0, 4, 'Intermediate', 'AJAX and DOM manipulation practice', CURDATE() - INTERVAL 1 DAY),
(3, 1.0, 1, 'Beginner', 'Studied color theory and typography', CURDATE() - INTERVAL 3 DAY),
(4, 0.5, 1, 'Beginner', 'Practiced elevator pitch', CURDATE());

-- Sample goals
INSERT INTO goals (skill_id, target_proficiency, target_date, target_hours, description) VALUES
(1, 'Advanced', DATE_ADD(CURDATE(), INTERVAL 30 DAY), 20.0, 'Master PHP MVC architecture and advanced features'),
(2, 'Advanced', DATE_ADD(CURDATE(), INTERVAL 45 DAY), 25.0, 'Learn modern JavaScript frameworks and ES6+ features'),
(3, 'Intermediate', DATE_ADD(CURDATE(), INTERVAL 60 DAY), 15.0, 'Complete UI/UX design fundamentals course'),
(4, 'Intermediate', DATE_ADD(CURDATE(), INTERVAL 90 DAY), 10.0, 'Deliver confident presentations to groups');

-- Display success message
SELECT 'Database schema created successfully!' as message;
SELECT 'Sample data inserted for testing.' as note;
SELECT 'You can now proceed with the application development.' as next_step;

