/**
 * Progress JavaScript for Smart Skill Progress Tracker
 * CSE470 Software Engineering Project
 */

document.addEventListener('DOMContentLoaded', function() {
    initializeProgressPage();
});

let progressTimeChart = null;
let skillsDistributionChart = null;

function initializeProgressPage() {
    initializeCharts();
    initializeViewToggle();
    initializeFilters();
    initializeEventListeners();
}

function initializeCharts() {
    // Initialize progress over time chart
    const progressTimeCtx = document.getElementById('progressTimeChart');
    if (progressTimeCtx && window.progressData.chartData) {
        createProgressTimeChart(progressTimeCtx);
    }

    // Initialize skills distribution chart
    const skillsDistCtx = document.getElementById('skillsDistributionChart');
    if (skillsDistCtx && window.progressData.entries) {
        createSkillsDistributionChart(skillsDistCtx);
    }
}

function createProgressTimeChart(ctx) {
    const data = window.progressData.chartData;
    
    if (!data || data.length === 0) {
        ctx.parentElement.innerHTML = '<div class="text-center text-muted py-4">No data available for chart</div>';
        return;
    }

    // Prepare chart data
    const labels = data.map(item => {
        const date = new Date(item.date);
        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    });
    
    const hoursData = data.map(item => parseFloat(item.daily_hours) || 0);
    const tasksData = data.map(item => parseInt(item.daily_tasks) || 0);

    const chartData = {
        labels: labels,
        datasets: [
            {
                label: 'Hours Spent',
                data: hoursData,
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                yAxisID: 'y'
            },
            {
                label: 'Tasks Completed',
                data: tasksData,
                borderColor: '#198754',
                backgroundColor: 'rgba(25, 135, 84, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                yAxisID: 'y1'
            }
        ]
    };

    const options = {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
            mode: 'index',
            intersect: false,
        },
        plugins: {
            legend: {
                position: 'top',
                labels: {
                    usePointStyle: true,
                    padding: 20
                }
            },
            tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                titleColor: 'white',
                bodyColor: 'white',
                borderColor: 'rgba(255, 255, 255, 0.1)',
                borderWidth: 1,
                callbacks: {
                    label: function(context) {
                        const label = context.dataset.label || '';
                        const value = context.parsed.y;
                        if (label === 'Hours Spent') {
                            return `${label}: ${value.toFixed(1)} hours`;
                        } else {
                            return `${label}: ${value} tasks`;
                        }
                    }
                }
            }
        },
        scales: {
            x: {
                grid: {
                    color: 'rgba(0, 0, 0, 0.1)'
                }
            },
            y: {
                type: 'linear',
                display: true,
                position: 'left',
                beginAtZero: true,
                grid: {
                    color: 'rgba(0, 0, 0, 0.1)'
                },
                title: {
                    display: true,
                    text: 'Hours'
                }
            },
            y1: {
                type: 'linear',
                display: true,
                position: 'right',
                beginAtZero: true,
                grid: {
                    drawOnChartArea: false,
                },
                title: {
                    display: true,
                    text: 'Tasks'
                }
            }
        }
    };

    progressTimeChart = new Chart(ctx, {
        type: 'line',
        data: chartData,
        options: options
    });
}

