<?php
/**
 * Session Management and Authentication Helper
 */

class Session {
    private static $settingsCache = null;
    
    public static function start() {
        if (session_status() === PHP_SESSION_NONE) {
            // Try to start session and log if it fails
            $started = @session_start();
            
            if (!$started) {
                error_log('CRITICAL: Session failed to start!');
                error_log('Session save path: ' . session_save_path());
                error_log('Session name: ' . session_name());
                return false;
            }
            
            // CRITICAL: Flush output buffer to force session cookie to be sent
            // Use try-catch to prevent 500 errors if flushing fails
            // Limit iterations to prevent infinite loops
            try {
                $maxFlushAttempts = 10;
                $attempts = 0;
                while (ob_get_level() > 0 && $attempts < $maxFlushAttempts) {
                    @ob_end_flush();
                    $attempts++;
                }
                @flush();
            } catch (Exception $e) {
                // Silently continue if flush fails
                error_log('Buffer flush warning: ' . $e->getMessage());
            }
            
            error_log('Session started. ID: ' . session_id() . ', Save path: ' . session_save_path());
            
            // Regenerate session ID periodically for security
            if (!isset($_SESSION['created'])) {
                $_SESSION['created'] = time();
                error_log('New session created at: ' . date('Y-m-d H:i:s'));
            } else if (time() - $_SESSION['created'] > 1800) {
                // Preserve CSRF token during regeneration
                $csrfToken = $_SESSION['csrf_token'] ?? null;
                
                // Regenerate session every 30 minutes
                session_regenerate_id(true);
                $_SESSION['created'] = time();
                
                // Restore CSRF token
                if ($csrfToken !== null) {
                    $_SESSION['csrf_token'] = $csrfToken;
                }
                
                error_log('Session regenerated at: ' . date('Y-m-d H:i:s'));
            }
            
            // Validate IP address (disabled for production proxy compatibility)
            // Strict IP validation can fail with load balancers, proxies, and CDNs
            // If needed, enable this only in development environments
            if (false && isset($_SESSION['ip_address']) && $_SESSION['ip_address'] !== $_SERVER['REMOTE_ADDR']) {
                error_log('Session IP mismatch: ' . $_SESSION['ip_address'] . ' vs ' . $_SERVER['REMOTE_ADDR']);
                self::destroy();
                return false;
            }
            $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
        }
        
        // Check session timeout
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
            error_log('Session timeout detected. Last activity: ' . date('Y-m-d H:i:s', $_SESSION['last_activity']));
            self::destroy();
            return false;
        }
        
        $_SESSION['last_activity'] = time();
        
        // Log session data for debugging (only if user is logged in)
        if (isset($_SESSION['user_id'])) {
            error_log('Active session for user_id: ' . $_SESSION['user_id'] . ', username: ' . ($_SESSION['username'] ?? 'unknown'));
        }
        
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
