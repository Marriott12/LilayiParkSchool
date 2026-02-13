<?php
/**
 * Database Connection Diagnostic
 * Tests database connection with detailed error messages
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Database Connection Diagnostic</h1>";
echo "<pre>";

echo "=== CHECKING config/database.php ===\n";

// Check if file exists
$dbConfigPath = __DIR__ . '/config/database.php';
if (!file_exists($dbConfigPath)) {
    echo "✗ config/database.php NOT FOUND at: $dbConfigPath\n";
    exit;
}
echo "✓ config/database.php exists\n\n";

echo "=== ENVIRONMENT VARIABLES ===\n";
echo "DB_HOST: " . ($_ENV['DB_HOST'] ?? 'NOT SET (defaults to localhost)') . "\n";
echo "DB_NAME: " . ($_ENV['DB_NAME'] ?? 'NOT SET (defaults to lilayiparkschool)') . "\n";
echo "DB_USER: " . ($_ENV['DB_USER'] ?? 'NOT SET (defaults to root)') . "\n";
echo "DB_PASSWORD: " . (isset($_ENV['DB_PASSWORD']) ? '***SET***' : 'NOT SET (defaults to empty)') . "\n\n";

echo "=== TESTING DIRECT PDO CONNECTION ===\n";

// Try with default credentials first
$configs = [
    [
        'name' => 'Default (localhost/lilayiparkschool/root/empty)',
        'host' => 'localhost',
        'db' => 'lilayiparkschool',
        'user' => 'root',
        'pass' => ''
    ],
    [
        'name' => 'cPanel typical (localhost/username_dbname/username_dbuser)',
        'host' => 'localhost',
        'db' => 'envithcy_lps',
        'user' => 'envithcy_lps',
        'pass' => 'LilayiParkSchool' // Will need to get from user
    ]
];

foreach ($configs as $config) {
    echo "\nTrying: {$config['name']}\n";
    echo "  Host: {$config['host']}\n";
    echo "  Database: {$config['db']}\n";
    echo "  User: {$config['user']}\n";
    
    try {
        $dsn = "mysql:host={$config['host']};dbname={$config['db']};charset=utf8mb4";
        $pdo = new PDO($dsn, $config['user'], $config['pass']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        echo "  ✓ CONNECTION SUCCESS!\n";
        
        // Test query
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM Users");
        $result = $stmt->fetch();
        echo "  ✓ Users table has {$result['count']} records\n";
        
        echo "\n<strong>SUCCESS! Use these credentials in production:</strong>\n";
        echo "  Host: {$config['host']}\n";
        echo "  Database: {$config['db']}\n";
        echo "  Username: {$config['user']}\n";
        
        break; // Stop on first success
        
    } catch (PDOException $e) {
        echo "  ✗ Failed: " . $e->getMessage() . "\n";
    }
}

echo "\n=== CHECKING FOR .env FILE ===\n";
$envPath = __DIR__ . '/.env';
if (file_exists($envPath)) {
    echo "✓ .env file exists at: $envPath\n";
    $envContent = file_get_contents($envPath);
    echo "Contents (passwords hidden):\n";
    $lines = explode("\n", $envContent);
    foreach ($lines as $line) {
        if (strpos($line, 'PASSWORD') !== false) {
            echo preg_replace('/=.*/', '=***HIDDEN***', $line) . "\n";
        } else {
            echo $line . "\n";
        }
    }
} else {
    echo "✗ .env file NOT FOUND\n";
    echo "  You may need to create one with database credentials\n";
}

echo "\n=== MYSQL DATABASES ON THIS SERVER ===\n";
echo "Attempting to list available databases...\n";

try {
    // Try to connect without database name to list databases
    $pdo = new PDO("mysql:host=localhost", 'root', '');
    $stmt = $pdo->query("SHOW DATABASES");
    $databases = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Available databases:\n";
    foreach ($databases as $db) {
        echo "  - $db\n";
        if (strpos($db, 'lilayi') !== false || strpos($db, 'parkschool') !== false || strpos($db, 'envithcy') !== false) {
            echo "    ^^^ THIS MIGHT BE IT ^^^\n";
        }
    }
} catch (PDOException $e) {
    echo "Cannot list databases (this is normal on shared hosting)\n";
    echo "Contact hosting provider or check cPanel > MySQL Databases\n";
}

echo "\n=== INSTRUCTIONS ===\n";
echo "1. Check cPanel > MySQL Databases for the actual database name\n";
echo "2. Database name is likely: envithcy_lilayiparkschool (or similar)\n";
echo "3. Username is likely: envithcy_dbuser (or similar)\n";
echo "4. You'll need to create a .env file OR edit config/database.php\n";
echo "5. Option A - Create .env file with:\n";
echo "   DB_HOST=localhost\n";
echo "   DB_NAME=your_actual_database_name\n";
echo "   DB_USER=your_actual_username\n";
echo "   DB_PASSWORD=your_actual_password\n";
echo "\n6. Option B - Edit config/database.php lines 17-20 to use actual credentials\n";

echo "</pre>";
?>
