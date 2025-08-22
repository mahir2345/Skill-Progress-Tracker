<?php
$pageTitle = 'Register - Smart Skill Progress Tracker';
require_once __DIR__ . '/../layouts/header.php';
?>

<main>
    <div style="max-width: 500px; margin: 50px auto; padding: 20px; border: 2px solid #ddd; border-radius: 10px; background-color: #f9f9f9;">
        <div style="text-align: center; margin-bottom: 30px;">
            <h1 style="color: #333; margin-bottom: 10px;">Skill Tracker</h1>
            <h2 style="color: #666; margin-bottom: 5px;">Create Account</h2>
            <p style="color: #888;">Join us and start tracking your skill progress</p>
        </div>

        <form id="registerForm" action="<?php echo BASE_URL; ?>?page=register" method="POST">
            <?php echo generateCSRFField(); ?>
            
            <div style="margin-bottom: 15px; display: flex; gap: 15px;">
                <div style="flex: 1;">
                    <label for="first_name">First Name:</label><br>
                    <input type="text" id="first_name" name="first_name" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;" placeholder="Enter your first name" required>
                </div>
                <div style="flex: 1;">
                    <label for="last_name">Last Name:</label><br>
                    <input type="text" id="last_name" name="last_name" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;" placeholder="Enter your last name" required>
                </div>
            </div>

            <div style="margin-bottom: 15px;">
                <label for="username">Username:</label><br>
                <input type="text" id="username" name="username" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;" placeholder="Choose a username" required minlength="3">
            </div>

            <div style="margin-bottom: 15px;">
                <label for="email">Email Address:</label><br>
                <input type="email" id="email" name="email" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;" placeholder="Enter your email address" required>
            </div>

            <div style="margin-bottom: 15px;">
                <label for="password">Password:</label><br>
                <input type="password" id="password" name="password" style="width: 85%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;" placeholder="Create a strong password" required minlength="8">
                <button type="button" id="togglePassword" style="width: 12%; padding: 8px; margin-left: 3%; border: 1px solid #ccc; border-radius: 4px; background: #f8f9fa;">Show</button>
            </div>

            <div style="margin-bottom: 15px;">
                <label for="confirm_password">Confirm Password:</label><br>
                <input type="password" id="confirm_password" name="confirm_password" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;" placeholder="Confirm your password" required>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 10px; margin-bottom: 15px;">Create Account</button>

            <div style="text-align: center;">
                <p>Already have an account? 
                    <a href="<?php echo BASE_URL; ?>?page=login" style="color: #007bff; text-decoration: none; font-weight: bold;">Sign In</a>
                </p>
            </div>
        </form>

    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Simple password toggle
    const togglePassword = document.getElementById('togglePassword');
    const passwordField = document.getElementById('password');
    
    if (togglePassword) {
        togglePassword.addEventListener('click', function() {
            const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordField.setAttribute('type', type);
            this.textContent = type === 'password' ? 'Show' : 'Hide';
        });
    }

    // Auto focus first name
    const firstNameField = document.getElementById('first_name');
    if (firstNameField) {
        firstNameField.focus();
    }
});
</script>

</main>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>



