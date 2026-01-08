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
     * Require valid CSRF token or set error
     * Returns false if token is invalid (so form can be re-displayed with error)
     */
    public static function requireToken() {
        if (!self::validate()) {
            $debug = '';
            if (!isset($_SESSION['csrf_token'])) {
                $debug = 'Your session may have expired.';
            } elseif (empty($_POST['csrf_token'])) {
                $debug = 'The form was not submitted properly.';
            } else {
                $debug = 'Token mismatch.';
            }
            
            // Regenerate token immediately so the form has a fresh one
            self::regenerateToken();
            
            // Set error in global scope for the form to display
            $GLOBALS['csrf_error'] = 'CSRF token validation failed. ' . $debug . ' Please try again.';
            
            return false;
        }
        return true;
    }
    
    /**
     * Regenerate CSRF token (call after successful form submission)
     */
    public static function regenerateToken() {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        return $_SESSION['csrf_token'];
    }
}
