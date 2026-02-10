<?php
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();

// Get Payment table columns
echo "Payment table columns:\n";
$stmt = $db->query('DESCRIBE Payment');
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($columns as $col) {
    echo "  - {$col['Field']} ({$col['Type']}) " . ($col['Null'] === 'NO' ? 'NOT NULL' : 'NULL') . "\n";
}

// Try to insert a test payment
echo "\nTesting payment insertion...\n";
try {
    $testData = [
        'pupilID' => 'L001',
        'feeID' => 1,
        'classID' => 1,
        'pmtAmt' => 100.00,
        'balance' => 400.00,
        'paymentDate' => date('Y-m-d'),
        'paymentMode' => 'Cash',
        'remark' => 'Test payment',
        'term' => 1,
        'academicYear' => date('Y')
    ];
    
    $fields = array_keys($testData);
    $placeholders = array_fill(0, count($fields), '?');
    
    $sql = "INSERT INTO Payment (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
    echo "SQL: $sql\n";
    echo "Values: " . json_encode(array_values($testData)) . "\n";
    
    $stmt = $db->prepare($sql);
    $result = $stmt->execute(array_values($testData));
    
    if ($result) {
        $lastId = $db->lastInsertId();
        echo "SUCCESS! Payment inserted with ID: $lastId\n";
    } else {
        echo "FAILED! Error info: " . json_encode($stmt->errorInfo()) . "\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