function createSkillsDistributionChart(ctx) {
    const entries = window.progressData.entries;
    
    if (!entries || entries.length === 0) {
        ctx.parentElement.innerHTML = '<div class="text-center text-muted py-4">No data available for chart</div>';
        return;
    }

    // Aggregate hours by skill
    const skillHours = {};
    entries.forEach(entry => {
        const skillName = entry.skill_name;
        skillHours[skillName] = (skillHours[skillName] || 0) + parseFloat(entry.hours_spent);
    });

    // Sort and take top 5 skills
    const sortedSkills = Object.entries(skillHours)
        .sort(([,a], [,b]) => b - a)
        .slice(0, 5);

    if (sortedSkills.length === 0) {
        ctx.parentElement.innerHTML = '<div class="text-center text-muted py-4">No data available for chart</div>';
        return;
    }

    const labels = sortedSkills.map(([skill]) => skill);
    const values = sortedSkills.map(([, hours]) => hours);
    const colors = ChartHelper.generateColors(labels.length);

    const chartData = {
        labels: labels,
        datasets: [{
            data: values,
            backgroundColor: colors,
            borderColor: colors.map(color => color + '80'),
            borderWidth: 2,
            hoverOffset: 4
        }]
    };

    const options = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    usePointStyle: true,
                    padding: 15,
                    generateLabels: function(chart) {
                        const data = chart.data;
                        if (data.labels.length && data.datasets.length) {
                            return data.labels.map((label, i) => {
                                const value = data.datasets[0].data[i];
                                return {
                                    text: `${label} (${value.toFixed(1)}h)`,
                                    fillStyle: data.datasets[0].backgroundColor[i],
                                    strokeStyle: data.datasets[0].borderColor[i],
                                    lineWidth: data.datasets[0].borderWidth,
                                    hidden: false,
                                    index: i
                                };
                            });
                        }
                        return [];
                    }
                }
            },
            tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                titleColor: 'white',
                bodyColor: 'white',
                borderColor: 'rgba(255, 255, 255, 0.1)',
                borderWidth: 1,
                callbacks: {
                    label: function(context) {
                        const label = context.label || '';
                        const value = context.parsed;
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = ((value / total) * 100).toFixed(1);
                        return `${label}: ${value.toFixed(1)} hours (${percentage}%)`;
                    }
                }
            }
        }
    };

    skillsDistributionChart = new Chart(ctx, {
        type: 'doughnut',
        data: chartData,
        options: options
    });
}

