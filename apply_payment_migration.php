<?php
/**
 * Apply Payment Table Migration
 * Run this script once to add feeID, term, and academicYear columns to Payment table
 */

require_once 'config/database.php';

echo "<h2>Payment Table Migration</h2>\n";
echo "<p>This will add feeID, term, and academicYear columns to the Payment table.</p>\n";

try {
    $db = Database::getInstance()->getConnection();
    
    echo "<h3>Current Payment Table Structure:</h3>\n";
    echo "<pre>";
    $stmt = $db->query("DESCRIBE Payment");
    $currentColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($currentColumns as $col) {
        echo sprintf("%-20s %-20s\n", $col['Field'], $col['Type']);
    }
    echo "</pre>\n";
    
    // Check if columns already exist
    $existingCols = array_column($currentColumns, 'Field');
    $needsMigration = !in_array('feeID', $existingCols) || 
                      !in_array('term', $existingCols) || 
                      !in_array('academicYear', $existingCols);
    
    if (!$needsMigration) {
        echo "<p style='color: green;'>✓ Migration already applied. All columns exist.</p>\n";
        exit;
    }
    
    echo "<h3>Applying Migration...</h3>\n";
    
    // Read migration file
    $migrationFile = __DIR__ . '/database/migrations/add_fee_tracking_to_payments.sql';
    if (!file_exists($migrationFile)) {
        throw new Exception("Migration file not found: $migrationFile");
    }
    
    $sql = file_get_contents($migrationFile);
    
    // Execute migration
    $db->exec($sql);
    
    echo "<p style='color: green;'>✓ Migration applied successfully!</p>\n";
    
    echo "<h3>Updated Payment Table Structure:</h3>\n";
    echo "<pre>";
    $stmt = $db->query("DESCRIBE Payment");
    $updatedColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($updatedColumns as $col) {
        $highlight = in_array($col['Field'], ['feeID', 'term', 'academicYear']) ? ' <-- NEW' : '';
        echo sprintf("%-20s %-20s%s\n", $col['Field'], $col['Type'], $highlight);
    }
    echo "</pre>\n";
    
    echo "<h3>✓ Migration Complete!</h3>\n";
    echo "<p>You can now record payments with fee tracking information.</p>\n";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Migration Error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>\n";
}
