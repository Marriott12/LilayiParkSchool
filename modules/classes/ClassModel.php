<?php
/**
 * Class Model
 */

class ClassModel extends BaseModel {
    protected $table = 'Class';
    protected $primaryKey = 'classID';
    
    /**
     * Get class with teacher info
     */
    public function getClassWithTeacher($classID) {
        $sql = "SELECT c.*, t.fName as teacherFirstName, t.lName as teacherLastName
                FROM {$this->table} c
                LEFT JOIN Teacher t ON c.teacherID = t.teacherID
                WHERE c.classID = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$classID]);
        return $stmt->fetch();
    }
    
    /**
     * Get all classes with teacher and pupil count
     */
    public function getAllWithDetails($limit = null, $offset = null) {
        $sql = "SELECT c.*, t.fName as teacherFirstName, t.lName as teacherLastName,
                       COUNT(DISTINCT pc.pupilID) as pupilCount
                FROM {$this->table} c
                LEFT JOIN Teacher t ON c.teacherID = t.teacherID
                LEFT JOIN pupil_Class pc ON c.classID = pc.classID
                GROUP BY c.classID
                ORDER BY c.className";
        
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
     * Get class roster (pupils in class)
     */
    public function getClassRoster($classID) {
        $sql = "SELECT p.*, pc.enrollmentDate
                FROM Pupil p
                INNER JOIN PupilClass pc ON p.pupilID = pc.pupilID
                WHERE pc.classID = ?
                ORDER BY p.fName, p.lName";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$classID]);
        return $stmt->fetchAll();
    }
    
    /**
     * Assign pupil to class
     */
    public function assignPupil($classID, $pupilID) {
        $sql = "INSERT INTO PupilClass (classID, pupilID, enrollmentDate) 
                VALUES (?, ?, NOW())
                ON DUPLICATE KEY UPDATE enrollmentDate = NOW()";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$classID, $pupilID]);
    }
    
    /**
     * Remove pupil from class
     */
    public function removePupil($classID, $pupilID) {
        $sql = "DELETE FROM PupilClass WHERE classID = ? AND pupilID = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$classID, $pupilID]);
    }
}
