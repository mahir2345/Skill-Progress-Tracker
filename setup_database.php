<?php
/**
 * Database Setup Script for Smart Skill Progress Tracker
 * Run this script to set up the database and insert sample data
 */

// Include configuration
require_once __DIR__ . '/config/config.php';

echo "<h1>Database Setup</h1>";

try {
    // Create database connection
    require_once __DIR__ . '/config/database.php';
    $pdo = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p>Connected to MySQL server successfully.</p>";
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME);
    echo "<p>Database '" . DB_NAME . "' created/verified successfully.</p>";
    
    // Select the database
    $pdo->exec("USE " . DB_NAME);
    
    // Read and execute the database schema
    $schema = file_get_contents(__DIR__ . '/sql/database_schema.sql');
    
    // Split the schema into individual statements
    $statements = array_filter(array_map('trim', explode(';', $schema)));
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            try {
                $pdo->exec($statement);
                echo "<p>Executed: " . substr($statement, 0, 50) . "...</p>";
            } catch (PDOException $e) {
                echo "<p>Warning: " . $e->getMessage() . "</p>";
            }
        }
    }
    
    // Insert default categories
    $categories = [
        ['Programming', 'Software development, coding, and programming languages'],
        ['Design', 'Graphic design, UI/UX, and visual arts'],
        ['Music', 'Musical instruments, composition, and audio production'],
        ['Language', 'Foreign languages and communication skills'],
        ['Sports', 'Physical activities, fitness, and athletic skills'],
        ['Business', 'Entrepreneurship, management, and professional skills'],
        ['Art', 'Creative arts, painting, drawing, and crafts'],
        ['Science', 'Scientific disciplines and research skills'],
        ['Other', 'Miscellaneous skills and hobbies']
    ];
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO categories (category_name, description) VALUES (?, ?)");
    foreach ($categories as $category) {
        $stmt->execute($category);
    }
    echo "<p>Default categories inserted successfully.</p>";
    
    // Check if we have a test user
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $userCount = $stmt->fetchColumn();
    
    if ($userCount == 0) {
        // Create a test user
        $password = password_hash('test123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, first_name, last_name) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute(['testuser', 'test@example.com', $password, 'Test', 'User']);
        echo "<p>Test user created: testuser / test123</p>";
    } else {
        echo "<p>Users already exist in database.</p>";
    }
    
    // Verify tables and data
    echo "<h2>Database Verification</h2>";
    
    $tables = ['users', 'categories', 'skills', 'goals', 'progress_entries'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
        $count = $stmt->fetchColumn();
        echo "<p>Table '$table': $count records</p>";
    }
    
    echo "<h2>Sample Categories</h2>";
    $stmt = $pdo->query("SELECT * FROM categories");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($categories);
    echo "</pre>";
    
    echo "<h2>Setup Complete!</h2>";
    echo "<p>Your database is now ready. You can:</p>";
    echo "<ul>";
    echo "<li><a href='?page=login'>Go to Login</a></li>";
    echo "<li><a href='?page=register'>Register New User</a></li>";
    echo "<li><a href='debug_form.php'>Test Form Submission</a></li>";
    echo "</ul>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Database Error: " . $e->getMessage() . "</p>";
    echo "<p>Please check your database configuration in config/database.php</p>";
}
?>
