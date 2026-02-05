<?php
/**
 * EmailService - Handles email sending using PHPMailer
 * Requires PHPMailer library (install via composer: composer require phpmailer/phpmailer)
 * For manual installation, download from: https://github.com/PHPMailer/PHPMailer
 */

// Try to load PHPMailer if using Composer
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailService {
    private $settingsModel;
    private $settings;
    
    public function __construct() {
        require_once __DIR__ . '/../modules/settings/SettingsModel.php';
        $this->settingsModel = new SettingsModel();
        $this->settings = $this->settingsModel->getAllSettings();
    }
    
    /**
     * Check if PHPMailer is available
     */
    private function isPHPMailerAvailable() {
        return class_exists('PHPMailer\PHPMailer\PHPMailer');
    }
    
    /**
     * Check if email is configured
     */
    public function isConfigured() {
        return !empty($this->settings['smtp_host']) && 
               !empty($this->settings['smtp_username']) && 
               !empty($this->settings['smtp_from_email']);
    }
    
    /**
     * Check if account emails are enabled
     */
    public function isAccountEmailsEnabled() {
        return ($this->settings['send_account_emails'] ?? '0') == '1';
    }
    
    /**
     * Send email
     */
    public function send($to, $subject, $body, $isHTML = true) {
        // Check if PHPMailer is available
        if (!$this->isPHPMailerAvailable()) {
            error_log('PHPMailer not installed. Please install via: composer require phpmailer/phpmailer');
            return [
                'success' => false,
                'message' => 'Email service not available. PHPMailer library not installed.'
            ];
        }
        
        // Check if email is configured
        if (!$this->isConfigured()) {
            error_log('Email not configured. Please configure SMTP settings.');
            return [
                'success' => false,
                'message' => 'Email not configured. Please configure SMTP settings in Settings page.'
            ];
        }
        
        try {
            $mail = new PHPMailer(true);
            
            // Server settings
            $mail->isSMTP();
            $mail->Host = $this->settings['smtp_host'];
            $mail->SMTPAuth = true;
            $mail->Username = $this->settings['smtp_username'];
            $mail->Password = $this->settings['smtp_password'];
            
            // Encryption
            $encryption = $this->settings['smtp_encryption'] ?? 'tls';
            if ($encryption == 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($encryption == 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            }
            
            $mail->Port = $this->settings['smtp_port'] ?? 587;
            
            // Recipients
            $mail->setFrom(
                $this->settings['smtp_from_email'],
                $this->settings['smtp_from_name'] ?? 'Lilayi Park School'
            );
            
            // Support multiple recipients
            if (is_array($to)) {
                foreach ($to as $email) {
                    $mail->addAddress($email);
                }
            } else {
                $mail->addAddress($to);
            }
            
            // Content
            $mail->isHTML($isHTML);
            $mail->Subject = $subject;
            $mail->Body = $body;
            
            if ($isHTML) {
                $mail->AltBody = strip_tags($body);
            }
            
            $mail->send();
            
            return [
                'success' => true,
                'message' => 'Email sent successfully'
            ];
            
        } catch (Exception $e) {
            error_log("Email send failed: " . $e->getMessage());
            return [
                'success' => false,
                'message' => "Email send failed: " . $e->getMessage()
            ];
        } catch (\Exception $generalException) {
            error_log("Email error: " . $generalException->getMessage());
            return [
                'success' => false,
                'message' => "Email error: " . $generalException->getMessage()
            ];
        }
    }
    
    /**
     * Send test email
     */
    public function sendTestEmail($to) {
        $subject = 'Test Email from Lilayi Park School';
        $body = '
            <html>
            <body style="font-family: Arial, sans-serif;">
                <h2 style="color: #2d5016;">Email Configuration Test</h2>
                <p>This is a test email from Lilayi Park School Management System.</p>
                <p>If you received this email, your SMTP configuration is working correctly!</p>
                <hr>
                <p style="color: #666; font-size: 12px;">
                    Sent from Lilayi Park School Management System<br>
                    ' . date('Y-m-d H:i:s') . '
                </p>
            </body>
            </html>
        ';
        
        return $this->send($to, $subject, $body);
    }
    
    /**
     * Send account credentials email
     */
    public function sendAccountCredentials($email, $username, $password, $userType = 'User') {
        if (!$this->isAccountEmailsEnabled()) {
            return [
                'success' => false,
                'message' => 'Account emails are disabled in settings'
            ];
        }
        
        $schoolName = $this->settings['school_name'] ?? 'Lilayi Park School';
        $subject = "Your $schoolName Account Credentials";
        
        // Get base URL from config or construct it
        $baseUrl = defined('BASE_URL') ? BASE_URL : '';
        if (empty($baseUrl)) {
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $baseUrl = $protocol . '://' . $_SERVER['HTTP_HOST'];
        }
        
        $body = '
            <html>
            <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
                <div style="max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px;">
                    <h2 style="color: #2d5016; border-bottom: 2px solid #2d5016; padding-bottom: 10px;">
                        Welcome to ' . htmlspecialchars($schoolName) . '
                    </h2>
                    
                    <p>Hello,</p>
                    
                    <p>Your ' . htmlspecialchars($userType) . ' account has been created in the ' . htmlspecialchars($schoolName) . ' Management System.</p>
                    
                    <div style="background-color: #f4f4f4; padding: 15px; border-radius: 5px; margin: 20px 0;">
                        <h3 style="margin-top: 0; color: #2d5016;">Login Credentials</h3>
                        <p style="margin: 5px 0;"><strong>Username:</strong> ' . htmlspecialchars($username) . '</p>
                        <p style="margin: 5px 0;"><strong>Password:</strong> ' . htmlspecialchars($password) . '</p>
                        <p style="margin: 5px 0;"><strong>Login URL:</strong> <a href="' . $baseUrl . '/login.php">' . $baseUrl . '/login.php</a></p>
                    </div>
                    
                    <div style="background-color: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 20px 0;">
                        <p style="margin: 0;"><strong>⚠ Important Security Notice:</strong></p>
                        <ul style="margin: 10px 0; padding-left: 20px;">
                            <li>Please change your password after your first login</li>
                            <li>Keep your login credentials secure and confidential</li>
                            <li>Never share your password with anyone</li>
                        </ul>
                    </div>
                    
                    <p>If you have any questions or need assistance, please contact the school administration.</p>
                    
                    <hr style="border: none; border-top: 1px solid #ddd; margin: 30px 0;">
                    
                    <p style="color: #666; font-size: 12px; margin: 0;">
                        This is an automated message from ' . htmlspecialchars($schoolName) . ' Management System.<br>
                        Please do not reply to this email.<br>
                        Sent on: ' . date('l, F j, Y \\a\\t g:i A') . '
                    </p>
                </div>
            </body>
            </html>
        ';
        
        return $this->send($email, $subject, $body);
    }
    
    /**
     * Send password reset email
     */
    public function sendPasswordReset($email, $username, $newPassword) {
        $schoolName = $this->settings['school_name'] ?? 'Lilayi Park School';
        $subject = "Password Reset - $schoolName";
        
        // Get base URL from config or construct it
        $baseUrl = defined('BASE_URL') ? BASE_URL : '';
        if (empty($baseUrl)) {
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $baseUrl = $protocol . '://' . $_SERVER['HTTP_HOST'];
        }
        
        $body = '
            <html>
            <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
                <div style="max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px;">
                    <h2 style="color: #2d5016; border-bottom: 2px solid #2d5016; padding-bottom: 10px;">
                        Password Reset
                    </h2>
                    
                    <p>Hello,</p>
                    
                    <p>Your password has been reset for your account in the ' . htmlspecialchars($schoolName) . ' Management System.</p>
                    
                    <div style="background-color: #f4f4f4; padding: 15px; border-radius: 5px; margin: 20px 0;">
                        <h3 style="margin-top: 0; color: #2d5016;">New Login Credentials</h3>
                        <p style="margin: 5px 0;"><strong>Username:</strong> ' . htmlspecialchars($username) . '</p>
                        <p style="margin: 5px 0;"><strong>New Password:</strong> ' . htmlspecialchars($newPassword) . '</p>
                        <p style="margin: 5px 0;"><strong>Login URL:</strong> <a href="' . $baseUrl . '/login.php">' . $baseUrl . '/login.php</a></p>
                    </div>
                    
                    <div style="background-color: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 20px 0;">
                        <p style="margin: 0;"><strong>⚠ Security Reminder:</strong></p>
                        <p style="margin: 10px 0;">Please change this password immediately after logging in.</p>
                    </div>
                    
                    <p>If you did not request this password reset, please contact the school administration immediately.</p>
                    
                    <hr style="border: none; border-top: 1px solid #ddd; margin: 30px 0;">
                    
                    <p style="color: #666; font-size: 12px; margin: 0;">
                        This is an automated message from ' . htmlspecialchars($schoolName) . ' Management System.<br>
                        Sent on: ' . date('l, F j, Y \\a\\t g:i A') . '
                    </p>
                </div>
            </body>
            </html>
        ';
        
        return $this->send($email, $subject, $body);
    }
    
    /**
     * Send notification email
     */
    public function sendNotification($to, $subject, $message, $additionalInfo = []) {
        $schoolName = $this->settings['school_name'] ?? 'Lilayi Park School';
        
        $additionalHTML = '';
        if (!empty($additionalInfo)) {
            $additionalHTML = '<div style="background-color: #f4f4f4; padding: 15px; border-radius: 5px; margin: 20px 0;">';
            foreach ($additionalInfo as $key => $value) {
                $additionalHTML .= '<p style="margin: 5px 0;"><strong>' . htmlspecialchars($key) . ':</strong> ' . htmlspecialchars($value) . '</p>';
            }
            $additionalHTML .= '</div>';
        }
        
        $body = '
            <html>
            <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
                <div style="max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px;">
                    <h2 style="color: #2d5016; border-bottom: 2px solid #2d5016; padding-bottom: 10px;">
                        ' . htmlspecialchars($subject) . '
                    </h2>
                    
                    <div style="margin: 20px 0;">
                        ' . nl2br(htmlspecialchars($message)) . '
                    </div>
                    
                    ' . $additionalHTML . '
                    
                    <hr style="border: none; border-top: 1px solid #ddd; margin: 30px 0;">
                    
                    <p style="color: #666; font-size: 12px; margin: 0;">
                        This is an automated notification from ' . htmlspecialchars($schoolName) . ' Management System.<br>
                        Sent on: ' . date('l, F j, Y \\a\\t g:i A') . '
                    </p>
                </div>
            </body>
            </html>
        ';
        
        return $this->send($to, $subject, $body);
    }
}
