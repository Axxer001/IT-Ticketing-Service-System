/**
 * Nexon IT Ticketing System - Simple Notifications
 * FIXED: Only creates UI elements if they don't exist
 */

(function() {
    'use strict';

    // Only initialize if user is logged in (check for navbar presence)
    if (!document.querySelector('.navbar-actions')) {
        return; // Exit if not on a page with notifications
    }

    // Notification Manager Class
    class NotificationManager {
        constructor() {
            this.unreadCount = 0;
            this.notifications = [];
            this.pollTimer = null;
            this.isDropdownOpen = false;
            this.apiBasePath = this.getApiBasePath();

            this.init();
        }

        init() {
            // Only setup if notification button exists
            this.notificationBtn = document.querySelector('.notification-btn');
            if (!this.notificationBtn) {
                return; // Exit if no notification button on page
            }

            this.setupUI();
            this.loadNotifications();
            this.startPolling();
            this.setupEventListeners();

            window.NotificationManager = this;
        }

        getApiBasePath() {
            const path = window.location.pathname;
            
            if (path.includes('/admin/') || path.includes('/tickets/') || 
                path.includes('/provider/') || path.includes('/reports/')) {
                return '../../api';
            } else if (path.includes('/public/')) {
                return '../api';
            } else {
                return '../api';
            }
        }

        setupUI() {
            // Create dropdown only if it doesn't exist
            if (!document.querySelector('.notification-dropdown')) {
                this.createNotificationDropdown();
            }

            this.notificationDropdown = document.querySelector('.notification-dropdown');
            this.badgeElement = document.querySelector('.notification-badge');
        }

        createNotificationDropdown() {
            const dropdown = document.createElement('div');
            dropdown.className = 'notification-dropdown';
            dropdown.id = 'notificationDropdown';
            dropdown.innerHTML = `
                <div class="notification-header">
                    <strong>Notifications</strong>
                    <a href="#" onclick="window.NotificationManager?.markAllRead(); return false;" 
                       style="font-size:12px; color:var(--primary); display:none" 
                       class="mark-all-read">Mark all read</a>
                </div>
                <div class="notification-list"></div>
            `;
            
            document.body.appendChild(dropdown);
        }

        setupEventListeners() {
            if (this.notificationBtn) {
                this.notificationBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    this.toggleDropdown();
                });
            }

            document.addEventListener('click', (e) => {
                if (this.isDropdownOpen && 
                    this.notificationDropdown &&
                    !this.notificationDropdown.contains(e.target) && 
                    this.notificationBtn &&
                    !this.notificationBtn.contains(e.target)) {
                    this.closeDropdown();
                }
            });

            document.addEventListener('visibilitychange', () => {
                if (document.hidden) {
                    this.stopPolling();
                } else {
                    this.startPolling();
                    this.loadNotifications();
                }
            });
        }

        async loadNotifications() {
            try {
                const response = await fetch(`${this.apiBasePath}/get_notifications.php`);
                const data = await response.json();

                if (data.success) {
                    this.notifications = data.notifications || [];
                    this.unreadCount = data.unread_count || 0;
                    this.updateBadge();
                    this.renderNotifications();
                }
            } catch (error) {
                console.error('Failed to load notifications:', error);
            }
        }

        async getUnreadCount() {
            try {
                const response = await fetch(`${this.apiBasePath}/get_unread_count.php`);
                const data = await response.json();

                if (data.success) {
                    this.unreadCount = data.count || 0;
                    this.updateBadge();
                }
            } catch (error) {
                console.error('Failed to get unread count:', error);
            }
        }

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

            if (this.notificationDropdown) {
                const markAllBtn = this.notificationDropdown.querySelector('.mark-all-read');
                if (markAllBtn) {
                    markAllBtn.style.display = this.unreadCount > 0 ? 'inline' : 'none';
                }
            }
        }

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

        async handleNotificationClick(notificationId, ticketId) {
            await this.markAsRead(notificationId);

            if (ticketId) {
                window.location.href = this.getTicketUrl(ticketId);
            }
        }

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
                return 'tickets/view.php?id=' + ticketId;
            }
        }

        async markAsRead(notificationId) {
            try {
                const response = await fetch(`${this.apiBasePath}/mark_notifications_read.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ notification_id: notificationId })
                });

                const data = await response.json();
                if (data.success) {
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

        async markAllRead() {
            try {
                const response = await fetch(`${this.apiBasePath}/mark_all_notifications_read.php`, {
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

        toggleDropdown() {
            if (this.isDropdownOpen) {
                this.closeDropdown();
            } else {
                this.openDropdown();
            }
        }

        openDropdown() {
            if (this.notificationDropdown) {
                this.notificationDropdown.classList.add('show');
                this.isDropdownOpen = true;
                this.loadNotifications();
            }
        }

        closeDropdown() {
            if (this.notificationDropdown) {
                this.notificationDropdown.classList.remove('show');
                this.isDropdownOpen = false;
            }
        }

        startPolling() {
            if (this.pollTimer) return;

            this.pollTimer = setInterval(() => {
                this.getUnreadCount();
            }, 30000); // 30 seconds
        }

        stopPolling() {
            if (this.pollTimer) {
                clearInterval(this.pollTimer);
                this.pollTimer = null;
            }
        }

        formatTime(timestamp) {
            const date = new Date(timestamp);
            const now = new Date();
            const diff = now - date;

            if (diff < 60000) {
                return 'Just now';
            }
            if (diff < 3600000) {
                const mins = Math.floor(diff / 60000);
                return `${mins} min${mins > 1 ? 's' : ''} ago`;
            }
            if (diff < 86400000) {
                const hours = Math.floor(diff / 3600000);
                return `${hours} hour${hours > 1 ? 's' : ''} ago`;
            }
            const options = { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' };
            return date.toLocaleDateString('en-US', options);
        }

        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

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

})();