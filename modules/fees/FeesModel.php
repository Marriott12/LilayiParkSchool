<?php
/**
 * Fees Model
 */

class FeesModel extends BaseModel {
    protected $table = 'Fees';
    protected $primaryKey = 'feeID';
    
    /**
     * Get fees by class
     */
    public function getFeesByClass($classID) {
        return $this->where(['classID' => $classID]);
    }
    
    /**
     * Get all fees with class info
     */
    public function getAllWithClass($limit = null, $offset = null) {
        $sql = "SELECT f.*, c.className
                FROM {$this->table} f
                LEFT JOIN Class c ON f.classID = c.classID
                ORDER BY f.term, c.className";
        
        if ($limit) {
            $sql .= " LIMIT " . (int)$limit;
            if ($offset) {
                $sql .= " OFFSET " . (int)$offset;
            }
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Get fee structure for a term
     */
    public function getFeesByTerm($term) {
        return $this->where(['term' => $term]);
    }
    
    /**
     * Get active fees
     */
    public function getActiveFees() {
        $currentTerm = 1; // This should be dynamic based on current term
        return $this->where(['term' => $currentTerm]);
    }
}
