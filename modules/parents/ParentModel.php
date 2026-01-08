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
        
        if ($limit) {
            $sql .= " LIMIT " . (int)$limit;
            if ($offset) {
                $sql .= " OFFSET " . (int)$offset;
            }
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
}
