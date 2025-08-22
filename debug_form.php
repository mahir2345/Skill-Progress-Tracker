<?php
/**
 * Debug Form Submission
 * This script helps debug form submission issues
 */

// Start session
session_start();

// Include configuration
require_once __DIR__ . '/config/config.php';

echo "<h1>Form Debug Information</h1>";

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h2>POST Data Received:</h2>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    
    echo "<h2>Server Information:</h2>";
    echo "<p>Request Method: " . $_SERVER['REQUEST_METHOD'] . "</p>";
    echo "<p>Content Type: " . $_SERVER['CONTENT_TYPE'] . "</p>";
    
    // Check CSRF token
    if (function_exists('validateCSRFToken')) {
        echo "<p>CSRF Token Valid: " . (validateCSRFToken() ? 'Yes' : 'No') . "</p>";
    }
    
    // Check database connection
    try {
        require_once __DIR__ . '/config/database.php';
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        echo "<p>Database Connection: Success</p>";
        
        // Check if categories table exists and has data
        $stmt = $pdo->query("SELECT COUNT(*) FROM categories");
        $categoryCount = $stmt->fetchColumn();
        echo "<p>Categories in database: " . $categoryCount . "</p>";
        
        if ($categoryCount > 0) {
            $stmt = $pdo->query("SELECT * FROM categories LIMIT 5");
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "<h3>Sample Categories:</h3>";
            echo "<pre>";
            print_r($categories);
            echo "</pre>";
        }
        
    } catch (PDOException $e) {
        echo "<p>Database Connection Error: " . $e->getMessage() . "</p>";
    }
    
} else {
    echo "<p>No form data submitted. This page is for debugging form submissions.</p>";
}

echo "<hr>";
echo "<h2>Test Forms</h2>";

echo "<h3>Test Skill Creation Form:</h3>";
echo '<form method="POST" action="">';
echo '<input type="hidden" name="csrf_token" value="' . (isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : 'test') . '">';
echo '<p>Skill Name: <input type="text" name="skill_name" value="Test Skill"></p>';
echo '<p>Description: <textarea name="description">Test Description</textarea></p>';
echo '<p>Category ID: <input type="number" name="category_id" value="1"></p>';
echo '<p>Current Proficiency: <select name="current_proficiency"><option value="Beginner">Beginner</option></select></p>';
echo '<button type="submit">Test Submit</button>';
echo '</form>';

echo "<h3>Test Goal Creation Form:</h3>";
echo '<form method="POST" action="">';
echo '<input type="hidden" name="csrf_token" value="' . (isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : 'test') . '">';
echo '<p>Skill ID: <input type="number" name="skill_id" value="1"></p>';
echo '<p>Target Proficiency: <select name="target_proficiency"><option value="Intermediate">Intermediate</option></select></p>';
echo '<p>Target Date: <input type="date" name="target_date" value="' . date('Y-m-d', strtotime('+30 days')) . '"></p>';
echo '<p>Description: <textarea name="description">Test Goal</textarea></p>';
echo '<button type="submit">Test Submit</button>';
echo '</form>';
?>
