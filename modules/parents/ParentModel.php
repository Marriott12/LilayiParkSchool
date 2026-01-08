<?php
/**
 * Parent Model
 */

class ParentModel extends BaseModel {
    protected $table = 'Parent';
    protected $primaryKey = 'parentID';
    
    /**
     * Get parent with user account info
     */
    public function getParentWithUser($parentID) {
        $sql = "SELECT p.*, u.username, u.email as userEmail, u.isActive
                FROM {$this->table} p
                LEFT JOIN Users u ON p.userID = u.userID
                WHERE p.parentID = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$parentID]);
        return $stmt->fetch();
    }
    
    /**
     * Get parent's children
     */
    public function getChildren($parentID) {
        $sql = "SELECT * FROM Pupil WHERE parentID = ? ORDER BY firstName, lastName";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$parentID]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get parent with children count
     */
    public function getAllWithChildrenCount($limit = null, $offset = null) {
        $sql = "SELECT p.*, COUNT(pu.pupilID) as childrenCount
                FROM {$this->table} p
                LEFT JOIN Pupil pu ON p.parentID = pu.parentID
                GROUP BY p.parentID
                ORDER BY p.fName, p.lName";
        
        if ($limit !== null) {
            $sql .= " LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Search parents
     */
    public function search($term) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE firstName LIKE ? OR lastName LIKE ? OR email LIKE ? OR phoneNumber LIKE ?
                ORDER BY firstName, lastName";
        $searchTerm = "%{$term}%";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        return $stmt->fetchAll();
    }
    
    /**
     * Check if email already exists
     */
    public function emailExists($email, $excludeParentID = null) {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE email1 = ? OR email2 = ?";
        $params = [$email, $email];
        
        if ($excludeParentID) {
            $sql .= " AND parentID != ?";
            $params[] = $excludeParentID;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Check if phone already exists
     */
    public function phoneExists($phone, $excludeParentID = null) {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE phone = ?";
        $params = [$phone];
        
        if ($excludeParentID) {
            $sql .= " AND parentID != ?";
            $params[] = $excludeParentID;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Check if NRC already exists
     */
    public function nrcExists($nrc, $excludeParentID = null) {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE NRC = ?";
        $params = [$nrc];
        
        if ($excludeParentID) {
            $sql .= " AND parentID != ?";
            $params[] = $excludeParentID;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Get parent by user ID
     */
    public function getByUserID($userID) {
        $sql = "SELECT * FROM {$this->table} WHERE userID = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userID]);
        return $stmt->fetch();
    }
    
    /**
     * Get parents without user accounts (for linking)
     */
    public function getWithoutUserAccount() {
        $sql = "SELECT * FROM {$this->table} WHERE userID IS NULL ORDER BY fName, lName";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
}
