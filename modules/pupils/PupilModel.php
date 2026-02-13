<?php
/**
 * Pupil Model
 */

class PupilModel extends BaseModel {
    protected $table = 'Pupil';
    protected $primaryKey = 'pupilID';

    /**
     * Get a single pupil with class information
     */
    public function getPupilWithParent($pupilID) {
        $sql = "SELECT p.*, c.className, c.classID, CONCAT(t.fName, ' ', t.lName) AS teacherName
            FROM {$this->table} p
            LEFT JOIN Pupil_Class pc ON p.pupilID = pc.pupilID
            LEFT JOIN Class c ON pc.classID = c.classID
            LEFT JOIN Teacher t ON c.teacherID = t.teacherID
            WHERE p.pupilID = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$pupilID]);
        return $stmt->fetch();
    }

    /**
     * Get all pupils (supports optional limit/offset)
     */
    public function getAllWithParents($limit = null, $offset = null) {
        $sql = "SELECT p.* FROM {$this->table} p ORDER BY p.fName, p.lName";
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
                INNER JOIN Pupil_Class pc ON p.pupilID = pc.pupilID
                WHERE pc.classID = ?
                ORDER BY p.fName, p.lName";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$classID]);
        return $stmt->fetchAll();
    }

    /**
     * Get pupils by parent (uses parentID column)
     */
    public function getPupilsByParent($parentID) {
        return $this->where(['parentID' => $parentID]);
    }

    /**
     * Search pupils with optional filters
     */
    public function search($term, $filters = []) {
        $where = [];
        $params = [];
        $joins = "";

        if (!empty($term)) {
            // Search across realistic Pupil columns only
            $where[] = "(
                p.fName LIKE ? OR
                p.lName LIKE ? OR
                p.pupilID LIKE ? OR
                p.parent1 LIKE ? OR
                p.parent2 LIKE ? OR
                p.phone LIKE ? OR
                p.parentEmail LIKE ? OR
                p.homeAddress LIKE ?
            )";
            $searchTerm = "%{$term}%";
            // push the same param for each placeholder
            for ($i = 0; $i < 8; $i++) {
                $params[] = $searchTerm;
            }
        }

        if (!empty($filters['classID'])) {
            $joins = " INNER JOIN Pupil_Class pc ON p.pupilID = pc.pupilID";
            $where[] = "pc.classID = ?";
            $params[] = $filters['classID'];
        }

        if (!empty($filters['gender'])) {
            $where[] = "p.gender = ?";
            $params[] = $filters['gender'];
        }

        if (isset($filters['hasParent'])) {
            if ($filters['hasParent']) {
                $where[] = "(p.parentID IS NOT NULL OR p.parent1 IS NOT NULL OR p.phone IS NOT NULL)";
            } else {
                $where[] = "(p.parentID IS NULL AND p.parent1 IS NULL AND p.phone IS NULL)";
            }
        }

        $sql = "SELECT DISTINCT p.* FROM {$this->table} p" . $joins;
        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        $sql .= " ORDER BY p.fName, p.lName";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Get pupils by array of IDs
     */
    public function getByIDs($pupilIDs, $limit = null, $offset = null) {
        if (empty($pupilIDs)) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($pupilIDs), '?'));
        $sql = "SELECT p.* FROM {$this->table} p WHERE p.pupilID IN ({$placeholders}) ORDER BY p.fName, p.lName";
        if ($limit !== null) {
            $sql .= " LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($pupilIDs);
        return $stmt->fetchAll();
    }

    /**
     * Find a pupil by parent and basic details (used to avoid duplicates)
     */
    /**
     * Find a pupil by parent identifier (parentID, phone, parent1 or parent2) and basic details
     * If $parentIdentifier is empty, search by name + DoB only.
     */
    public function findByParentAndDetails($parentIdentifier, $fName, $lName, $DoB) {
        if (empty($parentIdentifier)) {
            $sql = "SELECT pupilID FROM {$this->table} WHERE fName = ? AND lName = ? AND DoB = ? LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$fName, $lName, $DoB]);
            return $stmt->fetch();
        }

        $sql = "SELECT pupilID FROM {$this->table} WHERE (parentID = ? OR phone = ? OR parent1 = ? OR parent2 = ?) AND fName = ? AND lName = ? AND DoB = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$parentIdentifier, $parentIdentifier, $parentIdentifier, $parentIdentifier, $fName, $lName, $DoB]);
        return $stmt->fetch();
    }
}