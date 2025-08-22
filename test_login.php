<?php
require_once 'config/config.php';

echo "<h2>Login Test</h2>";
echo "<p>Session ID: " . session_id() . "</p>";
echo "<p>Is logged in: " . (isLoggedIn() ? 'YES' : 'NO') . "</p>";
echo "<p>User ID: " . (getCurrentUserId() ?? 'NULL') . "</p>";
echo "<p>Session data: " . print_r($_SESSION, true) . "</p>";

if (isLoggedIn()) {
    // Test getting skills
    $skill = new Skill();
    $userId = getCurrentUserId();
    $skillsData = $skill->getUserSkills($userId, [], 1, 10);
    echo "<h3>Skills Data:</h3>";
    echo "<pre>" . print_r($skillsData, true) . "</pre>";
} else {
    echo "<p><a href='?page=login'>Please login first</a></p>";
}
?>