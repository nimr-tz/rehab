/**
 * Dashboard Theming System
 * Handles role-based theming and dynamic theme switching
 */

class DashboardThemeManager {
    constructor() {
        this.currentRole = this.detectCurrentRole();
        this.init();
    }

    /**
     * Initialize the theme manager
     */
    init() {
        this.applyRoleTheme(this.currentRole);
        this.setupThemeListeners();
        this.setupProgressAnimations();
        this.setupCardInteractions();
    }

    /**
     * Detect current user role from DOM or session
     */
    detectCurrentRole() {
        // Try to get role from data attribute
        const roleElement = document.querySelector('[data-role]');
        if (roleElement) {
            return roleElement.getAttribute('data-role');
        }

        // Try to get role from body class
        const bodyClasses = document.body.className;
        if (bodyClasses.includes('role-admin')) return 'admin';
        if (bodyClasses.includes('role-reviewer')) return 'reviewer';
        if (bodyClasses.includes('role-author')) return 'author';

        // Default to author if no role detected
        return 'author';
    }

    /**
     * Apply role-specific theme to the document
     */
    applyRoleTheme(role) {
        // Remove existing role classes
        document.documentElement.classList.remove('theme-author', 'theme-reviewer', 'theme-admin');
        
        // Add new role class
        document.documentElement.classList.add(`theme-${role}`);
        
        // Set data attribute for CSS custom properties
        document.documentElement.setAttribute('data-role', role);
        
        // Update current role
        this.currentRole = role;
        
        // Trigger custom event for other components
        document.dispatchEvent(new CustomEvent('themeChanged', { 
            detail: { role } 
        }));
    }

    /**
     * Switch to a different role theme
     */
    switchRole(newRole) {
        if (['author', 'reviewer', 'admin'].includes(newRole)) {
            this.applyRoleTheme(newRole);
            
            // Store preference in localStorage
            localStorage.setItem('preferred-role-theme', newRole);
            
            // Animate theme transition
            this.animateThemeTransition();
        }
    }

