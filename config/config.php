<?php
/**
 * Main Configuration File
 */

// Error Reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
date_default_timezone_set('Africa/Lusaka');

// Session Configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS

// Application Settings
define('APP_NAME', 'Lilayi Park School Management System');
define('APP_VERSION', '1.0.0');
define('APP_ROOT', dirname(__DIR__));
define('UPLOAD_DIR', APP_ROOT . '/uploads/');
define('MAX_FILE_SIZE', 5242880); // 5MB

// URL Configuration
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$base_path = dirname($_SERVER['SCRIPT_NAME']);
define('BASE_URL', $protocol . '://' . $host . $base_path);

// Security Settings
define('PASSWORD_HASH_ALGO', PASSWORD_BCRYPT);
define('PASSWORD_HASH_COST', 12);

// Session timeout (30 minutes)
define('SESSION_TIMEOUT', 1800);

// Load environment variables if .env file exists
if (file_exists(APP_ROOT . '/.env')) {
    $env = parse_ini_file(APP_ROOT . '/.env');
    foreach ($env as $key => $value) {
        $_ENV[$key] = $value;
    }
}

// RBAC Roles
define('ROLE_ADMIN', 'admin');
define('ROLE_TEACHER', 'teacher');
define('ROLE_PARENT', 'parent');

// RBAC Permissions
$GLOBALS['permissions'] = [
    ROLE_ADMIN => [
        'pupils' => ['create', 'read', 'update', 'delete'],
        'teachers' => ['create', 'read', 'update', 'delete'],
        'parents' => ['create', 'read', 'update', 'delete'],
        'classes' => ['create', 'read', 'update', 'delete'],
        'fees' => ['create', 'read', 'update', 'delete'],
        'payments' => ['create', 'read', 'update', 'delete'],
        'attendance' => ['create', 'read', 'update', 'delete'],
        'reports' => ['read', 'export'],
        'users' => ['create', 'read', 'update', 'delete']
    ],
    ROLE_TEACHER => [
        'pupils' => ['read'],
        'classes' => ['read'],
        'attendance' => ['create', 'read', 'update'],
        'reports' => ['read']
    ],
    ROLE_PARENT => [
        'pupils' => ['read'], // Only their own children
        'payments' => ['read'], // Only their own payments
        'reports' => ['read'] // Only their children's reports
    ]
];
