/**
 * Smart Skill Progress Tracker - Main JavaScript
 * CSE470 Software Engineering Project
 */

// Global variables
const BASE_URL = window.location.origin + window.location.pathname.replace(/\/[^\/]*$/, '');
const API_BASE = BASE_URL + '/api';

// CSRF Token
const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

// Utility Functions
const Utils = {
    // Show loading spinner
    showLoading() {
        const spinner = document.createElement('div');
        spinner.className = 'spinner-overlay';
        spinner.innerHTML = `
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        `;
        spinner.id = 'loading-spinner';
        document.body.appendChild(spinner);
    },

    // Hide loading spinner
    hideLoading() {
        const spinner = document.getElementById('loading-spinner');
        if (spinner) {
            spinner.remove();
        }
    },

    // Show toast notification
    showToast(message, type = 'success') {
        const toastContainer = document.getElementById('toast-container') || this.createToastContainer();
        
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-white bg-${type === 'error' ? 'danger' : type} border-0`;
        toast.setAttribute('role', 'alert');
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas fa-${type === 'error' ? 'exclamation-triangle' : (type === 'success' ? 'check-circle' : 'info-circle')} me-2"></i>
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;
        
        toastContainer.appendChild(toast);
        
        const bsToast = new bootstrap.Toast(toast, { delay: 5000 });
        bsToast.show();
        
        // Remove toast element after it's hidden
        toast.addEventListener('hidden.bs.toast', () => {
            toast.remove();
        });
    },

    // Create toast container
    createToastContainer() {
        const container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'toast-container position-fixed top-0 end-0 p-3';
        container.style.zIndex = '9999';
        document.body.appendChild(container);
        return container;
    },

    // Format date
    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    },

    // Format relative time
    formatRelativeTime(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diffInSeconds = Math.floor((now - date) / 1000);
        
        if (diffInSeconds < 60) return 'Just now';
        if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)} minutes ago`;
        if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)} hours ago`;
        if (diffInSeconds < 604800) return `${Math.floor(diffInSeconds / 86400)} days ago`;
        
        return this.formatDate(dateString);
    },

    // Debounce function
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },

    // Validate email
    validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    },

    // Validate password strength
    validatePassword(password) {
        const minLength = password.length >= 8;
        const hasUpper = /[A-Z]/.test(password);
        const hasLower = /[a-z]/.test(password);
        const hasNumber = /\d/.test(password);
        
        return {
            valid: minLength && hasUpper && hasLower && hasNumber,
            minLength,
            hasUpper,
            hasLower,
            hasNumber
        };
    }
};

// API Helper
const API = {
    // Make API request
    async request(endpoint, options = {}) {
        const url = endpoint.startsWith('http') ? endpoint : `${API_BASE}${endpoint}`;
        
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        };

        // Add CSRF token for non-GET requests
        if (options.method && options.method !== 'GET' && CSRF_TOKEN) {
            defaultOptions.headers['X-CSRF-Token'] = CSRF_TOKEN;
        }

        const config = {
            ...defaultOptions,
            ...options,
            headers: {
                ...defaultOptions.headers,
                ...options.headers
            }
        };

        try {
            const response = await fetch(url, config);
            const data = await response.json();
            
            if (!response.ok) {
                throw new Error(data.message || `HTTP error! status: ${response.status}`);
            }
            
            return data;
        } catch (error) {
            console.error('API request failed:', error);
            throw error;
        }
    },

    // GET request
    get(endpoint, params = {}) {
        const url = new URL(endpoint.startsWith('http') ? endpoint : `${API_BASE}${endpoint}`);
        Object.keys(params).forEach(key => url.searchParams.append(key, params[key]));
        
        return this.request(url.toString(), { method: 'GET' });
    },

    // POST request
    post(endpoint, data = {}) {
        return this.request(endpoint, {
            method: 'POST',
            body: JSON.stringify(data)
        });
    },

    // PUT request
    put(endpoint, data = {}) {
        return this.request(endpoint, {
            method: 'PUT',
            body: JSON.stringify(data)
        });
    },

    // DELETE request
    delete(endpoint) {
        return this.request(endpoint, { method: 'DELETE' });
    }
};

// Chart Helper
const ChartHelper = {
    // Default chart options
    defaultOptions: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'top',
            },
            tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                titleColor: 'white',
                bodyColor: 'white',
                borderColor: 'rgba(255, 255, 255, 0.1)',
                borderWidth: 1
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    color: 'rgba(0, 0, 0, 0.1)'
                }
            },
            x: {
                grid: {
                    color: 'rgba(0, 0, 0, 0.1)'
                }
            }
        }
    },

    // Create line chart
    createLineChart(ctx, data, options = {}) {
        return new Chart(ctx, {
            type: 'line',
            data: data,
            options: {
                ...this.defaultOptions,
                ...options
            }
        });
    },

    // Create bar chart
    createBarChart(ctx, data, options = {}) {
        return new Chart(ctx, {
            type: 'bar',
            data: data,
            options: {
                ...this.defaultOptions,
                ...options
            }
        });
    },

    // Create doughnut chart
    createDoughnutChart(ctx, data, options = {}) {
        return new Chart(ctx, {
            type: 'doughnut',
            data: data,
            options: {
                ...this.defaultOptions,
                ...options,
                scales: {} // Remove scales for doughnut chart
            }
        });
    },

    // Generate chart colors
    generateColors(count) {
        const colors = [
            '#0d6efd', '#198754', '#dc3545', '#ffc107', '#0dcaf0',
            '#6f42c1', '#fd7e14', '#20c997', '#e83e8c', '#6c757d'
        ];
        
        const result = [];
        for (let i = 0; i < count; i++) {
            result.push(colors[i % colors.length]);
        }
        return result;
    }
};

// Form Helper
const FormHelper = {
    // Serialize form data
    serialize(form) {
        const formData = new FormData(form);
        const data = {};
        
        for (let [key, value] of formData.entries()) {
            if (data[key]) {
                if (Array.isArray(data[key])) {
                    data[key].push(value);
                } else {
                    data[key] = [data[key], value];
                }
            } else {
                data[key] = value;
            }
        }
        
        return data;
    },

    // Validate form
    validate(form) {
        const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
        let isValid = true;
        
        inputs.forEach(input => {
            if (!input.value.trim()) {
                this.showFieldError(input, 'This field is required');
                isValid = false;
            } else {
                this.clearFieldError(input);
                
                // Additional validation based on input type
                if (input.type === 'email' && !Utils.validateEmail(input.value)) {
                    this.showFieldError(input, 'Please enter a valid email address');
                    isValid = false;
                }
                
                if (input.type === 'password' && input.name === 'password') {
                    const validation = Utils.validatePassword(input.value);
                    if (!validation.valid) {
                        this.showFieldError(input, 'Password must be at least 8 characters with uppercase, lowercase, and number');
                        isValid = false;
                    }
                }
                
                if (input.name === 'confirm_password') {
                    const passwordField = form.querySelector('input[name="password"]');
                    if (passwordField && input.value !== passwordField.value) {
                        this.showFieldError(input, 'Passwords do not match');
                        isValid = false;
                    }
                }
            }
        });
        
        return isValid;
    },

    // Show field error
    showFieldError(field, message) {
        this.clearFieldError(field);
        
        field.classList.add('is-invalid');
        
        const errorDiv = document.createElement('div');
        errorDiv.className = 'invalid-feedback';
        errorDiv.textContent = message;
        
        field.parentNode.appendChild(errorDiv);
    },

    // Clear field error
    clearFieldError(field) {
        field.classList.remove('is-invalid');
        
        const errorDiv = field.parentNode.querySelector('.invalid-feedback');
        if (errorDiv) {
            errorDiv.remove();
        }
    },

    // Clear all form errors
    clearAllErrors(form) {
        const invalidFields = form.querySelectorAll('.is-invalid');
        const errorDivs = form.querySelectorAll('.invalid-feedback');
        
        invalidFields.forEach(field => field.classList.remove('is-invalid'));
        errorDivs.forEach(div => div.remove());
    }
};

// Modal Helper
const ModalHelper = {
    // Show confirmation modal
    showConfirmation(title, message, onConfirm, onCancel = null) {
        const modalId = 'confirmationModal';
        let modal = document.getElementById(modalId);
        
        if (!modal) {
            modal = this.createConfirmationModal(modalId);
            document.body.appendChild(modal);
        }
        
        modal.querySelector('.modal-title').textContent = title;
        modal.querySelector('.modal-body p').textContent = message;
        
        const confirmBtn = modal.querySelector('.btn-danger');
        const cancelBtn = modal.querySelector('.btn-secondary');
        
        // Remove existing event listeners
        const newConfirmBtn = confirmBtn.cloneNode(true);
        const newCancelBtn = cancelBtn.cloneNode(true);
        confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);
        cancelBtn.parentNode.replaceChild(newCancelBtn, cancelBtn);
        
        // Add new event listeners
        newConfirmBtn.addEventListener('click', () => {
            onConfirm();
            bootstrap.Modal.getInstance(modal).hide();
        });
        
        if (onCancel) {
            newCancelBtn.addEventListener('click', onCancel);
        }
        
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
    },

    // Create confirmation modal
    createConfirmationModal(id) {
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.id = id;
        modal.innerHTML = `
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Confirm Action</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to perform this action?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-danger">Confirm</button>
                    </div>
                </div>
            </div>
        `;
        return modal;
    }
};

// Initialize app when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize popovers
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });

    // Add smooth scrolling to anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Add fade-in animation to cards
    const cards = document.querySelectorAll('.card, .skill-card, .goal-card, .stat-card');
    cards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
        card.classList.add('fade-in');
    });
});

// Export for global use
window.Utils = Utils;
window.API = API;
window.ChartHelper = ChartHelper;
window.FormHelper = FormHelper;
window.ModalHelper = ModalHelper;

