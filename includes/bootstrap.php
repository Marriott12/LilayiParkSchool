<?php
/**
 * Application Bootstrap
 * Load all necessary files and initialize the application
 */

// Load configuration
require_once __DIR__ . '/config/config.php';

// Load core classes
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/Session.php';
require_once __DIR__ . '/includes/RBAC.php';
require_once __DIR__ . '/includes/BaseModel.php';
require_once __DIR__ . '/includes/Utils.php';

// Start session
Session::start();

// Set security headers
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');

// CSRF Token generation
if (!Session::has('csrf_token')) {
    Session::set('csrf_token', Utils::generateToken());
}

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
