/**
 * Application JavaScript Enhancements
 * Toast notifications, form validation, loading indicators
 */

// Toast Notification System
const Toast = {
    container: null,
    
    init() {
        if (!this.container) {
            this.container = document.createElement('div');
            this.container.className = 'toast-container';
            document.body.appendChild(this.container);
        }
    },
    
    show(message, type = 'info', duration = 5000) {
        this.init();
        
        const toastId = 'toast-' + Date.now();
        const iconMap = {
            success: 'check-circle-fill',
            error: 'exclamation-triangle-fill',
            warning: 'exclamation-circle-fill',
            info: 'info-circle-fill'
        };
        
        const toast = document.createElement('div');
        toast.id = toastId;
        toast.className = `toast toast-${type} show`;
        toast.setAttribute('role', 'alert');
        toast.innerHTML = `
            <div class="toast-header">
                <i class="bi bi-${iconMap[type]} me-2 text-${type === 'error' ? 'danger' : type}"></i>
                <strong class="me-auto">${type.charAt(0).toUpperCase() + type.slice(1)}</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body">${message}</div>
        `;
        
        this.container.appendChild(toast);
        
        // Auto remove
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, duration);
        
        // Manual close
        toast.querySelector('.btn-close').addEventListener('click', () => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        });
    },
    
    success(message, duration) {
        this.show(message, 'success', duration);
    },
    
    error(message, duration) {
        this.show(message, 'error', duration);
    },
    
    warning(message, duration) {
        this.show(message, 'warning', duration);
    },
    
    info(message, duration) {
        this.show(message, 'info', duration);
    }
};

// Loading Overlay
const Loading = {
    overlay: null,
    
    init() {
        if (!this.overlay) {
            this.overlay = document.createElement('div');
            this.overlay.id = 'loadingOverlay';
            this.overlay.innerHTML = '<div class="spinner-border spinner-border-lg text-light"></div>';
            document.body.appendChild(this.overlay);
        }
    },
    
    show() {
        this.init();
        this.overlay.classList.add('active');
    },
    
    hide() {
        if (this.overlay) {
            this.overlay.classList.remove('active');
        }
    }
};

// Form Validation
const FormValidator = {
    validateField(field) {
        const value = field.value.trim();
        const type = field.type;
        const required = field.hasAttribute('required');
        let isValid = true;
        let message = '';
        
        // Clear previous validation
        field.classList.remove('is-invalid', 'is-valid');
        const feedback = field.nextElementSibling;
        if (feedback && feedback.classList.contains('invalid-feedback')) {
            feedback.remove();
        }
        
        // Required validation
        if (required && !value) {
            isValid = false;
            message = 'This field is required';
        }
        
        // Email validation
        else if (type === 'email' && value) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
                isValid = false;
                message = 'Please enter a valid email address';
            }
        }
        
        // Number validation
        else if (type === 'number' && value) {
            const min = field.getAttribute('min');
            const max = field.getAttribute('max');
            const num = parseFloat(value);
            
            if (isNaN(num)) {
                isValid = false;
                message = 'Please enter a valid number';
            } else if (min && num < parseFloat(min)) {
                isValid = false;
                message = `Value must be at least ${min}`;
            } else if (max && num > parseFloat(max)) {
                isValid = false;
                message = `Value must not exceed ${max}`;
            }
        }
        
        // Password confirmation
        if (field.name === 'confirmPassword') {
            const password = document.querySelector('input[name="password"]');
            if (password && value !== password.value) {
                isValid = false;
                message = 'Passwords do not match';
            }
        }
        
        // Apply validation styling
        if (!isValid) {
            field.classList.add('is-invalid');
            const feedbackDiv = document.createElement('div');
            feedbackDiv.className = 'invalid-feedback';
            feedbackDiv.textContent = message;
            field.parentNode.insertBefore(feedbackDiv, field.nextSibling);
        } else if (value) {
            field.classList.add('is-valid');
        }
        
        return isValid;
    },
    
    validateForm(form) {
        let isValid = true;
        const fields = form.querySelectorAll('input, select, textarea');
        
        fields.forEach(field => {
            if (!this.validateField(field)) {
                isValid = false;
            }
        });
        
        return isValid;
    },
    
    init(formSelector = 'form') {
        const forms = document.querySelectorAll(formSelector);
        
        forms.forEach(form => {
            // Real-time validation
            form.querySelectorAll('input, select, textarea').forEach(field => {
                field.addEventListener('blur', () => this.validateField(field));
                field.addEventListener('input', () => {
                    if (field.classList.contains('is-invalid')) {
                        this.validateField(field);
                    }
                });
            });
            
            // Form submission validation
            form.addEventListener('submit', (e) => {
                if (!this.validateForm(form)) {
                    e.preventDefault();
                    Toast.error('Please fix the errors in the form');
                    // Focus on first invalid field
                    const firstInvalid = form.querySelector('.is-invalid');
                    if (firstInvalid) {
                        firstInvalid.focus();
                    }
                }
            });
        });
    }
};

