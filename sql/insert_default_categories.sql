-- Insert default categories for Smart Skill Progress Tracker
-- Run this after creating the database schema

USE skill_tracker;

-- Insert default categories
INSERT INTO categories (category_name, description) VALUES
('Programming', 'Software development, coding, and programming languages'),
('Design', 'Graphic design, UI/UX, and visual arts'),
('Music', 'Musical instruments, composition, and audio production'),
('Language', 'Foreign languages and communication skills'),
('Sports', 'Physical activities, fitness, and athletic skills'),
('Business', 'Entrepreneurship, management, and professional skills'),
('Art', 'Creative arts, painting, drawing, and crafts'),
('Science', 'Scientific disciplines and research skills'),
('Other', 'Miscellaneous skills and hobbies');

-- Verify the categories were inserted
SELECT * FROM categories;
