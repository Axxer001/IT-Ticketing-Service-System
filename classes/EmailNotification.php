<?php
require_once __DIR__ . "/../vendor/autoload.php";
require_once "Database.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Email Notification Handler using PHPMailer
 * Sends email notifications to Gmail addresses
 * ENHANCED VERSION - Better debugging and error handling
 */
class EmailNotification {
    private $db;
    private $mailer;
    private $enabled;
    private $debug;
    
    // Configuration
    private $smtpHost;
    private $smtpPort;
    private $smtpUsername;
    private $smtpPassword;
    private $fromEmail;
    private $fromName;
    
    public function __construct() {
        $this->db = new Database();
        
        // Load configuration
        $this->loadConfig();
        
        // Setup mailer if email is enabled
        if ($this->enabled) {
            $this->setupMailer();
        }
    }
    
    /**
     * Load email configuration
     */
    private function loadConfig() {
        $configFile = __DIR__ . '/email_config.php';
        
        if (file_exists($configFile)) {
            $config = require $configFile;
            
            $this->smtpHost = $config['smtp_host'] ?? 'smtp.gmail.com';
            $this->smtpPort = $config['smtp_port'] ?? 587;
            $this->smtpUsername = $config['smtp_username'] ?? '';
            $this->smtpPassword = $config['smtp_password'] ?? '';
            $this->fromEmail = $config['from_email'] ?? '';
            $this->fromName = $config['from_name'] ?? 'Nexon IT Support';
            $this->enabled = $config['enabled'] ?? false;
            $this->debug = $config['debug'] ?? false;
            
            if ($this->debug) {
                error_log("Email config loaded - Host: {$this->smtpHost}, Port: {$this->smtpPort}, From: {$this->fromEmail}");
            }
        } else {
            error_log("ERROR: Email config file not found at: " . $configFile);
            $this->enabled = false;
        }
        
        // Validate credentials
        if (empty($this->smtpUsername) || empty($this->smtpPassword)) {
            error_log("ERROR: SMTP credentials not configured - Username: " . (empty($this->smtpUsername) ? 'MISSING' : 'SET') . ", Password: " . (empty($this->smtpPassword) ? 'MISSING' : 'SET'));
            $this->enabled = false;
        }
    }
    
