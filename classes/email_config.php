<?php
/**
 * Email Configuration
 * IMPORTANT: Replace with your actual Gmail credentials
 */

define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'neewra45@gmail.com');        // ⚠️ CHANGE THIS
define('SMTP_PASSWORD', 'xcqc dpwc cqvt eqf');         // ⚠️ YOUR APP PASSWORD
define('SMTP_FROM_EMAIL', 'neewra45.com');      // ⚠️ CHANGE THIS
define('SMTP_FROM_NAME', 'Nexon IT Support');

define('ENABLE_EMAIL_NOTIFICATIONS', true);  // Set to false to disable
define('DEBUG_EMAIL', false);  // Set to true for debugging

return [
    'smtp_host' => SMTP_HOST,
    'smtp_port' => SMTP_PORT,
    'smtp_username' => SMTP_USERNAME,
    'smtp_password' => SMTP_PASSWORD,
    'from_email' => SMTP_FROM_EMAIL,
    'from_name' => SMTP_FROM_NAME,
    'enabled' => ENABLE_EMAIL_NOTIFICATIONS,
    'debug' => DEBUG_EMAIL
];