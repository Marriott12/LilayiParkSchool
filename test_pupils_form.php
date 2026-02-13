<?php
/**
 * Pupils Form Diagnostic
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Pupils Form Diagnostic</h1>";
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

echo "Step 2: Simulating login...\n";
$_SESSION['user_id'] = 1;
$_SESSION['user_role'] = 'admin';
$_SESSION['username'] = 'Admin';
echo "✓ Logged in as admin\n\n";

echo "Step 3: Loading PupilModel...\n";
try {
    require_once __DIR__ . '/modules/pupils/PupilModel.php';
    $pupilModel = new PupilModel();
    echo "✓ PupilModel loaded\n\n";
} catch (Throwable $e) {
    echo "✗ PupilModel failed: " . $e->getMessage() . "\n";
    echo "  File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
    exit;
}

echo "Step 4: Loading ParentModel...\n";
try {
    require_once __DIR__ . '/modules/parents/ParentModel.php';
    $parentModel = new ParentModel();
    echo "✓ ParentModel loaded\n\n";
} catch (Throwable $e) {
    echo "✗ ParentModel failed: " . $e->getMessage() . "\n";
    echo "  File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
    exit;
}

echo "Step 5: Loading ClassModel...\n";
try {
    require_once __DIR__ . '/modules/classes/ClassModel.php';
    $classModel = new ClassModel();
    echo "✓ ClassModel loaded\n\n";
} catch (Throwable $e) {
    echo "✗ ClassModel failed: " . $e->getMessage() . "\n";
    echo "  File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
    exit;
}

echo "Step 6: Getting all parents...\n";
try {
    $parents = $parentModel->getAll();
    echo "✓ Parents retrieved: " . count($parents) . " records\n\n";
} catch (Throwable $e) {
    echo "✗ Get parents failed: " . $e->getMessage() . "\n";
    echo "  File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
    exit;
}

echo "Step 7: Getting all classes...\n";
try {
    $classes = $classModel->getAll();
    echo "✓ Classes retrieved: " . count($classes) . " records\n\n";
} catch (Throwable $e) {
    echo "✗ Get classes failed: " . $e->getMessage() . "\n";
    echo "  File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
    echo "  Trace:\n" . $e->getTraceAsString() . "\n";
    exit;
}

echo "Step 8: Testing pupil retrieval...\n";
try {
    $pupils = $pupilModel->getAll();
    echo "✓ Pupils retrieved: " . count($pupils) . " records\n\n";
} catch (Throwable $e) {
    echo "✗ Get pupils failed: " . $e->getMessage() . "\n";
    echo "  File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
    echo "  Trace:\n" . $e->getTraceAsString() . "\n";
    exit;
}

echo "\n<strong>ALL STEPS PASSED! Pupils form should work.</strong>\n";

echo "\nERROR LOG (Last 20 lines):\n";
$errorLogPath = __DIR__ . '/logs/php-errors.log';
if (file_exists($errorLogPath)) {
    $logLines = file($errorLogPath);
    $lastLines = array_slice($logLines, -20);
    foreach ($lastLines as $line) {
        echo htmlspecialchars($line);
    }
}

echo "</pre>";
