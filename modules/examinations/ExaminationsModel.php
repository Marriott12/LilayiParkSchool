<?php
/**
 * Examinations Model
 * Handles examination scheduling and management
 */

class ExaminationsModel {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Create a new examination
     */
    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO Examinations 
            (examName, examType, term, academicYear, startDate, endDate, totalMarks, passingMarks, instructions, status, createdBy)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $data['examName'],
            $data['examType'],
            $data['term'],
            $data['academicYear'],
            $data['startDate'],
            $data['endDate'],
            $data['totalMarks'] ?? 100,
            $data['passingMarks'] ?? 40,
            $data['instructions'] ?? null,
            $data['status'] ?? 'Scheduled',
            Session::get('userID')
        ]);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Update an examination
     */
    public function update($examID, $data) {
        $fields = [];
        $values = [];
        
        $allowedFields = ['examName', 'examType', 'term', 'academicYear', 'startDate', 'endDate', 
                          'totalMarks', 'passingMarks', 'instructions', 'status'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = ?";
                $values[] = $data[$field];
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $values[] = $examID;
        $sql = "UPDATE Examinations SET " . implode(', ', $fields) . " WHERE examID = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($values);
    }
    
    /**
     * Delete an examination
     */
    public function delete($examID) {
        $stmt = $this->db->prepare("DELETE FROM Examinations WHERE examID = ?");
        return $stmt->execute([$examID]);
    }
    
    /**
     * Get examination by ID
     */
    public function getById($examID) {
        $stmt = $this->db->prepare("
            SELECT e.*, u.username as createdByName,
                   (SELECT COUNT(*) FROM ExamSchedule WHERE examID = e.examID) as scheduledClasses
            FROM Examinations e
            LEFT JOIN Users u ON e.createdBy = u.userID
            WHERE e.examID = ?
        ");
        $stmt->execute([$examID]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get all examinations with optional filters
     */
    public function getAll($filters = [], $limit = null, $offset = 0) {
        $where = [];
        $params = [];
        
        if (!empty($filters['term'])) {
            $where[] = "e.term = ?";
            $params[] = $filters['term'];
        }
        
        if (!empty($filters['academicYear'])) {
            $where[] = "e.academicYear = ?";
            $params[] = $filters['academicYear'];
        }
        
        if (!empty($filters['examType'])) {
            $where[] = "e.examType = ?";
            $params[] = $filters['examType'];
        }
        
        if (!empty($filters['status'])) {
            $where[] = "e.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['search'])) {
            $where[] = "e.examName LIKE ?";
            $params[] = "%{$filters['search']}%";
        }
        
        $whereClause = !empty($where) ? "WHERE " . implode(' AND ', $where) : "";
        
        $sql = "
            SELECT e.*, u.username as createdByName,
                   (SELECT COUNT(*) FROM ExamSchedule WHERE examID = e.examID) as scheduledClasses
            FROM Examinations e
            LEFT JOIN Users u ON e.createdBy = u.userID
            $whereClause
            ORDER BY e.startDate DESC, e.examName
        ";
        
        if ($limit !== null) {
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Count examinations with filters
     */
    public function count($filters = []) {
        $where = [];
        $params = [];
        
        if (!empty($filters['term'])) {
            $where[] = "term = ?";
            $params[] = $filters['term'];
        }
        
        if (!empty($filters['academicYear'])) {
            $where[] = "academicYear = ?";
            $params[] = $filters['academicYear'];
        }
        
        if (!empty($filters['examType'])) {
            $where[] = "examType = ?";
            $params[] = $filters['examType'];
        }
        
        if (!empty($filters['status'])) {
            $where[] = "status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['search'])) {
            $where[] = "examName LIKE ?";
            $params[] = "%{$filters['search']}%";
        }
        
        $whereClause = !empty($where) ? "WHERE " . implode(' AND ', $where) : "";
        
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM Examinations $whereClause");
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }
    
    /**
     * Schedule exam for a class/subject
     */
    public function scheduleExam($data) {
        // Check for conflicts
        $conflicts = $this->checkConflicts(
            $data['classID'],
            $data['examDate'],
            $data['startTime'],
            $data['endTime'],
            $data['scheduleID'] ?? null
        );
        
        if (!empty($conflicts)) {
            throw new Exception('Schedule conflict detected: ' . $conflicts[0]['conflict_type']);
        }
        
        $stmt = $this->db->prepare("
            INSERT INTO ExamSchedule 
            (examID, classID, subjectID, examDate, startTime, endTime, room, invigilator, maxMarks, duration, specialInstructions, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        return $stmt->execute([
            $data['examID'],
            $data['classID'],
            $data['subjectID'],
            $data['examDate'],
            $data['startTime'],
            $data['endTime'],
            $data['room'] ?? null,
            $data['invigilator'] ?? null,
            $data['maxMarks'] ?? 100,
            $data['duration'] ?? 60,
            $data['specialInstructions'] ?? null,
            $data['status'] ?? 'Scheduled'
        ]);
    }
    
    /**
     * Update exam schedule
     */
    public function updateSchedule($scheduleID, $data) {
        // Check for conflicts
        if (isset($data['classID']) && isset($data['examDate']) && isset($data['startTime']) && isset($data['endTime'])) {
            $conflicts = $this->checkConflicts(
                $data['classID'],
                $data['examDate'],
                $data['startTime'],
                $data['endTime'],
                $scheduleID
            );
            
            if (!empty($conflicts)) {
                throw new Exception('Schedule conflict detected: ' . $conflicts[0]['conflict_type']);
            }
        }
        
        $fields = [];
        $values = [];
        
        $allowedFields = ['examID', 'classID', 'subjectID', 'examDate', 'startTime', 'endTime', 
                          'room', 'invigilator', 'maxMarks', 'duration', 'specialInstructions', 'status'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = ?";
                $values[] = $data[$field];
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $values[] = $scheduleID;
        $sql = "UPDATE ExamSchedule SET " . implode(', ', $fields) . " WHERE scheduleID = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($values);
    }
    
    /**
     * Delete exam schedule
     */
    public function deleteSchedule($scheduleID) {
        $stmt = $this->db->prepare("DELETE FROM ExamSchedule WHERE scheduleID = ?");
        return $stmt->execute([$scheduleID]);
    }
    
    /**
     * Get schedule by ID
     */
    public function getScheduleById($scheduleID) {
        $stmt = $this->db->prepare("
            SELECT es.*, e.examName, e.examType, c.className, s.subjectName,
                   u.username as invigilatorName
            FROM ExamSchedule es
            JOIN Examinations e ON es.examID = e.examID
            JOIN Classes c ON es.classID = c.classID
            JOIN Subjects s ON es.subjectID = s.subjectID
            LEFT JOIN Users u ON es.invigilator = u.userID
            WHERE es.scheduleID = ?
        ");
        $stmt->execute([$scheduleID]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get all schedules for an exam
     */
    public function getSchedulesByExam($examID) {
        $stmt = $this->db->prepare("
            SELECT es.*, c.className, s.subjectName, u.username as invigilatorName
            FROM ExamSchedule es
            JOIN Classes c ON es.classID = c.classID
            JOIN Subjects s ON es.subjectID = s.subjectID
            LEFT JOIN Users u ON es.invigilator = u.userID
            WHERE es.examID = ?
            ORDER BY es.examDate, es.startTime, c.className
        ");
        $stmt->execute([$examID]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get upcoming examinations
     */
    public function getUpcoming($limit = 10) {
        $stmt = $this->db->prepare("
            SELECT e.*, u.username as createdByName,
                   (SELECT COUNT(*) FROM ExamSchedule WHERE examID = e.examID) as scheduledClasses
            FROM Examinations e
            LEFT JOIN Users u ON e.createdBy = u.userID
            WHERE e.startDate >= CURDATE() AND e.status != 'Cancelled'
            ORDER BY e.startDate ASC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get exam schedule for a class
     */
    public function getClassSchedule($classID, $filters = []) {
        $where = ["es.classID = ?"];
        $params = [$classID];
        
        if (!empty($filters['examID'])) {
            $where[] = "es.examID = ?";
            $params[] = $filters['examID'];
        }
        
        if (!empty($filters['startDate'])) {
            $where[] = "es.examDate >= ?";
            $params[] = $filters['startDate'];
        }
        
        if (!empty($filters['endDate'])) {
            $where[] = "es.examDate <= ?";
            $params[] = $filters['endDate'];
        }
        
        $whereClause = implode(' AND ', $where);
        
        $stmt = $this->db->prepare("
            SELECT es.*, e.examName, e.examType, s.subjectName, u.username as invigilatorName
            FROM ExamSchedule es
            JOIN Examinations e ON es.examID = e.examID
            JOIN Subjects s ON es.subjectID = s.subjectID
            LEFT JOIN Users u ON es.invigilator = u.userID
            WHERE $whereClause
            ORDER BY es.examDate, es.startTime
        ");
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Check for scheduling conflicts
     */
    public function checkConflicts($classID, $examDate, $startTime, $endTime, $excludeScheduleID = null) {
        $params = [$classID, $examDate, $startTime, $endTime, $startTime, $endTime];
        $excludeClause = "";
        
        if ($excludeScheduleID !== null) {
            $excludeClause = "AND scheduleID != ?";
            $params[] = $excludeScheduleID;
        }
        
        $stmt = $this->db->prepare("
            SELECT es.*, e.examName, s.subjectName,
                   'Class has another exam at this time' as conflict_type
            FROM ExamSchedule es
            JOIN Examinations e ON es.examID = e.examID
            JOIN Subjects s ON es.subjectID = s.subjectID
            WHERE es.classID = ?
              AND es.examDate = ?
              AND (
                  (es.startTime <= ? AND es.endTime > ?) OR
                  (es.startTime < ? AND es.endTime >= ?) OR
                  (es.startTime >= ? AND es.endTime <= ?)
              )
              AND es.status NOT IN ('Cancelled', 'Postponed')
              $excludeClause
        ");
        
        $params = array_merge($params, [$startTime, $endTime]);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get exam statistics
     */
    public function getStatistics($examID) {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(DISTINCT es.classID) as totalClasses,
                COUNT(DISTINCT es.subjectID) as totalSubjects,
                COUNT(es.scheduleID) as totalSchedules,
                COUNT(DISTINCT CASE WHEN es.status = 'Completed' THEN es.scheduleID END) as completedSchedules,
                MIN(es.examDate) as firstExamDate,
                MAX(es.examDate) as lastExamDate
            FROM ExamSchedule es
            WHERE es.examID = ?
        ");
        $stmt->execute([$examID]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Update exam status based on dates
     */
    public function updateExamStatuses() {
        // Set to Ongoing if start date is today or past and end date is future
        $this->db->exec("
            UPDATE Examinations 
            SET status = 'Ongoing' 
            WHERE startDate <= CURDATE() 
              AND endDate >= CURDATE() 
              AND status = 'Scheduled'
        ");
        
        // Set to Completed if end date is past
        $this->db->exec("
            UPDATE Examinations 
            SET status = 'Completed' 
            WHERE endDate < CURDATE() 
              AND status IN ('Scheduled', 'Ongoing')
        ");
        
        return true;
    }
}
