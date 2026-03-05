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
        
        if ($limit !== null) {
            $sql .= " LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
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
        // Use SettingsModel to determine the current term/year
        require_once __DIR__ . '/../settings/SettingsModel.php';
        $settingsModel = new SettingsModel();
        $currentTerm = $settingsModel->getCurrentTerm() ?? 1;
        $currentYear = $settingsModel->getCurrentYear() ?? date('Y');

        $sql = "SELECT * FROM {$this->table} WHERE term = ? AND year = ? ORDER BY classID";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$currentTerm, $currentYear]);
        return $stmt->fetchAll();
    }

    /**
     * Get the fee row for a specific class for the current term/year.
     * Returns null if not found.
     */
    public function getCurrentFeeForClass($classID) {
        require_once __DIR__ . '/../settings/SettingsModel.php';
        $settingsModel = new SettingsModel();
        $currentTermRaw = $settingsModel->getCurrentTerm();
        $currentYear = $settingsModel->getCurrentYear() ?? date('Y');

        // Build term candidates to handle different stored formats (numeric, 'Term 1', 'term1', etc.)
        $candidates = [];
        if ($currentTermRaw !== null) {
            $candidates[] = $currentTermRaw;
            if (is_numeric($currentTermRaw)) {
                $candidates[] = (int)$currentTermRaw;
                $candidates[] = 'Term ' . (int)$currentTermRaw;
            } else {
                // extract number if present
                if (preg_match('/(\d+)/', $currentTermRaw, $m)) {
                    $n = (int)$m[1];
                    $candidates[] = $n;
                    $candidates[] = 'Term ' . $n;
                }
            }
        } else {
            $candidates[] = 1;
            $candidates[] = 'Term 1';
        }

        // Try each candidate term value
        foreach ($candidates as $termCandidate) {
            $sql = "SELECT feeID, feeAmt, term, year FROM {$this->table} WHERE classID = ? AND year = ? AND term = ? LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$classID, $currentYear, $termCandidate]);
            $row = $stmt->fetch();
            if ($row) return $row;
        }

        // Fallback: return latest fee for the year if exact term not found
        $sql = "SELECT feeID, feeAmt, term, year FROM {$this->table} WHERE classID = ? AND year = ? ORDER BY term DESC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$classID, $currentYear]);
        return $stmt->fetch();
    }
}
