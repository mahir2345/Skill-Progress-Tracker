<?php
/**
 * 404 Error Page for Smart Skill Progress Tracker
 * CSE470 Software Engineering Project
 */

// Set HTTP response code
http_response_code(404);

// Include header
include_once __DIR__ . '/../layouts/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8 text-center">
            <div class="error-page">
                <h1 class="display-1 text-primary">404</h1>
                <h2 class="mb-4">Page Not Found</h2>
                <p class="lead mb-4">Sorry, the page you are looking for doesn't exist or has been moved.</p>
                
                <div class="mb-4">
                    <svg width="200" height="150" viewBox="0 0 200 150" class="mb-3">
                        <circle cx="100" cy="75" r="60" fill="#f8f9fa" stroke="#dee2e6" stroke-width="2"/>
                        <circle cx="85" cy="65" r="8" fill="#6c757d"/>
                        <circle cx="115" cy="65" r="8" fill="#6c757d"/>
                        <path d="M 80 90 Q 100 110 120 90" stroke="#6c757d" stroke-width="3" fill="none"/>
                    </svg>
                </div>
                
                <div class="d-flex justify-content-center gap-3">
                    <a href="<?php echo BASE_URL; ?>?page=dashboard" class="btn btn-primary">
                        <i class="fas fa-home me-2"></i>Go to Dashboard
                    </a>
                    <a href="<?php echo BASE_URL; ?>?page=skills" class="btn btn-outline-primary">
                        <i class="fas fa-list me-2"></i>View Skills
                    </a>
                    <button onclick="history.back()" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Go Back
                    </button>
                </div>
                
                <div class="mt-4">
                    <p class="text-muted">If you believe this is an error, please contact support.</p>
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
    color: #007bff;
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
</style>

<?php
// Include footer
include_once __DIR__ . '/../layouts/footer.php';
?>