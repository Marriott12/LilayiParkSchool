<?php
/**
 * Better Production Diagnostic - Starts session BEFORE any output
 */

// Start output buffering to prevent headers-sent issues
ob_start();

// Start session FIRST
session_start();

// Now we can output
?>
<!DOCTYPE html>
<html>
<head>
    <title>Production Diagnostic v2</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        pre { background: white; padding: 15px; border-radius: 5px; }
        h2 { background: #333; color: white; padding: 10px; border-radius: 5px; }
    </style>
</head>
<body>
<h1>Production Diagnostic v2</h1>

<?php
echo "<pre>";

echo "<h2>PHP ENVIRONMENT</h2>";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "\n";
echo "SAPI: " . php_sapi_name() . "\n";
echo "Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown') . "\n\n";

echo "<h2>SESSION STATUS</h2>";
echo "Session Status: " . (session_status() === PHP_SESSION_ACTIVE ? '<span class="success">✓ ACTIVE</span>' : '<span class="error">✗ INACTIVE</span>') . "\n";
echo "Session ID: " . session_id() . "\n";
echo "Session Save Path: " . session_save_path() . "\n";
echo "Save Path Writable: " . (is_writable(session_save_path()) ? '<span class="success">YES</span>' : '<span class="error">NO</span>') . "\n\n";

// Test session write
$_SESSION['diagnostic_test'] = 'test_value_' . time();
echo "Session Write Test: " . ($_SESSION['diagnostic_test'] ?? '<span class="error">FAILED</span>') . "\n\n";

echo "<h2>FILE VERSION CHECK</h2>";
// Check if Session.php has the new fix
$sessionContent = file_get_contents(__DIR__ . '/includes/Session.php');
$hasSessionFix = strpos($sessionContent, 'Fix empty session save path') !== false;
$hasBootstrapFix = strpos($sessionContent, 'Auto-detects empty session save path') !== false || strpos($sessionContent, 'possiblePaths') !== false;
echo "Session.php has auto-detect fix: " . ($hasBootstrapFix ? '<span class="success">✓ YES</span>' : '<span class="error">✗ NO - OLD VERSION</span>') . "\n";

// Check bootstrap order
$bootstrapContent = file_get_contents(__DIR__ . '/includes/bootstrap.php');
$dbBeforeSession = strpos($bootstrapContent, 'Load database first') !== false;
echo "Bootstrap.php loads DB first: " . ($dbBeforeSession ? '<span class="success">✓ YES</span>' : '<span class="error">✗ NO - OLD VERSION</span>') . "\n\n";

echo "<h2>DATABASE CONNECTION</h2>";
try {
    require_once __DIR__ . '/config/config.php';
    
    // Check if .env was loaded
    echo "ENV variables after config.php load:\n";
    echo "  DB_HOST: " . ($_ENV['DB_HOST'] ?? '<span class="error">NOT SET</span>') . "\n";
    echo "  DB_NAME: " . ($_ENV['DB_NAME'] ?? '<span class="error">NOT SET</span>') . "\n";
    echo "  DB_USER: " . ($_ENV['DB_USER'] ?? '<span class="error">NOT SET</span>') . "\n";
    echo "  DB_PASSWORD: " . (isset($_ENV['DB_PASSWORD']) ? '***SET***' : '<span class="error">NOT SET</span>') . "\n\n";
    
    require_once __DIR__ . '/config/database.php';
    
    $db = Database::getInstance()->getConnection();
    
    if ($db !== null) {
        echo '<span class="success">✓ Database connected successfully</span>' . "\n";
        
        // Test query
        $stmt = $db->query("SELECT COUNT(*) as count FROM Users");
        $result = $stmt->fetch();
        echo "Users table count: " . $result['count'] . "\n";
    } else {
        echo '<span class="error">✗ Database connection is NULL</span>' . "\n";
    }
} catch (Exception $e) {
    echo '<span class="error">✗ Database error: ' . htmlspecialchars($e->getMessage()) . '</span>' . "\n";
}
echo "\n";

echo "<h2>TESTING LOGIN</h2>";
try {
    require_once __DIR__ . '/includes/Auth.php';
    
    // Clear any existing login
    unset($_SESSION['user_id']);
    unset($_SESSION['user_role']);
    
    // Try login with production credentials
    echo "Attempting login with Admin/Admin@2026...\n";
    $loginResult = Auth::attempt('Admin', 'Admin@2026');
    
    if ($loginResult === true) {
        echo '<span class="success">✓ Login successful!</span>' . "\n";
        echo "  User ID: " . Auth::id() . "\n";
        echo "  Username: " . Auth::username() . "\n";
        echo "  Role: " . Auth::role() . "\n";
        echo "  Session has user_id: " . (isset($_SESSION['user_id']) ? 'YES' : 'NO') . "\n";
    } else {
        echo '<span class="error">✗ Login failed: ' . htmlspecialchars($loginResult) . '</span>' . "\n";
    }
} catch (Throwable $e) {
    echo '<span class="error">✗ Login exception: ' . htmlspecialchars($e->getMessage()) . '</span>' . "\n";
    echo "  File: " . $e->getFile() . "\n";
    echo "  Line: " . $e->getLine() . "\n";
}
echo "\n";

echo "<h2>ERROR LOG (Last 10 lines)</h2>";
$errorLogPath = __DIR__ . '/logs/php-errors.log';
if (file_exists($errorLogPath)) {
    $logLines = file($errorLogPath);
    $lastLines = array_slice($logLines, -10);
    foreach ($lastLines as $line) {
        echo htmlspecialchars($line);
    }
} else {
    echo "No error log found\n";
}

echo "</pre>";

echo "<h2>NEXT STEPS</h2>";
echo "<ul>";

if (!$hasBootstrapFix) {
    echo "<li><strong class='error'>CRITICAL: Upload the new Session.php file!</strong></li>";
}

if (!$dbBeforeSession) {
    echo "<li><strong class='error'>CRITICAL: Upload the new bootstrap.php file!</strong></li>";
}

if ($loginResult === true) {
    echo "<li><strong class='success'>✓ Everything is working! You can now login.</strong></li>";
    echo "<li><a href='login.php'>Go to Login Page</a></li>";
} else {
    echo "<li>Check the error messages above</li>";
    echo "<li>Verify database credentials in config/database.php</li>";
}

echo "<li><strong>Delete this diagnostic file after reviewing</strong></li>";
echo "</ul>";

?>
</body>
</html>
