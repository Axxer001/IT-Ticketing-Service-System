<?php
require_once __DIR__ . "/../vendor/autoload.php";
require_once "Database.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Email Notification Handler using PHPMailer
 * Sends email notifications to Gmail addresses
 */
class EmailNotification {
    private $db;
    private $mailer;
    private $enabled;
    
    // Configuration from email_config.php
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
        
        // Only setup mailer if email is enabled
        if ($this->enabled) {
            $this->setupMailer();
        }
    }
    
    /**
     * Load email configuration
     */
    private function loadConfig() {
        // Check if config file exists
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
        } else {
            // Default fallback (emails disabled if no config)
            error_log("Email config file not found. Email notifications disabled.");
            $this->enabled = false;
        }
        
        // Disable if credentials are not set
        if (empty($this->smtpUsername) || empty($this->smtpPassword)) {
            error_log("SMTP credentials not configured. Email notifications disabled.");
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
            
            // Sender info
            $this->mailer->setFrom($this->fromEmail, $this->fromName);
            
            // Optional: Reduce timeout for faster failure
            $this->mailer->Timeout = 10;
            
        } catch (Exception $e) {
            error_log("PHPMailer setup error: " . $e->getMessage());
            $this->enabled = false;
        }
    }
    
    /**
     * Send email notification
     * @param string $toEmail - Recipient email
     * @param string $subject - Email subject
     * @param string $message - Email body (HTML supported)
     * @return bool - Success status
     */
    public function sendEmail($toEmail, $subject, $message) {
        // Skip if emails are disabled
        if (!$this->enabled) {
            error_log("Email notifications disabled. Skipping email to: " . $toEmail);
            return true; // Return true to not break functionality
        }
        
        try {
            // Reset recipients for each email
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($toEmail);
            
            // Content
            $this->mailer->isHTML(true);
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $this->getEmailTemplate($subject, $message);
            $this->mailer->AltBody = strip_tags($message); // Plain text version
            
            $this->mailer->send();
            error_log("✅ Email sent successfully to: " . $toEmail);
            return true;
            
        } catch (Exception $e) {
            error_log("❌ Email send error: " . $this->mailer->ErrorInfo);
            // Don't throw exception - just log and continue
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
                }
                .container { 
                    max-width: 600px; 
                    margin: 0 auto; 
                    padding: 20px; 
                }
                .header { 
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
                    color: white; 
                    padding: 30px 20px; 
                    text-align: center; 
                    border-radius: 10px 10px 0 0; 
                }
                .header h1 {
                    margin: 0;
                    font-size: 32px;
                    letter-spacing: 2px;
                }
                .header p {
                    margin: 5px 0 0 0;
                    font-size: 14px;
                    opacity: 0.9;
                }
                .content { 
                    background: #f8f9fa; 
                    padding: 30px; 
                    border-radius: 0 0 10px 10px;
                    border: 1px solid #e0e0e0;
                }
                .content h2 {
                    color: #667eea;
                    margin-top: 0;
                    font-size: 22px;
                }
                .content ul {
                    background: white;
                    padding: 20px 20px 20px 40px;
                    border-left: 4px solid #667eea;
                    margin: 15px 0;
                }
                .content ul li {
                    margin: 10px 0;
                }
                .footer { 
                    text-align: center; 
                    margin-top: 30px; 
                    padding-top: 20px;
                    font-size: 12px; 
                    color: #666; 
                    border-top: 1px solid #e0e0e0;
                }
                .footer p {
                    margin: 5px 0;
                }
                .button { 
                    display: inline-block; 
                    padding: 12px 24px; 
                    background: #667eea; 
                    color: white !important; 
                    text-decoration: none; 
                    border-radius: 5px; 
                    margin-top: 15px;
                    font-weight: 600;
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
                    <div style='margin: 20px 0; color: #555;'>
                        {$message}
                    </div>
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
            <div style='background: white; padding: 15px; border-left: 3px solid #667eea; margin: 15px 0;'>
                <strong>Provider Comment:</strong><br>
                " . nl2br(htmlspecialchars($comment)) . "
            </div>";
        }
        
        if ($newStatus === 'resolved') {
            $message .= "<p style='margin-top: 20px;'><strong>Please log in to the system to rate the service provided.</strong></p>";
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
            <div style='background: white; padding: 15px; border-left: 3px solid #667eea; margin: 15px 0;'>
                <strong>Comment:</strong><br>
                " . nl2br(htmlspecialchars($comment)) . "
            </div>
            <p>Please log in to the system to view the full conversation and respond.</p>
        ";
        
        return $this->sendEmail($recipientEmail, $subject, $message);
    }
}