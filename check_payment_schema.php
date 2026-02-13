<?php
require_once 'config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Get Payment table columns
    $stmt = $db->query("DESCRIBE Payment");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Payment Table Structure:\n";
    echo str_repeat("=", 60) . "\n";
    foreach ($columns as $col) {
        echo sprintf("%-20s %-20s %s\n", 
            $col['Field'], 
            $col['Type'], 
            $col['Null'] === 'NO' ? 'NOT NULL' : 'NULL'
        );
    }
    echo str_repeat("=", 60) . "\n";
    
    // Check if there are any payments
    $stmt = $db->query("SELECT COUNT(*) as count FROM Payment");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "\nTotal payments in database: " . $result['count'] . "\n";
    
    // Show sample payment if any
    if ($result['count'] > 0) {
        echo "\nSample payment (most recent):\n";
        $stmt = $db->query("SELECT * FROM Payment ORDER BY createdAt DESC LIMIT 1");
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);
        print_r($payment);
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
