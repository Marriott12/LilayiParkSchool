<?php
/**
 * Emergency Session Diagnostic - NO DEPENDENCIES
 * Upload this file to check what's causing the 500 error
 */

// Turn on error display temporarily
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Emergency Diagnostic</h1>";

// Test 1: Basic PHP
echo "<h2>✅ Test 1: PHP is working</h2>";
echo "PHP Version: " . PHP_VERSION . "<br>";
echo "Server: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "<br>";

// Test 2: Check if files exist
echo "<h2>Test 2: File Checks</h2>";
$files = [
    'includes/Session.php',
    'includes/Auth.php',
    'includes/bootstrap.php',
    'config/config.php',
    'login.php'
];

foreach ($files as $file) {
    $exists = file_exists($file);
    $readable = $exists && is_readable($file);
    $status = $readable ? '✅' : '❌';
    echo "$status $file - " . ($readable ? 'OK' : ($exists ? 'Not readable' : 'Missing')) . "<br>";
}

// Test 3: Try to include bootstrap
echo "<h2>Test 3: Bootstrap Test</h2>";
try {
    ob_start();
    require_once __DIR__ . '/includes/bootstrap.php';
    $output = ob_get_clean();
    echo "✅ Bootstrap loaded successfully<br>";
    if ($output) {
        echo "⚠️ Bootstrap produced output:<br><pre>" . htmlspecialchars($output) . "</pre>";
    }
} catch (Throwable $e) {
    echo "❌ Bootstrap failed: " . htmlspecialchars($e->getMessage()) . "<br>";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "<br>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

// Test 4: Session test
echo "<h2>Test 4: Session Test</h2>";
try {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
        echo "✅ Session started: " . session_id() . "<br>";
    } else {
        echo "✅ Session already active: " . session_id() . "<br>";
    }
    
    if (!isset($_SESSION['diag_counter'])) {
        $_SESSION['diag_counter'] = 1;
    } else {
        $_SESSION['diag_counter']++;
    }
    
    echo "Counter: " . $_SESSION['diag_counter'] . " (refresh to test persistence)<br>";
    
} catch (Throwable $e) {
    echo "❌ Session failed: " . htmlspecialchars($e->getMessage()) . "<br>";
}

// Test 5: Output buffering
echo "<h2>Test 5: Output Buffering</h2>";
echo "Output buffer level: " . ob_get_level() . "<br>";
echo "Output buffering status: " . (ini_get('output_buffering') ? 'ON' : 'OFF') . "<br>";

echo "<hr>";
echo "<p><strong>Next steps:</strong></p>";
echo "<ol>";
echo "<li>If this page loads, the server is working</li>";
echo "<li>Check which test failed above</li>";
echo "<li>If bootstrap failed, check the error message</li>";
echo "<li>Refresh to test session persistence (counter should increase)</li>";
echo "</ol>";

echo "<p><a href='login.php'>Try Login Page</a> | <a href='?'>Refresh</a></p>";
?>
