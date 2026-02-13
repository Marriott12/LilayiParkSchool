<?php
/**
 * Payment Model
 */

class PaymentModel extends BaseModel {
    protected $table = 'Payment';
    protected $primaryKey = 'payID';
    
    /**
     * Get payment with pupil and parent info
     */
    public function getPaymentWithDetails($payID) {
        $sql = "SELECT p.*, pu.fName as pupilFirstName, pu.lName as pupilLastName,
                       pr.fName as parentFirstName, pr.lName as parentLastName,
                       f.term, f.feeAmt
                FROM {$this->table} p
                LEFT JOIN Pupil pu ON p.pupilID = pu.pupilID
                LEFT JOIN Parent pr ON pu.parentID = pr.parentID
                LEFT JOIN Fees f ON p.feeID = f.feeID
                WHERE p.payID = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$payID]);
        return $stmt->fetch();
    }
    
    /**
     * Get all payments with details
     */
    public function getAllWithDetails($limit = null, $offset = null) {
        $sql = "SELECT p.*, pu.fName as pupilFirstName, pu.lName as pupilLastName,
                       pr.fName as parentFirstName, pr.lName as parentLastName,
                       f.term, f.feeAmt
                FROM {$this->table} p
                LEFT JOIN Pupil pu ON p.pupilID = pu.pupilID
                LEFT JOIN Parent pr ON pu.parentID = pr.parentID
                LEFT JOIN Fees f ON p.feeID = f.feeID
                ORDER BY p.paymentDate DESC, p.payID DESC";
        
        if ($limit !== null) {
            $sql .= " LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Get payments by pupil
     */
    public function getPaymentsByPupil($pupilID) {
        $sql = "SELECT p.*, f.term, f.feeAmt
                FROM {$this->table} p
                LEFT JOIN Fees f ON p.feeID = f.feeID
                WHERE p.pupilID = ?
                ORDER BY p.paymentDate DESC, p.payID DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$pupilID]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get payments by parent (through pupils)
     */
    public function getPaymentsByParent($parentID) {
        $sql = "SELECT p.*, pu.fName as pupilFirstName, pu.lName as pupilLastName,
                       f.term, f.feeAmt
                FROM {$this->table} p
                INNER JOIN Pupil pu ON p.pupilID = pu.pupilID
                LEFT JOIN Fees f ON p.feeID = f.feeID
                WHERE pu.parentID = ?
                ORDER BY p.paymentDate DESC, p.payID DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$parentID]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get total payments for a term
     */
    public function getTotalByTerm($term) {
        $sql = "SELECT SUM(p.pmtAmt) as total
                FROM {$this->table} p
                INNER JOIN Fees f ON p.feeID = f.feeID
                WHERE f.term = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$term]);
        $result = $stmt->fetch();
        return $result['total'] ?? 0;
    }
}
