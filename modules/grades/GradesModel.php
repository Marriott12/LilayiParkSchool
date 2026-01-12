<?php
/**
 * Grades Model
 * Manages student grades, marks, and academic performance
 */

class GradesModel extends BaseModel {
    protected $table = 'grades';
    protected $primaryKey = 'gradeID';
    
    /**
     * Get current academic year from settings
     */
    private function getCurrentAcademicYear() {
        require_once __DIR__ . '/../settings/SettingsModel.php';
        $settingsModel = new SettingsModel();
        return $settingsModel->getSetting('current_academic_year', '2025-2026');
    }
    
    /**
     * Get current term from settings
     */
    private function getCurrentTerm() {
        require_once __DIR__ . '/../settings/SettingsModel.php';
        $settingsModel = new SettingsModel();
        return $settingsModel->getSetting('current_term', '1');
    }
    
    /**
     * Calculate letter grade based on marks
     */
    public function calculateGrade($marks, $maxMarks = 100) {
        $percentage = ($marks / $maxMarks) * 100;
        
        $sql = "SELECT grade, gradePoint FROM GradingScale 
                WHERE :percentage BETWEEN minMarks AND maxMarks 
                AND isActive = 1 LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['percentage' => $percentage]);
        $result = $stmt->fetch();
        
