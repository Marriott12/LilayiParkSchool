<?php
/**
 * Mobile API Helper
 * Provides common functions for mobile API endpoints
 */

class MobileAPI {
    
    /**
     * Send JSON response
     */
    public static function respond($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_PRETTY_PRINT);
        exit;
    }
    
    /**
     * Send success response
     */
    public static function success($data, $message = 'Success') {
        self::respond([
            'success' => true,
            'message' => $message,
            'data' => $data
        ]);
    }
    
    /**
     * Send error response
     */
    public static function error($message, $statusCode = 400, $errors = null) {
        $response = [
            'success' => false,
            'message' => $message
        ];
        
        if ($errors !== null) {
            $response['errors'] = $errors;
        }
        
        self::respond($response, $statusCode);
    }
    
    /**
     * Get request body as JSON
     */
    public static function getRequestBody() {
        $input = file_get_contents('php://input');
        return json_decode($input, true);
    }
    
    /**
     * Validate required fields
     */
    public static function validateRequired($data, $requiredFields) {
        $errors = [];
        
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $errors[$field] = "The $field field is required";
            }
        }
        
        return $errors;
    }
    
    /**
     * Authenticate user via Bearer token
     * Returns user data or false
     */
    public static function authenticate() {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;
        
        if (!$authHeader || !preg_match('/Bearer\s+(.+)/i', $authHeader, $matches)) {
            return false;
        }
        
        $token = $matches[1];
        
        // Validate token against database
        require_once __DIR__ . '/../config/database.php';
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->prepare("
            SELECT u.*, r.roleName
            FROM users u
            LEFT JOIN userroles ur ON u.userID = ur.userID
            LEFT JOIN roles r ON ur.roleID = r.roleID
            WHERE u.apiToken = ? AND u.apiTokenExpires > NOW()
            LIMIT 1
        ");
        $stmt->execute([$token]);
        $user = $stmt->fetch();
        
        return $user ?: false;
    }
    
    /**
     * Require authentication
     * Returns authenticated user or sends error response
     */
    public static function requireAuth() {
        $user = self::authenticate();
        
        if (!$user) {
            self::error('Unauthorized. Please login.', 401);
        }
        
        return $user;
    }
    
    /**
     * Check if user has permission
     */
    public static function hasPermission($userID, $permission) {
        require_once __DIR__ . '/../modules/roles/RolesModel.php';
        $rolesModel = new RolesModel();
        return $rolesModel->userHasPermission($userID, $permission);
    }
    
    /**
     * Require specific permission
     */
    public static function requirePermission($userID, $permission) {
        if (!self::hasPermission($userID, $permission)) {
            self::error('You do not have permission to perform this action.', 403);
        }
    }
    
    /**
     * Paginate results
     */
    public static function paginate($data, $page = 1, $perPage = 20) {
        $total = count($data);
        $totalPages = ceil($total / $perPage);
        $offset = ($page - 1) * $perPage;
        
        $items = array_slice($data, $offset, $perPage);
        
        return [
            'items' => $items,
            'pagination' => [
                'currentPage' => (int)$page,
                'perPage' => (int)$perPage,
                'totalItems' => $total,
                'totalPages' => $totalPages,
                'hasNext' => $page < $totalPages,
                'hasPrev' => $page > 1
            ]
        ];
    }
}
