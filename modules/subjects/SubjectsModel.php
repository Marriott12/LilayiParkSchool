<?php
/**
 * Subjects Model
 * Manages academic subjects
 */

class SubjectsModel extends BaseModel {
    protected $table = 'Subjects';
    protected $primaryKey = 'subjectID';
    
    /**
     * Get subject with teacher info
     */
    public function getSubjectWithTeacher($subjectID) {
        $sql = "SELECT s.*, t.fName as teacherFirstName, t.lName as teacherLastName
                FROM {$this->table} s
                LEFT JOIN Teacher t ON s.teacherID = t.teacherID
                WHERE s.subjectID = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$subjectID]);
        return $stmt->fetch();
    }
    
    /**
     * Get all subjects with teacher names
     */
    public function getAllWithTeachers() {
        $sql = "SELECT s.*, t.fName as teacherFirstName, t.lName as teacherLastName
                FROM {$this->table} s
                LEFT JOIN Teacher t ON s.teacherID = t.teacherID
                ORDER BY s.subjectName";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Get subjects by class
     */
    public function getSubjectsByClass($classID) {
        $sql = "SELECT s.*, t.fName as teacherFirstName, t.lName as teacherLastName
                FROM {$this->table} s
                INNER JOIN ClassSubjects cs ON s.subjectID = cs.subjectID
                LEFT JOIN Teacher t ON s.teacherID = t.teacherID
                WHERE cs.classID = ?
                ORDER BY s.subjectName";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$classID]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get subjects by teacher
     */
    public function getSubjectsByTeacher($teacherID) {
        return $this->where(['teacherID' => $teacherID]);
    }
    
    /**
     * Assign subject to class
     */
    public function assignToClass($subjectID, $classID) {
        $sql = "INSERT INTO ClassSubjects (classID, subjectID, createdAt) 
                VALUES (?, ?, NOW())
                ON DUPLICATE KEY UPDATE updatedAt = NOW()";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$classID, $subjectID]);
    }
    
    /**
     * Remove subject from class
     */
    public function removeFromClass($subjectID, $classID) {
        $sql = "DELETE FROM ClassSubjects WHERE classID = ? AND subjectID = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$classID, $subjectID]);
    }
    
    /**
     * Get classes assigned to subject
     */
    public function getAssignedClasses($subjectID) {
        $sql = "SELECT c.*, cs.createdAt as assignedDate
                FROM Class c
                INNER JOIN ClassSubjects cs ON c.classID = cs.classID
                WHERE cs.subjectID = ?
                ORDER BY c.className";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$subjectID]);
        return $stmt->fetchAll();
    }
    
    /**
     * Search subjects
     */
    public function search($term) {
        $sql = "SELECT s.*, t.fName as teacherFirstName, t.lName as teacherLastName
                FROM {$this->table} s
                LEFT JOIN Teacher t ON s.teacherID = t.teacherID
                WHERE s.subjectName LIKE ? OR s.subjectCode LIKE ?
                ORDER BY s.subjectName";
        $searchTerm = "%{$term}%";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$searchTerm, $searchTerm]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get subject statistics
     */
    public function getStatistics() {
        $sql = "SELECT 
                COUNT(DISTINCT s.subjectID) as totalSubjects,
                COUNT(DISTINCT s.teacherID) as assignedTeachers,
                COUNT(DISTINCT cs.classID) as classesWithSubjects
                FROM {$this->table} s
                LEFT JOIN ClassSubjects cs ON s.subjectID = cs.subjectID";
        $stmt = $this->db->query($sql);
        return $stmt->fetch();
    }
}
