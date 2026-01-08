<?php
/**
 * Users Model
 * Manages system users (Admin, Teachers, Parents)
 */

class UsersModel extends BaseModel {
    protected $table = 'Users';
    protected $primaryKey = 'userID';
    
    /**
     * Get user with role information
     */
    public function getUserWithRole($userID) {
        $sql = "SELECT u.*, u.role as roleName
                FROM {$this->table} u
                WHERE u.userID = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userID]);
        return $stmt->fetch();
    }
    
    /**
     * Get all users with role names
     */
    public function getAllWithRoles($limit = null, $offset = null) {
        $sql = "SELECT u.*, u.role as roleName
                FROM {$this->table} u
                ORDER BY u.username";
        
        if ($limit !== null) {
            $sql .= " LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Create user with hashed password
     */
    public function createUser($data) {
        // Hash password if provided
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);
        }
        
        return $this->create($data);
    }
    
    /**
     * Update user password
     */
    public function updatePassword($userID, $newPassword) {
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        return $this->update($userID, ['password' => $hashedPassword]);
    }
    
    /**
     * Check if username exists
     */
    public function usernameExists($username, $excludeUserID = null) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE username = ?";
        $params = [$username];
        
        if ($excludeUserID) {
            $sql .= " AND userID != ?";
            $params[] = $excludeUserID;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result['count'] > 0;
    }
    
    /**
     * Check if email exists
     */
    public function emailExists($email, $excludeUserID = null) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE email = ?";
        $params = [$email];
        
        if ($excludeUserID) {
            $sql .= " AND userID != ?";
            $params[] = $excludeUserID;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result['count'] > 0;
    }
    
    /**
     * Get users by role
     */
    public function getUsersByRole($roleID) {
        return $this->where(['roleID' => $roleID]);
    }
    
    /**
     * Get users by role (alias for compatibility)
     */
    public function getByRole($roleID) {
        $sql = "SELECT * FROM {$this->table} WHERE roleID = ? ORDER BY username";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$roleID]);
        return $stmt->fetchAll();
    }
    
    /**
     * Toggle user active status
     */
    public function toggleStatus($userID) {
        $user = $this->getById($userID);
        $newStatus = $user['isActive'] ? 0 : 1;
        return $this->update($userID, ['isActive' => $newStatus]);
    }
    
    /**
     * Get active users count
     */
    public function getActiveCount() {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE isActive = 1";
        $stmt = $this->db->query($sql);
        $result = $stmt->fetch();
        return $result['count'] ?? 0;
    }
    
    /**
     * Search users
     */
    public function search($term) {
        $sql = "SELECT u.*, 
                CASE 
                    WHEN u.roleID = 1 THEN 'Admin'
                    WHEN u.roleID = 2 THEN 'Teacher'
                    WHEN u.roleID = 3 THEN 'Parent'
                    ELSE 'Unknown'
                END as roleName
                FROM {$this->table} u
                WHERE u.username LIKE ? OR u.email LIKE ?
                ORDER BY u.username";
        $searchTerm = "%{$term}%";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$searchTerm, $searchTerm]);
        return $stmt->fetchAll();
    }
}
