<?php
/**
 * Permission Helper
 * Provides common permission check patterns and SQL filtering
 */

class PermissionHelper {
    
    /**
     * Get SQL WHERE clause for filtering pupils based on user context
     * 
     * @param string $pupilTableAlias The alias used for the Pupil table in the query (default: 'p')
     * @return array ['where' => string, 'params' => array]
     */
    public static function getPupilAccessFilter($pupilTableAlias = 'p') {
        $accessiblePupilIDs = Auth::getAccessiblePupilIDs();
        
        // Admin has access to all pupils (no filter needed)
        if ($accessiblePupilIDs === null) {
            return ['where' => '', 'params' => []];
        }
        
        // No accessible pupils - return impossible condition
        if (empty($accessiblePupilIDs)) {
            return ['where' => " AND 1=0", 'params' => []];
        }
        
        // Filter by accessible pupil IDs
        $placeholders = implode(',', array_fill(0, count($accessiblePupilIDs), '?'));
        return [
            'where' => " AND {$pupilTableAlias}.pupilID IN ({$placeholders})",
            'params' => $accessiblePupilIDs
        ];
    }
    
    /**
     * Get SQL WHERE clause for filtering classes based on user context
     * 
     * @param string $classTableAlias The alias used for the Class table in the query (default: 'c')
     * @return array ['where' => string, 'params' => array]
     */
    public static function getClassAccessFilter($classTableAlias = 'c') {
        $accessibleClassIDs = Auth::getAccessibleClassIDs();
        
        // Admin has access to all classes (no filter needed)
        if ($accessibleClassIDs === null) {
            return ['where' => '', 'params' => []];
        }
        
        // No accessible classes - return impossible condition
        if (empty($accessibleClassIDs)) {
            return ['where' => " AND 1=0", 'params' => []];
        }
        
        // Filter by accessible class IDs
        $placeholders = implode(',', array_fill(0, count($accessibleClassIDs), '?'));
        return [
            'where' => " AND {$classTableAlias}.classID IN ({$placeholders})",
            'params' => $accessibleClassIDs
        ];
    }
    
    /**
     * Validate access to a pupil and show 403 if denied
     */
    public static function requirePupilAccess($pupilID, $message = 'You do not have permission to access this pupil.') {
        if (!Auth::canAccessPupil($pupilID)) {
            Session::setFlash('error', $message);
            header('Location: 403.php');
            exit;
        }
    }
    
    /**
     * Validate access to a class and show 403 if denied
     */
    public static function requireClassAccess($classID, $message = 'You do not have permission to access this class.') {
        if (!Auth::canAccessClass($classID)) {
            Session::setFlash('error', $message);
            header('Location: 403.php');
            exit;
        }
    }
    
    /**
     * Validate access to pupil fees and show 403 if denied
     */
    public static function requireFeeAccess($pupilID, $message = 'You do not have permission to view fees for this pupil.') {
        if (!Auth::canAccessPupilFees($pupilID)) {
            Session::setFlash('error', $message);
            header('Location: 403.php');
            exit;
        }
    }
    
    /**
     * Get context description for current user (for UI messages)
     */
    public static function getContextDescription() {
        if (Auth::isAdmin()) {
            return 'all pupils and classes';
        }
        
        if (Auth::isTeacher()) {
            $classCount = count(Auth::getAccessibleClassIDs() ?? []);
            return $classCount . ' assigned ' . ($classCount == 1 ? 'class' : 'classes');
        }
        
        if (Auth::isParent()) {
            $childCount = count(Auth::getAccessiblePupilIDs() ?? []);
            return $childCount . ' ' . ($childCount == 1 ? 'child' : 'children');
        }
        
        return 'no accessible data';
    }
    
    /**
     * Check if current user can manage (create/edit/delete) data
     * Parents and read-only roles cannot manage
     */
    public static function canManage($module) {
        // Parents can never manage
        if (Auth::isParent() && !Auth::hasAnyRole(['admin', 'teacher', 'accountant', 'librarian'])) {
            return false;
        }
        
        // Check for manage permission
        $permissionMap = [
            'pupils' => 'manage_pupils',
            'grades' => 'manage_grades',
            'attendance' => 'manage_attendance',
            'fees' => 'manage_fees',
            'reports' => 'manage_reports',
            'library' => 'manage_library',
            'classes' => 'manage_classes',
            'teachers' => 'manage_teachers',
            'parents' => 'manage_parents',
            'users' => 'manage_users',
            'payments' => 'manage_payments',
            'subjects' => 'manage_subjects',
            'announcements' => 'manage_announcements',
            'settings' => 'manage_settings'
        ];
        
        $permission = $permissionMap[$module] ?? null;
        if (!$permission) {
            return false;
        }
        
        // Check via RBAC
        require_once __DIR__ . '/../modules/roles/RolesModel.php';
        $rolesModel = new RolesModel();
        return $rolesModel->userHasPermission(Auth::id(), $permission);
    }
    
    /**
     * Check if current user can view (read) data
     */
    public static function canView($module) {
        // Check for view permission
        $permissionMap = [
            'pupils' => 'view_pupils',
            'grades' => 'view_grades',
            'attendance' => 'view_attendance',
            'fees' => 'view_fees',
            'reports' => 'view_reports',
            'library' => 'view_library',
            'classes' => 'view_classes',
            'teachers' => 'view_teachers',
            'parents' => 'view_parents',
            'users' => 'view_users',
            'payments' => 'view_payments',
            'subjects' => 'view_subjects',
            'announcements' => 'view_announcements',
            'settings' => 'manage_settings',  // settings only has manage
            'examinations' => 'view_examinations'
        ];
        
        $permission = $permissionMap[$module] ?? null;
        if (!$permission) {
            return false;
        }
        
        // Check via RBAC
        require_once __DIR__ . '/../modules/roles/RolesModel.php';
        $rolesModel = new RolesModel();
        return $rolesModel->userHasPermission(Auth::id(), $permission);
    }
}
