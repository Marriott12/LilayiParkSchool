<?php
/**
 * Authentication Controller
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

class AuthController {
    private $authModel;
    
    public function __construct() {
        $this->authModel = new AuthModel();
    }
    
    /**
     * Handle login
     */
    public function login() {
        if (Utils::isPost()) {
            $username = Utils::sanitize($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            
            // Validate input
            if (empty($username) || empty($password)) {
                return ['success' => false, 'message' => 'Username and password are required'];
            }
            
            // Verify credentials
            $user = $this->authModel->verifyCredentials($username, $password);
            
            if (!$user) {
                return ['success' => false, 'message' => 'Invalid credentials'];
            }
            
            // Set session data
            Session::set('user_id', $user['userID']);
            Session::set('user_name', $user['firstName'] . ' ' . $user['lastName']);
            Session::set('user_email', $user['email']);
            Session::set('user_role', $user['role']);
            Session::set('user_username', $user['username']);
            
            // Update last login
            $this->authModel->updateLastLogin($user['userID']);
            
            return [
                'success' => true,
                'message' => 'Login successful',
                'user' => [
                    'id' => $user['userID'],
                    'name' => $user['firstName'] . ' ' . $user['lastName'],
                    'email' => $user['email'],
                    'role' => $user['role']
                ]
            ];
        }
        
        return ['success' => false, 'message' => 'Invalid request method'];
    }
    
    /**
     * Handle logout
     */
    public function logout() {
        Session::destroy();
        return ['success' => true, 'message' => 'Logged out successfully'];
    }
    
    /**
     * Get current user
     */
    public function getCurrentUser() {
        if (!Session::isLoggedIn()) {
            http_response_code(401);
            return ['success' => false, 'message' => 'Not authenticated'];
        }
        
        return [
            'success' => true,
            'user' => Session::getUserData()
        ];
    }
    
    /**
     * Change password
     */
    public function changePassword() {
        RBAC::requireAuth();
        
        if (Utils::isPost()) {
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            
            // Validate
            if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                return ['success' => false, 'message' => 'All fields are required'];
            }
            
            if ($newPassword !== $confirmPassword) {
                return ['success' => false, 'message' => 'New passwords do not match'];
            }
            
            if (strlen($newPassword) < 6) {
                return ['success' => false, 'message' => 'Password must be at least 6 characters'];
            }
            
            // Get current user
            $userId = Session::getUserId();
            $user = $this->authModel->find($userId);
            
            // Verify current password
            if (!Utils::verifyPassword($currentPassword, $user['password'])) {
                return ['success' => false, 'message' => 'Current password is incorrect'];
            }
            
            // Update password
            $this->authModel->updateUser($userId, ['password' => $newPassword]);
            
            return ['success' => true, 'message' => 'Password changed successfully'];
        }
        
        return ['success' => false, 'message' => 'Invalid request method'];
    }
}

// Handle API requests
if (basename($_SERVER['PHP_SELF']) === 'api.php') {
    $controller = new AuthController();
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'login':
            $result = $controller->login();
            break;
        case 'logout':
            $result = $controller->logout();
            break;
        case 'current':
            $result = $controller->getCurrentUser();
            break;
        case 'change-password':
            $result = $controller->changePassword();
            break;
        default:
            $result = ['success' => false, 'message' => 'Invalid action'];
    }
    
    Utils::jsonResponse($result);
}
