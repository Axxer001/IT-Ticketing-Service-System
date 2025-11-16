/**
 * Nexon IT Ticketing System - Theme Switcher
 * Handles light/dark theme switching with persistence
 * FIXED VERSION
 */

(function() {
    'use strict';

    // Theme Manager Class
    class ThemeManager {
        constructor() {
            this.currentTheme = this.getStoredTheme() || this.getPreferredTheme();
            this.init();
        }

        /**
         * Initialize theme manager
         */
        init() {
            // Apply theme on page load
            this.applyTheme(this.currentTheme);
            
            // Setup theme toggle buttons
            this.setupToggleButtons();
            
            // Listen for system theme changes
            this.watchSystemTheme();
            
            // Expose to window for external access
            window.ThemeManager = this;
        }

        /**
         * Get stored theme from localStorage or session
         */
        getStoredTheme() {
            // Check localStorage first
            const stored = localStorage.getItem('theme');
            if (stored) return stored;
            
            // Check session (from PHP)
            if (typeof PHP_SESSION_THEME !== 'undefined') {
                return PHP_SESSION_THEME;
            }
            
            return null;
        }

        /**
         * Get system preferred theme
         */
        getPreferredTheme() {
            if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                return 'dark';
            }
            return 'light';
        }

        /**
         * Apply theme to document
         */
        applyTheme(theme) {
            const html = document.documentElement;
            const oldTheme = html.getAttribute('data-theme');
            
            // Set theme attribute
            html.setAttribute('data-theme', theme);
            
            // Update all theme toggle buttons
            this.updateToggleButtons(theme);
            
            // Store theme
            this.storeTheme(theme);
            
            // Trigger custom event
            this.dispatchThemeChange(theme, oldTheme);
            
            this.currentTheme = theme;
        }

        /**
         * Store theme in localStorage and server
         */
        storeTheme(theme) {
            // Store in localStorage
            localStorage.setItem('theme', theme);
            
            // Send to server to update session/database
            this.syncThemeToServer(theme);
        }

        /**
         * Sync theme to server via AJAX
         */
        syncThemeToServer(theme) {
            // Check if API endpoint exists
            const apiPath = this.getApiPath();
            if (!apiPath) return;

            fetch(apiPath, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ theme: theme })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('Theme synced to server:', theme);
                }
            })
            .catch(error => {
                console.warn('Failed to sync theme to server:', error);
            });
        }

        /**
         * Get API path based on current location - FIXED
         */
        getApiPath() {
            const path = window.location.pathname;
            
            // Determine relative path to api folder
            if (path.includes('/admin/') || path.includes('/tickets/') || 
                path.includes('/provider/') || path.includes('/reports/')) {
                return '../../api/update_theme.php';
            } else if (path.includes('/public/')) {
                return '../api/update_theme.php';
            } else {
                // For root level pages in public directory
                return '../api/update_theme.php';
            }
        }

        /**
         * Toggle between light and dark themes
         */
        toggle() {
            const newTheme = this.currentTheme === 'light' ? 'dark' : 'light';
            this.applyTheme(newTheme);
            
            // Add transition animation
            this.addTransitionAnimation();
        }

        /**
         * Add smooth transition when switching themes
         */
        addTransitionAnimation() {
            const html = document.documentElement;
            html.style.transition = 'background-color 0.3s ease, color 0.3s ease';
            
            setTimeout(() => {
                html.style.transition = '';
            }, 300);
        }

        /**
         * Setup all theme toggle buttons on page
         */
        setupToggleButtons() {
            const buttons = document.querySelectorAll('[data-theme-toggle], .theme-toggle, #themeToggle');
            
            buttons.forEach(button => {
                button.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.toggle();
                });
            });
            
            // Initial button state
            this.updateToggleButtons(this.currentTheme);
        }

        /**
         * Update toggle button icons/text
         */
        updateToggleButtons(theme) {
            const buttons = document.querySelectorAll('[data-theme-toggle], .theme-toggle, #themeToggle');
            
            buttons.forEach(button => {
                if (button.hasAttribute('data-theme-icon')) {
                    // Use data attributes for icon
                    const lightIcon = button.getAttribute('data-light-icon') || '🌙';
                    const darkIcon = button.getAttribute('data-dark-icon') || '☀️';
                    button.textContent = theme === 'light' ? lightIcon : darkIcon;
                } else {
                    // Default icons
                    button.textContent = theme === 'light' ? '🌙' : '☀️';
                }
                
                // Update aria-label for accessibility
                button.setAttribute('aria-label', 
                    theme === 'light' ? 'Switch to dark mode' : 'Switch to light mode'
                );
            });
        }

        /**
         * Watch for system theme changes
         */
        watchSystemTheme() {
            if (!window.matchMedia) return;
            
            const darkModeQuery = window.matchMedia('(prefers-color-scheme: dark)');
            
            darkModeQuery.addEventListener('change', (e) => {
                // Only auto-switch if user hasn't manually set a preference
                const hasManualPreference = localStorage.getItem('theme');
                if (!hasManualPreference) {
                    const newTheme = e.matches ? 'dark' : 'light';
                    this.applyTheme(newTheme);
                }
            });
        }

        /**
         * Dispatch custom theme change event
         */
        dispatchThemeChange(newTheme, oldTheme) {
            const event = new CustomEvent('themechange', {
                detail: {
                    theme: newTheme,
                    oldTheme: oldTheme
                }
            });
            window.dispatchEvent(event);
        }

        /**
         * Get current theme
         */
        getTheme() {
            return this.currentTheme;
        }

        /**
         * Set specific theme
         */
        setTheme(theme) {
            if (theme === 'light' || theme === 'dark') {
                this.applyTheme(theme);
            }
        }

        /**
         * Reset to system preference
         */
        resetToSystem() {
            localStorage.removeItem('theme');
            const systemTheme = this.getPreferredTheme();
            this.applyTheme(systemTheme);
        }
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            new ThemeManager();
        });
    } else {
        new ThemeManager();
    }

    // Global helper functions
    window.toggleTheme = function() {
        if (window.ThemeManager) {
            window.ThemeManager.toggle();
        }
    };

    window.setTheme = function(theme) {
        if (window.ThemeManager) {
            window.ThemeManager.setTheme(theme);
        }
    };

    window.getTheme = function() {
        return window.ThemeManager ? window.ThemeManager.getTheme() : 'light';
    };

})();