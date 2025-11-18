<?php
/**
 * Email Configuration - BREVO (Real Email Delivery)
 * 
 * SETUP INSTRUCTIONS:
 * 1. Go to https://www.brevo.com/
 * 2. Sign up for FREE account (300 emails/day limit)
 * 3. Verify your email address
 * 4. Go to Settings -> SMTP & API
 * 5. Generate an SMTP key
 * 6. Copy the credentials below
 * 
 * ADVANTAGES:
 * - Sends to REAL email addresses
 * - 300 free emails per day
 * - No credit card required
 * - Professional delivery
 */

return [
    // SMTP Configuration
    'smtp_host' => 'smtp-relay.brevo.com',
    'smtp_port' => 587,
    
    // YOUR BREVO CREDENTIALS (Get from brevo.com dashboard)
    'smtp_username' => '9bd933001@smtp-brevo.com', // ⚠️ Your Brevo account email
    'smtp_password' => 'xsmtpsib-a54f84d7d183eb901ad80c12fae32aa3bec71062d87417037c16d64b872dfa54-DULV3W7PxHmUczan',     // ⚠️ SMTP key (NOT your password!)
    
    // Sender Information (MUST be verified in Brevo)
    'from_email' => 'garcingarween@gmail.com', // ⚠️ Use email verified in Brevo
    'from_name' => 'Nexon Ticketing System',
    
    // Enable/Disable emails
    'enabled' => true,
    
    // Debug mode
    'debug' => false // Set to true if having issues
];

/**
 * DETAILED SETUP GUIDE FOR BREVO:
 * 
 * 1. CREATE ACCOUNT:
 *    - Go to: https://app.brevo.com/account/register
 *    - Enter your email and create password
 *    - Verify your email (check inbox)
 * 
 * 2. GET SMTP CREDENTIALS:
 *    - Login to Brevo dashboard
 *    - Click your name (top right) -> "SMTP & API"
 *    - Click "Generate a new SMTP key"
 *    - Give it a name (e.g., "Nexon Ticketing")
 *    - Copy the generated key
 *    - Your username is your Brevo login email
 * 
 * 3. VERIFY SENDER EMAIL:
 *    - Go to "Senders & IP" in left menu
 *    - Click "Add a sender"
 *    - Enter an email you own (can be Gmail!)
 *    - Verify it by clicking the link they send you
 *    - Use this email as 'from_email' above
 * 
 * 4. UPDATE CONFIG:
 *    - Replace 'YOUR_BREVO_LOGIN_EMAIL' with your Brevo email
 *    - Replace 'YOUR_BREVO_SMTP_KEY' with the key you generated
 *    - Replace 'from_email' with your verified sender email
 * 
 * 5. TEST:
 *    - Save this file
 *    - Go to: public/admin/test_email.php
 *    - Enter a REAL email address (your Gmail)
 *    - Click "Send Test Email"
 *    - Check your Gmail inbox (and spam folder)
 * 
 * LIMITS:
 * - Free plan: 300 emails per day
 * - Perfect for college projects
 * - Scales to paid plans if needed
 * 
 * IMPORTANT NOTES:
 * - Emails will go to REAL Gmail accounts
 * - Recipients don't need Brevo accounts
 * - No tampering with recipient Gmail accounts
 * - Only YOU need to setup Brevo (once)
 */