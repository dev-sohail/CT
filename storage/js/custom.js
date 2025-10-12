/**
 * CyberTirah Framework Custom JavaScript
 * Version: 2.0.0
 */

// Framework namespace
window.CyberTirah = {
    version: '2.0.0',
    config: {},
    utils: {},
    components: {}
};

// Utility Functions
CyberTirah.utils = {
    /**
     * Make AJAX request
     */
    ajax: function(url, options = {}) {
        const defaults = {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        };
        
        const config = Object.assign({}, defaults, options);
        
        return fetch(url, config)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            });
    },

    /**
     * Show notification
     */
    notify: function(message, type = 'info', duration = 3000) {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} framework-notification`;
        notification.textContent = message;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            max-width: 300px;
            animation: slideIn 0.3s ease;
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, duration);
    },

    /**
     * Format date
     */
    formatDate: function(date, format = 'YYYY-MM-DD') {
        const d = new Date(date);
        const year = d.getFullYear();
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        const hours = String(d.getHours()).padStart(2, '0');
        const minutes = String(d.getMinutes()).padStart(2, '0');
        const seconds = String(d.getSeconds()).padStart(2, '0');
        
        return format
            .replace('YYYY', year)
            .replace('MM', month)
            .replace('DD', day)
            .replace('HH', hours)
            .replace('mm', minutes)
            .replace('ss', seconds);
    },

    /**
     * Debounce function
     */
    debounce: function(func, wait) {
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

    /**
     * Throttle function
     */
    throttle: function(func, limit) {
        let inThrottle;
        return function() {
            const args = arguments;
            const context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    }
};

// Framework Components
CyberTirah.components = {
    /**
     * Form Handler
     */
    FormHandler: {
        init: function() {
            const forms = document.querySelectorAll('.framework-form');
            forms.forEach(form => {
                form.addEventListener('submit', this.handleSubmit.bind(this));
            });
        },

        handleSubmit: function(e) {
            e.preventDefault();
            const form = e.target;
            const formData = new FormData(form);
            const data = Object.fromEntries(formData);
            
            // Show loading state
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.textContent = 'Processing...';
            submitBtn.disabled = true;
            
            // Simulate form submission
            setTimeout(() => {
                CyberTirah.utils.notify('Form submitted successfully!', 'success');
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            }, 1000);
        }
    },

    /**
     * Data Table
     */
    DataTable: {
        init: function() {
            const tables = document.querySelectorAll('.framework-table');
            tables.forEach(table => {
                this.addSorting(table);
                this.addFiltering(table);
            });
        },

        addSorting: function(table) {
            const headers = table.querySelectorAll('th[data-sort]');
            headers.forEach(header => {
                header.style.cursor = 'pointer';
                header.addEventListener('click', () => {
                    this.sortTable(table, header.dataset.sort);
                });
            });
        },

        sortTable: function(table, column) {
            const tbody = table.querySelector('tbody');
            const rows = Array.from(tbody.querySelectorAll('tr'));
            
            rows.sort((a, b) => {
                const aVal = a.querySelector(`td[data-column="${column}"]`).textContent;
                const bVal = b.querySelector(`td[data-column="${column}"]`).textContent;
                return aVal.localeCompare(bVal);
            });
            
            rows.forEach(row => tbody.appendChild(row));
        },

        addFiltering: function(table) {
            const filterInput = table.querySelector('.table-filter');
            if (filterInput) {
                filterInput.addEventListener('input', CyberTirah.utils.debounce((e) => {
                    this.filterTable(table, e.target.value);
                }, 300));
            }
        },

        filterTable: function(table, searchTerm) {
            const rows = table.querySelectorAll('tbody tr');
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                const matches = text.includes(searchTerm.toLowerCase());
                row.style.display = matches ? '' : 'none';
            });
        }
    },

    /**
     * Modal
     */
    Modal: {
        init: function() {
            // Modal triggers
            document.addEventListener('click', (e) => {
                if (e.target.matches('[data-modal-target]')) {
                    const target = e.target.dataset.modalTarget;
                    this.open(target);
                }
                
                if (e.target.matches('.modal-close') || e.target.matches('.modal-backdrop')) {
                    this.close();
                }
            });
        },

        open: function(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = 'block';
                document.body.style.overflow = 'hidden';
            }
        },

        close: function() {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                modal.style.display = 'none';
            });
            document.body.style.overflow = '';
        }
    }
};

// Framework Initialization
document.addEventListener('DOMContentLoaded', function() {
    // Initialize components
    CyberTirah.components.FormHandler.init();
    CyberTirah.components.DataTable.init();
    CyberTirah.components.Modal.init();
    
    // Add framework styles
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 15% auto;
            padding: 20px;
            border-radius: 8px;
            width: 80%;
            max-width: 500px;
        }
        
        .modal-close {
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .table-filter {
            width: 100%;
            padding: 0.5rem;
            margin-bottom: 1rem;
            border: 1px solid #ced4da;
            border-radius: 4px;
        }
    `;
    document.head.appendChild(style);
    
    console.log('CyberTirah Framework v' + CyberTirah.version + ' initialized');
});

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = CyberTirah;
}
