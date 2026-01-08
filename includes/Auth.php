<?php
/**
 * Authentication and Authorization Helper Class
 * Handles user login, logout, session management, and role-based access control
 */

class Auth {
    
    /**
     * Check if user is logged in
     */
    public static function check() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    /**
     * Require user to be logged in, redirect to login if not
     */
    public static function requireLogin() {
        if (!self::check()) {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            Session::setFlashMessage('Please log in to access this page.', 'warning');
            header('Location: /modules/auth/login.php');
            exit;
        }
    }
    
    /**
     * Get current user ID
     */
    public static function id() {
        return $_SESSION['user_id'] ?? null;
    }
    
    /**
     * Get current username
     */
    public static function username() {
        return $_SESSION['username'] ?? null;
    }
    
    /**
     * Get current user email
     */
    public static function email() {
        return $_SESSION['user_email'] ?? null;
    }
    
    /**
     * Get all roles for current user
     */
    public static function roles() {
        return $_SESSION['user_roles'] ?? [];
    }
    
    /**
     * Check if user has a specific role
     */
    public static function hasRole($role) {
        $roles = self::roles();
        return in_array($role, $roles);
    }
    
    /**
     * Check if user has any of the given roles
     */
    public static function hasAnyRole($roles) {
        if (!is_array($roles)) {
            $roles = [$roles];
        }
        $userRoles = self::roles();
        return !empty(array_intersect($roles, $userRoles));
    }
    
    /**
     * Check if user has all of the given roles
     */
    public static function hasAllRoles($roles) {
        if (!is_array($roles)) {
            $roles = [$roles];
        }
        $userRoles = self::roles();
        foreach ($roles as $role) {
            if (!in_array($role, $userRoles)) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * Require user to have a specific role, redirect to 403 if not
     */
    public static function requireRole($role) {
        self::requireLogin();
        if (!self::hasRole($role)) {
            header('Location: /403.php');
            exit;
        }
    }
    
    /**
     * Require user to have any of the given roles
     */
    public static function requireAnyRole($roles) {
        self::requireLogin();
        if (!self::hasAnyRole($roles)) {
            header('Location: /403.php');
            exit;
        }
    }
    
    /**
     * Check if user is admin
     */
    public static function isAdmin() {
        return self::hasRole('admin');
    }
    
    /**
     * Check if user is teacher
     */
    public static function isTeacher() {
        return self::hasRole('teacher');
    }
    
    /**
     * Check if user is parent
     */
    public static function isParent() {
        return self::hasRole('parent');
    }
    
    /**
     * Get teacher ID if user has teacher role
     */
    public static function getTeacherID() {
        return $_SESSION['teacher_id'] ?? null;
    }
    
    /**
     * Get parent ID if user has parent role
     */
    public static function getParentID() {
        return $_SESSION['parent_id'] ?? null;
    }
    
    /**
     * Attempt to log in a user
     * 
     * @param string $username
     * @param string $password
     * @return bool|string Returns true on success, error message on failure
     */
    public static function attempt($username, $password) {
        global $db;
        
        try {
            // Find user by username or email
            $sql = "SELECT * FROM Users WHERE (username = ? OR email = ?) AND isActive = 1";
            $stmt = $db->prepare($sql);
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();
            
            if (!$user) {
                return 'Invalid username or password.';
            }
            
            // Verify password
            if (!password_verify($password, $user['password'])) {
                return 'Invalid username or password.';
            }
            
            // Load user roles
            $sql = "SELECT r.roleName 
                    FROM UserRoles ur
                    JOIN Roles r ON ur.roleID = r.roleID
                    WHERE ur.userID = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$user['userID']]);
            $roles = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Load teacher ID if user is a teacher
            $teacherID = null;
            if (in_array('teacher', $roles)) {
                $sql = "SELECT teacherID FROM Teacher WHERE userID = ?";
                $stmt = $db->prepare($sql);
                $stmt->execute([$user['userID']]);
                $teacherID = $stmt->fetchColumn();
            }
            
            // Load parent ID if user is a parent
            $parentID = null;
            if (in_array('parent', $roles)) {
                $sql = "SELECT parentID FROM Parent WHERE userID = ?";
                $stmt = $db->prepare($sql);
                $stmt->execute([$user['userID']]);
                $parentID = $stmt->fetchColumn();
            }
            
            // Set session variables
            $_SESSION['user_id'] = $user['userID'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_roles'] = $roles;
            $_SESSION['teacher_id'] = $teacherID;
            $_SESSION['parent_id'] = $parentID;
            $_SESSION['login_time'] = time();
            
            // Update last login
            $sql = "UPDATE Users SET lastLogin = NOW() WHERE userID = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$user['userID']]);
            
            return true;
            
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            return 'An error occurred during login. Please try again.';
        }
    }
    
    /**
     * Log out the current user
     */
    public static function logout() {
        // Clear all session data
        $_SESSION = array();
        
        // Destroy the session cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 42000, '/');
        }
        
        // Destroy the session
        session_destroy();
        
        // Start a new session for flash messages
        session_start();
        Session::setFlashMessage('You have been logged out successfully.', 'success');
    }
    
    /**
     * Generate a random password
     */
    public static function generatePassword($length = 10) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%';
        $password = '';
        $max = strlen($chars) - 1;
        
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, $max)];
        }
        
        return $password;
    }
    
    /**
     * Generate a username from name
     */
    public static function generateUsername($firstName, $lastName) {
        global $db;
        
        // Create base username: first initial + last name
        $base = strtolower(substr($firstName, 0, 1) . $lastName);
        $base = preg_replace('/[^a-z0-9]/', '', $base); // Remove special chars
        
        $username = $base;
        $counter = 1;
        
        // Check if username exists, append number if needed
        while (self::usernameExists($username)) {
            $username = $base . $counter;
            $counter++;
        }
        
        return $username;
    }
    
    /**
     * Check if username already exists
     */
    private static function usernameExists($username) {
        global $db;
        
        $sql = "SELECT COUNT(*) FROM Users WHERE username = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$username]);
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Hash a password
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }
    
    /**
     * Check if current session is still valid (not expired)
     */
    public static function isSessionValid($maxLifetime = 7200) { // 2 hours default
        if (!self::check()) {
            return false;
        }
        
        $loginTime = $_SESSION['login_time'] ?? 0;
        $currentTime = time();
        
        // Check if session has expired
        if (($currentTime - $loginTime) > $maxLifetime) {
            self::logout();
            return false;
        }
        
        // Update login time to extend session
        $_SESSION['login_time'] = $currentTime;
        return true;
    }
}
