/**
 * Skills JavaScript for Smart Skill Progress Tracker
 * CSE470 Software Engineering Project
 */

document.addEventListener('DOMContentLoaded', function() {
    initializeSkillsPage();
});

function initializeSkillsPage() {
    initializeViewToggle();
    initializeFilters();
    initializeSearch();
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
            localStorage.setItem('skillsViewMode', 'grid');
        }
    });

    listViewBtn.addEventListener('change', function() {
        if (this.checked) {
            gridContainer.classList.add('d-none');
            listContainer.classList.remove('d-none');
            localStorage.setItem('skillsViewMode', 'list');
        }
    });

    // Restore saved view mode
    const savedViewMode = localStorage.getItem('skillsViewMode');
    if (savedViewMode === 'list') {
        listViewBtn.checked = true;
        listViewBtn.dispatchEvent(new Event('change'));
    }
}

function initializeFilters() {
    const filterForm = document.getElementById('filterForm');
    const clearFiltersBtn = document.getElementById('clearFilters');
    const clearFiltersBtnEmpty = document.getElementById('clearFiltersBtn');

    if (filterForm) {
        filterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            applyFilters();
        });
    }

    if (clearFiltersBtn) {
        clearFiltersBtn.addEventListener('click', clearAllFilters);
    }

    if (clearFiltersBtnEmpty) {
        clearFiltersBtnEmpty.addEventListener('click', clearAllFilters);
    }

    // Auto-apply filters when select boxes change
    const selectFilters = document.querySelectorAll('#categoryFilter, #proficiencyFilter');
    selectFilters.forEach(select => {
        select.addEventListener('change', function() {
            applyFilters();
        });
    });
}

function initializeSearch() {
    const searchInput = document.getElementById('searchQuery');
    
    if (searchInput) {
        // Debounced search
        const debouncedSearch = Utils.debounce(function() {
            applyFilters();
        }, 500);

        searchInput.addEventListener('input', debouncedSearch);
        
        // Clear search on Escape key
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                this.value = '';
                applyFilters();
            }
        });
    }
}

function initializeEventListeners() {
    // Skill card hover effects
    const skillCards = document.querySelectorAll('.skill-card');
    skillCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });

    // Quick action buttons
    document.addEventListener('click', function(e) {
        if (e.target.matches('[data-action="quick-progress"]')) {
            const skillId = e.target.getAttribute('data-skill-id');
            showQuickProgressModal(skillId);
        }
    });
}

function applyFilters() {
    const formData = new FormData(document.getElementById('filterForm'));
    const params = new URLSearchParams();
    
    // Add form data to URL params
    for (let [key, value] of formData.entries()) {
        if (value.trim()) {
            params.append(key, value);
        }
    }
    
    // Preserve current page if no new filters
    const currentParams = new URLSearchParams(window.location.search);
    if (!params.toString() && currentParams.has('page')) {
        params.append('page', currentParams.get('page'));
    }
    
    // Update URL and reload page
    const newUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
    window.location.href = newUrl;
}

function clearAllFilters() {
    // Clear form inputs
    document.getElementById('searchQuery').value = '';
    document.getElementById('categoryFilter').value = '';
    document.getElementById('proficiencyFilter').value = '';
    
    // Redirect to clean URL
    window.location.href = window.location.pathname;
}

