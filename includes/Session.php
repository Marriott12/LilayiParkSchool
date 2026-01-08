<?php
/**
 * Session Management and Authentication Helper
 */

class Session {
    private static $settingsCache = null;
    
    public static function start() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
            
            // Regenerate session ID periodically for security
            if (!isset($_SESSION['created'])) {
                $_SESSION['created'] = time();
            } else if (time() - $_SESSION['created'] > 1800) {
                // Regenerate session every 30 minutes
                session_regenerate_id(true);
                $_SESSION['created'] = time();
            }
            
            // Validate IP address
            if (isset($_SESSION['ip_address']) && $_SESSION['ip_address'] !== $_SERVER['REMOTE_ADDR']) {
                self::destroy();
                return false;
            }
            $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
        }
        
        // Check session timeout
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
            self::destroy();
            return false;
        }
        
        $_SESSION['last_activity'] = time();
        return true;
    }
    
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']) && isset($_SESSION['user_role']);
    }
    
    public static function getUserId() {
        return $_SESSION['user_id'] ?? null;
    }
    
    public static function getUserRole() {
        return $_SESSION['user_role'] ?? null;
    }
    
    public static function getUserData() {
        return [
            'id' => $_SESSION['user_id'] ?? null,
            'name' => $_SESSION['user_name'] ?? null,
            'email' => $_SESSION['user_email'] ?? null,
            'role' => $_SESSION['user_role'] ?? null
        ];
    }
    
    public static function set($key, $value) {
        $_SESSION[$key] = $value;
    }
    
    public static function get($key, $default = null) {
        return $_SESSION[$key] ?? $default;
    }
    
    public static function has($key) {
        return isset($_SESSION[$key]);
    }
    
    public static function remove($key) {
        unset($_SESSION[$key]);
    }
    
    public static function destroy() {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
    }
    
    public static function setFlash($type, $message) {
        $_SESSION['flash'][$type] = $message;
    }
    
    public static function getFlash($type) {
        if (isset($_SESSION['flash'][$type])) {
            $message = $_SESSION['flash'][$type];
            unset($_SESSION['flash'][$type]);
            return $message;
        }
        return null;
    }
    
    /**
     * Settings Cache - reduce database queries
     */
    public static function cacheSettings($settings) {
        $_SESSION['settings_cache'] = $settings;
        $_SESSION['settings_cache_time'] = time();
    }
    
    public static function getCachedSettings() {
        // Cache for 5 minutes
        if (isset($_SESSION['settings_cache']) && 
            isset($_SESSION['settings_cache_time']) && 
            (time() - $_SESSION['settings_cache_time'] < 300)) {
            return $_SESSION['settings_cache'];
        }
        return null;
    }
    
    public static function clearSettingsCache() {
        unset($_SESSION['settings_cache']);
        unset($_SESSION['settings_cache_time']);
    }
    
    /**
     * Get time remaining before session timeout (in seconds)
     */
    public static function getTimeRemaining() {
        if (isset($_SESSION['last_activity'])) {
            return SESSION_TIMEOUT - (time() - $_SESSION['last_activity']);
        }
        return SESSION_TIMEOUT;
    }
}
