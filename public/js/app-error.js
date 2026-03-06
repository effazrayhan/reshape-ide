/**
 * Reshape IDE - Global Error Handler
 * Handles client-side errors and API failures
 */

class AppError {
    constructor(message, code = 'UNKNOWN_ERROR', status = 500, details = null) {
        this.message = message;
        this.code = code;
        this.status = status;
        this.details = details;
        this.timestamp = new Date().toISOString();
    }

    /**
     * Create error from API response
     */
    static fromResponse(response) {
        try {
            const data = typeof response === 'string' ? JSON.parse(response) : response;
            return new AppError(
                data.message || 'An error occurred',
                data.code || 'API_ERROR',
                data.status || response.status || 500,
                data.details || null
            );
        } catch (e) {
            return new AppError(
                'Failed to parse error response',
                'PARSE_ERROR',
                500,
                { originalError: e.message }
            );
        }
    }

    /**
     * Create error from network failure
     */
    static networkError(originalError) {
        return new AppError(
            'Network error - please check your connection',
            'NETWORK_ERROR',
            0,
            { originalError: originalError.message }
        );
    }

    /**
     * Create error from validation failure
     */
    static validationError(message, details = null) {
        return new AppError(
            message,
            'VALIDATION_ERROR',
            400,
            details
        );
    }

    /**
     * Create error from authentication failure
     */
    static authError(message = 'Authentication failed') {
        return new AppError(
            message,
            'AUTH_ERROR',
            401
        );
    }

    /**
     * Create error from server failure
     */
    static serverError(message = 'Server error') {
        return new AppError(
            message,
            'SERVER_ERROR',
            500
        );
    }

    /**
     * Create error for not found resources
     */
    static notFound(message = 'Resource not found') {
        return new AppError(
            message,
            'NOT_FOUND',
            404
        );
    }

    /**
     * Display error to user
     */
    display() {
        // Show error message in UI
        this.showNotification(this.message, 'error');
    }

    /**
     * Show notification to user
     */
    showNotification(message, type = 'error') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <span class="notification-message">${this.escapeHtml(message)}</span>
            <button class="notification-close">&times;</button>
        `;

        // Add styles if not already added
        this.addNotificationStyles();

        // Add to DOM
        let container = document.querySelector('.notification-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'notification-container';
            document.body.appendChild(container);
        }
        container.appendChild(notification);

        // Auto-remove after 5 seconds
        setTimeout(() => {
            notification.classList.add('notification-fade-out');
            setTimeout(() => notification.remove(), 300);
        }, 5000);

        // Close button handler
        const closeBtn = notification.querySelector('.notification-close');
        closeBtn.addEventListener('click', () => {
            notification.classList.add('notification-fade-out');
            setTimeout(() => notification.remove(), 300);
        });
    }

    /**
     * Add notification styles dynamically
     */
    addNotificationStyles() {
        if (document.getElementById('app-error-styles')) return;

        const styles = document.createElement('style');
        styles.id = 'app-error-styles';
        styles.textContent = `
            .notification-container {
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 10000;
                max-width: 400px;
            }
            .notification {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 12px 16px;
                margin-bottom: 10px;
                border-radius: 6px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                animation: slideIn 0.3s ease-out;
            }
            .notification-error {
                background: #fee2e2;
                border-left: 4px solid #ef4444;
                color: #991b1b;
            }
            .notification-success {
                background: #dcfce7;
                border-left: 4px solid #22c55e;
                color: #166534;
            }
            .notification-warning {
                background: #fef3c7;
                border-left: 4px solid #f59e0b;
                color: #92400e;
            }
            .notification-info {
                background: #dbeafe;
                border-left: 4px solid #3b82f6;
                color: #1e40af;
            }
            .notification-message {
                flex: 1;
                font-size: 14px;
            }
            .notification-close {
                background: none;
                border: none;
                font-size: 20px;
                cursor: pointer;
                padding: 0 0 0 10px;
                opacity: 0.6;
            }
            .notification-close:hover {
                opacity: 1;
            }
            .notification-fade-out {
                animation: fadeOut 0.3s ease-out forwards;
            }
            @keyframes slideIn {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
            @keyframes fadeOut {
                from {
                    transform: translateX(0);
                    opacity: 1;
                }
                to {
                    transform: translateX(100%);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(styles);
    }

    /**
     * Escape HTML to prevent XSS
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Log error to console with context
     */
    log() {
        console.error('[AppError]', {
            message: this.message,
            code: this.code,
            status: this.status,
            details: this.details,
            timestamp: this.timestamp
        });
    }
}

/**
 * Global error handler for uncaught errors
 */
window.addEventListener('error', function(event) {
    const error = new AppError(
        event.message || 'An unexpected error occurred',
        'UNCAUGHT_ERROR',
        500,
        {
            filename: event.filename,
            lineno: event.lineno,
            colno: event.colno
        }
    );
    error.log();
    error.display();
});

/**
 * Global handler for unhandled promise rejections
 */
window.addEventListener('unhandledrejection', function(event) {
    const error = event.reason instanceof AppError 
        ? event.reason 
        : new AppError(
            event.reason?.message || 'Unhandled promise rejection',
            'UNHANDLED_REJECTION',
            500,
            { originalError: event.reason?.toString() }
        );
    error.log();
    error.display();
});

/**
 * API request helper with built-in error handling
 */
async function apiRequest(url, options = {}) {
    const defaultOptions = {
        headers: {
            'Content-Type': 'application/json',
        },
    };

    const config = { ...defaultOptions, ...options };

    // Add auth token if available
    const token = localStorage.getItem('auth_token');
    if (token) {
        config.headers['Authorization'] = `Bearer ${token}`;
    }

    try {
        const response = await fetch(url, config);
        const data = await response.json();

        if (!response.ok) {
            throw AppError.fromResponse(data);
        }

        return data;
    } catch (error) {
        if (error instanceof AppError) {
            error.log();
            error.display();
            throw error;
        }

        // Network error
        const networkError = AppError.networkError(error);
        networkError.log();
        networkError.display();
        throw networkError;
    }
}

/**
 * Show success notification
 */
function showSuccess(message) {
    const notification = document.createElement('div');
    notification.className = 'notification notification-success';
    notification.innerHTML = `
        <span class="notification-message">${message}</span>
        <button class="notification-close">&times;</button>
    `;

    // Add styles
    const error = new AppError('', '', 0);
    error.addNotificationStyles();

    let container = document.querySelector('.notification-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'notification-container';
        document.body.appendChild(container);
    }
    container.appendChild(notification);

    setTimeout(() => {
        notification.classList.add('notification-fade-out');
        setTimeout(() => notification.remove(), 300);
    }, 3000);

    const closeBtn = notification.querySelector('.notification-close');
    closeBtn.addEventListener('click', () => {
        notification.classList.add('notification-fade-out');
        setTimeout(() => notification.remove(), 300);
    });
}

/**
 * Show warning notification
 */
function showWarning(message) {
    const error = new AppError(message, 'WARNING', 0);
    error.showNotification(message, 'warning');
}

/**
 * Show info notification
 */
function showInfo(message) {
    const error = new AppError(message, 'INFO', 0);
    error.showNotification(message, 'info');
}

// Export for module use
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { AppError, apiRequest, showSuccess, showWarning, showInfo };
}
