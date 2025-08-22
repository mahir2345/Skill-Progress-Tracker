<?php
// Include configuration if not already included
if (!function_exists('getFlashMessages')) {
    require_once __DIR__ . '/../../config/config.php';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Smart Skill Progress Tracker'; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Custom CSS -->
    <link href="<?php echo BASE_URL; ?>/assets/css/style.css" rel="stylesheet">
    
    <!-- CSRF Token for AJAX requests -->
    <meta name="csrf-token" content="<?php echo generateCSRFToken(); ?>">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="<?php echo BASE_URL; ?>">
                <i class="fas fa-chart-line me-2"></i>
                Skill Tracker
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <?php if (isLoggedIn()): ?>
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($currentPage ?? '') === 'dashboard' ? 'active' : ''; ?>" 
                               href="<?php echo BASE_URL; ?>?page=dashboard">
                                <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($currentPage ?? '') === 'skills' ? 'active' : ''; ?>" 
                               href="<?php echo BASE_URL; ?>?page=skills">
                                <i class="fas fa-brain me-1"></i>Skills
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($currentPage ?? '') === 'progress' ? 'active' : ''; ?>" 
                               href="<?php echo BASE_URL; ?>?page=progress">
                                <i class="fas fa-chart-bar me-1"></i>Progress
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($currentPage ?? '') === 'goals' ? 'active' : ''; ?>" 
                               href="<?php echo BASE_URL; ?>?page=goals">
                                <i class="fas fa-target me-1"></i>Goals
                            </a>
                        </li>
                    </ul>
                    
                    <ul class="navbar-nav">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" 
                               data-bs-toggle="dropdown">
                                <i class="fas fa-user me-1"></i>
                                <?php echo htmlspecialchars($_SESSION['first_name'] ?? 'User'); ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="<?php echo BASE_URL; ?>/controllers/AuthController.php?action=logout">
                                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                                    </a>
                                </li>
                            </ul>
                        </li>
                    </ul>
                <?php else: ?>
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>?page=login">
                                <i class="fas fa-sign-in-alt me-1"></i>Login
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>?page=register">
                                <i class="fas fa-user-plus me-1"></i>Register
                            </a>
                        </li>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Flash Messages -->
    <?php if (hasFlashMessage()): ?>
        <div class="container mt-3">
            <?php foreach (getFlashMessages() as $type => $messages): ?>
                <?php foreach ($messages as $message): ?>
                    <div class="alert alert-<?php echo $type === 'error' ? 'danger' : $type; ?> alert-dismissible fade show" role="alert">
                        <i class="fas fa-<?php echo $type === 'error' ? 'exclamation-triangle' : ($type === 'success' ? 'check-circle' : 'info-circle'); ?> me-2"></i>
                        <?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Main Content -->
    <main class="main-content">

