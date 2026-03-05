<?php
require_once 'includes/bootstrap.php';
require_once 'includes/Auth.php';

Auth::requireLogin();

// Only admin can manage payments
if (!Auth::hasRole('admin')) {
    Session::setFlash('error', 'Only administrators can manage payments.');
    header('Location: 403.php');
    exit;
}

require_once 'modules/payments/PaymentModel.php';
require_once 'modules/pupils/PupilModel.php';
require_once 'modules/fees/FeesModel.php';

$paymentModel = new PaymentModel();
$pupilModel = new PupilModel();
$feesModel = new FeesModel();

// Edit mode: load existing payment when ?id= is provided
$editPayment = null;
$editMode = false;
$editPayID = null;
if (!empty($_GET['id'])) {
    $editPayID = $_GET['id'];
    try {
        $editPayment = $paymentModel->getPaymentWithDetails($editPayID);
        if ($editPayment) {
            $editMode = true;
        }
    } catch (Exception $e) {
        error_log('Failed to load payment for edit: ' . $e->getMessage());
    }
}

// Get pupils with their current class from Pupil_Class junction table
$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("
    SELECT DISTINCT p.pupilID, p.fName, p.lName, pc.classID, c.className
    FROM Pupil p
    INNER JOIN Pupil_Class pc ON p.pupilID = pc.pupilID
    INNER JOIN Class c ON pc.classID = c.classID
    ORDER BY p.fName, p.lName
");
$stmt->execute();
$pupils = $stmt->fetchAll();

// Handle AJAX request for pupil details
if (isset($_GET['action']) && $_GET['action'] === 'getPupilDetails' && isset($_GET['pupilID'])) {
    header('Content-Type: application/json');
    
    try {
        $pupilID = $_GET['pupilID'];
        
        // Get pupil's class
        $stmt = $db->prepare("
            SELECT pc.classID, c.className
            FROM Pupil_Class pc
            INNER JOIN Class c ON pc.classID = c.classID
            WHERE pc.pupilID = ?
            LIMIT 1
        ");
        $stmt->execute([$pupilID]);
        $pupilClass = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($pupilClass) {
            // Get current term fee for this class
            $stmt = $db->prepare("
                SELECT feeID, feeAmt, term, year
                FROM Fees
                WHERE classID = ? AND year = ?
                ORDER BY term DESC
                LIMIT 1
            ");
            $currentYear = date('Y');
            $stmt->execute([$pupilClass['classID'], $currentYear]);
            $currentFee = $stmt->fetch(PDO::FETCH_ASSOC);
            
                // Compute total fee across all terms for the academic year
                    $stmtFeesSum = $db->prepare("SELECT SUM(feeAmt) as totalFee FROM Fees WHERE classID = ? AND year = ?");
                    $stmtFeesSum->execute([$pupilClass['classID'], $currentYear]);
                    $feeSumRow = $stmtFeesSum->fetch(PDO::FETCH_ASSOC);
                    $totalFee = floatval($feeSumRow['totalFee'] ?? 0);

                    // Load service fees and pupil service opt-ins
                    require_once __DIR__ . '/modules/settings/SettingsModel.php';
                    $settingsModel = new SettingsModel();
                    $transportFeeSetting = (float)$settingsModel->getSetting('transport_fee', '0');
                    $mealFeeSetting = (float)$settingsModel->getSetting('meal_fee', '0');

                    // Check pupil's opt-ins
                    $stmtPupil = $db->prepare('SELECT transport, lunch FROM Pupil WHERE pupilID = ? LIMIT 1');
                    $stmtPupil->execute([$pupilID]);
                    $pupilRow = $stmtPupil->fetch(PDO::FETCH_ASSOC);
                    $transportOpt = isset($pupilRow['transport']) && $pupilRow['transport'] === 'Y';
                    $lunchOpt = isset($pupilRow['lunch']) && $pupilRow['lunch'] === 'Y';

                    // include service fees for the year (assume per-term charges)
                    $termCountStmt = $db->prepare("SELECT COUNT(*) as cnt FROM Fees WHERE classID = ? AND year = ?");
                    $termCountStmt->execute([$pupilClass['classID'], $currentYear]);
                    $termCountRow = $termCountStmt->fetch(PDO::FETCH_ASSOC);
                    $termCount = max(1, intval($termCountRow['cnt'] ?? 1));
                    $servicePerTerm = ($transportOpt ? $transportFeeSetting : 0.0) + ($lunchOpt ? $mealFeeSetting : 0.0);
                    $totalServiceForYear = $servicePerTerm * $termCount;

                    // Get all payments made by this pupil for this class and year
                    $stmt = $db->prepare("SELECT SUM(pmtAmt) as totalPaid FROM Payment WHERE pupilID = ? AND classID = ? AND academicYear = ?");
                    $stmt->execute([$pupilID, $pupilClass['classID'], $currentYear]);
                    $paymentsData = $stmt->fetch(PDO::FETCH_ASSOC);
                    $totalPaid = floatval($paymentsData['totalPaid'] ?? 0);

                    // Calculate balance (for display)
                    $balance = $totalFee - $totalPaid;

                    // Per-term remaining (terms 1..3)
                    $termRemaining = [];
                    $currentYear = $currentFee['year'] ?? date('Y');
                    for ($t = 1; $t <= 3; $t++) {
                        // determine stored term label (some rows store 'Term 1' while others store numeric)
                        $termLabel = $t;
                        if (isset($currentFee['term']) && !is_numeric($currentFee['term'])) {
                            $termLabel = 'Term ' . $t;
                        }
                        // fetch fee record for this specific term (fallback to currentFee if missing)
                        $stmtFee = $db->prepare("SELECT feeID, feeAmt FROM Fees WHERE classID = ? AND year = ? AND term = ? LIMIT 1");
                        $stmtFee->execute([$pupilClass['classID'], $currentYear, $termLabel]);
                        $feeRow = $stmtFee->fetch(PDO::FETCH_ASSOC);
                        $termFee = isset($feeRow['feeAmt']) ? floatval($feeRow['feeAmt']) : floatval($currentFee['feeAmt'] ?? 0);
                        // include per-term service fees if applicable
                        $termFee += $servicePerTerm;
                        $feeIdForTerm = $feeRow['feeID'] ?? $currentFee['feeID'] ?? null;

                        // Payments towards base fee (exclude service payments)
                        $stmt = $db->prepare("SELECT SUM(pmtAmt) as termPaid FROM Payment WHERE pupilID = ? AND classID = ? AND (term = ? OR term = ?) AND academicYear = ? AND (feeID IS NOT NULL AND feeID != '')");
                        $stmt->execute([$pupilID, $pupilClass['classID'], $t, 'Term ' . $t, $currentYear]);
                        $r = $stmt->fetch(PDO::FETCH_ASSOC);
                        $termPaid = (float)($r['termPaid'] ?? 0);
                        $remaining = max(0, $termFee - $termPaid);

                        // Payments towards services for this term
                        $stmtSvcT = $db->prepare("SELECT SUM(pmtAmt) as transportPaid FROM Payment WHERE pupilID = ? AND classID = ? AND academicYear = ? AND term = ? AND remark LIKE '%Transport%'");
                        $stmtSvcT->execute([$pupilID, $pupilClass['classID'], $currentYear, $t]);
                        $svcTPaidRow = $stmtSvcT->fetch(PDO::FETCH_ASSOC);
                        $transportPaid = (float)($svcTPaidRow['transportPaid'] ?? 0);
                        $stmtSvcM = $db->prepare("SELECT SUM(pmtAmt) as mealPaid FROM Payment WHERE pupilID = ? AND classID = ? AND academicYear = ? AND term = ? AND remark LIKE '%Meal%'");
                        $stmtSvcM->execute([$pupilID, $pupilClass['classID'], $currentYear, $t]);
                        $svcMPaidRow = $stmtSvcM->fetch(PDO::FETCH_ASSOC);
                        $mealPaid = (float)($svcMPaidRow['mealPaid'] ?? 0);

                        $transportRemaining = max(0, ($transportOpt ? $transportFeeSetting : 0) - $transportPaid);
                        $mealRemaining = max(0, ($lunchOpt ? $mealFeeSetting : 0) - $mealPaid);

                        $termRemaining[$t] = [
                            'paid' => $termPaid,
                            'remaining' => $remaining,
                            'fee' => $termFee,
                            'feeID' => $feeIdForTerm,
                            'services' => [
                                'transport' => $transportRemaining,
                                'meal' => $mealRemaining
                            ]
                        ];
                    }

                    // Compute outstanding from previous years (if any)
                    $stmtPrevFees = $db->prepare("SELECT SUM(feeAmt) as prevFees FROM Fees WHERE classID = ? AND year < ?");
                    $stmtPrevFees->execute([$pupilClass['classID'], $currentYear]);
                    $prevFeesRow = $stmtPrevFees->fetch(PDO::FETCH_ASSOC);
                    $totalPrevFees = floatval($prevFeesRow['prevFees'] ?? 0);

                    $stmtPrevPaid = $db->prepare("SELECT SUM(pmtAmt) as prevPaid FROM Payment WHERE pupilID = ? AND classID = ? AND academicYear < ?");
                    $stmtPrevPaid->execute([$pupilID, $pupilClass['classID'], $currentYear]);
                    $prevPaidRow = $stmtPrevPaid->fetch(PDO::FETCH_ASSOC);
                    $totalPrevPaid = floatval($prevPaidRow['prevPaid'] ?? 0);

                    $previousOutstanding = max(0, $totalPrevFees - $totalPrevPaid);
            
            // Get previous payments
            $stmt = $db->prepare("
                SELECT payID, pmtAmt, balance, paymentDate, paymentMode, remark
                FROM Payment
                WHERE pupilID = ? AND classID = ?
                ORDER BY paymentDate DESC
                LIMIT 5
            ");
            $stmt->execute([$pupilID, $pupilClass['classID']]);
            $previousPayments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'classID' => $pupilClass['classID'],
                'className' => $pupilClass['className'],
                'totalFee' => $totalFee,
                'totalPaid' => $totalPaid,
                'balance' => $balance,
                'currentTerm' => $currentFee['term'] ?? 'N/A',
                'currentYear' => $currentFee['year'] ?? date('Y'),
                'previousPayments' => $previousPayments,
                'termRemaining' => $termRemaining,
                'previousOutstanding' => $previousOutstanding
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Pupil not enrolled in any class. Please assign the pupil to a class first.']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Log all POST data for debugging
    error_log('=== PAYMENT FORM SUBMISSION ===' . PHP_EOL . print_r($_POST, true));
    
        // Validate CSRF token first
        if (!CSRF::requireToken()) {
            $error = $GLOBALS['csrf_error'] ?? 'Security validation failed. Please try again.';
            error_log('PAYMENT FORM ERROR: CSRF validation failed - ' . $error);
        } else {
            // If this is an edit of a single payment (payID present), perform an update and redirect
            if (!empty($_POST['payID'])) {
                $payID = $_POST['payID'];
                $updateData = [
                    'pupilID' => trim($_POST['pupilID'] ?? ''),
                    'classID' => trim($_POST['classID'] ?? ''),
                    'pmtAmt' => floatval($_POST['pmtAmt'] ?? 0),
                    'paymentDate' => $_POST['paymentDate'] ?? date('Y-m-d'),
                    'paymentMode' => trim($_POST['paymentMode'] ?? 'Cash'),
                    'remark' => trim($_POST['remark'] ?? ''),
                    'term' => intval((is_array($_POST['terms'] ?? null) ? ($_POST['terms'][0] ?? ($_POST['term'] ?? 0)) : ($_POST['terms'] ?? $_POST['term'] ?? 0))),
                    'academicYear' => $_POST['academicYear'] ?? date('Y')
                ];

                try {
                    $res = $paymentModel->update($payID, $updateData);
                    CSRF::regenerateToken();
                    // Redirect back to pupil view if present
                    $redirectPupil = $_POST['pupilID'] ?? $_GET['pupil'] ?? null;
                    if ($redirectPupil) {
                        header('Location: pupils_view.php?id=' . urlencode($redirectPupil));
                        exit;
                    }
                    header('Location: payments_list.php');
                    exit;
                } catch (Exception $e) {
                    error_log('Payment update error: ' . $e->getMessage());
                    $error = 'Failed to update payment: ' . $e->getMessage();
                }
            }
        $pupilID = trim($_POST['pupilID'] ?? '');
        $classID = trim($_POST['classID'] ?? '');
        $pmtAmt = floatval($_POST['pmtAmt'] ?? 0);

        error_log("Payment form data: pupilID={$pupilID}, classID={$classID}, pmtAmt={$pmtAmt}");

        // Server-side fallback: if classID wasn't provided by the form (JS failed),
        // attempt to determine it from the database using the pupilID.
        if (empty($classID) && !empty($pupilID)) {
            try {
                $stmt = $db->prepare("SELECT pc.classID FROM Pupil_Class pc WHERE pc.pupilID = ? LIMIT 1");
                $stmt->execute([$pupilID]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row && !empty($row['classID'])) {
                    $classID = $row['classID'];
                    error_log("PAYMENT FORM: populated missing classID from DB for pupil {$pupilID} => classID={$classID}");
                } else {
                    error_log("PAYMENT FORM: unable to determine classID from DB for pupil {$pupilID}");
                }
            } catch (Exception $e) {
                error_log('PAYMENT FORM: error fetching classID fallback: ' . $e->getMessage());
            }
        }
        
        // Validation
        if (empty($pupilID)) {
            $error = 'Please select a pupil';
            error_log('PAYMENT FORM ERROR: No pupil selected');
        } elseif (empty($classID)) {
            $error = 'Class information is required';
            error_log('PAYMENT FORM ERROR: No classID');
        } elseif ($pmtAmt <= 0) {
            $error = 'Amount paid must be greater than zero';
            error_log('PAYMENT FORM ERROR: Invalid amount - ' . $pmtAmt);
        }
        
        if (!isset($error)) {
            try {
                // Calculate the balance automatically
                // Get current term fee for this class
                $stmt = $db->prepare("
                    SELECT feeID, feeAmt, term
                    FROM Fees
                    WHERE classID = ? AND year = ?
                    ORDER BY term DESC
                    LIMIT 1
                ");
                $currentYear = date('Y');
                $stmt->execute([$classID, $currentYear]);
                $currentFee = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$currentFee) {
                    throw new Exception("No fee record found for this class and year. Please create a fee record first.");
                }
                
                $totalFee = $currentFee['feeAmt'] ?? 0;
                $feeID = $currentFee['feeID'] ?? null;
                
                // Get all payments made by this pupil for this class
                $stmt = $db->prepare("
                    SELECT SUM(pmtAmt) as totalPaid
                    FROM Payment
                    WHERE pupilID = ? AND classID = ?
                ");
                $stmt->execute([$pupilID, $classID]);
                $paymentsData = $stmt->fetch();
                $totalPaid = $paymentsData['totalPaid'] ?? 0;
                
                // Calculate current balance before this payment
                $currentBalance = $totalFee - $totalPaid;
                
                // Calculate new balance after this payment
                $newBalance = $currentBalance - $pmtAmt;
                
                // Multi-term payment handling continues below
                    // Determine per-term fee and handle multiple-term payments
                    $stmt = $db->prepare("SELECT feeID, feeAmt, term FROM Fees WHERE classID = ? AND year = ? ORDER BY term DESC LIMIT 1");
                    $currentYear = date('Y');
                    $stmt->execute([$classID, $currentYear]);
                    $currentFee = $stmt->fetch(PDO::FETCH_ASSOC);

                    if (!$currentFee) {
                        throw new Exception("No fee record found for this class and year. Please create a fee record first.");
                    }

                    // Load service fees and pupil service opt-ins (transport / meal)
                    require_once __DIR__ . '/modules/settings/SettingsModel.php';
                    $settingsModel = new SettingsModel();
                    $transportFeeSetting = (float)$settingsModel->getSetting('transport_fee', '0');
                    $mealFeeSetting = (float)$settingsModel->getSetting('meal_fee', '0');
                    $stmtPupilOpt = $db->prepare('SELECT transport, lunch FROM Pupil WHERE pupilID = ? LIMIT 1');
                    $stmtPupilOpt->execute([$pupilID]);
                    $pupilOptRow = $stmtPupilOpt->fetch(PDO::FETCH_ASSOC);
                    $transportOpt = isset($pupilOptRow['transport']) && $pupilOptRow['transport'] === 'Y';
                    $lunchOpt = isset($pupilOptRow['lunch']) && $pupilOptRow['lunch'] === 'Y';

                    $perTermFee = (float)($currentFee['feeAmt'] ?? 0);
                    $servicePerTerm = ($transportOpt ? $transportFeeSetting : 0.0) + ($lunchOpt ? $mealFeeSetting : 0.0);
                    // include service fee per term in per-term fee calculations
                    $perTermFee += $servicePerTerm;
                    $feeID = $currentFee['feeID'] ?? null;

                    // Terms selected by user (array of term numbers). If none provided, default to current term.
                    $selectedTerms = $_POST['terms'] ?? [];
                    // Normalize and filter to allowed term numbers (1-3)
                    $selectedTerms = array_map('intval', $selectedTerms);
                    $selectedTerms = array_values(array_filter($selectedTerms, function($v){ return in_array($v, [1,2,3]); }));
                    if (empty($selectedTerms)) {
                        $selectedTerms = [ (int)($currentFee['term'] ?? 1) ];
                    }
                    // Make unique
                    $selectedTerms = array_values(array_unique($selectedTerms));

                    // Determine terms to include: include all terms up to the highest selected term
                    $maxSelectedTerm = max($selectedTerms);
                    $termsToProcess = range(1, $maxSelectedTerm);

                    // Compute previous years' outstanding, to be included in totalNeeded
                    $stmtPrevFees = $db->prepare("SELECT SUM(feeAmt) as prevFees FROM Fees WHERE classID = ? AND year < ?");
                    $stmtPrevFees->execute([$classID, $currentYear]);
                    $prevFeesRow = $stmtPrevFees->fetch(PDO::FETCH_ASSOC);
                    $totalPrevFees = floatval($prevFeesRow['prevFees'] ?? 0);

                    $stmtPrevPaid = $db->prepare("SELECT SUM(pmtAmt) as prevPaid FROM Payment WHERE pupilID = ? AND classID = ? AND academicYear < ?");
                    $stmtPrevPaid->execute([$pupilID, $classID, $currentYear]);
                    $prevPaidRow = $stmtPrevPaid->fetch(PDO::FETCH_ASSOC);
                    $totalPrevPaid = floatval($prevPaidRow['prevPaid'] ?? 0);

                    $previousOutstanding = max(0, $totalPrevFees - $totalPrevPaid);

                    // For each term up to maxSelectedTerm compute already paid amount and remaining using that term's fee
                    $termRemaining = [];
                    $totalNeeded = $previousOutstanding; // start with previous years' outstanding
                    foreach ($termsToProcess as $termNumber) {
                        // determine stored term label (some rows store 'Term 1' while others store numeric)
                        $termLabel = $termNumber;
                        if (isset($currentFee['term']) && !is_numeric($currentFee['term'])) {
                            $termLabel = 'Term ' . $termNumber;
                        }
                        // fetch fee for this specific term (fallback to currentFee)
                        $stmtFeeTerm = $db->prepare("SELECT feeID, feeAmt FROM Fees WHERE classID = ? AND year = ? AND term = ? LIMIT 1");
                        $stmtFeeTerm->execute([$classID, $currentYear, $termLabel]);
                        $feeRowTerm = $stmtFeeTerm->fetch(PDO::FETCH_ASSOC);
                        $termFee = isset($feeRowTerm['feeAmt']) ? floatval($feeRowTerm['feeAmt']) : floatval($currentFee['feeAmt']);
                        $termFeeID = $feeRowTerm['feeID'] ?? $feeID;

                        // Payments towards base fee (exclude service payments)
                        $stmt = $db->prepare("SELECT SUM(pmtAmt) as termPaid FROM Payment WHERE pupilID = ? AND classID = ? AND (term = ? OR term = ?) AND academicYear = ? AND (feeID IS NOT NULL AND feeID != '')");
                        $stmt->execute([$pupilID, $classID, $termNumber, 'Term ' . $termNumber, $currentYear]);
                        $row = $stmt->fetch(PDO::FETCH_ASSOC);
                        $termPaid = (float)($row['termPaid'] ?? 0);
                        $remaining = max(0, $termFee - $termPaid);

                        // Compute service remaining for this term
                        $stmtSvcT = $db->prepare("SELECT SUM(pmtAmt) as transportPaid FROM Payment WHERE pupilID = ? AND classID = ? AND academicYear = ? AND term = ? AND remark LIKE '%Transport%'");
                        $stmtSvcT->execute([$pupilID, $classID, $currentYear, $termNumber]);
                        $svcTPaidRow = $stmtSvcT->fetch(PDO::FETCH_ASSOC);
                        $transportPaid = (float)($svcTPaidRow['transportPaid'] ?? 0);
                        $stmtSvcM = $db->prepare("SELECT SUM(pmtAmt) as mealPaid FROM Payment WHERE pupilID = ? AND classID = ? AND academicYear = ? AND term = ? AND remark LIKE '%Meal%'");
                        $stmtSvcM->execute([$pupilID, $classID, $currentYear, $termNumber]);
                        $svcMPaidRow = $stmtSvcM->fetch(PDO::FETCH_ASSOC);
                        $mealPaid = (float)($svcMPaidRow['mealPaid'] ?? 0);

                        $transportRemaining = max(0, ($transportOpt ? $transportFeeSetting : 0) - $transportPaid);
                        $mealRemaining = max(0, ($lunchOpt ? $mealFeeSetting : 0) - $mealPaid);

                        $termRemaining[$termNumber] = [
                            'paid' => $termPaid,
                            'remaining' => $remaining,
                            'fee' => $termFee,
                            'feeID' => $termFeeID,
                            'services' => [
                                'transport' => $transportRemaining,
                                'meal' => $mealRemaining
                            ]
                        ];

                        $totalNeeded += $remaining + $transportRemaining + $mealRemaining;
                    }

                    // Prevent overpayment across selected terms + previous outstanding
                    if ($pmtAmt > $totalNeeded) {
                        throw new Exception('Amount exceeds required total for the selected terms (including previous outstanding). Reduce the amount or uncheck some terms.');
                    }

                    // If all requested terms (and previous outstanding) require nothing, inform user
                    $fullyPaidTerms = array_filter($termRemaining, function($t){ return $t['remaining'] <= 0; });
                    if ($totalNeeded <= 0) {
                        throw new Exception('Selected terms and previous outstanding are already fully paid. Nothing to do.');
                    }

                    // Allocate the payment amount: first clear previous years' unpaid fees (oldest first), then current-year terms up to maxSelectedTerm
                    $amountToAllocate = $pmtAmt;
                    $createdPayments = [];

                    // Allocate to previous years' fee rows (oldest first)
                    if ($previousOutstanding > 0 && $amountToAllocate > 0) {
                        $stmtPrevFeeRows = $db->prepare("SELECT feeID, feeAmt, term, year FROM Fees WHERE classID = ? AND year < ? ORDER BY year ASC, term ASC");
                        $stmtPrevFeeRows->execute([$classID, $currentYear]);
                        $prevFeeRows = $stmtPrevFeeRows->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($prevFeeRows as $feeRow) {
                            if ($amountToAllocate <= 0) break;
                            $yr = $feeRow['year'];
                            $fid = $feeRow['feeID'];
                            $feeAmtRow = floatval($feeRow['feeAmt']);
                            // total paid for this fee row
                            $stmtPaidForFee = $db->prepare('SELECT SUM(pmtAmt) as paid FROM Payment WHERE pupilID = ? AND feeID = ? AND academicYear = ?');
                            $stmtPaidForFee->execute([$pupilID, $fid, $yr]);
                            $paidRow = $stmtPaidForFee->fetch(PDO::FETCH_ASSOC);
                            $paidForFee = floatval($paidRow['paid'] ?? 0);
                            $remainingForFee = max(0, $feeAmtRow - $paidForFee);
                            if ($remainingForFee <= 0) continue;
                            $allocPrev = min($remainingForFee, $amountToAllocate);
                            // create payment record for this previous fee row
                            $stmtInsertPrev = $db->prepare('INSERT INTO Payment (pupilID, feeID, classID, pmtAmt, balance, paymentDate, paymentMode, remark, term, academicYear) VALUES (:pupilID, :feeID, :classID, :pmtAmt, :balance, :paymentDate, :paymentMode, :remark, :term, :academicYear)');
                            $stmtInsertPrev->execute([
                                ':pupilID'=>$pupilID,
                                ':feeID'=>$fid,
                                ':classID'=>$classID,
                                ':pmtAmt'=>$allocPrev,
                                ':balance'=>$feeAmtRow - ($paidForFee + $allocPrev),
                                ':paymentDate'=>$paymentDate,
                                ':paymentMode'=>$paymentMode,
                                ':remark'=>$remark,
                                ':term'=>$feeRow['term'],
                                ':academicYear'=>$yr
                            ]);
                            $createdPayments[] = $db->lastInsertId();
                            $amountToAllocate -= $allocPrev;
                        }
                    }

                    // Allocate payments starting from earliest term up to maxSelectedTerm
                    foreach ($termsToProcess as $termNumber) {
                        if ($amountToAllocate <= 0) break;
                        $remaining = $termRemaining[$termNumber]['remaining'] ?? 0;
                        if ($remaining <= 0) continue;
                        $pmtForTerm = min($remaining, $amountToAllocate);

                        // use the term-specific feeID if available
                        $feeIdForTerm = $termRemaining[$termNumber]['feeID'] ?? $feeID;
                        $perTermForThis = $termRemaining[$termNumber]['fee'] ?? $perTermFee;

                        $data = [
                            'pupilID' => $pupilID,
                            'feeID' => $feeIdForTerm,
                            'classID' => $classID,
                            'pmtAmt' => $pmtForTerm,
                            'balance' => $perTermForThis - (($termRemaining[$termNumber]['paid'] ?? 0) + $pmtForTerm),
                            'paymentDate' => $_POST['paymentDate'] ?? date('Y-m-d'),
                            'paymentMode' => trim($_POST['paymentMode'] ?? 'Cash'),
                            'remark' => trim($_POST['remark'] ?? ''),
                            'term' => $termNumber,
                            'academicYear' => $currentYear
                        ];

                        error_log("Attempting to create payment for term {$termNumber}: " . json_encode($data));
                        $result = $paymentModel->create($data);
                        if (!$result) {
                            throw new Exception("Failed to create payment record for term {$termNumber}.");
                        }
                        $createdPayments[] = $result;
                        $amountToAllocate -= $pmtForTerm;

                            // After allocating base term fee, allocate service fees (transport then meal) for this term
                            $svc = $termRemaining[$termNumber]['services'] ?? ['transport' => 0, 'meal' => 0];

                            // Transport
                            if ($amountToAllocate > 0 && ($svc['transport'] ?? 0) > 0) {
                                $allocTransport = min($svc['transport'], $amountToAllocate);
                                $svcData = [
                                    'pupilID' => $pupilID,
                                    'feeID' => null,
                                    'classID' => $classID,
                                    'pmtAmt' => $allocTransport,
                                    'balance' => ($svc['transport'] - $allocTransport),
                                    'paymentDate' => $_POST['paymentDate'] ?? date('Y-m-d'),
                                    'paymentMode' => trim($_POST['paymentMode'] ?? 'Cash'),
                                    'remark' => 'Transport fee',
                                    'term' => $termNumber,
                                    'academicYear' => $currentYear
                                ];
                                $r2 = $paymentModel->create($svcData);
                                if (!$r2) {
                                    throw new Exception("Failed to create transport service payment for term {$termNumber}.");
                                }
                                $createdPayments[] = $r2;
                                $amountToAllocate -= $allocTransport;
                                // update remaining for reporting
                                $termRemaining[$termNumber]['services']['transport'] -= $allocTransport;
                            }

                            // Meal
                            if ($amountToAllocate > 0 && ($svc['meal'] ?? 0) > 0) {
                                $allocMeal = min($svc['meal'], $amountToAllocate);
                                $svcData = [
                                    'pupilID' => $pupilID,
                                    'feeID' => null,
                                    'classID' => $classID,
                                    'pmtAmt' => $allocMeal,
                                    'balance' => ($svc['meal'] - $allocMeal),
                                    'paymentDate' => $_POST['paymentDate'] ?? date('Y-m-d'),
                                    'paymentMode' => trim($_POST['paymentMode'] ?? 'Cash'),
                                    'remark' => 'Meal fee',
                                    'term' => $termNumber,
                                    'academicYear' => $currentYear
                                ];
                                $r3 = $paymentModel->create($svcData);
                                if (!$r3) {
                                    throw new Exception("Failed to create meal service payment for term {$termNumber}.");
                                }
                                $createdPayments[] = $r3;
                                $amountToAllocate -= $allocMeal;
                                $termRemaining[$termNumber]['services']['meal'] -= $allocMeal;
                            }
                    }

                    if ($amountToAllocate > 0) {
                        throw new Exception('Payment amount could not be fully allocated to the selected terms.');
                    }
                
                error_log("Payment created successfully with ID: " . $result);
                Session::setFlash('success', 'Payment recorded successfully');
                
                CSRF::regenerateToken();
                // If a pupil context was provided (either via GET or POST), redirect back to that pupil's view
                $redirectPupil = null;
                if (!empty($_GET['pupil'])) {
                    $redirectPupil = $_GET['pupil'];
                } elseif (!empty($_POST['pupilID'])) {
                    $redirectPupil = $_POST['pupilID'];
                }

                if ($redirectPupil) {
                    header('Location: pupils_view.php?id=' . urlencode($redirectPupil));
                    exit;
                }

                header('Location: payments_list.php');
                exit;
            } catch (Exception $e) {
                error_log("Payment creation error: " . $e->getMessage());
                $error = $e->getMessage();
            }
        }
    }
}

$pageTitle = 'Record New Payment';
$currentPage = 'payments_add';
require_once 'includes/header.php';
?>

<div class="mb-4">
    <a href="payments_list.php" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back to Payments
    </a>
</div>

<div class="card">
    <div class="card-header" style="background: linear-gradient(135deg, #2d5016 0%, #5cb85c 100%); color: white;">
        <h5 class="mb-0">
            <i class="bi bi-credit-card-fill"></i> <?= $pageTitle ?>
        </h5>
    </div>
    <div class="card-body">
        <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle-fill"></i> <strong>Error:</strong> <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <?php if (Session::getFlash('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle-fill"></i> <strong>Error:</strong> <?= htmlspecialchars(Session::getFlash('error')) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <?php if (Session::getFlash('success')): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars(Session::getFlash('success')) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($error)): ?>
        <div class="alert alert-info">
            <strong>Debug:</strong> Form submitted successfully but may have failed during save. Check browser console and error logs.
        </div>
        <?php endif; ?>
        
        <form method="POST" action="" id="paymentForm">
            <?= CSRF::field() ?>
            <?php if ($editMode): ?>
                <input type="hidden" name="payID" value="<?= htmlspecialchars($editPayID) ?>">
                <input type="hidden" name="academicYear" value="<?= htmlspecialchars($editPayment['academicYear'] ?? date('Y')) ?>">
            <?php endif; ?>
            
            <!-- Pupil Selection -->
            <div class="card mb-4" style="border-left: 4px solid #2d5016;">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="bi bi-person-fill"></i> Pupil Selection</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Select Pupil <span class="text-danger">*</span></label>
                        <?php $selectedPupil = $editPayment['pupilID'] ?? ($_GET['pupil'] ?? null); ?>
                        <select class="form-select" name="pupilID" id="pupilSelect" required>
                            <option value="">-- Select Pupil --</option>
                            <?php foreach ($pupils as $pupil): ?>
                            <option value="<?= $pupil['pupilID'] ?>" data-classid="<?= $pupil['classID'] ?>" <?= ($selectedPupil && $selectedPupil == $pupil['pupilID']) ? 'selected' : '' ?> >
                                <?= htmlspecialchars($pupil['fName'] . ' ' . $pupil['lName'] . ' - ' . $pupil['className']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="hidden" name="classID" id="classID" value="">
                        <div class="mt-3">
                            <label class="form-label">Terms to Pay</label>
                            <div class="d-flex gap-3" id="termsCheckboxes">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="1" id="term1" name="terms[]" <?= ($editMode && isset($editPayment['term']) && intval($editPayment['term']) === 1) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="term1">Term 1 <small class="text-muted" id="term1Info"></small></label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="2" id="term2" name="terms[]" <?= ($editMode && isset($editPayment['term']) && intval($editPayment['term']) === 2) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="term2">Term 2 <small class="text-muted" id="term2Info"></small></label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="3" id="term3" name="terms[]" <?= ($editMode && isset($editPayment['term']) && intval($editPayment['term']) === 3) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="term3">Term 3 <small class="text-muted" id="term3Info"></small></label>
                                        </div>
                            </div>
                            <div class="form-text">Select one or more terms for which payment is being made.</div>
                            <div id="paymentSummary" class="mt-2"></div>
                        </div>
                    </div>
                    
                    <div id="pupilDetails" style="display: none;">
                        <div class="alert alert-info">
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Class:</strong> <span id="displayClass"></span>
                                </div>
                                <div class="col-md-6">
                                    <strong>Term:</strong> <span id="displayTerm"></span> / <span id="displayYear"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Fee Summary -->
            <div class="card mb-4" style="border-left: 4px solid #5cb85c; display: none;" id="feeSummary">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="bi bi-calculator-fill"></i> Fee Summary</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="text-center p-3 bg-light rounded">
                                <small class="text-muted">Total Fee</small>
                                <h4 class="mb-0 text-primary">K <span id="totalFee">0.00</span></h4>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center p-3 bg-light rounded">
                                <small class="text-muted">Total Paid</small>
                                <h4 class="mb-0 text-success">K <span id="totalPaid">0.00</span></h4>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center p-3 bg-light rounded">
                                <small class="text-muted">Current Balance</small>
                                <h4 class="mb-0 text-warning">K <span id="currentBalance">0.00</span></h4>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-12 text-end">
                            <small class="text-muted">Unpaid Total (all terms):</small>
                            <strong class="ms-2">K <span id="unpaidTotal">0.00</span></strong>
                        </div>
                    </div>
                    <div class="row mt-1">
                        <div class="col-12 text-end">
                            <small class="text-muted">Selected Terms Outstanding:</small>
                            <strong class="ms-2">K <span id="selectedOutstanding">0.00</span></strong>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Payment Details -->
            <div class="card mb-4" style="border-left: 4px solid #f0ad4e;">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="bi bi-cash-stack"></i> Payment Details</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Amount Paid (K) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control" name="pmtAmt" id="pmtAmt"
                                   placeholder="0.00" required value="<?= htmlspecialchars($editPayment['pmtAmt'] ?? '') ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Payment Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="paymentDate" 
                                   value="<?= htmlspecialchars($editPayment['paymentDate'] ?? date('Y-m-d')) ?>" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Mode of Payment <span class="text-danger">*</span></label>
                            <select class="form-select" name="paymentMode" required>
                                <option value="Cash" <?= (!isset($editPayment['paymentMode']) || $editPayment['paymentMode'] === 'Cash') ? 'selected' : '' ?>>Cash</option>
                                <option value="Bank Transfer" <?= (isset($editPayment['paymentMode']) && $editPayment['paymentMode'] === 'Bank Transfer') ? 'selected' : '' ?>>Bank Transfer</option>
                                <option value="Mobile Money" <?= (isset($editPayment['paymentMode']) && $editPayment['paymentMode'] === 'Mobile Money') ? 'selected' : '' ?>>Mobile Money</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Receipt/Reference Number</label>
                        <input type="text" class="form-control" name="remark" 
                               placeholder="Payment reference or remarks" value="<?= htmlspecialchars($editPayment['remark'] ?? '') ?>">
                    </div>
                </div>
            </div>
            
            <!-- Previous Payments -->
            <div class="card mb-4" style="border-left: 4px solid #5bc0de; display: none;" id="previousPaymentsCard">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="bi bi-clock-history"></i> Recent Payments (Last 5)</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>Receipt No.</th>
                                    <th>Date</th>
                                    <th>Amount Paid</th>
                                    <th>Payment Mode</th>
                                    <th>Balance After</th>
                                    <th>Remark</th>
                                </tr>
                            </thead>
                            <tbody id="previousPaymentsBody">
                                <tr>
                                    <td colspan="6" class="text-center text-muted">Select a pupil to view payment history</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="d-flex gap-2">
                <button type="submit" class="btn" style="background-color: #2d5016; color: white;">
                    <i class="bi bi-save"></i> Save Payment
                </button>
                <a href="payments_list.php" class="btn btn-secondary">
                    <i class="bi bi-x-circle"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const currencyFormatter = new Intl.NumberFormat(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const pupilSelect = document.getElementById('pupilSelect');
    const pupilDetails = document.getElementById('pupilDetails');
    const feeSummary = document.getElementById('feeSummary');
    const previousPaymentsCard = document.getElementById('previousPaymentsCard');
    const pmtAmtInput = document.getElementById('pmtAmt');
    
    let currentData = null;
    
    // Check if pupil is pre-selected from URL parameter
    const urlParams = new URLSearchParams(window.location.search);
    const preSelectedPupil = urlParams.get('pupil');
    if (preSelectedPupil) {
        pupilSelect.value = preSelectedPupil;
        pupilSelect.dispatchEvent(new Event('change'));
    }
    
    pupilSelect.addEventListener('change', function() {
        const pupilID = this.value;
        
        if (!pupilID) {
            pupilDetails.style.display = 'none';
            feeSummary.style.display = 'none';
            previousPaymentsCard.style.display = 'none';
            // Clear the hidden classID field
            const classIDInput = document.getElementById('classID');
            if (classIDInput) classIDInput.value = '';
            return;
        }
        
        // IMMEDIATELY get classID from the selected option's data attribute
        const selectedOption = this.options[this.selectedIndex];
        const classID = selectedOption.getAttribute('data-classid');
        const classIDInput = document.getElementById('classID');
        
        if (classIDInput && classID) {
            classIDInput.value = classID;
            console.log('Set classID from dropdown:', classID);
        } else {
            console.warn('Could not set classID. Input exists:', !!classIDInput, 'ClassID value:', classID);
        }
        
        // Fetch pupil details via AJAX
        fetch(`?action=getPupilDetails&pupilID=${pupilID}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Pupil details:', data); // Debug log
                
                if (data.success) {
                    currentData = data;
                    
                    // Update pupil details with null checks
                    const displayClass = document.getElementById('displayClass');
                    const classIDInput = document.getElementById('classID');
                    const displayTerm = document.getElementById('displayTerm');
                    const displayYear = document.getElementById('displayYear');
                    const totalFeeEl = document.getElementById('totalFee');
                    const totalPaidEl = document.getElementById('totalPaid');
                    const currentBalanceEl = document.getElementById('currentBalance');
                    
                    if (displayClass) displayClass.textContent = data.className;
                    if (classIDInput && data.classID) {
                        classIDInput.value = data.classID;
                        console.log('Set classID from AJAX:', data.classID);
                    } else {
                        console.error('Cannot set classID from AJAX. Input exists:', !!classIDInput, 'Data classID:', data.classID);
                    }
                    if (displayTerm) displayTerm.textContent = data.currentTerm;
                    if (displayYear) displayYear.textContent = data.currentYear;
                    pupilDetails.style.display = 'block';
                    
                    // Update fee summary
                    if (totalFeeEl) totalFeeEl.textContent = currencyFormatter.format(parseFloat(data.totalFee));
                    if (totalPaidEl) totalPaidEl.textContent = currencyFormatter.format(parseFloat(data.totalPaid));
                    if (currentBalanceEl) currentBalanceEl.textContent = currencyFormatter.format(parseFloat(data.balance));
                    feeSummary.style.display = 'block';

                    // Terms checkbox logic
                    const termCheckboxes = document.querySelectorAll('#termsCheckboxes input[type="checkbox"]');
                    termCheckboxes.forEach(cb => { cb.checked = false; cb.disabled = false; });
                    // Show per-term remaining info and disable fully-paid terms
                    const termRemaining = data.termRemaining || {};
                    for (const t of [1,2,3]) {
                        const info = document.getElementById('term' + t + 'Info');
                        const cb = document.getElementById('term' + t);
                        const lbl = document.querySelector('label[for="term' + t + '"]');
                        const remObj = termRemaining[t] || {remaining: 0, paid: 0, fee: 0};
                        const feeFmt = currencyFormatter.format(parseFloat(remObj.fee || 0));
                        const remFmt = currencyFormatter.format(parseFloat(remObj.remaining || 0));
                        if (info) info.textContent = remObj.remaining > 0 ? `(Fee: K ${feeFmt}, Remaining: K ${remFmt})` : `(Fee: K ${feeFmt}) (Fully paid)`;
                        if (cb) {
                            if (remObj.remaining <= 0) {
                                cb.checked = false;
                                cb.disabled = true;
                                if (lbl) {
                                    lbl.classList.add('text-muted');
                                    lbl.classList.add('text-decoration-line-through');
                                }
                            } else {
                                if (lbl) {
                                    lbl.classList.remove('text-muted');
                                    lbl.classList.remove('text-decoration-line-through');
                                }
                            }
                        }
                    }

                    // Show unpaid total across all terms
                    const unpaidTotalEl = document.getElementById('unpaidTotal');
                    let unpaidTotal = 0;
                    for (const t of [1,2,3]) {
                        const remObj = termRemaining[t] || {remaining:0};
                        unpaidTotal += parseFloat(remObj.remaining || 0);
                    }
                    if (unpaidTotalEl) unpaidTotalEl.textContent = currencyFormatter.format(unpaidTotal);

                    // Check current term by default if it's not fully paid
                    if (data.currentTerm) {
                        const ct = parseInt(data.currentTerm, 10);
                        const curCb = document.getElementById('term' + ct);
                        if (curCb && !curCb.disabled) curCb.checked = true;
                    }

                    function computeRequiredTotal(){
                        // If any term selected, include all terms up to the highest selected term
                        let selected = [];
                        termCheckboxes.forEach(cb => { if (cb.checked) selected.push(parseInt(cb.value,10)); });
                        if (selected.length === 0) return 0;
                        const maxTerm = Math.max(...selected);
                        let total = 0;
                        for (let t = 1; t <= maxTerm; t++) {
                            const remObj = termRemaining[t] || {};
                            const rem = (typeof remObj.remaining !== 'undefined' && remObj.remaining !== null)
                                ? parseFloat(remObj.remaining)
                                : parseFloat(remObj.fee || 0);
                            total += rem;
                        }
                        // Include outstanding from previous years if present
                        const prevOut = parseFloat(data.previousOutstanding || 0);
                        total += prevOut;
                        return total;
                    }

                    function updateAmountFromTerms(){
                        const required = computeRequiredTotal();
                        if (pmtAmtInput) pmtAmtInput.value = required.toFixed(2);
                        updatePaymentSummary(required, parseFloat(pmtAmtInput.value || 0));
                        // visually format input display is left to native input; formatted fields updated separately
                        // update selected terms outstanding in fee summary (formatted)
                        const selectedOutstandingEl = document.getElementById('selectedOutstanding');
                        if (selectedOutstandingEl) selectedOutstandingEl.textContent = currencyFormatter.format(required);
                    }
                    
                    function updatePaymentSummary(required, entered){
                        const summary = document.getElementById('paymentSummary');
                        if (!summary) return;
                        const diff = entered - required;
                        let color = 'text-secondary';
                        if (entered < required) color = 'text-warning';
                        else if (entered === required) color = 'text-success';
                        else color = 'text-danger';
                        summary.setAttribute('data-required', required.toFixed(2));
                        summary.innerHTML = `<small class="${color}">Required total: K ${currencyFormatter.format(required)} &nbsp;|&nbsp; Entered: K ${currencyFormatter.format(entered)} &nbsp;|&nbsp; Difference: K ${currencyFormatter.format(diff)}</small>`;
                    }
                    termCheckboxes.forEach(cb => cb.addEventListener('change', updateAmountFromTerms));
                    // Update summary when amount manually changed
                    if (pmtAmtInput) pmtAmtInput.addEventListener('input', function(){
                        const required = computeRequiredTotal();
                        updatePaymentSummary(required, parseFloat(this.value || 0));
                    });
                    updateAmountFromTerms();
                    
                    // Update previous payments
                    const tbody = document.getElementById('previousPaymentsBody');
                    if (data.previousPayments && data.previousPayments.length > 0) {
                        tbody.innerHTML = '';
                        data.previousPayments.forEach(payment => {
                            const row = `
                                    <tr>
                                        <td>${payment.payID}</td>
                                        <td>${payment.paymentDate}</td>
                                        <td>K ${currencyFormatter.format(parseFloat(payment.pmtAmt))}</td>
                                        <td>${payment.paymentMode || 'Cash'}</td>
                                        <td>K ${currencyFormatter.format(parseFloat(payment.balance))}</td>
                                        <td>${payment.remark || '-'}</td>
                                    </tr>
                                `;
                            tbody.innerHTML += row;
                        });
                        previousPaymentsCard.style.display = 'block';
                    } else {
                        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No previous payments</td></tr>';
                        previousPaymentsCard.style.display = 'block';
                    }
                } else {
                    alert('Error: ' + (data.error || 'Unknown error occurred'));
                    pupilDetails.style.display = 'none';
                    feeSummary.style.display = 'none';
                    previousPaymentsCard.style.display = 'none';
                }
            })
            .catch(error => {
                console.error('Error fetching pupil details:', error);
                alert('Failed to fetch pupil details: ' + error.message);
                pupilDetails.style.display = 'none';
                feeSummary.style.display = 'none';
                previousPaymentsCard.style.display = 'none';
            });
    });
    
    // Add form submission validation
    const paymentForm = document.getElementById('paymentForm');
    if (paymentForm) {
        // Allocation confirmation flow
        let allocationConfirmed = false;
        paymentForm.addEventListener('submit', function(e) {
            if (allocationConfirmed) return true; // allow actual submit

            const classIDInput = document.querySelector('input[name="classID"]');
            const pupilIDInput = document.getElementById('pupilSelect');
            
            if (!classIDInput || !classIDInput.value) {
                e.preventDefault();
                alert('Error: Class information is missing. Please select a pupil and wait for their details to load before submitting.');
                return false;
            }

            const entered = parseFloat(document.getElementById('pmtAmt').value || 0);
            if (!currentData) {
                e.preventDefault();
                alert('Pupil details not loaded. Please wait and try again.');
                return false;
            }

            // build allocation breakdown (previous outstanding then terms up to highest selected)
            const termCheckboxesNow = Array.from(document.querySelectorAll('#termsCheckboxes input[type="checkbox"]'));
            const selected = termCheckboxesNow.filter(cb => cb.checked).map(cb => parseInt(cb.value,10));
            const maxTerm = selected.length ? Math.max(...selected) : parseInt(currentData.currentTerm || '1', 10);

            const allocations = [];
            let remainingToAllocate = entered;

            const prevOut = parseFloat(currentData.previousOutstanding || 0);
            if (prevOut > 0) {
                const allocPrev = Math.min(prevOut, remainingToAllocate);
                allocations.push({target: 'Previous outstanding', fee: prevOut, paid: 0, remaining: prevOut, allocated: allocPrev});
                remainingToAllocate -= allocPrev;
            }

            for (let t = 1; t <= maxTerm; t++) {
                const tr = (currentData.termRemaining && currentData.termRemaining[t]) ? currentData.termRemaining[t] : {fee:0, paid:0, remaining:0};
                const rem = parseFloat(tr.remaining || 0);
                const fee = parseFloat(tr.fee || 0);
                const paid = parseFloat(tr.paid || 0);
                const alloc = Math.min(rem, Math.max(0, remainingToAllocate));
                allocations.push({target: 'Term ' + t, fee: fee, paid: paid, remaining: rem, allocated: alloc});
                remainingToAllocate -= alloc;
            }

            // prepare modal content
            const tbody = document.getElementById('allocationBody');
            tbody.innerHTML = '';
            let totalAllocated = 0;
            allocations.forEach(row => {
                totalAllocated += parseFloat(row.allocated || 0);
                const tr = document.createElement('tr');
                tr.innerHTML = `<td>${row.target}</td><td>K ${currencyFormatter.format(row.fee)}</td><td>K ${currencyFormatter.format(row.paid)}</td><td>K ${currencyFormatter.format(row.remaining)}</td><td>K ${currencyFormatter.format(row.allocated || 0)}</td>`;
                tbody.appendChild(tr);
            });
            document.getElementById('allocationTotal').textContent = currencyFormatter.format(totalAllocated);
            document.getElementById('allocationUnallocated').textContent = currencyFormatter.format(Math.max(0, remainingToAllocate));

            // show modal
            const allocationModal = new bootstrap.Modal(document.getElementById('allocationModal'));
            allocationModal.show();

            // intercept confirm button
            const confirmBtn = document.getElementById('confirmAllocationBtn');
            const onConfirm = function() {
                allocationConfirmed = true;
                allocationModal.hide();
                confirmBtn.removeEventListener('click', onConfirm);
                paymentForm.submit();
            };
            confirmBtn.addEventListener('click', onConfirm);

            e.preventDefault();
            return false;
        });
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
<?php if (!empty($selectedPupil)): ?>
<script>
document.addEventListener('DOMContentLoaded', function(){
    const sel = document.getElementById('pupilSelect');
    if (sel) sel.dispatchEvent(new Event('change'));
});
</script>
<?php endif; ?>