/**
 * Dashboard JavaScript for Smart Skill Progress Tracker
 * CSE470 Software Engineering Project
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize dashboard
    initializeCharts();
    initializeEventListeners();
    loadRecentActivity();
});

let progressChart = null;
let categoryChart = null;

function initializeCharts() {
    // Initialize progress chart
    const progressCtx = document.getElementById('progressChart');
    if (progressCtx && window.dashboardData.dailyProgress) {
        createProgressChart(progressCtx);
    }

    // Initialize category chart
    const categoryCtx = document.getElementById('categoryChart');
    if (categoryCtx && window.dashboardData.categoryProgress) {
        createCategoryChart(categoryCtx);
    }
}

function createProgressChart(ctx) {
    const data = window.dashboardData.dailyProgress;
    
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

    progressChart = new Chart(ctx, {
        type: 'line',
        data: chartData,
        options: options
    });
}

function createCategoryChart(ctx) {
    const data = window.dashboardData.categoryProgress;
    
    if (!data || data.length === 0) {
        return;
    }

    const labels = data.map(item => item.category_name);
    const values = data.map(item => parseFloat(item.total_hours) || 0);
    const colors = ChartHelper.generateColors(data.length);

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

    categoryChart = new Chart(ctx, {
        type: 'doughnut',
        data: chartData,
        options: options
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

    // Refresh dashboard data every 5 minutes
    setInterval(refreshDashboardData, 5 * 60 * 1000);
}

function updateProgressChart(days) {
    if (!progressChart) return;

    Utils.showLoading();

    API.get('/dashboard/chart-data', { type: 'daily', days: days })
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

                progressChart.data.labels = labels;
                progressChart.data.datasets[0].data = hoursData;
                progressChart.data.datasets[1].data = tasksData;
                progressChart.update('active');
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

function loadRecentActivity() {
    API.get('/dashboard/activities', { limit: 5 })
        .then(response => {
            if (response.success) {
                updateRecentActivityDisplay(response.data.activities);
            }
        })
        .catch(error => {
            console.error('Error loading recent activity:', error);
        });
}

function updateRecentActivityDisplay(activities) {
    const container = document.getElementById('recentActivity');
    if (!container || !activities || activities.length === 0) {
        return;
    }

    container.innerHTML = '';

    activities.slice(0, 3).forEach(activity => {
        const activityElement = document.createElement('div');
        activityElement.className = 'd-flex align-items-center mb-3';
        activityElement.innerHTML = `
            <div class="flex-shrink-0">
                <div class="bg-${activity.color} rounded-circle d-flex align-items-center justify-content-center" 
                     style="width: 32px; height: 32px;">
                    <i class="${activity.icon} text-white" style="font-size: 0.75rem;"></i>
                </div>
            </div>
            <div class="flex-grow-1 ms-3">
                <div class="fw-semibold" style="font-size: 0.875rem;">
                    ${activity.title}
                </div>
                <div class="text-muted" style="font-size: 0.75rem;">
                    ${activity.description} • ${Utils.formatRelativeTime(activity.date)}
                </div>
            </div>
        `;
        container.appendChild(activityElement);
    });
}

function refreshDashboardData() {
    // Refresh statistics
    API.get('/dashboard/statistics')
        .then(response => {
            if (response.success) {
                updateStatistics(response.data);
            }
        })
        .catch(error => {
            console.error('Error refreshing dashboard data:', error);
        });

    // Refresh recent activity
    loadRecentActivity();
}

function updateStatistics(data) {
    // Update stat cards
    const statCards = document.querySelectorAll('.stat-card');
    
    if (data.user_stats) {
        const totalSkillsCard = statCards[0];
        if (totalSkillsCard) {
            totalSkillsCard.querySelector('.stat-number').textContent = data.user_stats.total_skills || 0;
        }
    }

    if (data.progress_stats) {
        const hoursCard = statCards[1];
        if (hoursCard) {
            hoursCard.querySelector('.stat-number').textContent = 
                parseFloat(data.progress_stats.total_hours || 0).toFixed(1);
        }

        const tasksCard = statCards[2];
        if (tasksCard) {
            tasksCard.querySelector('.stat-number').textContent = data.progress_stats.total_tasks || 0;
        }
    }

    if (data.goal_stats) {
        const goalsCard = statCards[3];
        if (goalsCard) {
            goalsCard.querySelector('.stat-number').textContent = 
                `${data.goal_stats.completed_goals || 0}/${data.goal_stats.total_goals || 0}`;
        }
    }

    // Update streak
    if (data.streaks) {
        const streakElement = document.querySelector('.h3.mb-0');
        if (streakElement) {
            streakElement.textContent = `${data.streaks.current_streak} days`;
        }
    }
}

// Export functions for global use
window.DashboardManager = {
    updateProgressChart,
    refreshDashboardData,
    loadRecentActivity
};

