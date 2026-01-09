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
        
        if ($limit !== null) {
            $sql .= " LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
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
                INNER JOIN Pupil_Class pc ON p.pupilID = pc.pupilID
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
        $sql = "INSERT INTO Pupil_Class (classID, pupilID, enrollmentDate) 
                VALUES (?, ?, NOW())
                ON DUPLICATE KEY UPDATE enrollmentDate = NOW()";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$classID, $pupilID]);
    }
    
    /**
     * Remove pupil from class
     */
    public function removePupil($classID, $pupilID) {
        $sql = "DELETE FROM Pupil_Class WHERE classID = ? AND pupilID = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$classID, $pupilID]);
    }
    
    /**
     * Get pupils not in this class (available to add)
     */
    public function getAvailablePupils($classID) {
        $sql = "SELECT p.pupilID, p.fName, p.lName, p.studentNumber
                FROM Pupil p
                WHERE p.pupilID NOT IN (
                    SELECT pupilID FROM Pupil_Class WHERE classID = ?
                )
                ORDER BY p.fName, p.lName";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$classID]);
        return $stmt->fetchAll();
    }
}