// Form Loading State
function setFormLoading(form, loading = true) {
    const submitBtn = form.querySelector('button[type="submit"]');
    if (!submitBtn) return;
    
    if (loading) {
        submitBtn.classList.add('btn-loading');
        submitBtn.disabled = true;
        form.querySelectorAll('input, select, textarea').forEach(field => {
            field.disabled = true;
        });
    } else {
        submitBtn.classList.remove('btn-loading');
        submitBtn.disabled = false;
        form.querySelectorAll('input, select, textarea').forEach(field => {
            field.disabled = false;
        });
    }
}

// Confirm Delete
function confirmDelete(message = 'Are you sure you want to delete this item?') {
    return confirm(message);
}

// Export to CSV
function exportTableToCSV(tableId, filename = 'export.csv') {
    const table = document.getElementById(tableId) || document.querySelector('table');
    if (!table) return;
    
    let csv = [];
    const rows = table.querySelectorAll('tr');
    
    for (let row of rows) {
        const cols = row.querySelectorAll('td, th');
        const csvRow = [];
        
        for (let col of cols) {
            // Skip action columns
            if (col.classList.contains('action-column') || col.textContent.includes('Actions')) {
                continue;
            }
            csvRow.push('"' + col.textContent.trim().replace(/"/g, '""') + '"');
        }
        
        if (csvRow.length > 0) {
            csv.push(csvRow.join(','));
        }
    }
    
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    a.click();
    window.URL.revokeObjectURL(url);
    
    Toast.success('Export completed successfully');
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Initialize form validation
    FormValidator.init();
    
    // Convert server-side flash messages to toasts
    const alertSuccess = document.querySelector('.alert-success');
    if (alertSuccess) {
        Toast.success(alertSuccess.textContent.trim());
        alertSuccess.remove();
    }
    
    const alertDanger = document.querySelector('.alert-danger');
    if (alertDanger) {
        Toast.error(alertDanger.textContent.trim());
        alertDanger.remove();
    }
    
    const alertWarning = document.querySelector('.alert-warning');
    if (alertWarning) {
        Toast.warning(alertWarning.textContent.trim());
        alertWarning.remove();
    }
    
    // Add loading state to forms
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function() {
            setFormLoading(form, true);
        });
    });
    
    // Add export buttons to tables
    const tables = document.querySelectorAll('table');
    tables.forEach(table => {
        if (table.rows.length > 1) {
            const wrapper = table.parentElement;
            if (!wrapper.querySelector('.export-buttons')) {
                const exportDiv = document.createElement('div');
                exportDiv.className = 'export-buttons mb-2';
                exportDiv.innerHTML = `
                    <button onclick="exportTableToCSV()" class="btn btn-sm btn-outline-success btn-export">
                        <i class="bi bi-file-earmark-excel"></i> Export CSV
                    </button>
                `;
                wrapper.insertBefore(exportDiv, table);
            }
        }
    });
});
