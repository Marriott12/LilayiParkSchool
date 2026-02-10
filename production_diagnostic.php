<?php
/**
 * Production Diagnostic Script
 * Run this on production to diagnose session/login issues
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Production Diagnostic Report</h1>";
echo "<pre>";

echo "=== PHP ENVIRONMENT ===\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "\n";
echo "SAPI: " . php_sapi_name() . "\n";
echo "\n";

echo "=== OUTPUT BUFFERING ===\n";
echo "Initial OB Level: " . ob_get_level() . "\n";
echo "Output Buffering (ini): " . ini_get('output_buffering') . "\n";
echo "Implicit Flush: " . ini_get('implicit_flush') . "\n";
echo "\n";

echo "=== SESSION CONFIGURATION ===\n";
echo "Session Save Path: " . session_save_path() . "\n";
echo "Session Save Path Writable: " . (is_writable(session_save_path()) ? 'YES' : 'NO') . "\n";
echo "Session Name: " . session_name() . "\n";
echo "Session Cookie Lifetime: " . ini_get('session.cookie_lifetime') . "\n";
echo "Session GC Max Lifetime: " . ini_get('session.gc_maxlifetime') . "\n";
echo "\n";

echo "=== TESTING SESSION START ===\n";
try {
    $result = session_start();
    if ($result) {
        echo "✓ Session started successfully\n";
        echo "  Session ID: " . session_id() . "\n";
        echo "  Session Status: " . session_status() . " (2 = active)\n";
        
        // Test session write
        $_SESSION['test_key'] = 'test_value_' . time();
        echo "✓ Session write test passed\n";
        
        // Test session read
        $testValue = $_SESSION['test_key'] ?? null;
        echo "✓ Session read test: " . $testValue . "\n";
        
    } else {
        echo "✗ Session failed to start\n";
    }
} catch (Exception $e) {
    echo "✗ Session start exception: " . $e->getMessage() . "\n";
}
echo "\n";

echo "=== TESTING BOOTSTRAP ===\n";
try {
    // Clear session first
    session_destroy();
    session_write_close();
    
    require_once __DIR__ . '/includes/bootstrap.php';
    echo "✓ Bootstrap loaded successfully\n";
    echo "  Session active: " . (session_status() === PHP_SESSION_ACTIVE ? 'YES' : 'NO') . "\n";
    echo "  Session ID after bootstrap: " . session_id() . "\n";
} catch (Throwable $e) {
    echo "✗ Bootstrap failed: " . $e->getMessage() . "\n";
    echo "  File: " . $e->getFile() . "\n";
    echo "  Line: " . $e->getLine() . "\n";
    echo "  Trace:\n" . $e->getTraceAsString() . "\n";
}
echo "\n";

echo "=== TESTING LOGIN ===\n";
try {
    require_once __DIR__ . '/includes/Auth.php';
    
    // Try login
    echo "Attempting login with admin/admin123...\n";
    $loginResult = Auth::attempt('admin', 'admin123');
    
    if ($loginResult === true) {
        echo "✓ Login successful\n";
        echo "  User ID: " . Auth::id() . "\n";
        echo "  Username: " . Auth::username() . "\n";
        echo "  Role: " . Auth::role() . "\n";
        echo "  Session user_id set: " . (isset($_SESSION['user_id']) ? 'YES' : 'NO') . "\n";
    } else {
        echo "✗ Login failed: " . $loginResult . "\n";
    }
} catch (Throwable $e) {
    echo "✗ Login test exception: " . $e->getMessage() . "\n";
    echo "  File: " . $e->getFile() . "\n";
    echo "  Line: " . $e->getLine() . "\n";
}
echo "\n";

echo "=== ERROR LOG (Last 20 lines) ===\n";
$errorLogPath = __DIR__ . '/logs/php-errors.log';
if (file_exists($errorLogPath)) {
    $logLines = file($errorLogPath);
    $lastLines = array_slice($logLines, -20);
    foreach ($lastLines as $line) {
        echo $line;
    }
} else {
    echo "No error log found at: $errorLogPath\n";
    
    // Check alternate locations
    $phpErrorLog = ini_get('error_log');
    if ($phpErrorLog && file_exists($phpErrorLog)) {
        echo "\nPHP error_log location: $phpErrorLog\n";
    }
}

echo "</pre>";

echo "<h2>Next Steps</h2>";
echo "<ul>";
echo "<li>If session start failed: Check session save path permissions</li>";
echo "<li>If bootstrap failed: Check the error trace above</li>";
echo "<li>If login failed: Verify database connection and credentials</li>";
echo "<li>Check the error log section for recent errors</li>";
echo "</ul>";

echo "<p><strong>After reviewing, delete this file for security.</strong></p>";