function initializeViewToggle() {
    const timelineViewBtn = document.getElementById('timelineView');
    const tableViewBtn = document.getElementById('tableView');
    const timelineContainer = document.getElementById('timelineViewContainer');
    const tableContainer = document.getElementById('tableViewContainer');

    if (!timelineViewBtn || !tableViewBtn || !timelineContainer || !tableContainer) {
        return;
    }

    timelineViewBtn.addEventListener('change', function() {
        if (this.checked) {
            timelineContainer.classList.remove('d-none');
            tableContainer.classList.add('d-none');
            localStorage.setItem('progressViewMode', 'timeline');
        }
    });

    tableViewBtn.addEventListener('change', function() {
        if (this.checked) {
            timelineContainer.classList.add('d-none');
            tableContainer.classList.remove('d-none');
            localStorage.setItem('progressViewMode', 'table');
        }
    });

    // Restore saved view mode
    const savedViewMode = localStorage.getItem('progressViewMode');
    if (savedViewMode === 'table') {
        tableViewBtn.checked = true;
        tableViewBtn.dispatchEvent(new Event('change'));
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
    const selectFilters = document.querySelectorAll('#skillFilter, #proficiencyFilter');
    selectFilters.forEach(select => {
        select.addEventListener('change', function() {
            applyFilters();
        });
    });

    // Auto-apply filters when date inputs change
    const dateFilters = document.querySelectorAll('#startDate, #endDate');
    dateFilters.forEach(input => {
        input.addEventListener('change', function() {
            applyFilters();
        });
    });
}

function initializeEventListeners() {
    // Chart period selector
    const chartPeriodInputs = document.querySelectorAll('input[name="chartPeriod"]');
    chartPeriodInputs.forEach(input => {
        input.addEventListener('change', function() {
            if (this.checked) {
                updateProgressChart(parseInt(this.value));
            }
        });
    });

    // Timeline item hover effects
    const timelineItems = document.querySelectorAll('.timeline-item');
    timelineItems.forEach(item => {
        item.addEventListener('mouseenter', function() {
            this.style.transform = 'translateX(5px)';
        });
        
        item.addEventListener('mouseleave', function() {
            this.style.transform = 'translateX(0)';
        });
    });

    // Auto-refresh data every 5 minutes
    setInterval(refreshProgressData, 5 * 60 * 1000);
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
    document.getElementById('skillFilter').value = '';
    document.getElementById('proficiencyFilter').value = '';
    document.getElementById('startDate').value = '';
    document.getElementById('endDate').value = '';
    
    // Redirect to clean URL
    window.location.href = window.location.pathname;
}

function updateProgressChart(days) {
    if (!progressTimeChart) return;

    Utils.showLoading();

    API.get('/progress/chart-data', { type: 'daily', days: days })
        .then(response => {
            if (response.success) {
                const data = response.data.chart_data;
                
                // Update chart data
                const labels = data.map(item => {
                    const date = new Date(item.date);
                    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                });
                
                const hoursData = data.map(item => parseFloat(item.daily_hours) || 0);
                const tasksData = data.map(item => parseInt(item.daily_tasks) || 0);

                progressTimeChart.data.labels = labels;
                progressTimeChart.data.datasets[0].data = hoursData;
                progressTimeChart.data.datasets[1].data = tasksData;
                progressTimeChart.update('active');
            }
        })
        .catch(error => {
            console.error('Error updating chart:', error);
            Utils.showToast('Failed to update chart data', 'error');
        })
        .finally(() => {
            Utils.hideLoading();
        });
}

function refreshProgressData() {
    // Refresh statistics
    API.get('/progress/statistics')
        .then(response => {
            if (response.success) {
                updateStatistics(response.data);
            }
        })
        .catch(error => {
            console.error('Error refreshing progress data:', error);
        });
}

function updateStatistics(data) {
    // Update stat cards
    const statCards = document.querySelectorAll('.stat-card');
    
    if (data.total_hours !== undefined) {
        const hoursCard = statCards[0];
        if (hoursCard) {
            hoursCard.querySelector('.stat-number').textContent = parseFloat(data.total_hours).toFixed(1);
        }
    }

    if (data.total_tasks !== undefined) {
        const tasksCard = statCards[1];
        if (tasksCard) {
            tasksCard.querySelector('.stat-number').textContent = data.total_tasks;
        }
    }

    if (data.entries_this_month !== undefined) {
        const monthCard = statCards[2];
        if (monthCard) {
            monthCard.querySelector('.stat-number').textContent = data.entries_this_month;
        }
    }

    if (data.current_streak !== undefined) {
        const streakCard = statCards[3];
        if (streakCard) {
            streakCard.querySelector('.stat-number').textContent = data.current_streak;
        }
    }
}

function bulkDeleteEntries() {
    const selectedEntries = document.querySelectorAll('.entry-checkbox:checked');
    
    if (selectedEntries.length === 0) {
        Utils.showToast('Please select entries to delete', 'warning');
        return;
    }

    const entryIds = Array.from(selectedEntries).map(cb => cb.value);
    
    ModalHelper.showConfirmation(
        'Delete Multiple Entries',
        `Are you sure you want to delete ${entryIds.length} progress entr${entryIds.length === 1 ? 'y' : 'ies'}? This action cannot be undone.`,
        function() {
            Utils.showLoading();
            
            Promise.all(entryIds.map(id => API.delete(`/progress/${id}`)))
                .then(responses => {
                    const successful = responses.filter(r => r.success).length;
                    const failed = responses.length - successful;
                    
                    if (successful > 0) {
                        Utils.showToast(`${successful} entr${successful === 1 ? 'y' : 'ies'} deleted successfully`, 'success');
                        
                        // Remove deleted entries from display
                        entryIds.forEach(id => {
                            const entryElements = document.querySelectorAll(`[data-entry-id="${id}"]`);
                            entryElements.forEach(element => element.remove());
                        });
                    }
                    
                    if (failed > 0) {
                        Utils.showToast(`Failed to delete ${failed} entr${failed === 1 ? 'y' : 'ies'}`, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error deleting entries:', error);
                    Utils.showToast('An error occurred while deleting entries', 'error');
                })
                .finally(() => {
                    Utils.hideLoading();
                });
        }
    );
}

// Export functions for global use
window.ProgressManager = {
    updateProgressChart,
    refreshProgressData,
    bulkDeleteEntries,
    applyFilters,
    clearAllFilters
};

