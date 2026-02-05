<?php
// migrations_run.php


require 'config/database.php'; // loads Database class

$db = Database::getInstance();
$conn = $db->getConnection();

$dir = __DIR__ . '/migrations/';
$files = glob($dir . '*.sql');

foreach ($files as $file) {
    $sql = file_get_contents($file);
    try {
        $conn->exec($sql);
        echo "Migration run: " . basename($file) . "<br>";
    } catch (PDOException $e) {
        echo "Error in " . basename($file) . ": " . $e->getMessage() . "<br>";
    }
}
?>
