<?php
/**
 * Simple Login Test - Minimal version to isolate the issue
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session like bootstrap does
require_once __DIR__ . '/includes/bootstrap.php';

echo "<!DOCTYPE html><html><head><title>Simple Login Test</title></head><body>";
echo "<h1>Simple Login Test</h1>";

// Check if already logged in
if (isset($_SESSION['user_id'])) {
    echo "<p>✅ You are logged in! User ID: " . htmlspecialchars($_SESSION['user_id']) . "</p>";
    echo "<p>Session ID: " . session_id() . "</p>";
    echo "<p><a href='?logout=1'>Logout</a> | <a href='index.php'>Go to Dashboard</a></p>";
    
    if (isset($_GET['logout'])) {
        session_destroy();
        header('Location: simple_login_test.php');
        exit;
    }
} else {
    echo "<p>❌ Not logged in</p>";
    echo "<p>Session ID: " . session_id() . "</p>";
    
    // Show login form
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        
        echo "<h2>Login Attempt</h2>";
        echo "<p>Username: " . htmlspecialchars($username) . "</p>";
        
        // Try Auth::attempt
        try {
            $result = Auth::attempt($username, $password);
            echo "<p>Auth::attempt result: " . var_export($result, true) . "</p>";
            
            if ($result === true) {
                echo "<p>✅ Auth::attempt succeeded!</p>";
                echo "<p>Auth::check(): " . (Auth::check() ? 'TRUE' : 'FALSE') . "</p>";
                echo "<p>Session user_id: " . ($_SESSION['user_id'] ?? 'NOT SET') . "</p>";
                echo "<p><a href='simple_login_test.php'>Reload page</a></p>";
            } else {
                echo "<p>❌ Auth::attempt failed: " . htmlspecialchars($result) . "</p>";
            }
        } catch (Exception $e) {
            echo "<p>❌ Exception: " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
        }
    }
    
    echo '<form method="POST">
        <p><label>Username: <input type="text" name="username" value="admin"></label></p>
        <p><label>Password: <input type="password" name="password" value="admin123"></label></p>
        <p><button type="submit">Login</button></p>
    </form>';
}

echo "<hr>";
echo "<h2>Session Debug</h2>";
echo "<pre>";
echo "Session Status: " . (session_status() === PHP_SESSION_ACTIVE ? 'ACTIVE' : 'INACTIVE') . "\n";
echo "Session ID: " . session_id() . "\n";
echo "Session Data: " . print_r($_SESSION, true) . "\n";
echo "Cookies Received: " . print_r($_COOKIE, true) . "\n";
echo "</pre>";

echo "</body></html>";
?>
