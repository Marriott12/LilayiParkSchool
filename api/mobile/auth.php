<?php
/**
 * Mobile API - Authentication
 * Handles login and token generation
 */

require_once '../includes/bootstrap.php';
require_once '../includes/MobileAPI.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    MobileAPI::error('Method not allowed', 405);
}

$data = MobileAPI::getRequestBody();

// Validate input
$errors = MobileAPI::validateRequired($data, ['username', 'password']);
if (!empty($errors)) {
    MobileAPI::error('Validation failed', 400, $errors);
}

$username = $data['username'];
$password = $data['password'];

// Authenticate user
$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password'])) {
    MobileAPI::error('Invalid username or password', 401);
}

// Check if account is active
if ($user['status'] !== 'active') {
    MobileAPI::error('Your account is inactive. Please contact administration.', 403);
}

// Generate API token
$token = bin2hex(random_bytes(32));
$expires = date('Y-m-d H:i:s', strtotime('+30 days'));

$stmt = $db->prepare("UPDATE users SET apiToken = ?, apiTokenExpires = ? WHERE userID = ?");
$stmt->execute([$token, $expires, $user['userID']]);

// Get user role
$stmt = $db->prepare("
    SELECT r.roleName, r.roleID
    FROM userroles ur
    JOIN roles r ON ur.roleID = r.roleID
    WHERE ur.userID = ?
    LIMIT 1
");
$stmt->execute([$user['userID']]);
$role = $stmt->fetch();

// Get user profile based on role
$profile = null;
if ($role) {
    switch ($role['roleName']) {
        case 'teacher':
            $stmt = $db->prepare("SELECT * FROM teacher WHERE email = ?");
            $stmt->execute([$user['email']]);
            $profile = $stmt->fetch();
            break;
        case 'parent':
            $stmt = $db->prepare("SELECT * FROM parent WHERE email1 = ? OR email2 = ?");
            $stmt->execute([$user['email'], $user['email']]);
            $profile = $stmt->fetch();
            break;
    }
}

MobileAPI::success([
    'token' => $token,
    'expiresAt' => $expires,
    'user' => [
        'userID' => $user['userID'],
        'username' => $user['username'],
        'email' => $user['email'],
        'role' => $role['roleName'] ?? null,
        'profile' => $profile
    ]
], 'Login successful');
