<?php
require_once __DIR__ . '/../../includes/BaseModel.php';

class SubjectModel extends BaseModel {
    protected $table = 'subjects';
    protected $primaryKey = 'subjectID';
    
    /**
     * Get all active subjects
     */
    public function getActive() {
        $sql = "SELECT * FROM {$this->table} WHERE isActive = 1 ORDER BY subjectName";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get subject by code
     */
    public function getByCode($subjectCode) {
        $sql = "SELECT * FROM {$this->table} WHERE subjectCode = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$subjectCode]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Check if subject code exists (for validation)
     */
    public function codeExists($subjectCode, $excludeID = null) {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE subjectCode = ?";
        $params = [$subjectCode];
        
        if ($excludeID) {
            $sql .= " AND {$this->primaryKey} != ?";
            $params[] = $excludeID;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Toggle subject active status
     */
    public function toggleActive($id) {
        $sql = "UPDATE {$this->table} SET isActive = NOT isActive WHERE {$this->primaryKey} = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id]);
    }
    
    /**
     * Get subjects with usage statistics
     */
    public function getAllWithStats() {
        $sql = "SELECT s.*, 
                COUNT(DISTINCT g.gradeID) as totalGrades,
                COUNT(DISTINCT t.timetableID) as totalTimetableEntries
                FROM {$this->table} s
                LEFT JOIN Grades g ON s.subjectID = g.subjectID
                LEFT JOIN Timetable t ON s.subjectID = t.subjectID
                GROUP BY s.subjectID
                ORDER BY s.subjectName";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
