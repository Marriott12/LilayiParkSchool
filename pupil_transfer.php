<?php
require_once 'includes/bootstrap.php';
require_once 'includes/Auth.php';

// Enable output buffering so we can redirect after includes that send output
if (!ob_get_level()) ob_start();

Auth::requireLogin();

require_once 'modules/roles/RolesModel.php';
$rolesModel = new RolesModel();
if (!$rolesModel->userHasPermission(Auth::id(), 'manage_pupils')) {
    Session::setFlash('error', 'You do not have permission to manage pupil transfers.');
    header('Location: 403.php');
    exit;
}

require_once 'modules/pupils/PupilModel.php';
$pupilModel = new PupilModel();

// AJAX endpoint to get outstanding balance for a pupil
if (isset($_GET['action']) && $_GET['action'] === 'getBalance' && isset($_GET['pupilID'])) {
    header('Content-Type: application/json');
    try {
        $pupilID = $_GET['pupilID'];
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT classID FROM Pupil_Class WHERE pupilID = ? LIMIT 1");
        $stmt->execute([$pupilID]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $balance = 0;
        $futurePaid = 0.00;
        $futurePayments = [];
        if ($row && !empty($row['classID'])) {
            $classID = $row['classID'];
            $currentYear = date('Y');
            $stmt = $db->prepare("SELECT feeAmt FROM Fees WHERE classID = ? AND year = ? ORDER BY term DESC LIMIT 1");
            $stmt->execute([$classID, $currentYear]);
            $feeRow = $stmt->fetch(PDO::FETCH_ASSOC);
            $totalFee = $feeRow['feeAmt'] ?? 0;

            $stmt = $db->prepare("SELECT COALESCE(SUM(pmtAmt),0) as totalPaid FROM Payment WHERE pupilID = ? AND classID = ?");
            $stmt->execute([$pupilID, $classID]);
            $payRow = $stmt->fetch(PDO::FETCH_ASSOC);
            $totalPaid = $payRow['totalPaid'] ?? 0;

            // Balance for current fee
            $balance = ((float)$totalFee - (float)$totalPaid);
            if ($balance < 0) $balance = 0.00;

            // Any amount paid beyond the current totalFee likely represents payment for a coming term
            $futurePaid = max(0.00, round(((float)$totalPaid - (float)$totalFee), 2));

            // Get recent payment ids for context
            $stmt = $db->prepare('SELECT payID, pmtAmt, paymentDate FROM Payment WHERE pupilID = ? AND classID = ? ORDER BY paymentDate DESC LIMIT 10');
            $stmt->execute([$pupilID, $classID]);
            $pays = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($pays as $pp) {
                $futurePayments[] = $pp['payID'];
            }
        }

        echo json_encode(['success' => true, 'balance' => (float)$balance, 'futurePaid' => (float)$futurePaid, 'futurePayments' => $futurePayments]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

$pageTitle = 'Pupil Transfer';
$currentPage = 'pupil_transfer';

// Handle submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CSRF::requireToken()) {
        $error = 'Security validation failed. Please try again.';
    } else {
        $pupilID = $_POST['pupilID'] ?? '';
        $transfer_day = $_POST['transfer_day'] ?? '';
        $transfer_month = $_POST['transfer_month'] ?? '';
        $transfer_year = $_POST['transfer_year'] ?? '';
        $transferDate = '';
        if ($transfer_year && $transfer_month && $transfer_day) {
            $transferDate = sprintf('%04d-%02d-%02d', (int)$transfer_year, (int)$transfer_month, (int)$transfer_day);
        }
        $newSchool = trim($_POST['newSchool'] ?? '');
        $reason = trim($_POST['reason'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        if (empty($pupilID)) {
            $error = 'Please select a pupil';
        } elseif (empty($transferDate)) {
            $error = 'Please provide a transfer date';
        } /*elseif (empty($newSchool)) {
            $error = 'Please provide the receiving school name';
        }*/

        if (!isset($error)) {
            try {
                $db = Database::getInstance()->getConnection();

                // Recompute outstanding server-side to avoid trusting client value
                $stmt = $db->prepare("SELECT classID FROM Pupil_Class WHERE pupilID = ? LIMIT 1");
                $stmt->execute([$pupilID]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $serverOutstanding = 0.00;
                $fromClassID = null;
                if ($row && !empty($row['classID'])) {
                    $fromClassID = $row['classID'];
                    $currentYear = date('Y');
                    // Attempt to find per-class current year fee (last term row as fallback)
                    $stmt = $db->prepare("SELECT feeAmt FROM Fees WHERE classID = ? AND year = ? ORDER BY term DESC LIMIT 1");
                    $stmt->execute([$fromClassID, $currentYear]);
                    $feeRow = $stmt->fetch(PDO::FETCH_ASSOC);
                    $totalFee = $feeRow['feeAmt'] ?? 0;

                    $stmt = $db->prepare("SELECT COALESCE(SUM(pmtAmt),0) as totalPaid FROM Payment WHERE pupilID = ? AND classID = ?");
                    $stmt->execute([$pupilID, $fromClassID]);
                    $payRow = $stmt->fetch(PDO::FETCH_ASSOC);
                    $totalPaid = $payRow['totalPaid'] ?? 0;

                    $serverOutstanding = round(((float)$totalFee - (float)$totalPaid), 2);
                    if ($serverOutstanding < 0) $serverOutstanding = 0.00;
                }

                // Validate posted balance if present -- prefer server value
                $postedBalance = isset($_POST['balance']) ? (float)str_replace(',', '', $_POST['balance']) : null;
                if ($postedBalance !== null) {
                    // Allow tiny rounding diffs (1 cent). If larger mismatch, prefer server value but warn.
                    if (abs($postedBalance - $serverOutstanding) > 0.01) {
                        error_log(sprintf('Pupil transfer: posted balance (%s) differs from server (%s) for pupil %s. Overriding with server value.', number_format($postedBalance,2), number_format($serverOutstanding,2), $pupilID));
                        Session::setFlash('warning', 'Outstanding balance was adjusted to the current server value (K ' . number_format($serverOutstanding, 2) . ').');
                        // Do not treat as fatal error; continue using $serverOutstanding as authoritative
                    }
                }

                if (!isset($error)) {
                    // Ensure Pupil_Transfer table exists with canonical schema (including pupil snapshot fields)
                    $createSql = "CREATE TABLE IF NOT EXISTS Pupil_Transfer (
                        transferID INT AUTO_INCREMENT PRIMARY KEY,
                        pupilID VARCHAR(10) NOT NULL,
                        fName VARCHAR(150) DEFAULT NULL,
                        lName VARCHAR(150) DEFAULT NULL,
                        fromClassID VARCHAR(10) DEFAULT NULL,
                        toSchool VARCHAR(150) DEFAULT NULL,
                        toClassID VARCHAR(10) DEFAULT NULL,
                        transferDate DATE NOT NULL,
                        reason TEXT,
                        notes TEXT,
                        outstanding DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                        futurePaidAmount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                        futurePaidTerm VARCHAR(50) DEFAULT NULL,
                        futurePaidDetails JSON DEFAULT NULL,
                        DoB DATE DEFAULT NULL,
                        gender VARCHAR(2) DEFAULT NULL,
                        enrollDate DATE DEFAULT NULL,
                        parentEmail VARCHAR(150) DEFAULT NULL,
                        phone VARCHAR(50) DEFAULT NULL,
                        createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
                    $db->exec($createSql);

                    // Ensure any missing columns (from older deployments) are added
                    $colsToEnsure = [
                        "fName" => "VARCHAR(150) DEFAULT NULL",
                        "lName" => "VARCHAR(150) DEFAULT NULL",
                        "futurePaidAmount" => "DECIMAL(10,2) NOT NULL DEFAULT 0.00",
                        "futurePaidTerm" => "VARCHAR(50) DEFAULT NULL",
                        "futurePaidDetails" => "JSON DEFAULT NULL",
                        "DoB" => "DATE DEFAULT NULL",
                        "gender" => "VARCHAR(2) DEFAULT NULL",
                        "enrollDate" => "DATE DEFAULT NULL",
                        "parentEmail" => "VARCHAR(150) DEFAULT NULL",
                        "phone" => "VARCHAR(50) DEFAULT NULL"
                    ];

                    foreach ($colsToEnsure as $col => $definition) {
                        try {
                            $check = $db->prepare("SHOW COLUMNS FROM Pupil_Transfer LIKE ?");
                            $check->execute([$col]);
                            $found = $check->fetch(PDO::FETCH_ASSOC);
                            if (!$found) {
                                $db->exec("ALTER TABLE Pupil_Transfer ADD COLUMN {$col} {$definition}");
                            }
                        } catch (Exception $e) {
                            // Non-fatal: log and continue
                            error_log('Could not ensure column ' . $col . ' on Pupil_Transfer: ' . $e->getMessage());
                        }
                    }

                    // If Pupil_Transfer had a foreign key to Pupil, drop it so we can delete the pupil record
                    try {
                        $fkStmt = $db->prepare("SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'Pupil_Transfer' AND REFERENCED_TABLE_NAME = 'Pupil'");
                        $fkStmt->execute();
                        $fks = $fkStmt->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($fks as $f) {
                            if (!empty($f['CONSTRAINT_NAME'])) {
                                $constraint = $f['CONSTRAINT_NAME'];
                                try {
                                    $db->exec("ALTER TABLE Pupil_Transfer DROP FOREIGN KEY `" . $constraint . "`");
                                } catch (Exception $inner) {
                                    error_log('Failed to drop FK ' . $constraint . ' on Pupil_Transfer: ' . $inner->getMessage());
                                }
                            }
                        }
                    } catch (Exception $e) {
                        // ignore
                    }

                    // Insert transfer record and optionally remove class assignment within a transaction
                    // Compute future-paid metadata (amount paid beyond current year's fee)
                    $futurePaid = 0.00;
                    $futurePayments = [];
                    // Fetch pupil snapshot fields to store in transfer record
                    $pupilSnapshot = null;
                    try {
                        $stmtP = $db->prepare('SELECT fName, lName, DoB, gender, enrollDate, parentEmail, phone FROM Pupil WHERE pupilID = ? LIMIT 1');
                        $stmtP->execute([$pupilID]);
                        $pupilSnapshot = $stmtP->fetch(PDO::FETCH_ASSOC);
                    } catch (Exception $e) {
                        $pupilSnapshot = null;
                    }
                    if ($fromClassID) {
                        $stmt = $db->prepare("SELECT COALESCE(SUM(pmtAmt),0) as totalPaid FROM Payment WHERE pupilID = ? AND classID = ?");
                        $stmt->execute([$pupilID, $fromClassID]);
                        $payRow = $stmt->fetch(PDO::FETCH_ASSOC);
                        $totalPaid = $payRow['totalPaid'] ?? 0;
                        $futurePaid = max(0.00, round(((float)$totalPaid - (float)$totalFee), 2));

                        $stmt = $db->prepare('SELECT payID, pmtAmt, paymentDate FROM Payment WHERE pupilID = ? AND classID = ? ORDER BY paymentDate DESC LIMIT 20');
                        $stmt->execute([$pupilID, $fromClassID]);
                        $pays = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($pays as $pp) $futurePayments[] = $pp['payID'];
                    }

                    $db->beginTransaction();

                    // Build insert using only columns that actually exist to avoid unknown-column errors
                    $desired = [
                        'fName' => $pupilSnapshot['fName'] ?? null,
                        'lName' => $pupilSnapshot['lName'] ?? null,
                        'pupilID' => $pupilID,
                        'fromClassID' => $fromClassID,
                        'toSchool' => $newSchool,
                        'toClassID' => null,
                        'transferDate' => $transferDate,
                        'reason' => $reason,
                        'notes' => $notes,
                        'outstanding' => $serverOutstanding,
                        'futurePaidAmount' => $futurePaid,
                        'futurePaidTerm' => null,
                        'futurePaidDetails' => json_encode($futurePayments),
                        'DoB' => $pupilSnapshot['DoB'] ?? null,
                        'gender' => $pupilSnapshot['gender'] ?? null,
                        'enrollDate' => $pupilSnapshot['enrollDate'] ?? null,
                        'parentEmail' => $pupilSnapshot['parentEmail'] ?? null,
                        'phone' => $pupilSnapshot['phone'] ?? null
                    ];

                    $existingCols = [];
                    try {
                        $colStmt = $db->query('SHOW COLUMNS FROM Pupil_Transfer');
                        $cols = $colStmt->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($cols as $c) $existingCols[] = $c['Field'];
                    } catch (Exception $e) {
                        // If we cannot fetch columns, fall back to desired keys (riskier)
                        $existingCols = array_keys($desired);
                    }

                    $insertData = [];
                    foreach ($desired as $k => $v) {
                        if (in_array($k, $existingCols, true)) {
                            $insertData[$k] = $v;
                        }
                    }

                    if (empty($insertData)) {
                        throw new Exception('No valid columns available to insert Pupil_Transfer record.');
                    }

                    $fields = array_keys($insertData);
                    $placeholders = implode(',', array_fill(0, count($fields), '?'));
                    $sql = 'INSERT INTO Pupil_Transfer (' . implode(',', $fields) . ') VALUES (' . $placeholders . ')';
                    $ins = $db->prepare($sql);
                    $ins->execute(array_values($insertData));

                    // Remove pupil from class so they are no longer assigned
                    if ($fromClassID) {
                        $del = $db->prepare('DELETE FROM Pupil_Class WHERE pupilID = ?');
                        $del->execute([$pupilID]);
                    }

                    // Mark pupil as transferred (soft-delete) instead of deleting to preserve referential integrity
                    try {
                        // Ensure transferred columns exist on Pupil
                        try {
                            $check = $db->query("SHOW COLUMNS FROM Pupil LIKE 'transferred'");
                            $found = $check->fetch(PDO::FETCH_ASSOC);
                            if (!$found) {
                                $db->exec("ALTER TABLE Pupil ADD COLUMN transferred TINYINT(1) NOT NULL DEFAULT 0, ADD COLUMN transferredAt DATETIME DEFAULT NULL");
                            }
                        } catch (Exception $ex) {
                            // ignore if unable to alter
                        }

                        $upd = $db->prepare('UPDATE Pupil SET transferred = 1, transferredAt = NOW() WHERE pupilID = ?');
                        $upd->execute([$pupilID]);
                    } catch (Exception $e) {
                        error_log('Failed to mark pupil as transferred for ' . $pupilID . ': ' . $e->getMessage());
                    }

                    $db->commit();

                    Session::setFlash('success', 'Pupil transfer recorded successfully.');
                    // Redirect to transfer report (pupil may be archived)
                    header('Location: pupil_transfer_report.php');
                    exit;
                }
            } catch (Exception $e) {
                if ($db && $db->inTransaction()) $db->rollBack();
                $error = 'Database error: ' . $e->getMessage();
            }
        }
    }
}

// Compute initial balance if the form was re-submitted or a pupil is pre-selected
$initialBalance = '0.00';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['pupilID'])) {
    try {
        $pupilID_check = $_POST['pupilID'];
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT classID FROM Pupil_Class WHERE pupilID = ? LIMIT 1");
        $stmt->execute([$pupilID_check]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $balance = 0;
        if ($row && !empty($row['classID'])) {
            $classID = $row['classID'];
            $currentYear = date('Y');
            $stmt = $db->prepare("SELECT feeAmt FROM Fees WHERE classID = ? AND year = ? ORDER BY term DESC LIMIT 1");
            $stmt->execute([$classID, $currentYear]);
            $feeRow = $stmt->fetch(PDO::FETCH_ASSOC);
            $totalFee = $feeRow['feeAmt'] ?? 0;

            $stmt = $db->prepare("SELECT SUM(pmtAmt) as totalPaid FROM Payment WHERE pupilID = ? AND classID = ?");
            $stmt->execute([$pupilID_check, $classID]);
            $payRow = $stmt->fetch(PDO::FETCH_ASSOC);
            $totalPaid = $payRow['totalPaid'] ?? 0;

            $balance = $totalFee - $totalPaid;
        }
        $initialBalance = number_format((float)$balance, 2, '.', '');
    } catch (Exception $e) {
        $initialBalance = '0.00';
    }
}

$allPupils = $pupilModel->getAllWithParents();

// Include header here so any redirects above have already run
require_once 'includes/header.php';
?>

<div class="mb-4">
    <a href="pupils_list.php" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back to Pupils
    </a>
</div>

<div class="card">
    <div class="card-header" style="background: linear-gradient(135deg, #2d5016 0%, #5cb85c 100%); color: white;">
        <h5 class="mb-0">
            <i class="bi bi-arrow-right-circle"></i> Record Pupil Transfer
        </h5>
    </div>
    <div class="card-body">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="bi bi-exclamation-triangle-fill"></i> <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="row g-3">
            <?= CSRF::field() ?>

            <div class="col-md-6">
                <label class="form-label">Select Pupil <span class="text-danger">*</span></label>
                <select name="pupilID" class="form-select" required>
                    <option value="">-- Select Pupil --</option>
                    <?php foreach ($allPupils as $p): ?>
                        <option value="<?= htmlspecialchars($p['pupilID']) ?>" <?= (isset($_POST['pupilID']) && $_POST['pupilID'] == $p['pupilID']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars(($p['fName'] ?? '') . ' ' . ($p['lName'] ?? '') . ' (' . ($p['pupilID'] ?? '') . ')') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label">Transfer Date <span class="text-danger">*</span></label>
                <div class="row g-1">
                    <div class="col">
                        <select name="transfer_day" class="form-select" required>
                            <option value="">Day</option>
                            <?php for ($d = 1; $d <= 31; $d++): ?>
                                <option value="<?= $d ?>" <?= (isset($_POST['transfer_day']) && $_POST['transfer_day'] == $d) ? 'selected' : '' ?>><?= $d ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col">
                        <select name="transfer_month" class="form-select" required>
                            <option value="">Month</option>
                            <?php for ($m = 1; $m <= 12; $m++): $mn = DateTime::createFromFormat('!m', $m)->format('F'); ?>
                                <option value="<?= $m ?>" <?= (isset($_POST['transfer_month']) && $_POST['transfer_month'] == $m) ? 'selected' : '' ?>><?= $mn ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col">
                        <select name="transfer_year" class="form-select" required>
                            <option value="">Year</option>
                            <?php $cy = date('Y'); for ($y = $cy; $y >= $cy - 5; $y--): ?>
                                <option value="<?= $y ?>" <?= (isset($_POST['transfer_year']) && $_POST['transfer_year'] == $y) ? 'selected' : '' ?>><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <label class="form-label">Receiving School</label>
                <input type="text" name="newSchool" class="form-control" value="<?= htmlspecialchars($_POST['newSchool'] ?? '') ?>">
            </div>

            <div class="col-md-6">
                <label class="form-label">Reason</label>
                <input type="text" name="reason" class="form-control" value="<?= htmlspecialchars($_POST['reason'] ?? '') ?>">
            </div>

            <div class="col-md-6">
                <label class="form-label">Outstanding Balance</label>
                <input type="text" id="outstandingBalance" class="form-control" value="<?= htmlspecialchars($initialBalance) ?>" readonly>
                <input type="hidden" name="balance" id="balanceHidden" value="<?= htmlspecialchars($initialBalance) ?>">
                <input type="hidden" name="futurePaidAmount" id="futurePaidAmount" value="0.00">
                <input type="hidden" name="futurePaidDetails" id="futurePaidDetails" value="">
            </div>

            <div class="col-12" id="futurePaidBannerContainer" style="display:none;">
                <div class="alert alert-info" id="futurePaidBanner">
                    <!-- filled by JS -->
                </div>
            </div>

            <div class="col-12">
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-control" rows="4"><?= htmlspecialchars($_POST['notes'] ?? '') ?></textarea>
            </div>

            <div class="col-12">
                <button class="btn btn-success"><i class="bi bi-save"></i> Record Transfer</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function(){
    const pupilSelect = document.querySelector('select[name="pupilID"]');
    const outInput = document.getElementById('outstandingBalance');
    const hidden = document.getElementById('balanceHidden');

    function fetchBalance(pupilID){
        if(!pupilID){
            if(outInput) outInput.value = '0.00';
            if(hidden) hidden.value = '0.00';
            return;
        }
        fetch('pupil_transfer.php?action=getBalance&pupilID=' + encodeURIComponent(pupilID))
            .then(res => res.json())
            .then(data => {
                if(data && data.success){
                    const b = parseFloat(data.balance) || 0;
                    const fp = parseFloat(data.futurePaid || 0);
                    if(outInput) outInput.value = b.toFixed(2);
                    if(hidden) hidden.value = b.toFixed(2);
                    const fpEl = document.getElementById('futurePaidAmount');
                    const fpDetailsEl = document.getElementById('futurePaidDetails');
                    const bannerContainer = document.getElementById('futurePaidBannerContainer');
                    const banner = document.getElementById('futurePaidBanner');
                    if (fp > 0) {
                        if (fpEl) fpEl.value = fp.toFixed(2);
                        if (fpDetailsEl) fpDetailsEl.value = JSON.stringify(data.futurePayments || []);
                        if (bannerContainer && banner) {
                            banner.innerHTML = `<strong>Note:</strong> This pupil has paid K ${fp.toFixed(2)} towards a future term. Outstanding is shown as K 0.00.`;
                            bannerContainer.style.display = 'block';
                        }
                    } else {
                        if (fpEl) fpEl.value = '0.00';
                        if (fpDetailsEl) fpDetailsEl.value = '';
                        if (bannerContainer) bannerContainer.style.display = 'none';
                    }
                } else {
                    if(outInput) outInput.value = '0.00';
                    if(hidden) hidden.value = '0.00';
                }
            }).catch(err => {
                if(outInput) outInput.value = '0.00';
                if(hidden) hidden.value = '0.00';
            });
    }

    if(pupilSelect){
        pupilSelect.addEventListener('change', function(){ fetchBalance(this.value); });
        if(pupilSelect.value) fetchBalance(pupilSelect.value);
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
<?php if (ob_get_level()) { ob_end_flush(); } ?>
