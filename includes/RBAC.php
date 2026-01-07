<?php
/**
 * Role-Based Access Control (RBAC) System
 */

class RBAC {
    /**
     * Check if user has permission for a specific action on a resource
     */
    public static function hasPermission($role, $resource, $action) {
        global $permissions;
        
        if (!isset($permissions[$role])) {
            return false;
        }
        
        if (!isset($permissions[$role][$resource])) {
            return false;
        }
        
        return in_array($action, $permissions[$role][$resource]);
    }
    
    /**
     * Require permission or redirect/exit
     */
    public static function requirePermission($resource, $action) {
        $role = Session::getUserRole();
        
        if (!$role || !self::hasPermission($role, $resource, $action)) {
            http_response_code(403);
            if (self::isAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Access denied. Insufficient permissions.']);
                exit;
            } else {
                Session::setFlash('error', 'Access denied. You don\'t have permission to perform this action.');
                header('Location: ' . BASE_URL . '/index.php');
                exit;
            }
        }
    }
    
    /**
     * Require specific role
     */
    public static function requireRole($requiredRole) {
        $role = Session::getUserRole();
        
        if ($role !== $requiredRole) {
            http_response_code(403);
            if (self::isAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Access denied. Insufficient role.']);
                exit;
            } else {
                Session::setFlash('error', 'Access denied.');
                header('Location: ' . BASE_URL . '/index.php');
                exit;
            }
        }
    }
    
    /**
     * Require authentication
     */
    public static function requireAuth() {
        if (!Session::isLoggedIn()) {
            if (self::isAjax()) {
                http_response_code(401);
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Authentication required.']);
                exit;
            } else {
                header('Location: ' . BASE_URL . '/login.php');
                exit;
            }
        }
    }
    
    /**
     * Check if request is AJAX
     */
    private static function isAjax() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    /**
     * Check if user is admin
     */
    public static function isAdmin() {
        return Session::getUserRole() === ROLE_ADMIN;
    }
    
    /**
     * Check if user is teacher
     */
    public static function isTeacher() {
        return Session::getUserRole() === ROLE_TEACHER;
    }
    
    /**
     * Check if user is parent
     */
    public static function isParent() {
        return Session::getUserRole() === ROLE_PARENT;
    }
    
    /**
     * Get all permissions for current user's role
     */
    public static function getUserPermissions() {
        global $permissions;
        $role = Session::getUserRole();
        return $permissions[$role] ?? [];
    }
}
