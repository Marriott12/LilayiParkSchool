<?php
// Diagnostic script to identify 500 error cause
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<h1>Diagnostic Check</h1>";

// 1. PHP Version
echo "<h2>1. PHP Version</h2>";
echo "PHP Version: " . phpversion() . "<br>";

// 2. Required Extensions
echo "<h2>2. Required Extensions</h2>";
$extensions = ['pdo', 'pdo_mysql', 'mbstring', 'json'];
foreach ($extensions as $ext) {
    echo "$ext: " . (extension_loaded($ext) ? '<span style="color:green">✓ Loaded</span>' : '<span style="color:red">✗ Missing</span>') . "<br>";
}

// 3. File Permissions
echo "<h2>3. Critical Files</h2>";
$files = [
    'config/database.php',
    'includes/bootstrap.php',
    'includes/BaseModel.php',
    'modules/payments/PaymentModel.php'
];
foreach ($files as $file) {
    $exists = file_exists($file);
    $readable = is_readable($file);
    echo "$file: ";
    if (!$exists) {
        echo '<span style="color:red">✗ Not found</span><br>';
    } elseif (!$readable) {
        echo '<span style="color:red">✗ Not readable</span><br>';
    } else {
        echo '<span style="color:green">✓ OK</span><br>';
    }
}

// 4. Try loading bootstrap
echo "<h2>4. Bootstrap Test</h2>";
try {
    require_once 'includes/bootstrap.php';
    echo '<span style="color:green">✓ Bootstrap loaded successfully</span><br>';
} catch (Exception $e) {
    echo '<span style="color:red">✗ Bootstrap error: ' . $e->getMessage() . '</span><br>';
}

// 5. Try database connection
echo "<h2>5. Database Connection</h2>";
try {
    require_once 'config/database.php';
    $db = Database::getInstance()->getConnection();
    echo '<span style="color:green">✓ Database connected</span><br>';
    echo "Database: " . $db->query("SELECT DATABASE()")->fetchColumn() . "<br>";
} catch (Exception $e) {
    echo '<span style="color:red">✗ Database error: ' . $e->getMessage() . '</span><br>';
}

// 6. Try loading PaymentModel
echo "<h2>6. PaymentModel Test</h2>";
try {
    require_once 'includes/BaseModel.php';
    require_once 'modules/payments/PaymentModel.php';
    $model = new PaymentModel();
    echo '<span style="color:green">✓ PaymentModel loaded</span><br>';
} catch (Exception $e) {
    echo '<span style="color:red">✗ PaymentModel error: ' . $e->getMessage() . '</span><br>';
    echo '<pre>' . $e->getTraceAsString() . '</pre>';
}

echo "<h2>7. Error Log Path</h2>";
echo "Error log: " . ini_get('error_log') . "<br>";
echo "Display errors: " . ini_get('display_errors') . "<br>";

echo "<p><strong>If all checks pass, the issue is likely in a specific page. Try accessing different pages to narrow down the problem.</strong></p>";
?>
