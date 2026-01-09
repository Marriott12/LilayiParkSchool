<?php
/**
 * Attendance Model
 */

class AttendanceModel extends BaseModel {
    protected $table = 'Attendance';
    protected $primaryKey = 'attendanceID';
    
    /**
     * Get attendance with pupil info
     */
    public function getAttendanceWithPupil($attendanceID) {
        $sql = "SELECT a.*, p.fName, p.lName, p.pupilID
                FROM {$this->table} a
                INNER JOIN Pupil p ON a.pupilID = p.pupilID
                WHERE a.attendanceID = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$attendanceID]);
        return $stmt->fetch();
    }
    
    /**
     * Get attendance by class and date
     */
    public function getByClassAndDate($classID, $date) {
        $sql = "SELECT a.*, p.fName, p.lName, p.pupilID
                FROM {$this->table} a
                INNER JOIN Pupil p ON a.pupilID = p.pupilID
                INNER JOIN Pupil_Class pc ON p.pupilID = pc.pupilID
                WHERE pc.classID = ? AND DATE(a.attendanceDate) = ?
                ORDER BY p.fName, p.lName";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$classID, $date]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get attendance by pupil and date range
     */
    public function getByPupilAndDateRange($pupilID, $startDate, $endDate) {
        $sql = "SELECT * FROM {$this->table}
                WHERE pupilID = ? AND attendanceDate BETWEEN ? AND ?
                ORDER BY attendanceDate DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$pupilID, $startDate, $endDate]);
        return $stmt->fetchAll();
    }
    
    /**
     * Mark attendance
     */
    public function markAttendance($pupilID, $date, $status, $remarks = null) {
        $sql = "INSERT INTO {$this->table} (pupilID, attendanceDate, status, remarks)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE status = VALUES(status), remarks = VALUES(remarks)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$pupilID, $date, $status, $remarks]);
    }
    
    /**
     * Get attendance summary for pupil
     */
    public function getPupilSummary($pupilID, $startDate, $endDate) {
        $sql = "SELECT 
                    COUNT(*) as totalDays,
                    SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as presentDays,
                    SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) as absentDays,
                    SUM(CASE WHEN status = 'Late' THEN 1 ELSE 0 END) as lateDays
                FROM {$this->table}
                WHERE pupilID = ? AND attendanceDate BETWEEN ? AND ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$pupilID, $startDate, $endDate]);
        return $stmt->fetch();
    }
    
    /**
     * Get class attendance summary for a date
     */
    public function getClassSummary($classID, $date) {
        $sql = "SELECT 
                    COUNT(*) as totalStudents,
                    SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END) as present,
                    SUM(CASE WHEN a.status = 'Absent' THEN 1 ELSE 0 END) as absent,
                    SUM(CASE WHEN a.status = 'Late' THEN 1 ELSE 0 END) as late
                FROM Pupil p
                INNER JOIN Pupil_Class pc ON p.pupilID = pc.pupilID
                LEFT JOIN {$this->table} a ON p.pupilID = a.pupilID AND DATE(a.attendanceDate) = ?
                WHERE pc.classID = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$date, $classID]);
        return $stmt->fetch();
    }
    
    /**
     * Get pupil attendance stats for term
     */
    public function getPupilAttendanceStats($pupilID, $term = null, $academicYear = null) {
        // Get term dates from settings
        require_once __DIR__ . '/../settings/SettingsModel.php';
        $settingsModel = new SettingsModel();
        
        if (!$term) {
            $term = $settingsModel->getSetting('current_term', '1');
        }
        
        $startDate = $settingsModel->getSetting("term_{$term}_start", date('Y-m-01'));
        $endDate = $settingsModel->getSetting("term_{$term}_end", date('Y-m-t'));
        
        return $this->getPupilSummary($pupilID, $startDate, $endDate);
    }
}

