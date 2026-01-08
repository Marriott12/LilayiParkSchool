<?php
/**
 * Teacher Model
 */

class TeacherModel extends BaseModel {
    protected $table = 'Teacher';
    protected $primaryKey = 'teacherID';
    
    /**
     * Get teacher with user account info
     */
    public function getTeacherWithUser($teacherID) {
        $sql = "SELECT t.*, u.username, u.email as userEmail, u.isActive
                FROM {$this->table} t
                LEFT JOIN Users u ON t.userID = u.userID
                WHERE t.teacherID = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$teacherID]);
        return $stmt->fetch();
    }
    
    /**
     * Get all active teachers
     */
    public function getActiveTeachers() {
        $sql = "SELECT * FROM {$this->table} ORDER BY fName, lName";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Get classes taught by teacher
     */
    public function getTeacherClasses($teacherID) {
        $sql = "SELECT c.* FROM Class c
                WHERE c.teacherID = ?
                ORDER BY c.className";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$teacherID]);
        return $stmt->fetchAll();
    }
    
    /**
     * Check if TPIN already exists
     */
    public function tpinExists($tpin, $excludeTeacherID = null) {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE Tpin = ?";
        $params = [$tpin];
        
        if ($excludeTeacherID) {
            $sql .= " AND teacherID != ?";
            $params[] = $excludeTeacherID;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Check if SSN already exists
     */
    public function ssnExists($ssn, $excludeTeacherID = null) {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE SSN = ?";
        $params = [$ssn];
        
        if ($excludeTeacherID) {
            $sql .= " AND teacherID != ?";
            $params[] = $excludeTeacherID;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Check if NRC already exists
     */
    public function nrcExists($nrc, $excludeTeacherID = null) {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE NRC = ?";
        $params = [$nrc];
        
        if ($excludeTeacherID) {
            $sql .= " AND teacherID != ?";
            $params[] = $excludeTeacherID;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Check if email already exists
     */
    public function emailExists($email, $excludeTeacherID = null) {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE email = ?";
        $params = [$email];
        
        if ($excludeTeacherID) {
            $sql .= " AND teacherID != ?";
            $params[] = $excludeTeacherID;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Search teachers
     */
    public function search($term) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE firstName LIKE ? OR lastName LIKE ? OR email LIKE ?
                ORDER BY firstName, lastName";
        $searchTerm = "%{$term}%";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get teacher by user ID
     */
    public function getByUserID($userID) {
        $sql = "SELECT * FROM {$this->table} WHERE userID = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userID]);
        return $stmt->fetch();
    }
    
    /**
     * Get teachers without user accounts (for linking)
     */
    public function getWithoutUserAccount() {
        $sql = "SELECT * FROM {$this->table} WHERE userID IS NULL ORDER BY fName, lName";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
}
