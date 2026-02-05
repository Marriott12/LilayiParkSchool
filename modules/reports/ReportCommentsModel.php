<?php
require_once __DIR__ . '/../../includes/BaseModel.php';

class ReportCommentsModel extends BaseModel {
    protected $table = 'reportcomments';
    protected $primaryKey = 'commentID';
    
    /**
     * Get comment for a pupil in a specific term/year
     */
    public function getByPupilTermYear($pupilID, $term, $academicYear) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE pupilID = ? AND term = ? AND academicYear = ? 
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$pupilID, $term, $academicYear]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get all comments for a class in a term
     */
    public function getByClassTermYear($classID, $term, $academicYear) {
        $sql = "SELECT rc.*, 
                p.firstName, p.lastName, p.admissionNumber,
                u1.username as createdByUser,
                u2.username as updatedByUser
                FROM {$this->table} rc
                INNER JOIN pupil p ON rc.pupilID = p.pupilID
                LEFT JOIN users u1 ON rc.createdBy = u1.userID
                LEFT JOIN users u2 ON rc.updatedBy = u2.userID
                WHERE rc.classID = ? AND rc.term = ? AND rc.academicYear = ?
                ORDER BY p.lastName, p.firstName";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$classID, $term, $academicYear]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Create or update comment
     */
    public function createOrUpdate($data) {
        $existing = $this->getByPupilTermYear($data['pupilID'], $data['term'], $data['academicYear']);
        
        if ($existing) {
            // Update existing
            $data['updatedBy'] = $data['createdBy'];
            unset($data['createdBy']);
            return $this->update($existing[$this->primaryKey], $data);
        } else {
            // Create new
            return $this->create($data);
        }
    }
    
    /**
     * Bulk create/update comments for multiple pupils
     */
    public function bulkCreateOrUpdate($commentsData) {
        $success = 0;
        $errors = [];
        
        foreach ($commentsData as $data) {
            try {
                $this->createOrUpdate($data);
                $success++;
            } catch (Exception $e) {
                $errors[] = "Error for pupil {$data['pupilID']}: " . $e->getMessage();
            }
        }
        
        return [
            'success' => $success,
            'errors' => $errors
        ];
    }
}
