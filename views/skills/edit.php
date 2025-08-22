<?php
$pageTitle = 'Edit Skill - Smart Skill Progress Tracker';
$currentPage = 'skills';
$pageScript = 'skills.js';
include __DIR__ . '/../layouts/header.php';

// Skill and categories data are provided by the routing in index.php
?>

<main>
    <div style="max-width: 600px; margin: 50px auto; padding: 20px; border: 2px solid #ddd; border-radius: 10px; background-color: #f9f9f9;">
        <div style="text-align: center; margin-bottom: 30px;">
            <h1 style="color: #333; margin-bottom: 10px;">Skill Tracker</h1>
            <h2 style="color: #666; margin-bottom: 5px;">Edit Skill</h2>
            <p style="color: #888;">Update your skill information</p>
        </div>

        <form id="skillForm" action="<?php echo BASE_URL; ?>?page=skill-edit&id=<?php echo $skill['skill_id']; ?>" method="POST">
            <?php echo generateCSRFField(); ?>
            
            <div style="margin-bottom: 15px;">
                <label for="skill_name">Skill Name:</label><br>
                <input type="text" id="skill_name" name="skill_name" 
                       value="<?php echo htmlspecialchars($skill['skill_name'] ?? ''); ?>"
                       style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;" 
                       placeholder="e.g., JavaScript, Python, Guitar" required>
            </div>

            <div style="margin-bottom: 15px;">
                <label for="description">Description:</label><br>
                <textarea id="description" name="description" rows="4" 
                          style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; resize: vertical;" 
                          placeholder="Describe what this skill involves..." required><?php echo htmlspecialchars($skill['description'] ?? ''); ?></textarea>
            </div>

            <div style="margin-bottom: 15px;">
                <label for="category_id">Category:</label><br>
                <select id="category_id" name="category_id" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;" required>
                    <option value="">Select a category</option>
                    <?php if (isset($categories) && is_array($categories)): ?>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['category_id']; ?>"
                                    <?php echo ($skill['category_id'] == $category['category_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['category_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div style="margin-bottom: 15px;">
                <label for="current_proficiency">Current Level:</label><br>
                <select id="current_proficiency" name="current_proficiency" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;" required>
                    <option value="">Select your current level</option>
                    <option value="Beginner" <?php echo ($skill['current_proficiency'] == 'Beginner') ? 'selected' : ''; ?>>Beginner</option>
                    <option value="Intermediate" <?php echo ($skill['current_proficiency'] == 'Intermediate') ? 'selected' : ''; ?>>Intermediate</option>
                    <option value="Advanced" <?php echo ($skill['current_proficiency'] == 'Advanced') ? 'selected' : ''; ?>>Advanced</option>
                    <option value="Expert" <?php echo ($skill['current_proficiency'] == 'Expert') ? 'selected' : ''; ?>>Expert</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 10px; margin-bottom: 15px;">Update Skill</button>

            <div style="text-align: center;">
                <a href="<?php echo BASE_URL; ?>?page=skill-view&id=<?php echo $skill['skill_id']; ?>" style="color: #007bff; text-decoration: none; margin-right: 15px;">← Back to Skill</a>
                <a href="<?php echo BASE_URL; ?>?page=skills" style="color: #007bff; text-decoration: none;">← Back to Skills</a>
            </div>
        </form>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Auto focus skill name
        const nameField = document.getElementById('skill_name');
        if (nameField) {
            nameField.focus();
        }

        // Handle form submission
        const skillForm = document.getElementById('skillForm');
        if (skillForm) {
            skillForm.addEventListener('submit', function(e) {
                // Show loading state
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.textContent;
                submitBtn.textContent = 'Updating Skill...';
                submitBtn.disabled = true;
                
                // Form will submit normally, but we'll handle the response
                // The PHP controller should redirect to skill view or skills list
                // If for some reason it doesn't, we'll force a refresh after 2 seconds
                setTimeout(function() {
                    if (window.location.search.includes('page=skill-edit')) {
                        // Still on edit page, redirect to skill view
                        window.location.href = '<?php echo BASE_URL; ?>?page=skill-view&id=<?php echo $skill['skill_id']; ?>';
                    }
                }, 2000);
            });
        }
    });
    </script>
</main>

<?php include __DIR__ . '/../layouts/footer.php'; ?>