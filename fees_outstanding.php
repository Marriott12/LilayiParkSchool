<?php
require_once 'includes/bootstrap.php';
require_once 'includes/Auth.php';
require_once 'includes/PermissionHelper.php';

Auth::requireLogin();

require_once 'modules/roles/RolesModel.php';
$rolesModel = new RolesModel();
if (!$rolesModel->userHasPermission(Auth::id(), 'view_fees')) {
    Session::setFlash('error', 'You do not have permission to view fee reports.');
    header('Location: 403.php');
    exit;
}

require_once 'modules/pupils/PupilModel.php';
require_once 'modules/fees/FeesModel.php';
require_once 'modules/payments/PaymentModel.php';

$pupilModel = new PupilModel();
$feesModel = new FeesModel();
$paymentModel = new PaymentModel();

$pupils = $pupilModel->getAllWithParents();
$rows = [];

foreach ($pupils as $p) {
    $details = $pupilModel->getPupilWithParent($p['pupilID']);
    $className = $details['className'] ?? '';
    $classID = $details['classID'] ?? null;

    $feeAmt = 0;
    if ($classID) {
        $classFees = $feesModel->getFeesByClass($classID);
        if (!empty($classFees)) {
            usort($classFees, function($a, $b) { return ($b['term'] ?? 0) <=> ($a['term'] ?? 0); });
            $feeAmt = $classFees[0]['feeAmt'] ?? 0;
        }
    }

    $paymentsForPupil = $paymentModel->getPaymentsByPupil($p['pupilID']);
    $amountPaid = 0;
    foreach ($paymentsForPupil as $pay) {
        $amountPaid += (float)($pay['pmtAmt'] ?? 0);
    }

    $balance = $feeAmt - $amountPaid;

    if ($balance > 0) {
        $rows[] = [
            'pupilID' => $p['pupilID'],
            'fName' => $p['fName'],
            'lName' => $p['lName'],
            'className' => $className,
            'amountPaid' => $amountPaid,
            'balance' => $balance
        ];
    }
}

usort($rows, function($a, $b) { return $b['balance'] <=> $a['balance']; });

$pageTitle = 'Outstanding Balances';
$currentPage = 'fees_outstanding';

// Handle export requests BEFORE any output
if (!empty($_GET['export'])) {
    require_once 'modules/reports/export_handler.php';
    $format = $_GET['export'] === 'pdf' ? 'pdf' : 'excel';
    if ($format === 'pdf') {
        exportToPDF('fees_outstanding', $rows, 'Outstanding Balances');
    } else {
        exportToExcel('fees_outstanding', $rows, 'Outstanding Balances');
    }
    exit;
}

require_once 'includes/header.php';
?>


<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-exclamation-circle"></i> Outstanding Balance</h2>
    <div>
        <a href="?export=pdf" class="btn btn-sm me-2" style="background-color: #d9534f; color: white;"><i class="bi bi-file-pdf"></i> Export PDF</a>
        <a href="?export=excel" class="btn btn-sm" style="background-color: #5cb85c; color: white;"><i class="bi bi-file-excel"></i> Export Excel</a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead style="background-color: #f8f9fa;">
                    <tr>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Class</th>
                        <th>Amount Paid (ZMW)</th>
                        <th>Balance (ZMW)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-4">
                            <i class="bi bi-inbox" style="font-size: 3rem; opacity: 0.3;"></i>
                            <p class="text-muted mt-2">No outstanding balances found</p>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars($r['fName']) ?></td>
                        <td><?= htmlspecialchars($r['lName']) ?></td>
                        <td><?= htmlspecialchars($r['className']) ?></td>
                        <td><strong style="color: #2d5016;">K <?= number_format($r['amountPaid'], 2) ?></strong></td>
                        <td><strong style="color: #d9534f;">K <?= number_format($r['balance'], 2) ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
