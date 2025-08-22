<?php
$pageTitle = 'Log Progress - Smart Skill Progress Tracker';
$currentPage = 'progress';
$pageScript = 'progress.js';
include __DIR__ . '/../layouts/header.php';

// Include the controller
require_once __DIR__ . '/../../controllers/ProgressController.php';
require_once __DIR__ . '/../../controllers/SkillController.php';

// Get skills for dropdown
$skillController = new SkillController();
$skillsData = $skillController->index();
$skills = $skillsData['skills'] ?? [];
?>

<main>
    <div style="max-width: 600px; margin: 50px auto; padding: 20px; border: 2px solid #ddd; border-radius: 10px; background-color: #f9f9f9;">
        <div style="text-align: center; margin-bottom: 30px;">
            <h1 style="color: #333; margin-bottom: 10px;">Skill Tracker</h1>
            <h2 style="color: #666; margin-bottom: 5px;">Log Progress</h2>
            <p style="color: #888;">Record your learning progress and achievements</p>
        </div>

        <form id="progressForm" action="<?php echo BASE_URL; ?>?page=progress-create" method="POST">
            <?php echo generateCSRFField(); ?>
            
            <div style="margin-bottom: 15px;">
                <label for="skill_id">Skill:</label><br>
                <select id="skill_id" name="skill_id" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;" required>
                    <option value="">Select a skill</option>
                    <?php foreach ($skills as $skill): ?>
                        <option value="<?php echo htmlspecialchars($skill['id']); ?>">
                            <?php echo htmlspecialchars($skill['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="margin-bottom: 15px;">
                <label for="hours_spent">Hours Spent:</label><br>
                <input type="number" id="hours_spent" name="hours_spent" step="0.5" min="0.1" max="24" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;" placeholder="e.g., 2.5" required>
            </div>

            <div style="margin-bottom: 15px;">
                <label for="description">What did you learn/practice?</label><br>
                <textarea id="description" name="description" rows="4" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; resize: vertical;" placeholder="Describe what you learned or practiced..." required></textarea>
            </div>

            <div style="margin-bottom: 15px;">
                <label for="difficulty_level">Difficulty Level:</label><br>
                <select id="difficulty_level" name="difficulty_level" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;" required>
                    <option value="">Select difficulty</option>
                    <option value="1">1 - Very Easy</option>
                    <option value="2">2 - Easy</option>
                    <option value="3">3 - Medium</option>
                    <option value="4">4 - Hard</option>
                    <option value="5">5 - Very Hard</option>
                </select>
            </div>

            <div style="margin-bottom: 15px;">
                <label for="progress_date">Date:</label><br>
                <input type="date" id="progress_date" name="progress_date" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;" value="<?php echo date('Y-m-d'); ?>" required>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 10px; margin-bottom: 15px;">Log Progress</button>

            <div style="text-align: center;">
                <a href="<?php echo BASE_URL; ?>?page=progress" style="color: #007bff; text-decoration: none;">← Back to Progress</a>
            </div>
        </form>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Auto focus skill selection
        const skillField = document.getElementById('skill_id');
        if (skillField) {
            skillField.focus();
        }
    });
    </script>
</main>