function showQuickProgressModal(skillId) {
    const skill = window.skillsData.skills.find(s => s.skill_id == skillId);
    if (!skill) return;

    const modalHtml = `
        <div class="modal fade" id="quickProgressModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-plus me-2"></i>Quick Progress Log
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form id="quickProgressForm">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Skill</label>
                                <div class="p-2 bg-light rounded">
                                    ${skill.skill_name} - ${skill.category_name}
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="quickHours" class="form-label">Hours Spent</label>
                                    <input type="number" class="form-control" id="quickHours" 
                                           name="hours_spent" step="0.5" min="0" max="24" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="quickTasks" class="form-label">Tasks Completed</label>
                                    <input type="number" class="form-control" id="quickTasks" 
                                           name="tasks_completed" min="0" value="0">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="quickProficiency" class="form-label">Current Proficiency</label>
                                <select class="form-select" id="quickProficiency" name="proficiency_level" required>
                                    <option value="Beginner" ${skill.current_proficiency === 'Beginner' ? 'selected' : ''}>Beginner</option>
                                    <option value="Intermediate" ${skill.current_proficiency === 'Intermediate' ? 'selected' : ''}>Intermediate</option>
                                    <option value="Advanced" ${skill.current_proficiency === 'Advanced' ? 'selected' : ''}>Advanced</option>
                                    <option value="Expert" ${skill.current_proficiency === 'Expert' ? 'selected' : ''}>Expert</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="quickNotes" class="form-label">Notes (Optional)</label>
                                <textarea class="form-control" id="quickNotes" name="notes" rows="3" 
                                          placeholder="What did you work on? Any insights or challenges?"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Log Progress
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    `;

    // Remove existing modal if any
    const existingModal = document.getElementById('quickProgressModal');
    if (existingModal) {
        existingModal.remove();
    }

    // Add modal to page
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    // Initialize modal
    const modal = new bootstrap.Modal(document.getElementById('quickProgressModal'));
    modal.show();

    // Handle form submission
    document.getElementById('quickProgressForm').addEventListener('submit', function(e) {
        e.preventDefault();
        submitQuickProgress(skillId, modal);
    });

    // Focus first input
    setTimeout(() => {
        document.getElementById('quickHours').focus();
    }, 500);
}

function submitQuickProgress(skillId, modal) {
    const form = document.getElementById('quickProgressForm');
    const formData = FormHelper.serialize(form);
    
    if (!FormHelper.validate(form)) {
        return;
    }

    Utils.showLoading();

    const progressData = {
        skill_id: skillId,
        hours_spent: parseFloat(formData.hours_spent),
        tasks_completed: parseInt(formData.tasks_completed) || 0,
        proficiency_level: formData.proficiency_level,
        notes: formData.notes || '',
        entry_date: new Date().toISOString().split('T')[0]
    };

    API.post('/progress', progressData)
        .then(response => {
            if (response.success) {
                Utils.showToast('Progress logged successfully!', 'success');
                modal.hide();
                
                // Refresh the page to show updated data
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                Utils.showToast(response.message || 'Failed to log progress', 'error');
            }
        })
        .catch(error => {
            console.error('Error logging progress:', error);
            Utils.showToast('An error occurred while logging progress', 'error');
        })
        .finally(() => {
            Utils.hideLoading();
        });
}

function exportSkills() {
    Utils.showLoading();
    
    API.get('/skills/export')
        .then(response => {
            if (response.success) {
                // Create download link
                const blob = new Blob([response.data], { type: 'text/csv' });
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `skills_export_${new Date().toISOString().split('T')[0]}.csv`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);
                
                Utils.showToast('Skills exported successfully!', 'success');
            } else {
                Utils.showToast(response.message || 'Failed to export skills', 'error');
            }
        })
        .catch(error => {
            console.error('Error exporting skills:', error);
            Utils.showToast('An error occurred while exporting skills', 'error');
        })
        .finally(() => {
            Utils.hideLoading();
        });
}

function bulkDeleteSkills() {
    const selectedSkills = document.querySelectorAll('.skill-checkbox:checked');
    
    if (selectedSkills.length === 0) {
        Utils.showToast('Please select skills to delete', 'warning');
        return;
    }

    const skillIds = Array.from(selectedSkills).map(cb => cb.value);
    
    ModalHelper.showConfirmation(
        'Delete Multiple Skills',
        `Are you sure you want to delete ${skillIds.length} skill(s)? This action cannot be undone.`,
        function() {
            Utils.showLoading();
            
            Promise.all(skillIds.map(id => API.delete(`/skills/${id}`)))
                .then(responses => {
                    const successful = responses.filter(r => r.success).length;
                    const failed = responses.length - successful;
                    
                    if (successful > 0) {
                        Utils.showToast(`${successful} skill(s) deleted successfully`, 'success');
                        
                        // Remove deleted skills from display
                        skillIds.forEach(id => {
                            const skillElements = document.querySelectorAll(`[data-skill-id="${id}"]`);
                            skillElements.forEach(element => element.remove());
                        });
                    }
                    
                    if (failed > 0) {
                        Utils.showToast(`Failed to delete ${failed} skill(s)`, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error deleting skills:', error);
                    Utils.showToast('An error occurred while deleting skills', 'error');
                })
                .finally(() => {
                    Utils.hideLoading();
                });
        }
    );
}

// Export functions for global use
window.SkillsManager = {
    showQuickProgressModal,
    exportSkills,
    bulkDeleteSkills,
    applyFilters,
    clearAllFilters
};

