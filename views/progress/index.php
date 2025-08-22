<?php
$pageTitle = 'Progress - Smart Skill Progress Tracker';
$currentPage = 'progress';
$pageScript = 'progress.js';
include __DIR__ . '/../layouts/header.php';

// Include the controller
require_once __DIR__ . '/../../controllers/ProgressController.php';

// Get progress data from controller
$progressController = new ProgressController();
$progressData = $progressController->index();
extract($progressData);
?>

<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h2 fw-bold text-dark">
                <i class="fas fa-chart-bar me-3 text-primary"></i>Progress Tracking
            </h1>
            <p class="text-muted mb-0">Monitor your learning journey and track skill development</p>
        </div>
        <div class="col-md-4 text-md-end">
            <a href="<?php echo BASE_URL; ?>?page=progress-create" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Log Progress
            </a>
        </div>
    </div>



    <!-- Charts Row -->
    <div class="row mb-4">
        <!-- Progress Over Time Chart -->
        <div class="col-12 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-line me-2"></i>Progress Over Time
                    </h5>
                    <div class="btn-group btn-group-sm" role="group">
                        <input type="radio" class="btn-check" name="chartPeriod" id="chart7days" value="7" checked>
                        <label class="btn btn-outline-primary" for="chart7days">7D</label>
                        
                        <input type="radio" class="btn-check" name="chartPeriod" id="chart30days" value="30">
                        <label class="btn btn-outline-primary" for="chart30days">30D</label>
                        
                        <input type="radio" class="btn-check" name="chartPeriod" id="chart90days" value="90">
                        <label class="btn btn-outline-primary" for="chart90days">90D</label>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="progressTimeChart"></canvas>
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
                        <div class="col-md-3">
                            <label for="skillFilter" class="form-label">Skill</label>
                            <select class="form-select" id="skillFilter" name="skill_id">
                                <option value="">All Skills</option>
                                <?php foreach ($skills as $skill): ?>
                                    <option value="<?php echo $skill['skill_id']; ?>" 
                                            <?php echo ($_GET['skill_id'] ?? '') == $skill['skill_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($skill['skill_name']); ?>
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
                        <div class="col-md-2">
                            <label for="startDate" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="startDate" name="start_date" 
                                   value="<?php echo $_GET['start_date'] ?? ''; ?>">
                        </div>
                        <div class="col-md-2">
                            <label for="endDate" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="endDate" name="end_date" 
                                   value="<?php echo $_GET['end_date'] ?? ''; ?>">
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

    <!-- Progress Entries -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-history me-2"></i>Progress Entries
                    </h5>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-outline-success" onclick="exportProgress()">
                            <i class="fas fa-download me-1"></i>Export
                        </button>
                        <div class="btn-group btn-group-sm" role="group">
                            <input type="radio" class="btn-check" name="viewMode" id="timelineView" value="timeline" checked>
                            <label class="btn btn-outline-primary" for="timelineView">
                                <i class="fas fa-stream"></i> Timeline
                            </label>
                            
                            <input type="radio" class="btn-check" name="viewMode" id="tableView" value="table">
                            <label class="btn btn-outline-primary" for="tableView">
                                <i class="fas fa-table"></i> Table
                            </label>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (!empty($progress_entries)): ?>
                        <!-- Timeline View -->
                        <div id="timelineViewContainer">
                            <div class="progress-timeline">
                                <?php foreach ($progress_entries as $entry): ?>
                                    <div class="timeline-item" data-entry-id="<?php echo $entry['progress_id']; ?>">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <div class="d-flex align-items-center mb-2">
                                                    <h6 class="mb-0 me-3"><?php echo htmlspecialchars($entry['skill_name']); ?></h6>
                                                    <span class="proficiency-badge proficiency-<?php echo strtolower($entry['proficiency_level']); ?>">
                                                        <?php echo $entry['proficiency_level']; ?>
                                                    </span>
                                                </div>
                                                
                                                <div class="row mb-2">
                                                    <div class="col-md-6">
                                                        <small class="text-muted">
                                                            <i class="fas fa-clock me-1"></i>
                                                            <?php echo number_format($entry['hours_spent'], 1); ?> hours
                                                        </small>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <small class="text-muted">
                                                            <i class="fas fa-tasks me-1"></i>
                                                            <?php echo $entry['tasks_completed']; ?> tasks completed
                                                        </small>
                                                    </div>
                                                </div>
                                                
                                                <?php if (!empty($entry['notes'])): ?>
                                                    <div class="mb-2">
                                                        <small class="text-muted">
                                                            <i class="fas fa-sticky-note me-1"></i>
                                                            <?php echo htmlspecialchars($entry['notes']); ?>
                                                        </small>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <div class="text-muted small">
                                                    <i class="fas fa-calendar me-1"></i>
                                                    <?php echo date('M j, Y', strtotime($entry['entry_date'])); ?>
                                                    <span class="ms-2">
                                                        <i class="fas fa-clock me-1"></i>
                                                        <?php 
                                echo Utils::formatRelativeTimeStatic($entry['created_at']);
                                        ?>
                                                    </span>
                                                </div>
                                            </div>
                                            
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" 
                                                        data-bs-toggle="dropdown">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li>
                                                        <a class="dropdown-item" href="<?php echo BASE_URL; ?>?page=progress-edit&id=<?php echo $entry['progress_id']; ?>">
                                                            <i class="fas fa-edit me-2"></i>Edit
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="<?php echo BASE_URL; ?>?page=skill-view&id=<?php echo $entry['skill_id']; ?>">
                                                            <i class="fas fa-eye me-2"></i>View Skill
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item text-danger" href="#" 
                                                           onclick="deleteProgressEntry(<?php echo $entry['progress_id']; ?>)">
                                                            <i class="fas fa-trash me-2"></i>Delete
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Table View (Hidden by default) -->
                        <div id="tableViewContainer" class="d-none">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Skill</th>
                                            <th>Hours</th>
                                            <th>Tasks</th>
                                            <th>Proficiency</th>
                                            <th>Notes</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($progress_entries as $entry): ?>
                                            <tr data-entry-id="<?php echo $entry['progress_id']; ?>">
                                                <td><?php echo date('M j, Y', strtotime($entry['entry_date'])); ?></td>
                                                <td>
                                                    <div class="fw-semibold"><?php echo htmlspecialchars($entry['skill_name']); ?></div>
                                                    <small class="text-muted"><?php echo htmlspecialchars($entry['category_name']); ?></small>
                                                </td>
                                                <td><?php echo number_format($entry['hours_spent'], 1); ?>h</td>
                                                <td><?php echo $entry['tasks_completed']; ?></td>
                                                <td>
                                                    <span class="proficiency-badge proficiency-<?php echo strtolower($entry['proficiency_level']); ?>">
                                                        <?php echo $entry['proficiency_level']; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if (!empty($entry['notes'])): ?>
                                                        <span class="text-truncate d-inline-block" style="max-width: 150px;" 
                                                              title="<?php echo htmlspecialchars($entry['notes']); ?>">
                                                            <?php echo htmlspecialchars($entry['notes']); ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="<?php echo BASE_URL; ?>?page=progress-edit&id=<?php echo $entry['progress_id']; ?>" 
                                                           class="btn btn-outline-warning" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <button class="btn btn-outline-danger" title="Delete" 
                                                                onclick="deleteProgressEntry(<?php echo $entry['progress_id']; ?>)">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    <?php else: ?>
                        <!-- Empty State -->
                        <div class="text-center py-5">
                            <div class="mb-4">
                                <i class="fas fa-chart-bar fa-4x text-muted opacity-50"></i>
                            </div>
                            <h4 class="text-muted mb-3">No progress entries found</h4>
                            <?php if (!empty($_GET['skill_id']) || !empty($_GET['proficiency']) || !empty($_GET['start_date']) || !empty($_GET['end_date'])): ?>
                                <p class="text-muted mb-4">Try adjusting your filters to see more results.</p>
                                <button class="btn btn-outline-primary me-2" id="clearFiltersBtn">
                                    <i class="fas fa-times me-2"></i>Clear Filters
                                </button>
                            <?php else: ?>
                                <p class="text-muted mb-4">Start tracking your progress by logging your first entry!</p>
                            <?php endif; ?>
                            <a href="<?php echo BASE_URL; ?>?page=progress-create" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Log Your First Progress
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Pagination -->
    <?php if (isset($pagination) && $pagination['total_pages'] > 1): ?>
        <div class="row mt-4">
            <div class="col-12">
                <nav aria-label="Progress pagination">
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
</div>

<script>
// Pass PHP data to JavaScript
window.progressData = {
    entries: <?php echo json_encode($progress_entries); ?>,
    skills: <?php echo json_encode($skills); ?>,
    stats: <?php echo json_encode($stats); ?>,
    chartData: <?php echo json_encode($chart_data ?? []); ?>
};

function deleteProgressEntry(entryId) {
    ModalHelper.showConfirmation(
        'Delete Progress Entry',
        'Are you sure you want to delete this progress entry? This action cannot be undone.',
        function() {
            Utils.showLoading();
            
            API.delete(`/progress/${entryId}`)
                .then(response => {
                    if (response.success) {
                        Utils.showToast('Progress entry deleted successfully', 'success');
                        // Remove entry from display
                        const entryElements = document.querySelectorAll(`[data-entry-id="${entryId}"]`);
                        entryElements.forEach(element => {
                            element.style.transition = 'all 0.3s ease';
                            element.style.opacity = '0';
                            element.style.transform = 'scale(0.8)';
                            setTimeout(() => element.remove(), 300);
                        });
                    } else {
                        Utils.showToast(response.message || 'Failed to delete progress entry', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error deleting progress entry:', error);
                    Utils.showToast('An error occurred while deleting the progress entry', 'error');
                })
                .finally(() => {
                    Utils.hideLoading();
                });
        }
    );
}

function exportProgress() {
    Utils.showLoading();
    
    // Get current filters
    const formData = new FormData(document.getElementById('filterForm'));
    const params = {};
    for (let [key, value] of formData.entries()) {
        if (value.trim()) {
            params[key] = value;
        }
    }
    
    API.get('/progress/export', params)
        .then(response => {
            if (response.success) {
                // Create download link
                const blob = new Blob([response.data], { type: 'text/csv' });
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `progress_export_${new Date().toISOString().split('T')[0]}.csv`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);
                
                Utils.showToast('Progress data exported successfully!', 'success');
            } else {
                Utils.showToast(response.message || 'Failed to export progress data', 'error');
            }
        })
        .catch(error => {
            console.error('Error exporting progress:', error);
            Utils.showToast('An error occurred while exporting progress data', 'error');
        })
        .finally(() => {
            Utils.hideLoading();
        });
}
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>


