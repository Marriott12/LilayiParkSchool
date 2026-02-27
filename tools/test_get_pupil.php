<?php
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');
$inputPupil = $argv[1] ?? 'L002';
try {
    $pupilID = $inputPupil;
    $db = $GLOBALS['db'] ?? Database::getInstance()->getConnection();

    // Get pupil's class
    $stmt = $db->prepare("SELECT pc.classID, c.className FROM Pupil_Class pc INNER JOIN Class c ON pc.classID = c.classID WHERE pc.pupilID = ? LIMIT 1");
    $stmt->execute([$pupilID]);
    $pupilClass = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$pupilClass) {
        echo json_encode(['success'=>false, 'error'=>'Pupil not enrolled in any class']);
        exit;
    }
    $currentYear = date('Y');
    // get latest fee row for class+year
    $stmt = $db->prepare("SELECT feeID, feeAmt, term, year FROM Fees WHERE classID = ? AND year = ? ORDER BY term DESC LIMIT 1");
    $stmt->execute([$pupilClass['classID'], $currentYear]);
    $currentFee = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    // total fee across all terms for the year
    $stmtFeesSum = $db->prepare("SELECT SUM(feeAmt) as totalFee FROM Fees WHERE classID = ? AND year = ?");
    $stmtFeesSum->execute([$pupilClass['classID'], $currentYear]);
    $feeSumRow = $stmtFeesSum->fetch(PDO::FETCH_ASSOC);
    $totalFee = floatval($feeSumRow['totalFee'] ?? 0);

    // total paid for year
    $stmt = $db->prepare("SELECT SUM(pmtAmt) as totalPaid FROM Payment WHERE pupilID = ? AND classID = ? AND academicYear = ?");
    $stmt->execute([$pupilID, $pupilClass['classID'], $currentYear]);
    $paymentsData = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalPaid = floatval($paymentsData['totalPaid'] ?? 0);

    // per-term breakdown
    $termRemaining = [];
    for ($t=1;$t<=3;$t++){
        // detect term label
        $termLabel = $t;
        if (isset($currentFee['term']) && !is_numeric($currentFee['term'])) $termLabel = 'Term ' . $t;
        $stmtFee = $db->prepare('SELECT feeID, feeAmt FROM Fees WHERE classID = ? AND year = ? AND term = ? LIMIT 1');
        $stmtFee->execute([$pupilClass['classID'], $currentYear, $termLabel]);
        $feeRow = $stmtFee->fetch(PDO::FETCH_ASSOC);
        $termFee = isset($feeRow['feeAmt']) ? floatval($feeRow['feeAmt']) : floatval($currentFee['feeAmt'] ?? 0);
        $feeId = $feeRow['feeID'] ?? $currentFee['feeID'] ?? null;

        $stmtPaid = $db->prepare('SELECT SUM(pmtAmt) as termPaid FROM Payment WHERE pupilID = ? AND classID = ? AND term = ? AND academicYear = ?');
        $stmtPaid->execute([$pupilID, $pupilClass['classID'], $t, $currentYear]);
        $r = $stmtPaid->fetch(PDO::FETCH_ASSOC);
        $termPaid = floatval($r['termPaid'] ?? 0);
        $remaining = max(0, $termFee - $termPaid);
        $termRemaining[$t] = ['paid'=>$termPaid, 'remaining'=>$remaining, 'fee'=>$termFee, 'feeID'=>$feeId];
    }

    $result = [
        'success'=>true,
        'pupilID'=>$pupilID,
        'classID'=>$pupilClass['classID'],
        'className'=>$pupilClass['className'],
        'totalFee'=>$totalFee,
        'totalPaid'=>$totalPaid,
        'balance'=>$totalFee - $totalPaid,
        'currentTerm'=>$currentFee['term'] ?? null,
        'currentYear'=>$currentFee['year'] ?? $currentYear,
        'previousPayments'=>[],
        'termRemaining'=>$termRemaining
    ];
    echo json_encode($result, JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo json_encode(['success'=>false, 'error'=>$e->getMessage()]);
}
