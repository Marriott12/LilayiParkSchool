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
            error_log('Auth::requireLogin - User not logged in. Session user_id: ' . ($_SESSION['user_id'] ?? 'NOT SET'));
            error_log('Redirecting to login from: ' . $_SERVER['REQUEST_URI']);
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            Session::setFlash('warning', 'Please log in to access this page.');
            header('Location: ' . BASE_URL . '/login.php');
            exit;
        }
        error_log('Auth::requireLogin - User is logged in: ' . $_SESSION['user_id']);
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
            header('Location: ' . BASE_URL . '/403.php');
            exit;
        }
    }
    
    /**
     * Require user to have any of the given roles
     */
    public static function requireAnyRole($roles) {
        self::requireLogin();
        if (!self::hasAnyRole($roles)) {
            header('Location: ' . BASE_URL . '/403.php');
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
            error_log("Login attempt for username: " . $username);
            
            // Find user by username or email
            $sql = "SELECT * FROM Users WHERE (username = ? OR email = ?) AND isActive = 'Y'";
            $stmt = $db->prepare($sql);
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();
            
            if (!$user) {
                error_log("Login failed: User not found - " . $username);
                return 'Invalid username or password.';
            }
            
            error_log("User found: " . $user['username'] . " (ID: " . $user['userID'] . ")");
            
            // Verify password
            if (!password_verify($password, $user['password'])) {
                error_log("Login failed: Invalid password for user - " . $username);
                return 'Invalid username or password.';
            }
            
            error_log("Password verified successfully for user: " . $username);
            
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
            $_SESSION['user_name'] = $user['firstName'] . ' ' . $user['lastName'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_roles'] = $roles;
            $_SESSION['user_role'] = !empty($roles) ? $roles[0] : 'user';
            $_SESSION['teacher_id'] = $teacherID;
            $_SESSION['parent_id'] = $parentID;
            $_SESSION['login_time'] = time();
            
            error_log("Login successful for user: " . $username . " with roles: " . implode(', ', $roles));
            error_log("Session data set. Session ID: " . session_id());
            error_log("Session user_id: " . $_SESSION['user_id']);
            
            // Force session write immediately
            session_write_close();
            session_start(); // Restart session for continued use
            
            error_log("Session written and restarted. Checking persistence...");
            error_log("Session user_id after restart: " . ($_SESSION['user_id'] ?? 'NOT SET!'));
            
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
        Session::setFlash('success', 'You have been logged out successfully.');
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
    
    /**
     * Context-based permission checking for teachers
     * Check if teacher can access a specific class
     */
    public static function canAccessClass($classID) {
        // Admin can access all classes
        if (self::isAdmin()) {
            return true;
        }
        
        // Teachers can only access their assigned classes
        if (self::isTeacher()) {
            $teacherID = self::getTeacherID();
            if (!$teacherID) {
                return false;
            }
            
            require_once __DIR__ . '/../modules/teachers/TeacherModel.php';
            $teacherModel = new TeacherModel();
            return $teacherModel->isAssignedToClass($teacherID, $classID);
        }
        
        return false;
    }
    
    /**
     * Context-based permission checking for teachers
     * Check if teacher can access a specific pupil
     */
    public static function canAccessPupil($pupilID) {
        $accessibleIDs = self::getAccessiblePupilIDs();
        
        // null means all pupils accessible (admin)
        if ($accessibleIDs === null) {
            return true;
        }
        
        return in_array($pupilID, $accessibleIDs);
    }
    
    /**
     * Check if user can access a specific teacher
     */
    public static function canAccessTeacher($teacherID) {
        $accessibleIDs = self::getAccessibleTeacherIDs();
        
        // null means all teachers accessible
        if ($accessibleIDs === null) {
            return true;
        }
        
        return in_array($teacherID, $accessibleIDs);
    }
    
    /**
     * Check if user can access a specific parent
     */
    public static function canAccessParent($parentID) {
        $accessibleIDs = self::getAccessibleParentIDs();
        
        // null means all parents accessible
        if ($accessibleIDs === null) {
            return true;
        }
        
        return in_array($parentID, $accessibleIDs);
    }
    
    /**
     * Get list of pupil IDs accessible to current user
     * Used for filtering queries based on user context
     */
    public static function getAccessiblePupilIDs() {
        // Admin can access all pupils
        if (self::isAdmin()) {
            return null; // null means all pupils
        }
        
        // Teachers can access pupils in their classes
        if (self::isTeacher()) {
            $teacherID = self::getTeacherID();
            if (!$teacherID) {
                return [];
            }
            
            require_once __DIR__ . '/../modules/teachers/TeacherModel.php';
            $teacherModel = new TeacherModel();
            return $teacherModel->getAccessiblePupilIDs($teacherID);
        }
        
        // Parents can access their own children only
        if (self::isParent()) {
            $parentID = self::getParentID();
            if (!$parentID) {
                return [];
            }
            
            require_once __DIR__ . '/../modules/parents/ParentModel.php';
            $parentModel = new ParentModel();
            return $parentModel->getMyChildrenIDs($parentID);
        }
        
        return [];
    }
    
    /**
     * Get list of teacher IDs accessible to current user
     */
    public static function getAccessibleTeacherIDs() {
        // Admin can access all teachers
        if (self::isAdmin()) {
            return null; // null means all teachers
        }
        
        // Teachers can view all teachers
        if (self::isTeacher()) {
            return null; // null means all teachers
        }
        
        // Parents cannot view teacher profiles directly
        if (self::isParent()) {
            return [];
        }
        
        return [];
    }
    
    /**
     * Get list of parent IDs accessible to current user
     */
    public static function getAccessibleParentIDs() {
        // Admin can access all parents
        if (self::isAdmin()) {
            return null; // null means all parents
        }
        
        // Teachers can view all parents
        if (self::isTeacher()) {
            return null; // null means all parents
        }
        
        // Parents can only view themselves
        if (self::isParent()) {
            $parentID = self::getParentID();
            if (!$parentID) {
                return [];
            }
            return [$parentID];
        }
        
        return [];
    }
    
    /**
     * Get list of class IDs accessible to current user
     */
    public static function getAccessibleClassIDs() {
        // Admin can access all classes
        if (self::isAdmin()) {
            return null; // null means all classes
        }
        
        // Teachers can access their assigned classes
        if (self::isTeacher()) {
            $teacherID = self::getTeacherID();
            if (!$teacherID) {
                return [];
            }
            
            require_once __DIR__ . '/../modules/teachers/TeacherModel.php';
            $teacherModel = new TeacherModel();
            return $teacherModel->getAssignedClassIDs($teacherID);
        }
        
        return [];
    }
    
    /**
     * Check if user has permission to view/manage fees for a pupil
     */
    public static function canAccessPupilFees($pupilID) {
        // Admin and accountant can access all fees
        if (self::isAdmin() || self::hasRole('accountant')) {
            return true;
        }
        
        // Parents can view fees for their children only
        if (self::isParent()) {
            return self::canAccessPupil($pupilID);
        }
        
        return false;
    }
}
