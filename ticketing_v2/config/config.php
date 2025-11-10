<?php
/**
 * Configuration File
 * Nexon IT Ticketing System
 */

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'ticketing_v2');
define('DB_USER', 'root');
define('DB_PASS', '');

// Application Settings
define('APP_NAME', 'Nexon IT Ticketing System');
define('APP_VERSION', '2.0');

// File Upload Settings
define('MAX_FILE_SIZE', 10485760); // 10MB in bytes
define('MAX_ATTACHMENTS', 5);
define('ALLOWED_FILE_TYPES', ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx']);
define('UPLOAD_PATH', __DIR__ . '/../uploads/tickets/');

// Session Settings
define('SESSION_LIFETIME', 3600); // 1 hour

// Timezone
date_default_timezone_set('Asia/Manila');

// Error Reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Security Settings
define('PASSWORD_MIN_LENGTH', 6);
define('SESSION_NAME', 'nexon_ticketing');

// Start session with custom name
session_name(SESSION_NAME);

// Notification Settings
define('ENABLE_EMAIL_NOTIFICATIONS', false); // Set to true when email is configured
define('SMTP_HOST', 'smtp.example.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', '');
define('SMTP_PASSWORD', '');
define('SMTP_FROM_EMAIL', 'noreply@nexon.com');
define('SMTP_FROM_NAME', 'Nexon IT Support');