<?php
$pageTitle = 'Dashboard - Smart Skill Progress Tracker';
$currentPage = 'dashboard';
$pageScript = 'dashboard.js';
include __DIR__ . '/../layouts/header.php';

// Include the controller
require_once __DIR__ . '/../../controllers/DashboardController.php';

// Get dashboard data from controller
$dashboardController = new DashboardController();
$dashboardData = $dashboardController->index();
extract($dashboardData);
?>

<div class="container-fluid py-4">
    <!-- Welcome Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="dashboard-card">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="display-5 fw-bold mb-2">
                            Welcome back, <?php echo htmlspecialchars($_SESSION['first_name']); ?>!
                        </h1>
                        <p class="lead mb-0">
                            Ready to continue your learning journey? Here's your progress overview.
                        </p>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <div class="d-flex flex-column align-items-md-end">
                            <div class="mb-2">
                                <small class="opacity-75">Current Streak</small>
                            </div>
                            <div class="d-flex align-items-center">
                                <i class="fas fa-fire me-2" style="font-size: 1.5rem;"></i>
                                <span class="h3 mb-0"><?php echo $streaks['current_streak']; ?> days</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-brain"></i>
                </div>
                <div class="stat-number"><?php echo $user_stats['total_skills'] ?? 0; ?></div>
                <div class="stat-label">Total Skills</div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-number"><?php echo number_format($progress_stats['total_hours'] ?? 0, 1); ?></div>
                <div class="stat-label">Hours This Month</div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-tasks"></i>
                </div>
                <div class="stat-number"><?php echo $progress_stats['total_tasks'] ?? 0; ?></div>
                <div class="stat-label">Tasks Completed</div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-target"></i>
                </div>
                <div class="stat-number"><?php echo $goal_stats['completed_goals'] ?? 0; ?>/<?php echo $goal_stats['total_goals'] ?? 0; ?></div>
                <div class="stat-label">Goals Achieved</div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Quick Actions & Recent Activity -->
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-bolt me-2"></i>Quick Actions
                    </h5>
                </div>
                <div class="card-body">
                    <hr class="my-4">
                    
                    <h6 class="mb-3">
                        <i class="fas fa-history me-2"></i>Recent Activity
                    </h6>
                    <div id="recentActivity">
                        <?php if (!empty($recent_progress)): ?>
                            <?php foreach (array_slice($recent_progress, 0, 3) as $entry): ?>
                                <div class="d-flex align-items-center mb-3">
                                    <div class="flex-shrink-0">
                                        <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center" 
                                             style="width: 32px; height: 32px;">
                                            <i class="fas fa-chart-bar text-white" style="font-size: 0.75rem;"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <div class="fw-semibold" style="font-size: 0.875rem;">
                                            <?php echo htmlspecialchars($entry['skill_name']); ?>
                                        </div>
                                        <div class="text-muted" style="font-size: 0.75rem;">
                                            <?php 
                                                echo number_format($entry['hours_spent'] ?? 0, 1); 
                                            ?>h • <?php echo Utils::formatRelativeTimeStatic($entry['created_at']); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center text-muted py-3">
                                <i class="fas fa-clock fa-2x mb-2 opacity-50"></i>
                                <p class="mb-0">No recent activity</p>
                                <small>Start logging your progress!</small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Recent Skills -->
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-brain me-2"></i>Recent Skills
                    </h5>
                    <a href="<?php echo BASE_URL; ?>?page=skills" class="btn btn-sm btn-outline-primary">
                        View All
                    </a>
                </div>
                <div class="card-body">
                    <?php if (!empty($recent_skills)): ?>
                        <?php foreach ($recent_skills as $skill): ?>
                            <div class="skill-card mb-3">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <div class="skill-title"><?php echo htmlspecialchars($skill['skill_name']); ?></div>
                                        <div class="skill-category"><?php echo htmlspecialchars($skill['category_name']); ?></div>
                                        <div class="skill-proficiency">
                                            <span class="proficiency-badge proficiency-<?php echo strtolower($skill['current_proficiency']); ?>">
                                                <?php echo $skill['current_proficiency']; ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <div class="text-muted small">
                                            <?php echo number_format($skill['total_hours'] ?? 0, 1); ?>h total
                                        </div>
                                        <a href="<?php echo BASE_URL; ?>?page=skill-view&id=<?php echo $skill['skill_id']; ?>" 
                                           class="btn btn-sm btn-outline-primary mt-2">
                                            View
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-brain fa-3x mb-3 opacity-50"></i>
                            <h6>No skills yet</h6>
                            <p class="mb-3">Start by adding your first skill to track!</p>
                            <a href="<?php echo BASE_URL; ?>?page=skill-create" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Add Your First Skill
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Upcoming Goals -->
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-target me-2"></i>Upcoming Goals
                    </h5>
                    <a href="<?php echo BASE_URL; ?>?page=goals" class="btn btn-sm btn-outline-primary">
                        View All
                    </a>
                </div>
                <div class="card-body">
                    <?php if (!empty($upcoming_goals)): ?>
                        <?php foreach ($upcoming_goals as $goal): ?>
                            <div class="goal-card mb-3 <?php echo strtotime($goal['target_date']) < time() ? 'overdue' : ''; ?>">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold"><?php echo htmlspecialchars($goal['skill_name']); ?></div>
                                        <div class="text-muted small">
                                            Target: <?php echo $goal['target_proficiency']; ?>
                                        </div>
                                        <div class="text-muted small">
                                            Due: <?php echo formatDate($goal['target_date'], 'M j, Y'); ?>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <?php if (strtotime($goal['target_date']) < time()): ?>
                                            <span class="badge bg-danger">Overdue</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning">Due Soon</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-target fa-3x mb-3 opacity-50"></i>
                            <h6>No upcoming goals</h6>
                            <p class="mb-3">Set goals to stay motivated and track your progress!</p>
                            <a href="<?php echo BASE_URL; ?>?page=goal-create" class="btn btn-success">
                                <i class="fas fa-plus me-2"></i>Set Your First Goal
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Category Progress -->
    <?php if (!empty($category_progress)): ?>
    <div class="row">
        <div class="col-12 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-pie me-2"></i>Progress by Category
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="chart-container" style="height: 250px;">
                                <canvas id="categoryChart"></canvas>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="mt-3 mt-lg-0">
                                <?php foreach ($category_progress as $index => $category): ?>
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div class="d-flex align-items-center">
                                            <div class="me-3" style="width: 12px; height: 12px; background-color: <?php 
                                                require_once __DIR__ . '/../../helpers/ChartHelper.php';
echo ChartHelper::generateColors(count($category_progress))[$index];
                                            ?>; border-radius: 50%;"></div>
                                            <span class="fw-semibold"><?php echo htmlspecialchars($category['category_name']); ?></span>
                                        </div>
                                        <div class="text-end">
                                            <div class="fw-bold"><?php echo number_format($category['total_hours'], 1); ?>h</div>
                                            <small class="text-muted"><?php echo $category['total_entries']; ?> entries</small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
// Pass PHP data to JavaScript
window.dashboardData = {
    dailyProgress: <?php echo json_encode($daily_progress); ?>,
    categoryProgress: <?php echo json_encode($category_progress); ?>,
    userStats: <?php echo json_encode($user_stats); ?>,
    progressStats: <?php echo json_encode($progress_stats); ?>
};
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>

