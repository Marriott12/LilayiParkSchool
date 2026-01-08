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
        $sql = "SELECT * FROM {$this->table} WHERE status = 'Active' ORDER BY firstName, lastName";
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
}
