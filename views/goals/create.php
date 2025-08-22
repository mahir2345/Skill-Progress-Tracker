<?php
$pageTitle = 'Set New Goal - Smart Skill Progress Tracker';
$currentPage = 'goals';
$pageScript = 'goals.js';
include __DIR__ . '/../layouts/header.php';

// Skills are now provided by the routing in index.php
?>

<main style="max-width: 600px; margin: 50px auto; padding: 20px; background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
    <div style="text-align: center; margin-bottom: 30px;">
        <h2 style="color: #333; margin-bottom: 10px;">Set New Goal</h2>
        <p style="color: #666; margin: 0;">Define your learning objectives and target achievements</p>
    </div>

    <?php if (hasFlashMessage()): ?>
        <?php $flashMessages = getFlashMessages(); ?>
        <?php foreach ($flashMessages as $type => $messages): ?>
            <?php foreach ($messages as $message): ?>
                <div style="padding: 10px; margin-bottom: 20px; border-radius: 4px; <?php echo $type === 'success' ? 'background: #d4edda; color: #155724; border: 1px solid #c3e6cb;' : 'background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endforeach; ?>
        <?php endforeach; ?>
    <?php endif; ?>

    <form method="POST" action="<?php echo BASE_URL; ?>?page=goal-create">
        <div style="margin-bottom: 15px;">
            <label for="skill_id">Select Skill:</label><br>
            <select id="skill_id" name="skill_id" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;" required>
                <option value="">Choose a skill to set goal for</option>
                <?php foreach ($skills as $skill): ?>
                    <option value="<?php echo $skill['skill_id']; ?>">
                        <?php echo htmlspecialchars($skill['skill_name']); ?> 
                        (Current: <?php echo $skill['current_proficiency']; ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="margin-bottom: 15px;">
            <label for="target_proficiency">Target Proficiency Level:</label><br>
            <select id="target_proficiency" name="target_proficiency" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;" required>
                <option value="">Select target level</option>
                <option value="Beginner">Beginner</option>
                <option value="Intermediate">Intermediate</option>
                <option value="Advanced">Advanced</option>
                <option value="Expert">Expert</option>
            </select>
        </div>

        <div style="margin-bottom: 15px;">
            <label for="target_date">Target Date:</label><br>
            <input type="date" id="target_date" name="target_date" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;" min="<?php echo date('Y-m-d'); ?>" required>
        </div>

        <div style="margin-bottom: 15px;">
            <label for="description">Description (Optional):</label><br>
            <textarea id="description" name="description" rows="3" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; resize: vertical;" placeholder="Describe your goal and what you want to achieve..."></textarea>
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%; padding: 10px; margin-bottom: 15px;">Create Goal</button>

        <div style="text-align: center;">
            <a href="<?php echo BASE_URL; ?>?page=goals" style="color: #007bff; text-decoration: none;">← Back to Goals</a>
        </div>
    </form>
</main>

<script>
    // Auto-focus the skill selection
    document.getElementById('skill_id').focus();
    
    // Set default target date to 30 days from now
    const targetDateInput = document.getElementById('target_date');
    const defaultDate = new Date();
    defaultDate.setDate(defaultDate.getDate() + 30);
    targetDateInput.value = defaultDate.toISOString().split('T')[0];

    // Handle form submission and auto-refresh
    document.addEventListener('DOMContentLoaded', function() {
        const goalForm = document.querySelector('form');
        if (goalForm) {
            goalForm.addEventListener('submit', function(e) {
                // Show loading state
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.textContent;
                submitBtn.textContent = 'Creating Goal...';
                submitBtn.disabled = true;
                
                // Form will submit normally, but we'll handle the response
                // The PHP controller should redirect to goals list
                // If for some reason it doesn't, we'll force a refresh after 2 seconds
                setTimeout(function() {
                    if (window.location.search.includes('page=goal-create')) {
                        // Still on create page, redirect to goals list
                        window.location.href = '<?php echo BASE_URL; ?>?page=goals';
                    }
                }, 2000);
            });
        }
    });
</script>
