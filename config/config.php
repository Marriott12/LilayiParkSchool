<?php
/**
 * Main Configuration File
 */

// Error Reporting (environment-aware)
$appEnv = $_ENV['APP_ENV'] ?? 'production';
if ($appEnv === 'development' || $appEnv === 'dev') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', dirname(__DIR__) . '/logs/php-errors.log');
}

// Timezone
date_default_timezone_set('Africa/Lusaka');

// Session Configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
// Automatically enable secure cookies if HTTPS is detected
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
           (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
           (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
ini_set('session.cookie_secure', $isHttps ? 1 : 0);

// Application Settings
define('APP_NAME', 'Lilayi Park School Management System');
define('APP_VERSION', '1.0.0');
define('APP_ROOT', dirname(__DIR__));
define('UPLOAD_DIR', APP_ROOT . '/uploads/');
define('MAX_FILE_SIZE', 5242880); // 5MB

// URL Configuration
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];

// Always detect and use the subdirectory for BASE_URL (works for local and production)
$base_path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
if ($base_path === '/' || $base_path === '\\' || $base_path === '.') {
    $base_path = '';
}
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
        'grades' => ['create', 'read', 'update', 'delete'],
        'examinations' => ['create', 'read', 'update', 'delete'],
        'timetable' => ['create', 'read', 'update', 'delete'],
        'reports' => ['read', 'export'],
        'users' => ['create', 'read', 'update', 'delete'],
        'subjects' => ['create', 'read', 'update', 'delete'],
        'announcements' => ['create', 'read', 'update', 'delete'],
        'settings' => ['read', 'update']
    ],
    ROLE_TEACHER => [
        'pupils' => ['read'],
        'classes' => ['read'],
        'attendance' => ['create', 'read', 'update'],
        'grades' => ['create', 'read', 'update'],
        'examinations' => ['read'],
        'timetable' => ['read'],
        'reports' => ['read'],
        'subjects' => ['read'],
        'announcements' => ['read']
    ],
    ROLE_PARENT => [
        'pupils' => ['read'], // Only their own children
        'payments' => ['read'], // Only their own payments
        'grades' => ['read'], // Only their children's grades
        'reports' => ['read'], // Only their children's reports
        'announcements' => ['read']
    ]
];
