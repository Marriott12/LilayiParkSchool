<?php
/**
 * Index.php Diagnostic - Find what's breaking the dashboard
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Index.php Diagnostic</h1>";
echo "<pre>";

echo "Step 1: Loading bootstrap...\n";
try {
    require_once __DIR__ . '/includes/bootstrap.php';
    echo "✓ Bootstrap loaded\n\n";
} catch (Throwable $e) {
    echo "✗ Bootstrap failed: " . $e->getMessage() . "\n";
    echo "  File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
    exit;
}

echo "Step 2: Loading Auth...\n";
try {
    require_once __DIR__ . '/includes/Auth.php';
    echo "✓ Auth loaded\n\n";
} catch (Throwable $e) {
    echo "✗ Auth failed: " . $e->getMessage() . "\n";
    exit;
}

echo "Step 3: Checking login status...\n";
try {
    // Simulate being logged in
    $_SESSION['user_id'] = 1;
    $_SESSION['user_role'] = 'admin';
    $_SESSION['username'] = 'Admin';
    
    echo "Session user_id: " . ($_SESSION['user_id'] ?? 'NOT SET') . "\n";
    echo "Auth::check(): " . (Auth::check() ? 'TRUE' : 'FALSE') . "\n\n";
} catch (Throwable $e) {
    echo "✗ Auth check failed: " . $e->getMessage() . "\n";
    exit;
}

echo "Step 4: Loading PermissionHelper...\n";
try {
    require_once __DIR__ . '/includes/PermissionHelper.php';
    echo "✓ PermissionHelper loaded\n\n";
} catch (Throwable $e) {
    echo "✗ PermissionHelper failed: " . $e->getMessage() . "\n";
    echo "  File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
    exit;
}

echo "Step 5: Loading UsersModel...\n";
try {
    require_once __DIR__ . '/modules/users/UsersModel.php';
    $usersModel = new UsersModel();
    echo "✓ UsersModel loaded\n\n";
} catch (Throwable $e) {
    echo "✗ UsersModel failed: " . $e->getMessage() . "\n";
    echo "  File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
    exit;
}

echo "Step 6: Getting current user...\n";
try {
    $currentUser = $usersModel->find(1);
    echo "✓ User found: " . ($currentUser['username'] ?? 'UNKNOWN') . "\n\n";
} catch (Throwable $e) {
    echo "✗ User find failed: " . $e->getMessage() . "\n";
    exit;
}

echo "Step 7: Loading ReportsModel...\n";
try {
    require_once __DIR__ . '/modules/reports/ReportsModel.php';
    $reportsModel = new ReportsModel();
    echo "✓ ReportsModel loaded\n\n";
} catch (Throwable $e) {
    echo "✗ ReportsModel failed: " . $e->getMessage() . "\n";
    echo "  File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
    exit;
}

echo "Step 8: Getting dashboard stats...\n";
try {
    $stats = $reportsModel->getDashboardStats();
    echo "✓ Dashboard stats retrieved\n";
    echo "Stats: " . print_r($stats, true) . "\n\n";
} catch (Throwable $e) {
    echo "✗ Dashboard stats failed: " . $e->getMessage() . "\n";
    echo "  File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
    echo "  Trace: " . $e->getTraceAsString() . "\n";
    exit;
}

echo "Step 9: Loading RolesModel...\n";
try {
    require_once __DIR__ . '/modules/roles/RolesModel.php';
    $rolesModel = new RolesModel();
    echo "✓ RolesModel loaded\n\n";
} catch (Throwable $e) {
    echo "✗ RolesModel failed: " . $e->getMessage() . "\n";
    echo "  File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
    exit;
}

echo "Step 10: Loading AnnouncementsModel...\n";
try {
    require_once __DIR__ . '/modules/announcements/AnnouncementsModel.php';
    $announcementsModel = new AnnouncementsModel();
    echo "✓ AnnouncementsModel loaded\n\n";
} catch (Throwable $e) {
    echo "✗ AnnouncementsModel failed: " . $e->getMessage() . "\n";
    echo "  File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
    exit;
}

echo "\n<strong>ALL STEPS PASSED! Index.php should work.</strong>\n";
echo "\nIf index.php still fails, check the error log below:\n\n";

$errorLogPath = __DIR__ . '/logs/php-errors.log';
if (file_exists($errorLogPath)) {
    echo "ERROR LOG (Last 20 lines):\n";
    $logLines = file($errorLogPath);
    $lastLines = array_slice($logLines, -20);
    foreach ($lastLines as $line) {
        echo htmlspecialchars($line);
    }
}

echo "</pre>";
