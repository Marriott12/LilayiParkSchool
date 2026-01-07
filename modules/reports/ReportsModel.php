<?php
/**
 * Reports Model
 */

class ReportsModel extends BaseModel {
    
    /**
     * Get dashboard statistics
     */
    public function getDashboardStats() {
        $stats = [];
        
        // Total Pupils
        $sql = "SELECT COUNT(*) as total FROM Pupil";
        $stmt = $this->db->query($sql);
        $stats['totalPupils'] = $stmt->fetch()['total'] ?? 0;
        
        // Total Teachers
        $sql = "SELECT COUNT(*) as total FROM Teacher";
        $stmt = $this->db->query($sql);
        $stats['totalTeachers'] = $stmt->fetch()['total'] ?? 0;
        
        // Total Classes
        $sql = "SELECT COUNT(*) as total FROM Class";
        $stmt = $this->db->query($sql);
        $stats['totalClasses'] = $stmt->fetch()['total'] ?? 0;
        
        // Recent Enrollments (last 30 days)
        $sql = "SELECT COUNT(*) as total FROM Pupil WHERE enrollDate >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
        $stmt = $this->db->query($sql);
        $stats['recentEnrollments'] = $stmt->fetch()['total'] ?? 0;
        
        // Total Fees
        $sql = "SELECT COALESCE(SUM(feeAmt), 0) as total FROM Fees";
        $stmt = $this->db->query($sql);
        $stats['totalFees'] = $stmt->fetch()['total'] ?? 0;
        
        // Total Payments
        $sql = "SELECT COALESCE(SUM(pmtAmt), 0) as total FROM Payment";
        $stmt = $this->db->query($sql);
        $stats['totalPayments'] = $stmt->fetch()['total'] ?? 0;
        
        // Outstanding Balance
        $stats['outstandingBalance'] = $stats['totalFees'] - $stats['totalPayments'];
        
        // Recent Pupils
        $sql = "SELECT pupilID, fName, sName, enrollDate FROM Pupil ORDER BY enrollDate DESC LIMIT 10";
        $stmt = $this->db->query($sql);
        $stats['recentPupils'] = $stmt->fetchAll();
        
        return $stats;
    }
    
    /**
     * Get fee collection report
     */
    public function getFeeCollectionReport($term = null, $year = null) {
        $sql = "SELECT 
                    c.className,
                    f.term,
                    f.year,
                    f.feeAmt,
                    COALESCE(SUM(p.pmtAmt), 0) as totalCollected,
                    (f.feeAmt - COALESCE(SUM(p.pmtAmt), 0)) as outstanding
                FROM Fees f
                JOIN Class c ON f.classID = c.classID
                LEFT JOIN Payment p ON f.classID = p.classID
                WHERE 1=1";
        
        $params = [];
        
        if ($term) {
            $sql .= " AND f.term = :term";
            $params[':term'] = $term;
        }
        
        if ($year) {
            $sql .= " AND f.year = :year";
            $params[':year'] = $year;
        }
        
        $sql .= " GROUP BY f.feeID, c.className, f.term, f.year, f.feeAmt ORDER BY f.year DESC, f.term";
        
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Get attendance report
     */
    public function getAttendanceReport($term = null, $year = null) {
        $sql = "SELECT 
                    p.pupilID,
                    p.fName,
                    p.sName,
                    a.term,
                    a.year,
                    a.daysPresent,
                    a.daysAbsent,
                    (a.daysPresent + a.daysAbsent) as totalDays,
                    ROUND((a.daysPresent / (a.daysPresent + a.daysAbsent)) * 100, 2) as attendanceRate
                FROM Attendance a
                JOIN Pupil p ON a.pupilID = p.pupilID
                WHERE 1=1";
        
        $params = [];
        
        if ($term) {
            $sql .= " AND a.term = :term";
            $params[':term'] = $term;
        }
        
        if ($year) {
            $sql .= " AND a.year = :year";
            $params[':year'] = $year;
        }
        
        $sql .= " ORDER BY a.year DESC, a.term, p.sName, p.fName";
        
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Get class enrollment report
     */
    public function getClassEnrollmentReport() {
        $sql = "SELECT 
                    c.classID,
                    c.className,
                    t.fName as teacherFirstName,
                    t.lName as teacherLastName,
                    COUNT(pc.pupilID) as totalPupils
                FROM Class c
                LEFT JOIN Teacher t ON c.teacherID = t.teacherID
                LEFT JOIN Pupil_Class pc ON c.classID = pc.classID
                GROUP BY c.classID, c.className, t.fName, t.lName
                ORDER BY c.className";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
}
