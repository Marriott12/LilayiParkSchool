<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$db = Database::getInstance()->getConnection();
$stmt = $db->query('SELECT classID, className FROM Class ORDER BY className');
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (empty($rows)) {
    echo "No classes found\n";
    exit(1);
}
foreach ($rows as $r) {
    echo "{$r['classID']} - {$r['className']}\n";
}
