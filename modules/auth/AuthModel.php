<?php
/**
 * User/Auth Model
 */

class AuthModel extends BaseModel {
    protected $table = 'Users';
    protected $primaryKey = 'userID';
    
    /**
     * Find user by username or email
     */
    public function findByCredential($credential) {
        $sql = "SELECT * FROM {$this->table} WHERE username = :credential OR email = :credential LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':credential', $credential);
        $stmt->execute();
        return $stmt->fetch();
    }
    
    /**
     * Find user by email
     */
    public function findByEmail($email) {
        return $this->where(['email' => $email], null, 1)[0] ?? null;
    }
    
    /**
     * Find user by username
     */
    public function findByUsername($username) {
        return $this->where(['username' => $username], null, 1)[0] ?? null;
    }
    
    /**
     * Create new user
     */
    public function createUser($data) {
        // Hash password before storing
        if (isset($data['password'])) {
            $data['password'] = Utils::hashPassword($data['password']);
        }
        
        // Set defaults
        $data['isActive'] = $data['isActive'] ?? 'Y';
        $data['createdAt'] = date('Y-m-d H:i:s');
        $data['updatedAt'] = date('Y-m-d H:i:s');
        
        return $this->create($data);
    }
    
    /**
     * Update user
     */
    public function updateUser($id, $data) {
        // Hash password if being updated
        if (isset($data['password'])) {
            $data['password'] = Utils::hashPassword($data['password']);
        }
        
        $data['updatedAt'] = date('Y-m-d H:i:s');
        
        return $this->update($id, $data);
    }
    
    /**
     * Update last login
     */
    public function updateLastLogin($userId) {
        $sql = "UPDATE {$this->table} SET lastLogin = NOW() WHERE {$this->primaryKey} = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $userId);
        return $stmt->execute();
    }
    
    /**
     * Verify user credentials
     */
    public function verifyCredentials($credential, $password) {
        $user = $this->findByCredential($credential);
        
        if (!$user) {
            return false;
        }
        
        if ($user['isActive'] !== 'Y') {
            return false;
        }
        
        if (!Utils::verifyPassword($password, $user['password'])) {
            return false;
        }
        
        return $user;
    }
    
    /**
     * Get all active users
     */
    public function getActiveUsers($role = null) {
        if ($role) {
            return $this->where(['isActive' => 'Y', 'role' => $role], 'firstName ASC');
        }
        return $this->where(['isActive' => 'Y'], 'firstName ASC');
    }
}
