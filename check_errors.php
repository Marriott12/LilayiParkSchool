<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<h1>Error Log Check</h1>";

$logFile = '/home/envithcy/lps.envisagezm.com/logs/php-errors.log';

if (file_exists($logFile) && is_readable($logFile)) {
    echo "<h2>Last 50 lines of PHP error log:</h2>";
    echo "<pre style='background:#f5f5f5; padding:10px; overflow:auto; max-height:500px;'>";
    $lines = file($logFile);
    $lastLines = array_slice($lines, -50);
    foreach ($lastLines as $line) {
        // Highlight errors
        if (stripos($line, 'fatal') !== false || stripos($line, 'error') !== false) {
            echo "<span style='color:red; font-weight:bold;'>$line</span>";
        } else {
            echo htmlspecialchars($line);
        }
    }
    echo "</pre>";
} else {
    echo "<p style='color:red;'>Error log not found or not readable: $logFile</p>";
}

echo "<h2>Page Tests</h2>";
echo "<p>Click each link to test:</p>";
echo "<ul>";
$pages = [
    'index.php' => 'Home Page',
    'login.php' => 'Login',
    'pupils_list.php' => 'Pupils List',
    'pupils_form.php' => 'Pupil Form',
    'payments_list.php' => 'Payments List',
    'payments_form.php' => 'Payment Form',
    'classes_list.php' => 'Classes List',
    'fees_list.php' => 'Fees List'
];

foreach ($pages as $file => $label) {
    if (file_exists($file)) {
        echo "<li><a href='$file' target='_blank'>$label ($file)</a></li>";
    } else {
        echo "<li style='color:red;'>$label ($file) - FILE NOT FOUND</li>";
    }
}
echo "</ul>";
?>
