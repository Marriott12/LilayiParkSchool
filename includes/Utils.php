<?php
/**
 * Utility Helper Functions
 */

class Utils {
    /**
     * Sanitize input data
     */
    public static function sanitize($data) {
        if (is_array($data)) {
            return array_map([self::class, 'sanitize'], $data);
        }
        return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Validate email
     */
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }
    
    /**
     * Generate random token
     */
    public static function generateToken($length = 32) {
        return bin2hex(random_bytes($length));
    }
    
    /**
     * Hash password
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_HASH_ALGO, ['cost' => PASSWORD_HASH_COST]);
    }
    
    /**
     * Verify password
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    /**
     * Redirect to URL
     */
    public static function redirect($url) {
        header("Location: {$url}");
        exit;
    }
    
    /**
     * JSON response
     */
    public static function jsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
    
    /**
     * Validate required fields
     */
    public static function validateRequired($data, $requiredFields) {
        $errors = [];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty(trim($data[$field]))) {
                $errors[] = ucfirst(str_replace('_', ' ', $field)) . " is required";
            }
        }
        return $errors;
    }
    
    /**
     * Upload file
     */
    public static function uploadFile($file, $allowedTypes = ['jpg', 'jpeg', 'png', 'pdf'], $maxSize = MAX_FILE_SIZE) {
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => 'File upload failed'];
        }
        
        if ($file['size'] > $maxSize) {
            return ['success' => false, 'error' => 'File size exceeds maximum allowed'];
        }
        
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedTypes)) {
            return ['success' => false, 'error' => 'File type not allowed'];
        }
        
        $filename = uniqid() . '_' . time() . '.' . $extension;
        $uploadPath = UPLOAD_DIR . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            return ['success' => true, 'filename' => $filename];
        }
        
        return ['success' => false, 'error' => 'Failed to move uploaded file'];
    }
    
    /**
     * Format date for display
     */
    public static function formatDate($date, $format = 'd M Y') {
        if (!$date) return '';
        return date($format, strtotime($date));
    }
    
    /**
     * Format currency
     */
    public static function formatCurrency($amount, $currency = 'K') {
        return $currency . number_format($amount, 2);
    }
    
    /**
     * Get request method
     */
    public static function getRequestMethod() {
        return $_SERVER['REQUEST_METHOD'];
    }
    
    /**
     * Check if POST request
     */
    public static function isPost() {
        return self::getRequestMethod() === 'POST';
    }
    
    /**
     * Check if GET request
     */
    public static function isGet() {
        return self::getRequestMethod() === 'GET';
    }
    
    /**
     * Get POST data
     */
    public static function getPostData() {
        return $_POST;
    }
    
    /**
     * Get GET data
     */
    public static function getGetData() {
        return $_GET;
    }
}
