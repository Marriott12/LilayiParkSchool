<?php
/**
 * Roles Model
 * Handles role and permission management
 */

require_once __DIR__ . '/../../includes/BaseModel.php';

class RolesModel extends BaseModel {
    
    protected $table = 'Roles';
    protected $primaryKey = 'roleID';
    
    /**
     * Get all roles
     */
    public function getAllRoles() {
        $sql = "SELECT * FROM {$this->table} ORDER BY roleName";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
    
    /**
     * Get role by name
     */
    public function getRoleByName($roleName) {
        $sql = "SELECT * FROM {$this->table} WHERE roleName = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$roleName]);
        return $stmt->fetch();
    }
    
    /**
     * Get all roles for a user
     */
    public function getUserRoles($userID) {
        $sql = "SELECT r.*, ur.assignedAt, ur.assignedBy
                FROM {$this->table} r
                JOIN UserRoles ur ON r.roleID = ur.roleID
                WHERE ur.userID = ?
                ORDER BY r.roleName";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userID]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get all users with a specific role
     */
    public function getUsersWithRole($roleID) {
        $sql = "SELECT u.*, ur.assignedAt
                FROM Users u
                JOIN UserRoles ur ON u.userID = ur.userID
                WHERE ur.roleID = ?
                ORDER BY u.username";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$roleID]);
        return $stmt->fetchAll();
    }
    
    /**
     * Assign a role to a user
     */
    public function assignRole($userID, $roleID, $assignedBy = null) {
        try {
            $sql = "INSERT INTO UserRoles (userID, roleID, assignedBy) 
                    VALUES (?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$userID, $roleID, $assignedBy]);
        } catch (PDOException $e) {
            // Role already assigned (unique constraint violation)
            if ($e->getCode() == 23000) {
                return true; // Already has role, consider it success
            }
            throw $e;
        }
    }
    
    /**
     * Remove a role from a user
     */
    public function removeRole($userID, $roleID) {
        $sql = "DELETE FROM UserRoles WHERE userID = ? AND roleID = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$userID, $roleID]);
    }
    
    /**
     * Remove all roles from a user
     */
    public function removeAllRoles($userID) {
        $sql = "DELETE FROM UserRoles WHERE userID = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$userID]);
    }
    
    /**
     * Check if user has a specific role
     */
    public function userHasRole($userID, $roleName) {
        $sql = "SELECT COUNT(*) FROM UserRoles ur
                JOIN Roles r ON ur.roleID = r.roleID
                WHERE ur.userID = ? AND r.roleName = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userID, $roleName]);
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Get all permissions for a role
     */
    public function getRolePermissions($roleID) {
        $sql = "SELECT p.*
                FROM Permissions p
                JOIN RolePermissions rp ON p.permissionID = rp.permissionID
                WHERE rp.roleID = ?
                ORDER BY p.module, p.action";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$roleID]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get all permissions for a user (from all their roles)
     */
    public function getUserPermissions($userID) {
        $sql = "SELECT DISTINCT p.*
                FROM Permissions p
                JOIN RolePermissions rp ON p.permissionID = rp.permissionID
                JOIN UserRoles ur ON rp.roleID = ur.roleID
                WHERE ur.userID = ?
                ORDER BY p.module, p.action";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userID]);
        return $stmt->fetchAll();
    }
    
    /**
     * Check if user has a specific permission
     */
    public function userHasPermission($userID, $permissionName) {
        $sql = "SELECT COUNT(*) FROM UserRoles ur
                JOIN RolePermissions rp ON ur.roleID = rp.roleID
                JOIN Permissions p ON rp.permissionID = p.permissionID
                WHERE ur.userID = ? AND p.permissionName = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userID, $permissionName]);
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Assign a permission to a role
     */
    public function assignPermissionToRole($roleID, $permissionID) {
        try {
            $sql = "INSERT INTO RolePermissions (roleID, permissionID) VALUES (?, ?)";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$roleID, $permissionID]);
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                return true; // Already assigned
            }
            throw $e;
        }
    }
    
    /**
     * Remove a permission from a role
     */
    public function removePermissionFromRole($roleID, $permissionID) {
        $sql = "DELETE FROM RolePermissions WHERE roleID = ? AND permissionID = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$roleID, $permissionID]);
    }
    
    /**
     * Get all permissions
     */
    public function getAllPermissions() {
        $sql = "SELECT * FROM Permissions ORDER BY module, action";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
    
    /**
     * Get permissions grouped by module
     */
    public function getPermissionsByModule() {
        $sql = "SELECT * FROM Permissions ORDER BY module, action";
        $stmt = $this->db->query($sql);
        $permissions = $stmt->fetchAll();
        
        $grouped = [];
        foreach ($permissions as $perm) {
            $module = $perm['module'] ?? 'general';
            if (!isset($grouped[$module])) {
                $grouped[$module] = [];
            }
            $grouped[$module][] = $perm;
        }
        
        return $grouped;
    }
}
