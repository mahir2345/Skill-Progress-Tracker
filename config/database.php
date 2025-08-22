<?php
/**
 * Database Configuration for Smart Skill Progress Tracker
 * CSE470 Software Engineering Project
 */

// Database configuration constants
define('DB_HOST', 'localhost');
define('DB_NAME', 'skill_tracker');
define('DB_USER', 'root');
define('DB_PASS', ''); // Empty for XAMPP default
define('DB_CHARSET', 'utf8mb4');

// Create PDO connection
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    
    // Set PDO attributes for better error handling and security
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    
} catch (PDOException $e) {
    // Log error and show user-friendly message
    error_log("Database connection failed: " . $e->getMessage());
    die("Database connection failed. Please check your configuration.");
}

/**
 * Execute a prepared statement with parameters
 * @param string $sql SQL query with placeholders
 * @param array $params Parameters to bind
 * @return PDOStatement
 */
function executeQuery($sql, $params = []) {
    global $pdo;
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("Query execution failed: " . $e->getMessage());
        throw new Exception("Database operation failed");
    }
}

/**
 * Get the last inserted ID
 * @return string
 */
function getLastInsertId() {
    global $pdo;
    return $pdo->lastInsertId();
}

/**
 * Begin a database transaction
 */
function beginTransaction() {
    global $pdo;
    $pdo->beginTransaction();
}

/**
 * Commit a database transaction
 */
function commitTransaction() {
    global $pdo;
    $pdo->commit();
}

/**
 * Rollback a database transaction
 */
function rollbackTransaction() {
    global $pdo;
    $pdo->rollBack();
}
?>