    /**
     * Setup PHPMailer configuration
     */
    private function setupMailer() {
        $this->mailer = new PHPMailer(true);
        
        try {
            // Server settings
            $this->mailer->isSMTP();
            $this->mailer->Host = $this->smtpHost;
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = $this->smtpUsername;
            $this->mailer->Password = $this->smtpPassword;
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mailer->Port = $this->smtpPort;
            
            // Debug output (if enabled)
            if ($this->debug) {
                $this->mailer->SMTPDebug = 2; // Enable verbose debug output
                $this->mailer->Debugoutput = function($str, $level) {
                    error_log("SMTP DEBUG: $str");
                };
            }
            
            // Set from address
            $this->mailer->setFrom($this->fromEmail, $this->fromName);
            
            // Encoding
            $this->mailer->CharSet = 'UTF-8';
            
            // Timeout
            $this->mailer->Timeout = 30; // Increased timeout for better reliability
            
            // Additional settings for Gmail
            $this->mailer->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );
            
            if ($this->debug) {
                error_log("PHPMailer configured successfully");
            }
            
        } catch (Exception $e) {
            error_log("ERROR: PHPMailer setup failed - " . $e->getMessage());
            $this->enabled = false;
        }
    }
    
    /**
     * Send email notification
     */
    public function sendEmail($toEmail, $subject, $message) {
        if (!$this->enabled) {
            $msg = "Email notifications disabled - To: $toEmail, Subject: $subject";
            error_log("WARNING: " . $msg);
            return true; // Return true to not break functionality
        }
        
        // Validate email
        if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            error_log("ERROR: Invalid email address - $toEmail");
            return false;
        }
        
        try {
            // Clear previous recipients
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            $this->mailer->clearReplyTos();
            
            // Set recipient
            $this->mailer->addAddress($toEmail);
            
            // Content
            $this->mailer->isHTML(true);
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $this->getEmailTemplate($subject, $message);
            $this->mailer->AltBody = strip_tags($message);
            
            if ($this->debug) {
                error_log("Attempting to send email to: $toEmail, Subject: $subject");
            }
            
            // Send
            $result = $this->mailer->send();
            
            if ($result) {
                error_log("✅ SUCCESS: Email sent to $toEmail - Subject: $subject");
                return true;
            } else {
                error_log("❌ FAILED: Email not sent to $toEmail");
                return false;
            }
            
        } catch (Exception $e) {
            $errorMsg = $this->mailer->ErrorInfo;
            error_log("❌ ERROR: Email send failed to $toEmail - " . $errorMsg);
            error_log("Exception: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get HTML email template
     */
    private function getEmailTemplate($subject, $message) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <style>
                body { 
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; 
                    line-height: 1.6; 
                    color: #333; 
                    margin: 0;
                    padding: 0;
                    background-color: #f4f4f4;
                }
                .container { 
                    max-width: 600px; 
                    margin: 20px auto; 
                    background: white;
                    border-radius: 10px;
                    overflow: hidden;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                }
                .header { 
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
                    color: white; 
                    padding: 30px 20px; 
                    text-align: center; 
                }
                .header h1 {
                    margin: 0;
                    font-size: 28px;
                    letter-spacing: 2px;
                }
                .header p {
                    margin: 5px 0 0 0;
                    font-size: 14px;
                    opacity: 0.9;
                }
                .content { 
                    padding: 30px; 
                }
                .content h2 {
                    color: #667eea;
                    margin-top: 0;
                    font-size: 20px;
                }
                .content p {
                    margin: 15px 0;
                    color: #555;
                }
                .content ul {
                    background: #f8f9fa;
                    padding: 20px 20px 20px 40px;
                    border-left: 4px solid #667eea;
                    margin: 15px 0;
                }
                .content ul li {
                    margin: 10px 0;
                    color: #333;
                }
                .button { 
                    display: inline-block; 
                    padding: 12px 30px; 
                    background: #667eea; 
                    color: white !important; 
                    text-decoration: none; 
                    border-radius: 5px; 
                    margin-top: 20px;
                    font-weight: 600;
                }
                .footer { 
                    text-align: center; 
                    padding: 20px; 
                    font-size: 12px; 
                    color: #666; 
                    background: #f8f9fa;
                    border-top: 1px solid #e0e0e0;
                }
                .footer p {
                    margin: 5px 0;
                }
                .important {
                    background: #fff3cd;
                    border-left: 4px solid #ffc107;
                    padding: 15px;
                    margin: 20px 0;
                    border-radius: 4px;
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>NEXON</h1>
                    <p>IT Ticketing System</p>
                </div>
                <div class='content'>
                    <h2>{$subject}</h2>
                    {$message}
                </div>
                <div class='footer'>
                    <p><strong>This is an automated email from Nexon IT Ticketing System.</strong></p>
                    <p>Please do not reply to this email.</p>
                    <p style='margin-top: 15px; color: #999;'>
                        © " . date('Y') . " Nexon IT Support. All rights reserved.
                    </p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Send ticket created notification
     */
    public function notifyTicketCreated($ticketNumber, $employeeEmail, $employeeName, $deviceType, $priority) {
        $subject = "Ticket Created - #{$ticketNumber}";
        $message = "
            <p>Dear <strong>{$employeeName}</strong>,</p>
            <p>Your support ticket has been successfully created and submitted to our IT support team.</p>
            <p><strong>Ticket Details:</strong></p>
            <ul>
                <li><strong>Ticket Number:</strong> {$ticketNumber}</li>
                <li><strong>Device Type:</strong> {$deviceType}</li>
                <li><strong>Priority:</strong> " . ucfirst($priority) . "</li>
                <li><strong>Status:</strong> Pending Assignment</li>
            </ul>
            <p>You will receive email updates as your ticket is processed by our team.</p>
            <div class='important'>
                <strong>📧 Important:</strong> Please keep this email for your reference. Your ticket number is <strong>{$ticketNumber}</strong>.
            </div>
            <p style='margin-top: 20px;'>Thank you for contacting IT Support.</p>
        ";
        
        return $this->sendEmail($employeeEmail, $subject, $message);
    }
    
    /**
     * Send ticket assigned notification
     */
    public function notifyTicketAssigned($ticketNumber, $employeeEmail, $employeeName, $providerName) {
        $subject = "Ticket Assigned - #{$ticketNumber}";
        $message = "
            <p>Dear <strong>{$employeeName}</strong>,</p>
            <p>Great news! Your support ticket has been assigned to a service provider.</p>
            <p><strong>Assignment Details:</strong></p>
            <ul>
                <li><strong>Ticket Number:</strong> {$ticketNumber}</li>
                <li><strong>Assigned To:</strong> {$providerName}</li>
                <li><strong>Status:</strong> Assigned</li>
            </ul>
            <p>The service provider will begin working on your request shortly. You will receive further updates as work progresses.</p>
            <div class='important'>
                <strong>⏱️ Expected Response:</strong> A service provider will contact you within 24 hours.
            </div>
        ";
        
        return $this->sendEmail($employeeEmail, $subject, $message);
    }
    
    /**
     * Send ticket status update notification
     */
    public function notifyTicketStatusChange($ticketNumber, $employeeEmail, $employeeName, $oldStatus, $newStatus, $comment = null) {
        $subject = "Ticket Updated - #{$ticketNumber}";
        
        $statusMessages = [
            'in_progress' => '🔄 Work has started on your ticket.',
            'resolved' => '✅ Your issue has been resolved!',
            'closed' => '📋 Your ticket has been closed.'
        ];
        
        $statusMessage = $statusMessages[$newStatus] ?? 'Your ticket status has been updated.';
        
        $message = "
            <p>Dear <strong>{$employeeName}</strong>,</p>
            <p>{$statusMessage}</p>
            <p><strong>Update Details:</strong></p>
            <ul>
                <li><strong>Ticket Number:</strong> {$ticketNumber}</li>
                <li><strong>Previous Status:</strong> " . ucfirst(str_replace('_', ' ', $oldStatus)) . "</li>
                <li><strong>New Status:</strong> " . ucfirst(str_replace('_', ' ', $newStatus)) . "</li>
            </ul>
        ";
        
        if ($comment) {
            $message .= "
            <div style='background: #f8f9fa; padding: 15px; border-left: 3px solid #667eea; margin: 15px 0;'>
                <strong>Provider Comment:</strong><br>
                " . nl2br(htmlspecialchars($comment)) . "
            </div>";
        }
        
        if ($newStatus === 'resolved') {
            $message .= "
            <div class='important'>
                <strong>⭐ Rate Our Service:</strong> Please log in to the system to rate the service provided. Your feedback helps us improve!
            </div>";
        }
        
        return $this->sendEmail($employeeEmail, $subject, $message);
    }
    
    /**
     * Send new ticket notification to provider
     */
    public function notifyProviderNewTicket($ticketNumber, $providerEmail, $providerName, $employeeName, $deviceType, $priority) {
        $priorityColors = [
            'low' => '#3b82f6',
            'medium' => '#f59e0b',
            'high' => '#ef4444',
            'critical' => '#dc2626'
        ];
        
        $priorityColor = $priorityColors[$priority] ?? '#666';
        
        $subject = "New Ticket Assigned - #{$ticketNumber}";
        $message = "
            <p>Dear <strong>{$providerName}</strong>,</p>
            <p>A new support ticket has been assigned to you and requires your attention.</p>
            <p><strong>Ticket Details:</strong></p>
            <ul>
                <li><strong>Ticket Number:</strong> {$ticketNumber}</li>
                <li><strong>Employee:</strong> {$employeeName}</li>
                <li><strong>Device Type:</strong> {$deviceType}</li>
                <li><strong>Priority:</strong> <span style='color: {$priorityColor}; font-weight: bold;'>" . strtoupper($priority) . "</span></li>
            </ul>
            <p>Please log in to the system to review the ticket details and begin working on this request.</p>
            <div class='important'>
                <strong>⚡ Action Required:</strong> Please acknowledge this ticket within 2 hours.
            </div>
        ";
        
        return $this->sendEmail($providerEmail, $subject, $message);
    }
    
    /**
     * Send comment notification
     */
    public function notifyNewComment($ticketNumber, $recipientEmail, $recipientName, $commenterName, $comment) {
        $subject = "New Comment on Ticket #{$ticketNumber}";
        $message = "
            <p>Dear <strong>{$recipientName}</strong>,</p>
            <p>A new comment has been added to your ticket.</p>
            <p><strong>Ticket Number:</strong> {$ticketNumber}</p>
            <p><strong>From:</strong> {$commenterName}</p>
            <div style='background: #f8f9fa; padding: 15px; border-left: 3px solid #667eea; margin: 15px 0;'>
                <strong>Comment:</strong><br>
                " . nl2br(htmlspecialchars($comment)) . "
            </div>
            <p>Please log in to the system to view the full conversation and respond.</p>
        ";
        
        return $this->sendEmail($recipientEmail, $subject, $message);
    }
    
    /**
     * Test email configuration
     */
    public function testEmailConfig($testEmail) {
        if (!$this->enabled) {
            return [
                'success' => false,
                'message' => '❌ Email notifications are DISABLED. Please check classes/email_config.php - Make sure "enabled" is set to true and credentials are configured.'
            ];
        }
        
        // Validate email
        if (!filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'message' => '❌ Invalid email address format. Please enter a valid email.'
            ];
        }
        
        $subject = "Nexon IT Ticketing System - Email Test ✅";
        $message = "
            <p><strong>🎉 Congratulations!</strong></p>
            <p>Your email configuration is working correctly.</p>
            <p>This is a test email sent from the Nexon IT Ticketing System at <strong>" . date('Y-m-d H:i:s') . "</strong></p>
            <p><strong>Configuration Details:</strong></p>
            <ul>
                <li><strong>SMTP Host:</strong> {$this->smtpHost}</li>
                <li><strong>SMTP Port:</strong> {$this->smtpPort}</li>
                <li><strong>From Email:</strong> {$this->fromEmail}</li>
                <li><strong>Test Sent To:</strong> {$testEmail}</li>
            </ul>
            <div class='important'>
                <strong>✅ Success!</strong> Your SMTP configuration is correct. You can now receive ticket notifications via email.
            </div>
            <p style='margin-top: 20px;'>If you received this email, the system is ready to send notifications for all ticket activities.</p>
        ";
        
        error_log("📧 Attempting test email to: $testEmail");
        $result = $this->sendEmail($testEmail, $subject, $message);
        
        if ($result) {
            return [
                'success' => true,
                'message' => '✅ Test email sent successfully! Please check your inbox at <strong>' . htmlspecialchars($testEmail) . '</strong> (also check spam/junk folder). If you don\'t see it within 2-3 minutes, check the PHP error log.'
            ];
        } else {
            return [
                'success' => false,
                'message' => '❌ Failed to send test email. Common issues:<br>
                    1. Check your Gmail App Password (must be 16 characters, no spaces)<br>
                    2. Verify 2-Step Verification is enabled on your Google Account<br>
                    3. Make sure your internet connection is working<br>
                    4. Check if port 587 is blocked by firewall<br>
                    5. Review PHP error log at: C:\xampp\php\logs\php_error_log<br>
                    <br><strong>Current Config:</strong> ' . $this->smtpUsername . ' connecting to ' . $this->smtpHost . ':' . $this->smtpPort
            ];
        }
    }
}