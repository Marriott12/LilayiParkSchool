<?php
/**
 * Base Model Class
 * All models extend this class for common database operations
 */

class BaseModel {
    protected $db;
    protected $table;
    protected $primaryKey = 'id';
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Find record by ID
     */
    public function find($id) {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch();
    }
    
    /**
     * Alias for find() - Get record by ID
     */
    public function getById($id) {
        return $this->find($id);
    }
    
    /**
     * Get all records
     */
    public function all($orderBy = null, $limit = null, $offset = null) {
        $sql = "SELECT * FROM {$this->table}";
        
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }
        
        if ($limit) {
            $sql .= " LIMIT {$limit}";
            if ($offset) {
                $sql .= " OFFSET {$offset}";
            }
        }
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
    
    /**
     * Alias for all() - Get all records
     */
    public function getAll($orderBy = null, $limit = null, $offset = null) {
        return $this->all($orderBy, $limit, $offset);
    }
    
    /**
     * Create new record
     * Filters input data to actual table columns to avoid unknown-column errors
     */
    public function create($data) {
        // Determine actual columns for this table and filter $data
        try {
            $currentDb = $this->db->query('SELECT DATABASE()')->fetchColumn();
            $colStmt = $this->db->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?");
            $colStmt->execute([$currentDb, $this->table]);
            $cols = $colStmt->fetchAll(PDO::FETCH_COLUMN);
            if (!empty($cols)) {
                $colsLower = array_map('strtolower', $cols);
                $filtered = [];
                foreach ($data as $k => $v) {
                    if (in_array(strtolower($k), $colsLower, true)) {
                        $filtered[$k] = $v;
                    }
                }
                $data = $filtered;
            }
        } catch (Exception $e) {
            // If metadata query fails, continue with original data (best-effort)
            error_log('BaseModel:create - failed to read table columns: ' . $e->getMessage());
        }

        if (empty($data)) {
            throw new Exception('No valid data provided for insert');
        }

        $fields = array_keys($data);
        $placeholders = array_map(function($field) { return ":{$field}"; }, $fields);

        $sql = "INSERT INTO {$this->table} (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
        $stmt = $this->db->prepare($sql);

        foreach ($data as $key => $value) {
            $stmt->bindValue(":{$key}", $value);
        }

        $stmt->execute();

        // Try to get last insert ID (works for auto-increment)
        $lastId = $this->db->lastInsertId();

        // If lastInsertId returns 0, it means we're using triggers for ID generation
        // Try to get the ID from the inserted record using unique field
        if ($lastId == 0) {
            // Find a unique field to query by (prefer email, then other unique fields)
            $uniqueField = null;
            if (isset($data['email'])) {
                $uniqueField = 'email';
            } elseif (isset($data['NRC'])) {
                $uniqueField = 'NRC';
            } elseif (isset($data['SSN'])) {
                $uniqueField = 'SSN';
            } elseif (isset($data['phone'])) {
                $uniqueField = 'phone';
            }

            if ($uniqueField && isset($data[$uniqueField])) {
                $sql = "SELECT {$this->primaryKey} FROM {$this->table} WHERE {$uniqueField} = ? LIMIT 1";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$data[$uniqueField]]);
                $result = $stmt->fetch();
                if ($result) {
                    return $result[$this->primaryKey];
                }
            }

            // Fallback: try using multiple fields for Pupil table
            if ($this->table === 'Pupil' && isset($data['fName'], $data['lName'], $data['DoB'])) {
                $sql = "SELECT {$this->primaryKey} FROM {$this->table} 
                        WHERE fName = ? AND lName = ? AND DoB = ? 
                        ORDER BY {$this->primaryKey} DESC LIMIT 1";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$data['fName'], $data['lName'], $data['DoB']]);
                $result = $stmt->fetch();
                if ($result) {
                    return $result[$this->primaryKey];
                }
            }

            // Fallback: try using multiple fields for Payment table
            if ($this->table === 'Payment' && isset($data['pupilID'], $data['paymentDate'], $data['pmtAmt'])) {
                $sql = "SELECT {$this->primaryKey} FROM {$this->table} 
                        WHERE pupilID = ? AND paymentDate = ? AND pmtAmt = ? 
                        ORDER BY createdAt DESC LIMIT 1";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$data['pupilID'], $data['paymentDate'], $data['pmtAmt']]);
                $result = $stmt->fetch();
                if ($result) {
                    return $result[$this->primaryKey];
                }
            }
        }

        return $lastId;
    }
    
    /**
     * Update record
     */
    public function update($id, $data) {
        $fields = [];
        foreach (array_keys($data) as $field) {
            $fields[] = "{$field} = :{$field}";
        }
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . 
               " WHERE {$this->primaryKey} = :id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id);
        
        foreach ($data as $key => $value) {
            $stmt->bindValue(":{$key}", $value);
        }
        
        return $stmt->execute();
    }
    
    /**
     * Delete record
     */
    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
    
    /**
     * Find records by condition
     */
    public function where($conditions, $orderBy = null, $limit = null) {
        $whereClauses = [];
        foreach (array_keys($conditions) as $field) {
            $whereClauses[] = "{$field} = :{$field}";
        }
        
        $sql = "SELECT * FROM {$this->table} WHERE " . implode(' AND ', $whereClauses);
        
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }
        
        if ($limit) {
            $sql .= " LIMIT {$limit}";
        }
        
        $stmt = $this->db->prepare($sql);
        
        foreach ($conditions as $key => $value) {
            $stmt->bindValue(":{$key}", $value);
        }
        
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Count records
     */
    public function count($conditions = []) {
        $sql = "SELECT COUNT(*) as total FROM {$this->table}";
        
        if (!empty($conditions)) {
            $whereClauses = [];
            foreach (array_keys($conditions) as $field) {
                $whereClauses[] = "{$field} = :{$field}";
            }
            $sql .= " WHERE " . implode(' AND ', $whereClauses);
        }
        
        $stmt = $this->db->prepare($sql);
        
        foreach ($conditions as $key => $value) {
            $stmt->bindValue(":{$key}", $value);
        }
        
        $stmt->execute();
        $result = $stmt->fetch();
        return $result['total'] ?? 0;
    }
    
    /**
     * Execute custom query
     */
    public function query($sql, $params = []) {
        $stmt = $this->db->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