        return $result ?: ['grade' => 'F', 'gradePoint' => 0.00];
    }
    
    /**
     * Create or update grade with automatic grade calculation
     */
    public function saveGrade($data) {
        // Calculate grade automatically
        $gradeInfo = $this->calculateGrade($data['marks'], $data['maxMarks'] ?? 100);
        $data['grade'] = $gradeInfo['grade'];
        $data['gradePoint'] = $gradeInfo['gradePoint'];
        
        // Set academic year and term if not provided
        if (empty($data['academicYear'])) {
            $data['academicYear'] = $this->getCurrentAcademicYear();
        }
        if (empty($data['term'])) {
            $data['term'] = $this->getCurrentTerm();
        }
        
        // Check if grade exists for this combination
        $existing = $this->getGradeByPupilSubject(
            $data['pupilID'],
            $data['subjectID'],
            $data['term'],
            $data['academicYear'],
            $data['examType']
        );
        
        if ($existing) {
            // Update existing grade
            return $this->update($existing['gradeID'], $data);
        } else {
            // Create new grade
            $data['recordedBy'] = Session::getUserId();
            return $this->create($data);
        }
    }
    
    /**
     * Get grade by unique combination
     */
    public function getGradeByPupilSubject($pupilID, $subjectID, $term, $academicYear, $examType) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE pupilID = ? AND subjectID = ? AND term = ? 
                AND academicYear = ? AND examType = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$pupilID, $subjectID, $term, $academicYear, $examType]);
        return $stmt->fetch();
    }
    
    /**
     * Get all grades for a pupil in a term
     */
    public function getGradesByPupil($pupilID, $term = null, $academicYear = null, $examType = null) {
        $term = $term ?? $this->getCurrentTerm();
        $academicYear = $academicYear ?? $this->getCurrentAcademicYear();
        
        $sql = "SELECT g.*, s.subjectName, s.subjectCode, 
                       c.className, u.username as recordedByName
                FROM {$this->table} g
                LEFT JOIN Subjects s ON g.subjectID = s.subjectID
                LEFT JOIN Class c ON g.classID = c.classID
                LEFT JOIN Users u ON g.recordedBy = u.userID
                WHERE g.pupilID = ? AND g.term = ? AND g.academicYear = ?";
        
        $params = [$pupilID, $term, $academicYear];
        
        if ($examType) {
            $sql .= " AND g.examType = ?";
            $params[] = $examType;
        }
        
        $sql .= " ORDER BY s.subjectName";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Get all grades for a class with pagination and filtering
     */
    public function getGradesByClass($classID, $term = null, $academicYear = null, 
                                      $subjectID = null, $examType = null, 
                                      $limit = null, $offset = null) {
        $term = $term ?? $this->getCurrentTerm();
        $academicYear = $academicYear ?? $this->getCurrentAcademicYear();
        
        $sql = "SELECT g.*, p.fName, p.lName, p.admNo,
                       s.subjectName, s.subjectCode,
                       u.username as recordedByName
                FROM {$this->table} g
                INNER JOIN Pupil p ON g.pupilID = p.pupilID
                LEFT JOIN Subjects s ON g.subjectID = s.subjectID
                LEFT JOIN Users u ON g.recordedBy = u.userID
                WHERE g.classID = ? AND g.term = ? AND g.academicYear = ?";
        
        $params = [$classID, $term, $academicYear];
        
        if ($subjectID) {
            $sql .= " AND g.subjectID = ?";
            $params[] = $subjectID;
        }
        
        if ($examType) {
            $sql .= " AND g.examType = ?";
            $params[] = $examType;
        }
        
        $sql .= " ORDER BY p.lName, p.fName, s.subjectName";
        
        if ($limit !== null) {
            $sql .= " LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Get grades by subject
     */
    public function getGradesBySubject($subjectID, $classID = null, $term = null, 
                                       $academicYear = null, $examType = null) {
        $term = $term ?? $this->getCurrentTerm();
        $academicYear = $academicYear ?? $this->getCurrentAcademicYear();
        
        $sql = "SELECT g.*, p.fName, p.lName, p.admNo,
                       c.className
                FROM {$this->table} g
                INNER JOIN Pupil p ON g.pupilID = p.pupilID
                LEFT JOIN Class c ON g.classID = c.classID
                WHERE g.subjectID = ? AND g.term = ? AND g.academicYear = ?";
        
        $params = [$subjectID, $term, $academicYear];
        
        if ($classID) {
            $sql .= " AND g.classID = ?";
            $params[] = $classID;
        }
        
        if ($examType) {
            $sql .= " AND g.examType = ?";
            $params[] = $examType;
        }
        
        $sql .= " ORDER BY p.lName, p.fName";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Calculate class average for a subject
     */
    public function getClassAverage($classID, $subjectID, $term = null, 
                                    $academicYear = null, $examType = null) {
        $term = $term ?? $this->getCurrentTerm();
        $academicYear = $academicYear ?? $this->getCurrentAcademicYear();
        
        $sql = "SELECT AVG(marks) as average, AVG(gradePoint) as avgGPA,
                       COUNT(*) as totalStudents,
                       MAX(marks) as highestMark,
                       MIN(marks) as lowestMark
                FROM {$this->table}
                WHERE classID = ? AND subjectID = ? AND term = ? AND academicYear = ?";
        
        $params = [$classID, $subjectID, $term, $academicYear];
        
        if ($examType) {
            $sql .= " AND examType = ?";
            $params[] = $examType;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }
    
    /**
     * Get pupil's overall average for a term
     */
    public function getPupilAverage($pupilID, $term = null, $academicYear = null, $examType = null) {
        $term = $term ?? $this->getCurrentTerm();
        $academicYear = $academicYear ?? $this->getCurrentAcademicYear();
        
        $sql = "SELECT AVG(marks) as average, AVG(gradePoint) as GPA,
                       COUNT(*) as subjectsTaken,
                       SUM(marks) as totalMarks,
                       SUM(maxMarks) as totalMaxMarks
                FROM {$this->table}
                WHERE pupilID = ? AND term = ? AND academicYear = ?";
        
        $params = [$pupilID, $term, $academicYear];
        
        if ($examType) {
            $sql .= " AND examType = ?";
            $params[] = $examType;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }
    
    /**
     * Get pupil's rank in class
     */
    public function getPupilRank($pupilID, $classID, $term = null, 
                                 $academicYear = null, $examType = null) {
        $term = $term ?? $this->getCurrentTerm();
        $academicYear = $academicYear ?? $this->getCurrentAcademicYear();
        
        $sql = "SELECT pupilID, AVG(marks) as average
                FROM {$this->table}
                WHERE classID = ? AND term = ? AND academicYear = ?";
        
        $params = [$classID, $term, $academicYear];
        
        if ($examType) {
            $sql .= " AND examType = ?";
            $params[] = $examType;
        }
        
        $sql .= " GROUP BY pupilID ORDER BY average DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rankings = $stmt->fetchAll();
        
        foreach ($rankings as $index => $ranking) {
            if ($ranking['pupilID'] == $pupilID) {
                return [
                    'rank' => $index + 1,
                    'totalStudents' => count($rankings),
                    'average' => $ranking['average']
                ];
            }
        }
        
        return null;
    }
    
    /**
     * Get class rankings
     */
    public function getClassRankings($classID, $term = null, $academicYear = null, $examType = null) {
        $term = $term ?? $this->getCurrentTerm();
        $academicYear = $academicYear ?? $this->getCurrentAcademicYear();
        
        $sql = "SELECT g.pupilID, p.fName, p.lName, p.admNo,
                       AVG(g.marks) as average, AVG(g.gradePoint) as GPA,
                       SUM(g.marks) as totalMarks, COUNT(g.gradeID) as subjectCount
                FROM {$this->table} g
                INNER JOIN Pupil p ON g.pupilID = p.pupilID
                WHERE g.classID = ? AND g.term = ? AND g.academicYear = ?";
        
        $params = [$classID, $term, $academicYear];
        
        if ($examType) {
            $sql .= " AND g.examType = ?";
            $params[] = $examType;
        }
        
        $sql .= " GROUP BY g.pupilID, p.fName, p.lName, p.admNo
                  ORDER BY average DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Bulk insert/update grades for a class
     */
    public function bulkSaveGrades($grades) {
        $saved = 0;
        $errors = [];
        
        foreach ($grades as $gradeData) {
            try {
                $this->saveGrade($gradeData);
                $saved++;
            } catch (Exception $e) {
                $errors[] = [
                    'data' => $gradeData,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return [
            'saved' => $saved,
            'errors' => $errors
        ];
    }
    
    /**
     * Count grades with filters
     */
    public function countGrades($classID = null, $term = null, $academicYear = null, 
                                 $subjectID = null, $examType = null) {
        $term = $term ?? $this->getCurrentTerm();
        $academicYear = $academicYear ?? $this->getCurrentAcademicYear();
        
        $sql = "SELECT COUNT(*) as total FROM {$this->table} 
                WHERE term = ? AND academicYear = ?";
        $params = [$term, $academicYear];
        
        if ($classID) {
            $sql .= " AND classID = ?";
            $params[] = $classID;
        }
        
        if ($subjectID) {
            $sql .= " AND subjectID = ?";
            $params[] = $subjectID;
        }
        
        if ($examType) {
            $sql .= " AND examType = ?";
            $params[] = $examType;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result['total'];
    }
}
