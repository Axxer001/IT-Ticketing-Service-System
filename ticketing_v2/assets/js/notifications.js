/**
 * Nexon IT Ticketing System - Real-time Notifications
 * Handles notification polling, display, and interactions
 * FIXED VERSION
 */

(function() {
    'use strict';

    // Notification Manager Class
    class NotificationManager {
        constructor(options = {}) {
            this.options = {
                pollInterval: options.pollInterval || 30000, // 30 seconds
                maxNotifications: options.maxNotifications || 50,
                apiBasePath: options.apiBasePath || this.getApiBasePath(),
                showBrowserNotifications: options.showBrowserNotifications || false,
                soundEnabled: options.soundEnabled || false
            };

            this.unreadCount = 0;
            this.notifications = [];
            this.pollTimer = null;
            this.isDropdownOpen = false;

            this.init();
        }

        /**
         * Initialize notification manager
         */
        init() {
            // Setup UI elements
            this.setupUI();
            
            // Load initial notifications
            this.loadNotifications();
            
            // Start polling
            this.startPolling();
            
            // Request browser notification permission if enabled
            if (this.options.showBrowserNotifications) {
                this.requestNotificationPermission();
            }

            // Setup event listeners
            this.setupEventListeners();

            // Expose to window
            window.NotificationManager = this;
        }

        /**
         * Get API base path
         */
        getApiBasePath() {
            const path = window.location.pathname;
            
            // Check if we're in a subdirectory
            if (path.includes('/admin/')) {
                return '../../api';
            } else if (path.includes('/tickets/')) {
                return '../../api';
            } else if (path.includes('/provider/')) {
                return '../../api';
            } else if (path.includes('/reports/')) {
                return '../../api';
            } else if (path.includes('/public/')) {
                return '../api';
            } else {
                // Root level (dashboard.php)
                return '../api';
            }
        }

        /**
         * Setup UI elements
         */
        setupUI() {
            // Create notification button if not exists
            if (!document.querySelector('.notification-btn')) {
                this.createNotificationButton();
            }

            // Create notification dropdown if not exists
            if (!document.querySelector('.notification-dropdown')) {
                this.createNotificationDropdown();
            }

            this.notificationBtn = document.querySelector('.notification-btn');
            this.notificationDropdown = document.querySelector('.notification-dropdown');
            this.badgeElement = document.querySelector('.notification-badge');
        }

        /**
         * Create notification button
         */
        createNotificationButton() {
            const navbarActions = document.querySelector('.navbar-actions');
            if (!navbarActions) return;

            const button = document.createElement('button');
            button.className = 'notification-btn';
            button.innerHTML = '🔔';
            button.setAttribute('aria-label', 'Notifications');
            
            // Insert before theme toggle or at the beginning
            const themeToggle = navbarActions.querySelector('.theme-toggle');
            if (themeToggle) {
                navbarActions.insertBefore(button, themeToggle);
            } else {
                navbarActions.insertBefore(button, navbarActions.firstChild);
            }
        }

        /**
         * Create notification dropdown
         */
        createNotificationDropdown() {
            const dropdown = document.createElement('div');
            dropdown.className = 'notification-dropdown';
            dropdown.id = 'notificationDropdown';
            dropdown.innerHTML = `
                <div class="notification-header">
                    <strong>Notifications</strong>
                    <a href="#" onclick="window.NotificationManager?.markAllRead(); return false;" 
                       style="font-size:12px; color:var(--primary)" 
                       class="mark-all-read" style="display:none">Mark all read</a>
                </div>
                <div class="notification-list"></div>
            `;
            
            document.body.appendChild(dropdown);
        }

        /**
         * Setup event listeners
         */
        setupEventListeners() {
            // Toggle dropdown
            if (this.notificationBtn) {
                this.notificationBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    this.toggleDropdown();
                });
            }

            // Close dropdown when clicking outside
            document.addEventListener('click', (e) => {
                if (this.isDropdownOpen && 
                    this.notificationDropdown &&
                    !this.notificationDropdown.contains(e.target) && 
                    this.notificationBtn &&
                    !this.notificationBtn.contains(e.target)) {
                    this.closeDropdown();
                }
            });

            // Handle visibility change (pause polling when tab not visible)
            document.addEventListener('visibilitychange', () => {
                if (document.hidden) {
                    this.stopPolling();
                } else {
                    this.startPolling();
                    this.loadNotifications(); // Refresh on tab focus
                }
            });
        }

        /**
         * Load notifications from server
         */
        async loadNotifications() {
            try {
                const response = await fetch(`${this.options.apiBasePath}/get_notifications.php`);
                const data = await response.json();

                if (data.success) {
                    const oldCount = this.unreadCount;
                    this.notifications = data.notifications || [];
                    this.unreadCount = data.unread_count || 0;

                    this.updateBadge();
                    this.renderNotifications();

                    // Show browser notification for new notifications
                    if (this.unreadCount > oldCount && oldCount > 0) {
                        this.showNewNotificationAlert(this.unreadCount - oldCount);
                    }
                }
            } catch (error) {
                console.error('Failed to load notifications:', error);
            }
        }

        /**
         * Get unread count from server
         */
        async getUnreadCount() {
            try {
                const response = await fetch(`${this.options.apiBasePath}/get_unread_count.php`);
                const data = await response.json();

                if (data.success) {
                    this.unreadCount = data.count || 0;
                    this.updateBadge();
                }
            } catch (error) {
                console.error('Failed to get unread count:', error);
            }
        }

        /**
         * Update badge display
         */
        updateBadge() {
            if (this.unreadCount > 0) {
                if (!this.badgeElement) {
                    this.badgeElement = document.createElement('span');
                    this.badgeElement.className = 'notification-badge';
                    if (this.notificationBtn) {
                        this.notificationBtn.appendChild(this.badgeElement);
                    }
                }
                this.badgeElement.textContent = this.unreadCount > 99 ? '99+' : this.unreadCount;
                this.badgeElement.style.display = 'block';
            } else {
                if (this.badgeElement) {
                    this.badgeElement.style.display = 'none';
                }
            }

            // Update mark all read button visibility
            if (this.notificationDropdown) {
                const markAllBtn = this.notificationDropdown.querySelector('.mark-all-read');
                if (markAllBtn) {
                    markAllBtn.style.display = this.unreadCount > 0 ? 'inline' : 'none';
                }
            }
        }

        /**
         * Render notifications in dropdown
         */
        renderNotifications() {
            if (!this.notificationDropdown) return;
            
            const notificationList = this.notificationDropdown.querySelector('.notification-list');
            if (!notificationList) return;

            if (this.notifications.length === 0) {
                notificationList.innerHTML = `
                    <div class="empty-state">
                        <div class="empty-state-icon">🔔</div>
                        <p>No notifications</p>
                    </div>
                `;
                return;
            }

            notificationList.innerHTML = this.notifications.map(notif => `
                <div class="notification-item ${!notif.is_read ? 'unread' : ''}" 
                     data-id="${notif.id}"
                     onclick="window.NotificationManager?.handleNotificationClick(${notif.id}, ${notif.ticket_id || 'null'})">
                    <div class="notification-title">${this.escapeHtml(notif.title)}</div>
                    <div class="notification-message">${this.escapeHtml(notif.message)}</div>
                    <div class="notification-time">${this.formatTime(notif.created_at)}</div>
                </div>
            `).join('');
        }

        /**
         * Handle notification click
         */
        async handleNotificationClick(notificationId, ticketId) {
            // Mark as read
            await this.markAsRead(notificationId);

            // Navigate to ticket if available
            if (ticketId) {
                window.location.href = this.getTicketUrl(ticketId);
            }
        }

        /**
         * Get ticket URL based on current location
         */
        getTicketUrl(ticketId) {
            const path = window.location.pathname;
            
            if (path.includes('/admin/')) {
                return '../tickets/view.php?id=' + ticketId;
            } else if (path.includes('/provider/')) {
                return '../tickets/view.php?id=' + ticketId;
            } else if (path.includes('/reports/')) {
                return '../tickets/view.php?id=' + ticketId;
            } else if (path.includes('/tickets/')) {
                return 'view.php?id=' + ticketId;
            } else {
                // From dashboard
                return 'tickets/view.php?id=' + ticketId;
            }
        }

        /**
         * Mark notification as read - FIXED ENDPOINT NAME
         */
        async markAsRead(notificationId) {
            try {
                const response = await fetch(`${this.options.apiBasePath}/mark_notifications_read.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ notification_id: notificationId })
                });

                const data = await response.json();
                if (data.success) {
                    // Update local state
                    const notif = this.notifications.find(n => n.id === notificationId);
                    if (notif && !notif.is_read) {
                        notif.is_read = true;
                        this.unreadCount = Math.max(0, this.unreadCount - 1);
                        this.updateBadge();
                        this.renderNotifications();
                    }
                }
            } catch (error) {
                console.error('Failed to mark notification as read:', error);
            }
        }

        /**
         * Mark all notifications as read
         */
        async markAllRead() {
            try {
                const response = await fetch(`${this.options.apiBasePath}/mark_all_notifications_read.php`, {
                    method: 'POST'
                });

                const data = await response.json();
                if (data.success) {
                    this.notifications.forEach(notif => notif.is_read = true);
                    this.unreadCount = 0;
                    this.updateBadge();
                    this.renderNotifications();
                }
            } catch (error) {
                console.error('Failed to mark all as read:', error);
            }
        }

        /**
         * Toggle dropdown
         */
        toggleDropdown() {
            if (this.isDropdownOpen) {
                this.closeDropdown();
            } else {
                this.openDropdown();
            }
        }

        /**
         * Open dropdown
         */
        openDropdown() {
            if (this.notificationDropdown) {
                this.notificationDropdown.classList.add('show');
                this.isDropdownOpen = true;
                this.loadNotifications(); // Refresh when opened
            }
        }

        /**
         * Close dropdown
         */
        closeDropdown() {
            if (this.notificationDropdown) {
                this.notificationDropdown.classList.remove('show');
                this.isDropdownOpen = false;
            }
        }

        /**
         * Start polling for new notifications
         */
        startPolling() {
            if (this.pollTimer) return;

            this.pollTimer = setInterval(() => {
                this.getUnreadCount();
            }, this.options.pollInterval);
        }

        /**
         * Stop polling
         */
        stopPolling() {
            if (this.pollTimer) {
                clearInterval(this.pollTimer);
                this.pollTimer = null;
            }
        }

        /**
         * Request browser notification permission
         */
        async requestNotificationPermission() {
            if ('Notification' in window && Notification.permission === 'default') {
                await Notification.requestPermission();
            }
        }

        /**
         * Show browser notification
         */
        showNewNotificationAlert(count) {
            if (!this.options.showBrowserNotifications) return;
            if (!('Notification' in window)) return;
            if (Notification.permission !== 'granted') return;

            const title = `${count} New Notification${count > 1 ? 's' : ''}`;
            const options = {
                body: 'Click to view your notifications',
                icon: '/assets/img/notification-icon.png',
                badge: '/assets/img/badge-icon.png',
                tag: 'nexon-notification',
                requireInteraction: false
            };

            const notification = new Notification(title, options);
            
            notification.onclick = () => {
                window.focus();
                this.openDropdown();
                notification.close();
            };

            // Auto close after 5 seconds
            setTimeout(() => notification.close(), 5000);
        }

        /**
         * Format timestamp
         */
        formatTime(timestamp) {
            const date = new Date(timestamp);
            const now = new Date();
            const diff = now - date;

            // Less than 1 minute
            if (diff < 60000) {
                return 'Just now';
            }
            // Less than 1 hour
            if (diff < 3600000) {
                const mins = Math.floor(diff / 60000);
                return `${mins} min${mins > 1 ? 's' : ''} ago`;
            }
            // Less than 24 hours
            if (diff < 86400000) {
                const hours = Math.floor(diff / 3600000);
                return `${hours} hour${hours > 1 ? 's' : ''} ago`;
            }
            // Format as date
            const options = { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' };
            return date.toLocaleDateString('en-US', options);
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
         * Destroy notification manager
         */
        destroy() {
            this.stopPolling();
        }
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            new NotificationManager();
        });
    } else {
        new NotificationManager();
    }

    // Global helper functions
    window.toggleNotifications = function() {
        if (window.NotificationManager) {
            window.NotificationManager.toggleDropdown();
        }
    };

    window.markAsRead = function(notificationId) {
        if (window.NotificationManager) {
            window.NotificationManager.markAsRead(notificationId);
        }
    };

    window.markAllRead = function() {
        if (window.NotificationManager) {
            window.NotificationManager.markAllRead();
        }
    };

})();