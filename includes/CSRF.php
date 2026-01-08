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
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Require valid CSRF token or die
     */
    public static function requireToken() {
        if (!self::validate()) {
            http_response_code(403);
            die('CSRF token validation failed. Please refresh and try again.');
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
