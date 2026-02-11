<?php
/**
 * Mail Configuration File
 * Configure your SMTP settings for sending emails
 * 
 * For Gmail: 
 * 1. Enable 2-Step Verification on your Google account
 * 2. Go to https://myaccount.google.com/apppasswords
 * 3. Generate an App Password for "Mail"
 * 4. Use that 16-character password below
 */

define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);
define('MAIL_USERNAME', 'sanjaygawade0203@gmail.com');      // Your Gmail address
define('MAIL_PASSWORD', 'ktptciddygfdahsd');          // Your Gmail App Password (16 chars)
define('MAIL_FROM_EMAIL', 'sanjaygawade0203@gmail.com');     // Same as MAIL_USERNAME
define('MAIL_FROM_NAME', 'Career Guide');
?>
