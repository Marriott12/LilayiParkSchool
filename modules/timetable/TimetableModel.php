<?php
/**
 * Timetable Model
 * Manages class timetables and scheduling
 */

class TimetableModel extends BaseModel {
    protected $table = 'timetable';
    protected $primaryKey = 'timetableID';
    
    /**
     * Get timetable for a specific class
     */
    public function getByClass($classID, $term = null, $academicYear = null) {
        $sql = "SELECT t.*, s.subjectName, s.subjectCode,
                       tc.fName as teacherFirstName, tc.lName as teacherLastName
                FROM {$this->table} t
                LEFT JOIN Subjects s ON t.subjectID = s.subjectID
                LEFT JOIN Teacher tc ON t.teacherID = tc.teacherID
                WHERE t.classID = ? AND t.isActive = 1";
        
        $params = [$classID];
        
        if ($term) {
            $sql .= " AND t.term = ?";
            $params[] = $term;
        }
        
        if ($academicYear) {
            $sql .= " AND t.academicYear = ?";
            $params[] = $academicYear;
        }
        
        $sql .= " ORDER BY 
                  FIELD(t.dayOfWeek, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'),
                  t.startTime";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Get timetable for a specific teacher
     */
    public function getByTeacher($teacherID, $term = null, $academicYear = null) {
        $sql = "SELECT t.*, s.subjectName, c.className,
                       s.subjectCode
                FROM {$this->table} t
                LEFT JOIN Subjects s ON t.subjectID = s.subjectID
                LEFT JOIN Class c ON t.classID = c.classID
                WHERE t.teacherID = ? AND t.isActive = 1";
        
        $params = [$teacherID];
        
        if ($term) {
            $sql .= " AND t.term = ?";
            $params[] = $term;
        }
        
        if ($academicYear) {
            $sql .= " AND t.academicYear = ?";
            $params[] = $academicYear;
        }
        
        $sql .= " ORDER BY 
                  FIELD(t.dayOfWeek, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'),
                  t.startTime";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Check for time conflicts
     */
    public function hasConflict($classID, $dayOfWeek, $startTime, $endTime, $term, $academicYear, $excludeID = null) {
        $sql = "SELECT COUNT(*) as count
                FROM {$this->table}
                WHERE classID = ?
                AND dayOfWeek = ?
                AND term = ?
                AND academicYear = ?
                AND isActive = 1
                AND (
                    (startTime < ? AND endTime > ?)
                    OR (startTime < ? AND endTime > ?)
                    OR (startTime >= ? AND endTime <= ?)
                )";
        
        $params = [$classID, $dayOfWeek, $term, $academicYear,
                   $endTime, $startTime,
                   $endTime, $endTime,
                   $startTime, $endTime];
        
        if ($excludeID) {
            $sql .= " AND timetableID != ?";
            $params[] = $excludeID;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        
        return $result['count'] > 0;
    }
    
    /**
     * Get all active timetables
     */
    public function getAllActive($term = null, $academicYear = null) {
        $sql = "SELECT t.*, c.className, s.subjectName, 
                       tc.fName as teacherFirstName, tc.lName as teacherLastName
                FROM {$this->table} t
                LEFT JOIN Class c ON t.classID = c.classID
                LEFT JOIN Subjects s ON t.subjectID = s.subjectID
                LEFT JOIN Teacher tc ON t.teacherID = tc.teacherID
                WHERE t.isActive = 1";
        
        $params = [];
        
        if ($term) {
            $sql .= " AND t.term = ?";
            $params[] = $term;
        }
        
        if ($academicYear) {
            $sql .= " AND t.academicYear = ?";
            $params[] = $academicYear;
        }
        
        $sql .= " ORDER BY c.className, 
                  FIELD(t.dayOfWeek, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'),
                  t.startTime";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
