<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/Auth.php';

// Simple web-run migration script for Pupil_Transfer snapshot columns and Pupil soft-delete
// Run from browser or CLI. Requires a logged-in admin to run via web.

if (PHP_SAPI !== 'cli') {
    Auth::requireLogin();
    if (!Auth::hasRole('admin')) {
        echo "Only admin can run this migration.";
        exit;
    }
}

$db = Database::getInstance()->getConnection();
$changes = [];

function colExists($db, $table, $col) {
    try {
        $stmt = $db->prepare("SHOW COLUMNS FROM `" . $table . "` LIKE ?");
        $stmt->execute([$col]);
        return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return false;
    }
}

// Ensure Pupil_Transfer table exists
try {
    $db->exec("CREATE TABLE IF NOT EXISTS Pupil_Transfer (
        transferID INT AUTO_INCREMENT PRIMARY KEY,
        pupilID VARCHAR(10) NOT NULL,
        fromClassID VARCHAR(10) DEFAULT NULL,
        toSchool VARCHAR(150) DEFAULT NULL,
        toClassID VARCHAR(10) DEFAULT NULL,
        transferDate DATE NOT NULL,
        reason TEXT,
        notes TEXT,
        outstanding DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    $changes[] = "Ensured table Pupil_Transfer exists";
} catch (Exception $e) {
    echo "Failed to ensure Pupil_Transfer table: " . $e->getMessage();
    exit(1);
}

// Desired columns for Pupil_Transfer
$desiredPTCols = [
    'fName' => 'VARCHAR(150) DEFAULT NULL',
    'lName' => 'VARCHAR(150) DEFAULT NULL',
    'futurePaidAmount' => 'DECIMAL(10,2) NOT NULL DEFAULT 0.00',
    'futurePaidTerm' => 'VARCHAR(50) DEFAULT NULL',
    'futurePaidDetails' => 'JSON DEFAULT NULL',
    'DoB' => 'DATE DEFAULT NULL',
    'gender' => 'VARCHAR(10) DEFAULT NULL',
    'enrollDate' => 'DATE DEFAULT NULL',
    'parentEmail' => 'VARCHAR(150) DEFAULT NULL',
    'phone' => 'VARCHAR(50) DEFAULT NULL'
];

foreach ($desiredPTCols as $col => $def) {
    if (!colExists($db, 'Pupil_Transfer', $col)) {
        try {
            $db->exec("ALTER TABLE Pupil_Transfer ADD COLUMN `" . $col . "` " . $def);
            $changes[] = "Added column Pupil_Transfer.`$col`";
        } catch (Exception $e) {
            $changes[] = "Failed to add Pupil_Transfer.`$col`: " . $e->getMessage();
        }
    } else {
        $changes[] = "Pupil_Transfer.`$col` already exists";
    }
}

// Ensure Pupil table has transferred columns
$desiredPupilCols = [
    'transferred' => "TINYINT(1) NOT NULL DEFAULT 0",
    'transferredAt' => "DATETIME DEFAULT NULL"
];
foreach ($desiredPupilCols as $col => $def) {
    if (!colExists($db, 'Pupil', $col)) {
        try {
            $db->exec("ALTER TABLE Pupil ADD COLUMN `" . $col . "` " . $def);
            $changes[] = "Added column Pupil.`$col`";
        } catch (Exception $e) {
            $changes[] = "Failed to add Pupil.`$col`: " . $e->getMessage();
        }
    } else {
        $changes[] = "Pupil.`$col` already exists";
    }
}

// Output result
if (PHP_SAPI === 'cli') {
    echo "Migration results:\n";
    foreach ($changes as $c) echo " - $c\n";
} else {
    require_once __DIR__ . '/../includes/header.php';
    echo '<div class="container mt-4">';
    echo '<h4>Pupil Transfer Migration Results</h4>';
    echo '<ul class="list-group mt-3">';
    foreach ($changes as $c) echo '<li class="list-group-item">' . htmlspecialchars($c) . '</li>';
    echo '</ul>';
    echo '<p class="mt-3"><a class="btn btn-primary" href="pupil_transfer_report.php">Back to Transfers</a></p>';
    echo '</div>';
    require_once __DIR__ . '/../includes/footer.php';
}

return 0;
