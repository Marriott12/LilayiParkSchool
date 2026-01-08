<?php
/**
 * Pupil Model
 */

class PupilModel extends BaseModel {
    protected $table = 'Pupil';
    protected $primaryKey = 'pupilID';
    
    /**
     * Get pupil with parent information
     */
    public function getPupilWithParent($pupilID) {
        $sql = "SELECT p.*, pr.fName as parentFirstName, pr.lName as parentLastName, 
                       pr.phoneNumber as parentPhone, pr.email as parentEmail
                FROM {$this->table} p
                LEFT JOIN Parent pr ON p.parentID = pr.parentID
                WHERE p.pupilID = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$pupilID]);
        return $stmt->fetch();
    }
    
    /**
     * Get all pupils with parent info
     */
    public function getAllWithParents($limit = null, $offset = null) {
        $sql = "SELECT p.*, pr.fName as parentFirstName, pr.lName as parentLastName
                FROM {$this->table} p
                LEFT JOIN Parent pr ON p.parentID = pr.parentID
                ORDER BY p.fName, p.lName";
        
        if ($limit !== null) {
            $sql .= " LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Get pupils by class
     */
    public function getPupilsByClass($classID) {
        $sql = "SELECT p.* FROM {$this->table} p
                INNER JOIN PupilClass pc ON p.pupilID = pc.pupilID
                WHERE pc.classID = ?
                ORDER BY p.fName, p.lName";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$classID]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get pupils by parent
     */
    public function getPupilsByParent($parentID) {
        return $this->where(['parentID' => $parentID]);
    }
    
    /**
     * Search pupils
     */
    public function search($term) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE fName LIKE ? OR lName LIKE ? OR studentNumber LIKE ?
                ORDER BY fName, lName";
        $searchTerm = "%{$term}%";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
        return $stmt->fetchAll();
    }
}
