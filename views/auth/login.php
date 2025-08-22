<?php
$pageTitle = 'Login - Smart Skill Progress Tracker';
require_once __DIR__ . '/../layouts/header.php';
?>

<main class="container">
    <div class="text-center mb-4">
        <h1>Skill Tracker</h1>
        <p>Please login to continue</p>
    </div>
    
    <div style="max-width: 400px; margin: 0 auto; background: white; padding: 20px; border: 2px solid #ddd; border-radius: 5px;">
        <h2 style="text-align: center; margin-bottom: 20px;">Welcome Back</h2>
        <p style="text-align: center; color: #666; margin-bottom: 30px;">Sign in to your account to continue</p>

        <form id="loginForm" action="<?php echo BASE_URL; ?>?page=login" method="POST">
            <?php echo generateCSRFField(); ?>
            
            <div style="margin-bottom: 15px;">
                <label for="username" class="form-label">Username or Email:</label>
                <input type="text" class="form-control" id="username" name="username" 
                       placeholder="Enter your username or email" required>
            </div>
            <div style="margin-bottom: 15px;">
                <label for="password" class="form-label">Password:</label>
                <input type="password" class="form-control" id="password" name="password" 
                       placeholder="Enter your password" required>
                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 10px; margin-bottom: 15px;">Sign In</button>

            <div style="text-align: center;">
                <p>Don't have an account? 
                    <a href="<?php echo BASE_URL; ?>?page=register" style="color: #007bff; text-decoration: none; font-weight: bold;">Create Account</a>
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

    // Auto focus username
    const usernameField = document.getElementById('username');
    if (usernameField) {
        usernameField.focus();
    }

});
</script>

</main>