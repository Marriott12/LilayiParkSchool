<?php
require_once 'includes/bootstrap.php';
require_once 'includes/Auth.php';

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
require_once 'includes/header.php';

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
        } elseif (empty($newSchool)) {
            $error = 'Please provide the receiving school name';
        }

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
                    // Allow tiny rounding diffs (1 cent)
                    if (abs($postedBalance - $serverOutstanding) > 0.01) {
                        $error = 'Outstanding balance mismatch. Please reload the pupil details and try again.';
                    }
                }

                if (!isset($error)) {
                    // Ensure Pupil_Transfer table exists with canonical schema (including future-paid columns)
                    $createSql = "CREATE TABLE IF NOT EXISTS Pupil_Transfer (
                        transferID INT AUTO_INCREMENT PRIMARY KEY,
                        pupilID VARCHAR(10) NOT NULL,
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
                        createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
                    $db->exec($createSql);

                    // Insert transfer record and optionally remove class assignment within a transaction
                    // Compute future-paid metadata (amount paid beyond current year's fee)
                    $futurePaid = 0.00;
                    $futurePayments = [];
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
                    $ins = $db->prepare('INSERT INTO Pupil_Transfer (pupilID, fromClassID, toSchool, toClassID, transferDate, reason, notes, outstanding, futurePaidAmount, futurePaidTerm, futurePaidDetails) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                    $ins->execute([$pupilID, $fromClassID, $newSchool, null, $transferDate, $reason, $notes, $serverOutstanding, $futurePaid, null, json_encode($futurePayments)]);

                    // Remove pupil from class so they are no longer assigned
                    if ($fromClassID) {
                        $del = $db->prepare('DELETE FROM Pupil_Class WHERE pupilID = ?');
                        $del->execute([$pupilID]);
                    }

                    $db->commit();

                    Session::setFlash('success', 'Pupil transfer recorded successfully.');
                    header('Location: pupils_view.php?id=' . urlencode($pupilID));
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
                <label class="form-label">Receiving School <span class="text-danger">*</span></label>
                <input type="text" name="newSchool" class="form-control" value="<?= htmlspecialchars($_POST['newSchool'] ?? '') ?>" required>
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
