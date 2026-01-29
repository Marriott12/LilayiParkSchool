<?php
// Force error display for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);
/**
 * Emergency Error Debug Script
 * Upload this to your server root and access it to see actual errors
 */

// Force error display
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 0);

echo "<h1>Server Debug Information</h1>";
echo "<hr>";

// PHP Version
echo "<h2>1. PHP Version</h2>";
echo "PHP Version: " . PHP_VERSION . "<br>";
echo "Required: PHP 7.4+ (Recommended: 8.0+)<br>";
if (version_compare(PHP_VERSION, '7.4.0') >= 0) {
    echo "✅ PHP version OK<br>";
} else {
    echo "❌ PHP version too old<br>";
}

echo "<hr><h2>2. Required Extensions</h2>";
$required_extensions = ['pdo', 'pdo_mysql', 'mbstring', 'json', 'session'];
foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "✅ $ext loaded<br>";
    } else {
        echo "❌ $ext MISSING<br>";
    }
}

echo "<hr><h2>3. File System Check</h2>";
$critical_files = [
    'includes/bootstrap.php',
    'config/config.php',
    'config/database.php',
    'includes/Session.php',
    'includes/Auth.php'
];

foreach ($critical_files as $file) {
    if (file_exists(__DIR__ . '/' . $file)) {
        echo "✅ $file exists<br>";
    } else {
        echo "❌ $file MISSING<br>";
    }
}

echo "<hr><h2>4. Directory Permissions</h2>";
$writable_dirs = ['uploads', 'logs'];
foreach ($writable_dirs as $dir) {
    $path = __DIR__ . '/' . $dir;
    if (is_dir($path)) {
        if (is_writable($path)) {
            echo "✅ $dir/ writable<br>";
        } else {
            echo "❌ $dir/ NOT writable - chmod 755 or 775<br>";
        }
    } else {
        echo "⚠️ $dir/ doesn't exist - creating...<br>";
        @mkdir($path, 0755, true);
        if (is_dir($path)) {
            echo "✅ $dir/ created successfully<br>";
        } else {
            echo "❌ Failed to create $dir/<br>";
        }
    }
}

echo "<hr><h2>5. Database Connection Test</h2>";
try {
    require_once __DIR__ . '/config/database.php';
    $db = Database::getInstance()->getConnection();
    echo "✅ Database connected successfully!<br>";
    echo "Database: " . $db->query("SELECT DATABASE()")->fetchColumn() . "<br>";
    
    // Test critical tables
    echo "<h3>Tables Check:</h3>";
    $tables = ['Users', 'Roles', 'Permissions', 'Pupil', 'Teacher'];
    foreach ($tables as $table) {
        try {
            $count = $db->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
            echo "✅ $table ($count records)<br>";
        } catch (Exception $e) {
            echo "❌ $table - " . $e->getMessage() . "<br>";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Database Error: " . $e->getMessage() . "<br>";
    echo "<pre>Stack trace:\n" . $e->getTraceAsString() . "</pre>";
}

echo "<hr><h2>6. Bootstrap Test</h2>";
try {
    require_once __DIR__ . '/includes/bootstrap.php';
    echo "✅ Bootstrap loaded successfully<br>";
} catch (Exception $e) {
    echo "❌ Bootstrap Error: " . $e->getMessage() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr><h2>7. Session Test</h2>";
try {
    if (session_status() === PHP_SESSION_ACTIVE) {
        echo "✅ Session started<br>";
        echo "Session ID: " . session_id() . "<br>";
    } else {
        echo "⚠️ Session not started<br>";
    }
} catch (Exception $e) {
    echo "❌ Session Error: " . $e->getMessage() . "<br>";
}

echo "<hr><h2>8. BASE_URL Configuration</h2>";
require_once __DIR__ . '/config/config.php';
echo "BASE_URL: <strong>" . BASE_URL . "</strong><br>";
echo "Expected: http://" . $_SERVER['HTTP_HOST'] . " or https://" . $_SERVER['HTTP_HOST'] . "<br>";
echo "SCRIPT_NAME: " . $_SERVER['SCRIPT_NAME'] . "<br>";
echo "dirname(SCRIPT_NAME): " . dirname($_SERVER['SCRIPT_NAME']) . "<br>";
if (strpos(BASE_URL, 'LilayiParkSchool') !== false) {
    echo "⚠️ WARNING: BASE_URL contains 'LilayiParkSchool' - this will cause 404 errors<br>";
    echo "Fix: Remove '/LilayiParkSchool' from BASE_URL<br>";
} else {
    echo "✅ BASE_URL looks correct<br>";
}

echo "<hr><h2>9. Test Full Index Load</h2>";
try {
    ob_start();
    include __DIR__ . '/index.php';
    $output = ob_get_clean();
    echo "✅ index.php loaded without fatal errors<br>";
    echo "Output length: " . strlen($output) . " bytes<br>";
    if (strlen($output) < 100) {
        echo "<h3>Output Preview:</h3>";
        echo "<pre>" . htmlspecialchars($output) . "</pre>";
    }
} catch (Exception $e) {
    ob_end_clean();
    echo "❌ index.php Error: " . $e->getMessage() . "<br>";
    echo "<pre>File: " . $e->getFile() . "\nLine: " . $e->getLine() . "\n\n";
    echo $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<p><strong>⚠️ SECURITY WARNING:</strong> Delete this file after debugging!</p>";
echo "<p>Diagnostic complete: " . date('Y-m-d H:i:s') . "</p>";