    /**
     * Setup event listeners for theme switching
     */
    setupThemeListeners() {
        // Listen for role switch buttons
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-switch-role]')) {
                const newRole = e.target.getAttribute('data-switch-role');
                this.switchRole(newRole);
            }
        });

        // Listen for system theme changes
        if (window.matchMedia) {
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
                this.handleSystemThemeChange();
            });
        }
    }

    /**
     * Setup progress bar animations
     */
    setupProgressAnimations() {
        const progressBars = document.querySelectorAll('.progress-bar');
        
        progressBars.forEach(bar => {
            const progress = bar.getAttribute('data-progress') || '0';
            const progressValue = Math.min(Math.max(parseFloat(progress), 0), 100);
            
            // Set CSS custom property for animation
            bar.style.setProperty('--progress-width', `${progressValue}%`);
            
            // Animate on intersection
            if ('IntersectionObserver' in window) {
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('animate-progress-fill');
                            observer.unobserve(entry.target);
                        }
                    });
                });
                
                observer.observe(bar);
            }
        });

        // Setup progress rings
        this.setupProgressRings();
    }

    /**
     * Setup progress ring animations
     */
    setupProgressRings() {
        const progressRings = document.querySelectorAll('.progress-ring');
        
        progressRings.forEach(ring => {
            const circle = ring.querySelector('.progress-ring-fill');
            if (!circle) return;

            const radius = circle.r.baseVal.value;
            const circumference = 2 * Math.PI * radius;
            const progress = parseFloat(ring.getAttribute('data-progress') || '0');
            const progressValue = Math.min(Math.max(progress, 0), 100);
            
            // Set up the circle
            circle.style.strokeDasharray = circumference;
            circle.style.strokeDashoffset = circumference;
            
            // Set CSS custom properties
            ring.style.setProperty('--circumference', circumference);
            ring.style.setProperty('--dash-offset', circumference - (progressValue / 100) * circumference);
            
            // Animate on intersection
            if ('IntersectionObserver' in window) {
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const targetOffset = circumference - (progressValue / 100) * circumference;
                            circle.style.strokeDashoffset = targetOffset;
                            observer.unobserve(entry.target);
                        }
                    });
                });
                
                observer.observe(ring);
            }
        });
    }

    /**
     * Setup card hover interactions
     */
    setupCardInteractions() {
        const interactiveCards = document.querySelectorAll('.card-interactive');
        
        interactiveCards.forEach(card => {
            card.addEventListener('mouseenter', () => {
                card.style.transform = 'translateY(-4px) scale(1.02)';
            });
            
            card.addEventListener('mouseleave', () => {
                card.style.transform = 'translateY(0) scale(1)';
            });
        });
    }

    /**
     * Animate theme transition
     */
    animateThemeTransition() {
        document.body.style.transition = 'all 0.3s ease-out';
        
        setTimeout(() => {
            document.body.style.transition = '';
        }, 300);
    }

    /**
     * Handle system theme changes
     */
    handleSystemThemeChange() {
        // Re-apply current role theme to ensure consistency
        this.applyRoleTheme(this.currentRole);
    }

    /**
     * Get current theme colors for JavaScript use
     */
    getCurrentThemeColors() {
        const computedStyle = getComputedStyle(document.documentElement);
        
        return {
            primary: `hsl(${computedStyle.getPropertyValue('--role-primary').trim()})`,
            secondary: `hsl(${computedStyle.getPropertyValue('--role-secondary').trim()})`,
            accent: `hsl(${computedStyle.getPropertyValue('--role-accent').trim()})`,
        };
    }

    /**
     * Update progress value dynamically
     */
    updateProgress(element, newValue) {
        const progressValue = Math.min(Math.max(parseFloat(newValue), 0), 100);
        
        if (element.classList.contains('progress-bar')) {
            element.style.setProperty('--progress-width', `${progressValue}%`);
            element.setAttribute('data-progress', progressValue);
        } else if (element.classList.contains('progress-ring')) {
            const circle = element.querySelector('.progress-ring-fill');
            if (circle) {
                const radius = circle.r.baseVal.value;
                const circumference = 2 * Math.PI * radius;
                const offset = circumference - (progressValue / 100) * circumference;
                
                circle.style.strokeDashoffset = offset;
                element.setAttribute('data-progress', progressValue);
            }
        }
    }

    /**
     * Add loading state to an element
     */
    addLoadingState(element, text = 'Loading...') {
        const originalContent = element.innerHTML;
        element.setAttribute('data-original-content', originalContent);
        
        element.innerHTML = `
            <div class="flex items-center justify-center">
                <div class="loading-spinner mr-2"></div>
                <span>${text}</span>
            </div>
        `;
        
        element.classList.add('loading');
        element.disabled = true;
    }

    /**
     * Remove loading state from an element
     */
    removeLoadingState(element) {
        const originalContent = element.getAttribute('data-original-content');
        if (originalContent) {
            element.innerHTML = originalContent;
            element.removeAttribute('data-original-content');
        }
        
        element.classList.remove('loading');
        element.disabled = false;
    }

    /**
     * Show notification with role-specific styling
     */
    showNotification(message, type = 'info', duration = 5000) {
        const notification = document.createElement('div');
        notification.className = `alert-${type} fixed top-4 right-4 z-50 max-w-sm animate-slide-down`;
        notification.innerHTML = `
            <div class="flex items-center">
                <div class="flex-1">${message}</div>
                <button class="ml-2 text-current opacity-70 hover:opacity-100" onclick="this.parentElement.parentElement.remove()">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        // Auto-remove after duration
        if (duration > 0) {
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.style.animation = 'slideUp 0.3s ease-out forwards';
                    setTimeout(() => notification.remove(), 300);
                }
            }, duration);
        }
        
        return notification;
    }
}

// Initialize theme manager when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.dashboardTheme = new DashboardThemeManager();
});

// Export for module use
if (typeof module !== 'undefined' && module.exports) {
    module.exports = DashboardThemeManager;
}

// Global utility functions
window.switchRole = function(role) {
    if (window.dashboardTheme) {
        window.dashboardTheme.switchRole(role);
        
        // Also make an AJAX request to update the session if needed
        fetch('/switch-role', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ role: role })
        }).then(response => {
            if (response.ok) {
                // Optionally reload the page to update server-side role-specific content
                // window.location.reload();
            }
        }).catch(error => {
            console.warn('Role switch request failed:', error);
        });
    }
};

window.updateProgress = function(selector, value) {
    const element = document.querySelector(selector);
    if (element && window.dashboardTheme) {
        window.dashboardTheme.updateProgress(element, value);
    }
};

window.showNotification = function(message, type = 'info', duration = 5000) {
    if (window.dashboardTheme) {
        return window.dashboardTheme.showNotification(message, type, duration);
    }
};