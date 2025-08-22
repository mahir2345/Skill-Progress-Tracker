<?php
$pageTitle = 'Skills - Smart Skill Progress Tracker';
$currentPage = 'skills';
$pageScript = 'skills.js';
include __DIR__ . '/../layouts/header.php';

// Skills data is now provided by the routing in index.php
?>

<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h2 fw-bold text-dark">
                <i class="fas fa-brain me-3 text-primary"></i>My Skills
            </h1>
            <p class="text-muted mb-0">Manage and track your skills and expertise areas</p>
        </div>
        <div class="col-md-4 text-md-end">
            <a href="<?php echo BASE_URL; ?>?page=skill-create" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Add New Skill
            </a>
        </div>
    </div>

    <!-- Skills Distribution Chart -->
    <div class="row mb-4">
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-pie me-2"></i>Skills Distribution
                    </h5>
                </div>
                <div class="card-body">
                    <div class="chart-container" style="height: 300px;">
                        <canvas id="skillsDistributionChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-bar me-2"></i>Proficiency Levels
                    </h5>
                </div>
                <div class="card-body">
                    <div class="chart-container" style="height: 300px;">
                        <canvas id="proficiencyChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters and Search -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form id="filterForm" class="row g-3">
                        <div class="col-md-4">
                            <label for="searchQuery" class="form-label">Search Skills</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-search"></i>
                                </span>
                                <input type="text" class="form-control" id="searchQuery" name="search" 
                                       placeholder="Search by name or description..." 
                                       value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label for="categoryFilter" class="form-label">Category</label>
                            <select class="form-select" id="categoryFilter" name="category">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['category_id']; ?>" 
                                            <?php echo ($_GET['category'] ?? '') == $category['category_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['category_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="proficiencyFilter" class="form-label">Proficiency</label>
                            <select class="form-select" id="proficiencyFilter" name="proficiency">
                                <option value="">All Levels</option>
                                <option value="Beginner" <?php echo ($_GET['proficiency'] ?? '') === 'Beginner' ? 'selected' : ''; ?>>Beginner</option>
                                <option value="Intermediate" <?php echo ($_GET['proficiency'] ?? '') === 'Intermediate' ? 'selected' : ''; ?>>Intermediate</option>
                                <option value="Advanced" <?php echo ($_GET['proficiency'] ?? '') === 'Advanced' ? 'selected' : ''; ?>>Advanced</option>
                                <option value="Expert" <?php echo ($_GET['proficiency'] ?? '') === 'Expert' ? 'selected' : ''; ?>>Expert</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-outline-primary me-2">
                                <i class="fas fa-filter me-1"></i>Filter
                            </button>
                            <button type="button" class="btn btn-outline-secondary" id="clearFilters">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Debug Information -->
    <?php if (isset($_GET['debug'])): ?>
        <div class="alert alert-info">
            <h6>Debug Information:</h6>
            <p><strong>Skills count:</strong> <?php echo isset($skills) ? count($skills) : 'undefined'; ?></p>
            <p><strong>Skills variable type:</strong> <?php echo isset($skills) ? gettype($skills) : 'undefined'; ?></p>
            <p><strong>Categories count:</strong> <?php echo isset($categories) ? count($categories) : 'undefined'; ?></p>
            <?php if (isset($skills) && is_array($skills) && count($skills) > 0): ?>
                <p><strong>First skill:</strong> <?php echo htmlspecialchars(json_encode($skills[0])); ?></p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Skills Count and Actions -->
    <div class="row mb-3">
        <div class="col-md-6">
            <div class="d-flex align-items-center">
                <span class="text-muted me-3">
                    Showing <?php echo isset($skills) ? count($skills) : 0; ?> skills
                </span>
                <?php if (!empty($_GET['search']) || !empty($_GET['category']) || !empty($_GET['proficiency'])): ?>
                    <span class="badge bg-info">Filtered</span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Skills Display -->
    <div id="skillsContainer">
        <?php if (!empty($skills)): ?>
            <!-- Skills List (Dashboard Style) -->
            <div class="row">
                <?php foreach ($skills as $skill): ?>
                    <div class="col-12 mb-3">
                        <div class="skill-card">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <div class="skill-title"><?php echo htmlspecialchars($skill['skill_name']); ?></div>
                                    <div class="skill-category"><?php echo htmlspecialchars($skill['category_name']); ?></div>
                                    <div class="skill-proficiency">
                                        <span class="proficiency-badge proficiency-<?php echo strtolower($skill['current_proficiency']); ?>">
                                            <?php echo $skill['current_proficiency']; ?>
                                        </span>
                                    </div>
                                    <?php if (!empty($skill['description'])): ?>
                                        <div class="text-muted small mt-2">
                                            <?php echo htmlspecialchars(substr($skill['description'], 0, 100)); ?>
                                            <?php if (strlen($skill['description']) > 100): ?>...<?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="text-end">
                                    <div class="text-muted small mb-2">
                                        <?php echo number_format($skill['total_hours'] ?? 0, 1); ?>h total
                                    </div>
                                    <div class="btn-group btn-group-sm mb-2">
                                        <a href="<?php echo BASE_URL; ?>?page=skill-view&id=<?php echo $skill['skill_id']; ?>" 
                                           class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-eye me-1"></i>View
                                        </a>
                                        <a href="<?php echo BASE_URL; ?>?page=progress-create&skill_id=<?php echo $skill['skill_id']; ?>" 
                                           class="btn btn-outline-success btn-sm">
                                            <i class="fas fa-plus me-1"></i>Log Progress
                                        </a>
                                    </div>
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?php echo BASE_URL; ?>?page=skill-edit&id=<?php echo $skill['skill_id']; ?>" 
                                           class="btn btn-outline-warning btn-sm">
                                            <i class="fas fa-edit me-1"></i>Edit
                                        </a>
                                        <button type="button" class="btn btn-outline-danger btn-sm" 
                                                onclick="deleteSkill(<?php echo $skill['skill_id']; ?>)">
                                            <i class="fas fa-trash me-1"></i>Delete
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php else: ?>
            <!-- Empty State -->
            <div class="text-center py-5">
                <div class="mb-4">
                    <i class="fas fa-brain fa-4x text-muted opacity-50"></i>
                </div>
                <h4 class="text-muted mb-3">No skills found</h4>
                <?php if (!empty($_GET['search']) || !empty($_GET['category']) || !empty($_GET['proficiency'])): ?>
                    <p class="text-muted mb-4">Try adjusting your filters or search terms.</p>
                    <button class="btn btn-outline-primary me-2" id="clearFiltersBtn">
                        <i class="fas fa-times me-2"></i>Clear Filters
                    </button>
                <?php else: ?>
                    <p class="text-muted mb-4">Start building your skill portfolio by adding your first skill!</p>
                <?php endif; ?>
                <a href="<?php echo BASE_URL; ?>?page=skill-create" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Add Your First Skill
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if (isset($pagination) && $pagination['total_pages'] > 1): ?>
        <div class="row mt-4">
            <div class="col-12">
                <nav aria-label="Skills pagination">
                    <ul class="pagination justify-content-center">
                        <?php if ($pagination['current_page'] > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $pagination['current_page'] - 1])); ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php for ($i = max(1, $pagination['current_page'] - 2); $i <= min($pagination['total_pages'], $pagination['current_page'] + 2); $i++): ?>
                            <li class="page-item <?php echo $i === $pagination['current_page'] ? 'active' : ''; ?>">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $pagination['current_page'] + 1])); ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- All Skills Section -->
    <div class="row mt-5">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-list-ul me-2"></i>
                        All My Skills
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($skills)): ?>
                        <div class="row">
                            <?php foreach ($skills as $skill): ?>
                                <div class="col-md-6 col-lg-4 mb-3">
                                    <div class="card h-100 skill-card">
                                        <div class="card-body">
                                            <h6 class="card-title"><?php echo htmlspecialchars($skill['skill_name']); ?></h6>
                                            <p class="text-muted small mb-2"><?php echo htmlspecialchars($skill['category_name']); ?></p>
                                            <div class="mb-2">
                                                <span class="badge bg-<?php 
                                                    echo $skill['proficiency_level'] === 'Beginner' ? 'success' : 
                                                        ($skill['proficiency_level'] === 'Intermediate' ? 'warning' : 
                                                        ($skill['proficiency_level'] === 'Advanced' ? 'info' : 'primary')); 
                                                ?>">
                                                    <?php echo htmlspecialchars($skill['proficiency_level']); ?>
                                                </span>
                                            </div>
                                            <p class="card-text small">
                                                <?php 
                                                $description = htmlspecialchars($skill['description']);
                                                echo strlen($description) > 80 ? substr($description, 0, 80) . '...' : $description;
                                                ?>
                                            </p>
                                            <div class="text-muted small mb-2">
                                                <i class="fas fa-clock"></i> <?php echo number_format($skill['total_hours'], 1); ?>h total
                                            </div>
                                            <div class="d-flex gap-1">
                                                <a href="?page=skills&highlight=<?php echo $skill['skill_id']; ?>" class="btn btn-outline-primary btn-sm">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                                <a href="?page=progress-create&skill_id=<?php echo $skill['skill_id']; ?>" class="btn btn-outline-success btn-sm">
                                                    <i class="fas fa-plus"></i> Log
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-graduation-cap fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No Skills Added Yet</h5>
                            <p class="text-muted">Start building your skill portfolio by adding your first skill!</p>
                            <a href="?page=skill-create" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Add Your First Skill
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Pass PHP data to JavaScript
window.skillsData = {
    skills: <?php echo json_encode($skills); ?>,
    categories: <?php echo json_encode($categories); ?>,
    pagination: <?php echo json_encode($pagination); ?>
};

function deleteSkill(skillId) {
    ModalHelper.showConfirmation(
        'Delete Skill',
        'Are you sure you want to delete this skill? This action cannot be undone and will also delete all associated progress entries.',
        function() {
            // Perform deletion
            Utils.showLoading();
            
            API.delete(`/skills/${skillId}`)
                .then(response => {
                    if (response.success) {
                        Utils.showToast('Skill deleted successfully', 'success');
                        // Remove skill from display
                        const skillElements = document.querySelectorAll(`[data-skill-id="${skillId}"]`);
                        skillElements.forEach(element => {
                            element.style.transition = 'all 0.3s ease';
                            element.style.opacity = '0';
                            element.style.transform = 'scale(0.8)';
                            setTimeout(() => element.remove(), 300);
                        });
                    } else {
                        Utils.showToast(response.message || 'Failed to delete skill', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error deleting skill:', error);
                    Utils.showToast('An error occurred while deleting the skill', 'error');
                })
                .finally(() => {
                    Utils.hideLoading();
                });
        }
    );
}
</script>

