<?php
/**
 * Announcements Model
 * Manages school announcements and notices
 */

class AnnouncementsModel extends BaseModel {
    protected $table = 'Announcements';
    protected $primaryKey = 'announcementID';
    
    /**
     * Get announcement with author info
     */
    public function getAnnouncementWithAuthor($announcementID) {
        $sql = "SELECT a.*, u.username as authorName
                FROM {$this->table} a
                LEFT JOIN Users u ON a.createdBy = u.userID
                WHERE a.announcementID = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$announcementID]);
        return $stmt->fetch();
    }
    
    /**
     * Get all announcements with author info
     */
    public function getAllWithAuthors($limit = null, $offset = null) {
        $sql = "SELECT a.*, u.username as authorName
                FROM {$this->table} a
                LEFT JOIN Users u ON a.createdBy = u.userID
                ORDER BY a.isPinned DESC, a.createdAt DESC";
        
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
     * Get active announcements (published and not expired)
     */
    public function getActiveAnnouncements($limit = null) {
        $sql = "SELECT a.*, u.username as authorName
                FROM {$this->table} a
                LEFT JOIN Users u ON a.createdBy = u.userID
                WHERE a.status = 'published'
                AND (a.expiryDate IS NULL OR a.expiryDate >= CURDATE())
                ORDER BY a.isPinned DESC, a.createdAt DESC";
        
        if ($limit) {
            $sql .= " LIMIT " . (int)$limit;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Get pinned announcements
     */
    public function getPinnedAnnouncements() {
        $sql = "SELECT a.*, u.username as authorName
                FROM {$this->table} a
                LEFT JOIN Users u ON a.createdBy = u.userID
                WHERE a.isPinned = 1 AND a.status = 'published'
                ORDER BY a.createdAt DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Get announcements by target audience
     */
    public function getByAudience($audience, $limit = null) {
        $sql = "SELECT a.*, u.username as authorName
                FROM {$this->table} a
                LEFT JOIN Users u ON a.createdBy = u.userID
                WHERE (a.targetAudience = ? OR a.targetAudience = 'all')
                AND a.status = 'published'
                AND (a.expiryDate IS NULL OR a.expiryDate >= CURDATE())
                ORDER BY a.isPinned DESC, a.createdAt DESC";
        
        if ($limit) {
            $sql .= " LIMIT " . (int)$limit;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$audience]);
        return $stmt->fetchAll();
    }
    
    /**
     * Toggle pin status
     */
    public function togglePin($announcementID) {
        $announcement = $this->getById($announcementID);
        $newStatus = $announcement['isPinned'] ? 0 : 1;
        return $this->update($announcementID, ['isPinned' => $newStatus]);
    }
    
    /**
     * Publish announcement
     */
    public function publish($announcementID) {
        return $this->update($announcementID, [
            'status' => 'published',
            'publishedAt' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Unpublish/archive announcement
     */
    public function unpublish($announcementID) {
        return $this->update($announcementID, ['status' => 'draft']);
    }
    
    /**
     * Get announcements count by status
     */
    public function getCountByStatus() {
        $sql = "SELECT 
                COUNT(CASE WHEN status = 'published' THEN 1 END) as published,
                COUNT(CASE WHEN status = 'draft' THEN 1 END) as draft,
                COUNT(CASE WHEN isPinned = 1 THEN 1 END) as pinned
                FROM {$this->table}";
        $stmt = $this->db->query($sql);
        return $stmt->fetch();
    }
    
    /**
     * Search announcements
     */
    public function search($term) {
        $sql = "SELECT a.*, u.username as authorName
                FROM {$this->table} a
                LEFT JOIN Users u ON a.createdBy = u.userID
                WHERE a.title LIKE ? OR a.content LIKE ?
                ORDER BY a.createdAt DESC";
        $searchTerm = "%{$term}%";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$searchTerm, $searchTerm]);
        return $stmt->fetchAll();
    }
}
