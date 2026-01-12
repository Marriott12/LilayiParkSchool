<?php
/**
 * Settings Model
 * Manages system settings and configuration
 */

class SettingsModel extends BaseModel {
    protected $table = 'settings';
    protected $primaryKey = 'settingID';
    
    /**
     * Get setting by key
     */
    public function getSetting($key, $default = null) {
        $sql = "SELECT settingValue FROM {$this->table} WHERE settingKey = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$key]);
        $result = $stmt->fetch();
        return $result ? $result['settingValue'] : $default;
    }
    
    /**
     * Set or update setting
     */
    public function setSetting($key, $value, $category = 'general') {
        $existing = $this->where(['settingKey' => $key]);
        
        if (!empty($existing)) {
            // Update existing
            $sql = "UPDATE {$this->table} SET settingValue = ?, updatedAt = NOW() WHERE settingKey = ?";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([$value, $key]);
        } else {
            // Create new
            $result = $this->create([
                'settingKey' => $key,
                'settingValue' => $value,
                'category' => $category
            ]);
        }
        
        // Clear cache when settings change
        Session::clearSettingsCache();
        return $result;
    }
    
    /**
     * Get all settings as key-value array
     */
    public function getAllSettings() {
        // Check cache first
        $cached = Session::getCachedSettings();
        if ($cached !== null) {
            return $cached;
        }
        
        $sql = "SELECT settingKey, settingValue FROM {$this->table}";
        $stmt = $this->db->query($sql);
        $results = $stmt->fetchAll();
        
        $settings = [];
        foreach ($results as $row) {
            $settings[$row['settingKey']] = $row['settingValue'];
        }
        
        // Cache for future requests
        Session::cacheSettings($settings);
        
        return $settings;
    }
    
    /**
     * Get settings by category
     */
    public function getByCategory($category) {
        $sql = "SELECT * FROM {$this->table} WHERE category = ? ORDER BY settingKey";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$category]);
        return $stmt->fetchAll();
    }
    
    /**
     * Initialize default settings
     */
    public function initializeDefaults() {
        $defaults = [
            ['key' => 'school_name', 'value' => 'Lilayi Park School', 'category' => 'school'],
            ['key' => 'school_address', 'value' => 'Lilayi, Lusaka, Zambia', 'category' => 'school'],
            ['key' => 'school_phone', 'value' => '', 'category' => 'school'],
            ['key' => 'school_email', 'value' => 'info@lilayiparkschool.edu.zm', 'category' => 'school'],
            ['key' => 'current_term', 'value' => '1', 'category' => 'academic'],
            ['key' => 'current_year', 'value' => date('Y'), 'category' => 'academic'],
            ['key' => 'currency', 'value' => 'ZMW', 'category' => 'financial'],
            ['key' => 'late_fee_penalty', 'value' => '0', 'category' => 'financial'],
            ['key' => 'attendance_threshold', 'value' => '75', 'category' => 'academic'],
        ];
        
        foreach ($defaults as $setting) {
            $this->setSetting($setting['key'], $setting['value'], $setting['category']);
        }
        
        return true;
    }
    
    /**
     * Get current academic term
     */
    public function getCurrentTerm() {
        return $this->getSetting('current_term', 1);
    }
    
    /**
     * Get current academic year
     */
    public function getCurrentYear() {
        return $this->getSetting('current_year', date('Y'));
    }
}
