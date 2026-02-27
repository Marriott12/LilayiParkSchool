<?php
require_once __DIR__ . '/../config/database.php';
$pupil = $argv[1] ?? 'L002';
$db = Database::getInstance()->getConnection();
$stmt = $db->prepare('SELECT payID, pupilID, feeID, classID, pmtAmt, balance, paymentDate, term, academicYear, remark FROM Payment WHERE pupilID = ? AND remark LIKE ? ORDER BY payID DESC');
$stmt->execute([$pupil, 'SIM_TEST_%']);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($rows, JSON_PRETTY_PRINT);
