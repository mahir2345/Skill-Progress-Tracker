<?php
/**
 * 500 Error Page for Smart Skill Progress Tracker
 * CSE470 Software Engineering Project
 */

// Set HTTP response code
http_response_code(500);

// Include header
include_once __DIR__ . '/../layouts/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8 text-center">
            <div class="error-page">
                <h1 class="display-1 text-danger">500</h1>
                <h2 class="mb-4">Internal Server Error</h2>
                <p class="lead mb-4">Something went wrong on our end. We're working to fix it!</p>
                
                <div class="mb-4">
                    <svg width="200" height="150" viewBox="0 0 200 150" class="mb-3">
                        <circle cx="100" cy="75" r="60" fill="#f8f9fa" stroke="#dee2e6" stroke-width="2"/>
                        <circle cx="85" cy="65" r="8" fill="#dc3545"/>
                        <circle cx="115" cy="65" r="8" fill="#dc3545"/>
                        <path d="M 80 100 Q 100 80 120 100" stroke="#dc3545" stroke-width="3" fill="none"/>
                        <text x="100" y="130" text-anchor="middle" font-size="12" fill="#6c757d">Oops!</text>
                    </svg>
                </div>
                
                <div class="alert alert-warning" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Technical Issue:</strong> Our servers are experiencing some difficulties.
                </div>
                
                <div class="d-flex justify-content-center gap-3">
                    <a href="<?php echo BASE_URL; ?>?page=dashboard" class="btn btn-primary">
                        <i class="fas fa-home me-2"></i>Go to Dashboard
                    </a>
                    <button onclick="location.reload()" class="btn btn-outline-primary">
                        <i class="fas fa-redo me-2"></i>Try Again
                    </button>
                    <button onclick="history.back()" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Go Back
                    </button>
                </div>
                
                <div class="mt-4">
                    <p class="text-muted">If this problem persists, please contact our support team.</p>
                    <small class="text-muted">Error Code: 500 | Time: <?php echo date('Y-m-d H:i:s'); ?></small>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.error-page {
    padding: 2rem;
}

.display-1 {
    font-size: 6rem;
    font-weight: bold;
    color: #dc3545;
}

.btn {
    padding: 0.75rem 1.5rem;
    border-radius: 0.5rem;
    text-decoration: none;
    transition: all 0.3s ease;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.gap-3 {
    gap: 1rem;
}

.alert {
    border-radius: 0.5rem;
    margin: 1rem 0;
}
</style>

<?php
// Include footer
include_once __DIR__ . '/../layouts/footer.php';
?>