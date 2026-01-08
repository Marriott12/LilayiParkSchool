<?php
/**
 * CSRF Protection Class
 * Generates and validates CSRF tokens for form submissions
 */

class CSRF {
    /**
     * Generate a CSRF token
     */
    public static function generateToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Get the current CSRF token
     */
    public static function getToken() {
        return isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : self::generateToken();
    }
    
    /**
     * Generate hidden input field with CSRF token
     */
    public static function field() {
        $token = self::getToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
    }
    
    /**
     * Validate CSRF token
     */
    public static function validate($token = null) {
        if ($token === null) {
            $token = $_POST['csrf_token'] ?? '';
        }
        
        if (!isset($_SESSION['csrf_token'])) {
            error_log('CSRF Validation Failed: No session token');
            return false;
        }
        
        if (empty($token)) {
            error_log('CSRF Validation Failed: No POST token');
            return false;
        }
        
        $isValid = hash_equals($_SESSION['csrf_token'], $token);
        if (!$isValid) {
            error_log('CSRF Validation Failed: Token mismatch. Session: ' . substr($_SESSION['csrf_token'], 0, 10) . '... POST: ' . substr($token, 0, 10) . '...');
        }
        
        return $isValid;
    }
    
    /**
     * Require valid CSRF token or die
     */
    public static function requireToken() {
        if (!self::validate()) {
            http_response_code(403);
            $debug = '';
            if (!isset($_SESSION['csrf_token'])) {
                $debug = 'Session token not found. ';
            } elseif (empty($_POST['csrf_token'])) {
                $debug = 'POST token not found. ';
            } else {
                $debug = 'Token mismatch. ';
            }
            die('CSRF token validation failed. ' . $debug . 'Please refresh the page and try again.');
        }
    }
    
    /**
     * Regenerate CSRF token (call after successful form submission)
     */
    public static function regenerateToken() {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        return $_SESSION['csrf_token'];
    }
}
