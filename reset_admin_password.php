<?php
require_once __DIR__ . '/config/database.php';

$password = 'admin123';
$hash = password_hash($password, PASSWORD_BCRYPT);

try {
    $sql = "UPDATE Users SET password = ? WHERE username = 'admin'";
    $stmt = $db->prepare($sql);
    $stmt->execute([$hash]);
    
    echo "Admin password has been reset to: admin123\n";
    echo "Hash: " . $hash . "\n";
    echo "Rows updated: " . $stmt->rowCount() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
