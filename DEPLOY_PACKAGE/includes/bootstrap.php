<?php
/**
 * Application Bootstrap
 * Load all necessary files and initialize the application
 */

// Load configuration
require_once __DIR__ . '/../config/config.php';

// Load database first to ensure $db is available globally
require_once __DIR__ . '/../config/database.php';

// Load session management
require_once __DIR__ . '/Session.php';

// Start session BEFORE loading Auth (Auth may check session)
Session::start();

// Load core classes (these may use session or database)
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/BaseModel.php';
require_once __DIR__ . '/Utils.php';
require_once __DIR__ . '/CSRF.php';
require_once __DIR__ . '/Pagination.php';

// Set security headers
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');

// CSRF Token generation via CSRF class
CSRF::generateToken();

/**
 * Auto-load models
 */
function autoloadModel($className) {
    $modelFile = APP_ROOT . '/modules/' . strtolower($className) . '/' . $className . 'Model.php';
    if (file_exists($modelFile)) {
        require_once $modelFile;
    }
}

spl_autoload_register('autoloadModel');
