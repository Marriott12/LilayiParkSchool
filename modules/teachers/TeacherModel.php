<?php
/**
 * Teacher Model
 */

class TeacherModel extends BaseModel {
    protected $table = 'Teacher';
    protected $primaryKey = 'teacherID';
    
    /**
     * Override all() to include user account info
     */
    public function all($orderBy = null, $limit = null, $offset = null) {
        $sql = "SELECT t.*, u.isActive as userIsActive 
                FROM {$this->table} t
                LEFT JOIN Users u ON t.userID = u.userID";
        
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        } else {
            $sql .= " ORDER BY t.fName, t.lName";
        }
        
        if ($limit) {
            $sql .= " LIMIT {$limit}";
            if ($offset) {
                $sql .= " OFFSET {$offset}";
            }
        }
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
    
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
        $sql = "SELECT t.*, u.isActive as userIsActive FROM {$this->table} t
                LEFT JOIN Users u ON t.userID = u.userID
                WHERE fName LIKE ? OR lName LIKE ? OR email LIKE ? OR phone LIKE ? OR tczNo LIKE ?
                ORDER BY fName, lName";
        $searchTerm = "%{$term}%";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
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
    
    /**
     * Check if teacher is assigned to a specific class
     */
    public function isAssignedToClass($teacherID, $classID) {
        $sql = "SELECT COUNT(*) FROM Class WHERE teacherID = ? AND classID = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$teacherID, $classID]);
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Check if teacher can access a specific pupil (pupil is in one of their classes)
     */
    public function canAccessPupil($teacherID, $pupilID) {
        $sql = "SELECT COUNT(*) FROM Pupil_Class pc
                INNER JOIN Class c ON pc.classID = c.classID
                WHERE c.teacherID = ? AND pc.pupilID = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$teacherID, $pupilID]);
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Get all pupil IDs that teacher can access (pupils in their classes)
     */
    public function getAccessiblePupilIDs($teacherID) {
        $sql = "SELECT DISTINCT pc.pupilID FROM Pupil_Class pc
                INNER JOIN Class c ON pc.classID = c.classID
                WHERE c.teacherID = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$teacherID]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    /**
     * Get all class IDs assigned to teacher
     */
    public function getAssignedClassIDs($teacherID) {
        $sql = "SELECT classID FROM Class WHERE teacherID = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$teacherID]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
