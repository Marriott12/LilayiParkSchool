<?php
require_once __DIR__ . '/../config/database.php';

if ($argc < 4) {
    echo "Usage: php simulate_payment.php <pupilID> <amount> <selectedTerm>\n";
    exit(1);
}
$pupilID = $argv[1];
$amount = floatval($argv[2]);
$selectedTerm = intval($argv[3]);

$db = Database::getInstance()->getConnection();

try {
    $db->beginTransaction();
    // determine class
    $stmt = $db->prepare("SELECT pc.classID FROM Pupil_Class pc WHERE pc.pupilID = ? LIMIT 1");
    $stmt->execute([$pupilID]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) throw new Exception('Pupil not assigned to class');
    $classID = $row['classID'];
    $currentYear = date('Y');

    // get latest fee row for class+year
    $stmt = $db->prepare("SELECT feeID, feeAmt, term, year FROM Fees WHERE classID = ? AND year = ? ORDER BY term DESC LIMIT 1");
    $stmt->execute([$classID, $currentYear]);
    $currentFee = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    // compute previous years outstanding
    $stmtPrevFees = $db->prepare("SELECT feeID, feeAmt, term, year FROM Fees WHERE classID = ? AND year < ? ORDER BY year ASC, term ASC");
    $stmtPrevFees->execute([$classID, $currentYear]);
    $prevFeeRows = $stmtPrevFees->fetchAll(PDO::FETCH_ASSOC);

    // simulate allocation: previous years first
    $remaining = $amount;
    $created = [];
    foreach ($prevFeeRows as $feeRow) {
        if ($remaining <= 0) break;
        $yr = $feeRow['year'];
        $fid = $feeRow['feeID'];
        $feeAmtRow = floatval($feeRow['feeAmt']);
        $stmtPaidForFee = $db->prepare('SELECT SUM(pmtAmt) as paid FROM Payment WHERE pupilID = ? AND feeID = ? AND academicYear = ?');
        $stmtPaidForFee->execute([$pupilID, $fid, $yr]);
        $paidRow = $stmtPaidForFee->fetch(PDO::FETCH_ASSOC);
        $paidForFee = floatval($paidRow['paid'] ?? 0);
        $remainingForFee = max(0, $feeAmtRow - $paidForFee);
        if ($remainingForFee <= 0) continue;
        $alloc = min($remainingForFee, $remaining);
        $stmtIns = $db->prepare('INSERT INTO Payment (pupilID, feeID, classID, pmtAmt, balance, paymentDate, paymentMode, remark, term, academicYear) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmtIns->execute([$pupilID, $fid, $classID, $alloc, $feeAmtRow - ($paidForFee + $alloc), date('Y-m-d'), 'Cash', 'SIM_TEST_PREV', $feeRow['term'], $yr]);
        $created[] = $db->lastInsertId();
        $remaining -= $alloc;
    }

    // now current year: include all terms up to selectedTerm
    $perTermFees = [];
    for ($t=1;$t<=$selectedTerm;$t++){
        $termLabel = (isset($currentFee['term']) && !is_numeric($currentFee['term'])) ? 'Term ' . $t : $t;
        $stmtFee = $db->prepare('SELECT feeID, feeAmt FROM Fees WHERE classID = ? AND year = ? AND term = ? LIMIT 1');
        $stmtFee->execute([$classID, $currentYear, $termLabel]);
        $f = $stmtFee->fetch(PDO::FETCH_ASSOC);
        if ($f) $perTermFees[$t] = $f;
        else $perTermFees[$t] = ['feeID'=>$currentFee['feeID'] ?? null, 'feeAmt'=>floatval($currentFee['feeAmt'] ?? 0)];
    }

    foreach ($perTermFees as $t => $frow) {
        if ($remaining <= 0) break;
        $fid = $frow['feeID'];
        $feeAmtRow = floatval($frow['feeAmt']);
        $stmtPaid = $db->prepare('SELECT SUM(pmtAmt) as paid FROM Payment WHERE pupilID = ? AND (term = ? OR term = ?) AND academicYear = ? AND classID = ?');
        $stmtPaid->execute([$pupilID, $t, 'Term ' . $t, $currentYear, $classID]);
        $r = $stmtPaid->fetch(PDO::FETCH_ASSOC);
        $paid = floatval($r['paid'] ?? 0);
        $remForTerm = max(0, $feeAmtRow - $paid);
        if ($remForTerm <= 0) continue;
        $alloc = min($remForTerm, $remaining);
        $stmtIns = $db->prepare('INSERT INTO Payment (pupilID, feeID, classID, pmtAmt, balance, paymentDate, paymentMode, remark, term, academicYear) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmtIns->execute([$pupilID, $fid, $classID, $alloc, $feeAmtRow - ($paid + $alloc), date('Y-m-d'), 'Cash', 'SIM_TEST_CUR', $t, $currentYear]);
        $created[] = $db->lastInsertId();
        $remaining -= $alloc;
    }

    $db->commit();
    echo "Inserted payments: " . implode(',', $created) . "\n";
    if ($remaining > 0) echo "Unallocated amount remaining: " . $remaining . "\n";
} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    echo 'Error: ' . $e->getMessage() . "\n";
    exit(1);
}
