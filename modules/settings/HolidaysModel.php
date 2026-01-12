<?php
require_once 'includes/BaseModel.php';

class HolidaysModel extends BaseModel {
    
    /**
     * Get all holidays
     */
    public function getAllHolidays($academicYear = null) {
        $sql = "SELECT * FROM holidays";
        $params = [];
        
        if ($academicYear) {
            $sql .= " WHERE academicYear = ?";
            $params[] = $academicYear;
        }
        
        $sql .= " ORDER BY startDate";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Get holiday by ID
     */
    public function getById($id) {
        $sql = "SELECT * FROM holidays WHERE holidayID = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    /**
     * Create new holiday
     */
    public function create($data) {
        $sql = "INSERT INTO holidays (holidayName, holidayType, startDate, endDate, academicYear, description, createdAt) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['holidayName'],
            $data['holidayType'],
            $data['startDate'],
            $data['endDate'],
            $data['academicYear'],
            $data['description'] ?? null
        ]);
    }
    
    /**
     * Update holiday
     */
    public function update($id, $data) {
        $sql = "UPDATE holidays 
                SET holidayName = ?, holidayType = ?, startDate = ?, endDate = ?, 
                    academicYear = ?, description = ?
                WHERE holidayID = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['holidayName'],
            $data['holidayType'],
            $data['startDate'],
            $data['endDate'],
            $data['academicYear'],
            $data['description'] ?? null,
            $id
        ]);
    }
    
    /**
     * Delete holiday
     */
    public function delete($id) {
        $sql = "DELETE FROM holidays WHERE holidayID = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id]);
    }
    
    /**
     * Get upcoming holidays
     */
    public function getUpcoming($limit = 5) {
        $sql = "SELECT * FROM holidays 
                WHERE startDate >= CURDATE() 
                ORDER BY startDate 
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get holidays by type
     */
    public function getByType($type, $academicYear = null) {
        $sql = "SELECT * FROM holidays WHERE holidayType = ?";
        $params = [$type];
        
        if ($academicYear) {
            $sql .= " AND academicYear = ?";
            $params[] = $academicYear;
        }
        
        $sql .= " ORDER BY startDate";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Check if date falls within a holiday
     */
    public function isHoliday($date) {
        $sql = "SELECT * FROM holidays 
                WHERE ? BETWEEN startDate AND endDate 
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$date]);
        return $stmt->fetch();
    }
    
    /**
     * Get holidays in date range
     */
    public function getInRange($startDate, $endDate) {
        $sql = "SELECT * FROM holidays 
                WHERE (startDate BETWEEN ? AND ?) 
                   OR (endDate BETWEEN ? AND ?) 
                   OR (startDate <= ? AND endDate >= ?)
                ORDER BY startDate";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$startDate, $endDate, $startDate, $endDate, $startDate, $endDate]);
        return $stmt->fetchAll();
    }
}
