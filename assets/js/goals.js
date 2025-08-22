/**
 * Goals JavaScript for Smart Skill Progress Tracker
 * CSE470 Software Engineering Project
 */

document.addEventListener('DOMContentLoaded', function() {
    initializeGoalsPage();
});

function initializeGoalsPage() {
    initializeViewToggle();
    initializeFilters();
    initializeEventListeners();
}

function initializeViewToggle() {
    const gridViewBtn = document.getElementById('gridView');
    const listViewBtn = document.getElementById('listView');
    const gridContainer = document.getElementById('gridViewContainer');
    const listContainer = document.getElementById('listViewContainer');

    if (!gridViewBtn || !listViewBtn || !gridContainer || !listContainer) {
        return;
    }

    gridViewBtn.addEventListener('change', function() {
        if (this.checked) {
            gridContainer.classList.remove('d-none');
            listContainer.classList.add('d-none');
            localStorage.setItem('goalsViewMode', 'grid');
        }
    });

    listViewBtn.addEventListener('change', function() {
        if (this.checked) {
            gridContainer.classList.add('d-none');
            listContainer.classList.remove('d-none');
            localStorage.setItem('goalsViewMode', 'list');
        }
    });

    // Restore saved view mode
    const savedViewMode = localStorage.getItem('goalsViewMode');
    if (savedViewMode === 'list') {
        listViewBtn.checked = true;
        listViewBtn.dispatchEvent(new Event('change'));
    }
}

function initializeFilters() {
    const filterForm = document.getElementById('filterForm');
    const clearFiltersBtn = document.getElementById('clearFilters');

    if (filterForm) {
        filterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            applyFilters();
        });
    }

    if (clearFiltersBtn) {
        clearFiltersBtn.addEventListener('click', clearAllFilters);
    }
}

function initializeEventListeners() {
    // Goal card hover effects
    const goalCards = document.querySelectorAll('.goal-card');
    goalCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
}

function applyFilters() {
    const formData = new FormData(document.getElementById('filterForm'));
    const params = new URLSearchParams();
    
    for (let [key, value] of formData.entries()) {
        if (value.trim()) {
            params.append(key, value);
        }
    }
    
    const newUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
    window.location.href = newUrl;
}

function clearAllFilters() {
    document.getElementById('statusFilter').value = '';
    document.getElementById('skillFilter').value = '';
    window.location.href = window.location.pathname;
}

function markGoalComplete(goalId) {
    ModalHelper.showConfirmation(
        'Mark Goal Complete',
        'Are you sure you want to mark this goal as completed?',
        function() {
            Utils.showLoading();
            
            API.put(`/goals/${goalId}/complete`)
                .then(response => {
                    if (response.success) {
                        Utils.showToast('Goal marked as completed!', 'success');
                        setTimeout(() => window.location.reload(), 1000);
                    } else {
                        Utils.showToast(response.message || 'Failed to mark goal as completed', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error marking goal complete:', error);
                    Utils.showToast('An error occurred while updating the goal', 'error');
                })
                .finally(() => {
                    Utils.hideLoading();
                });
        }
    );
}

function deleteGoal(goalId) {
    ModalHelper.showConfirmation(
        'Delete Goal',
        'Are you sure you want to delete this goal? This action cannot be undone.',
        function() {
            Utils.showLoading();
            
            API.delete(`/goals/${goalId}`)
                .then(response => {
                    if (response.success) {
                        Utils.showToast('Goal deleted successfully!', 'success');
                        setTimeout(() => window.location.reload(), 1000);
                    } else {
                        Utils.showToast(response.message || 'Failed to delete goal', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error deleting goal:', error);
                    Utils.showToast('An error occurred while deleting the goal', 'error');
                })
                .finally(() => {
                    Utils.hideLoading();
                });
        }
    );
}

window.GoalsManager = {
    markGoalComplete,
    deleteGoal,
    applyFilters,
    clearAllFilters
};

