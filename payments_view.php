<?php
require_once 'includes/bootstrap.php';

RBAC::requireAuth();
RBAC::requirePermission('payments', 'read');

require_once 'modules/payments/PaymentModel.php';

$paymentID = $_GET['id'] ?? null;
if (empty($paymentID)) {
    header('Location: payments_list.php');
    exit;
}

$paymentModel = new PaymentModel();
$payment = $paymentModel->getPaymentWithDetails($paymentID) ?: $paymentModel->getById($paymentID);

$pageTitle = 'Payment Details';
$currentPage = 'payments';
require_once 'includes/header.php';
?>

<div class="mb-4">
    <a href="payments_list.php" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back to Payments
    </a>
</div>

<div class="card">
    <div class="card-header" style="background: linear-gradient(135deg, #2d5016 0%, #5cb85c 100%); color: white;">
        <h5 class="mb-0">Payment #<?= htmlspecialchars($payment['paymentID'] ?? '') ?></h5>
    </div>
    <div class="card-body">
        <dl class="row">
            <dt class="col-sm-3">Pupil</dt>
            <dd class="col-sm-9"><?= htmlspecialchars(($payment['pupilFirstName'] ?? $payment['fName'] ?? '') . ' ' . ($payment['pupilLastName'] ?? $payment['lName'] ?? '')) ?></dd>

            <dt class="col-sm-3">Parent</dt>
            <dd class="col-sm-9"><?= htmlspecialchars(($payment['parentFirstName'] ?? '') . ' ' . ($payment['parentLastName'] ?? '')) ?></dd>

            <dt class="col-sm-3">Term</dt>
            <dd class="col-sm-9"><?= htmlspecialchars($payment['term'] ?? '') ?></dd>

            <dt class="col-sm-3">Amount</dt>
            <dd class="col-sm-9"><?= htmlspecialchars($payment['amount'] ?? $payment['feeAmount'] ?? '') ?></dd>

            <dt class="col-sm-3">Date</dt>
            <dd class="col-sm-9"><?= htmlspecialchars($payment['paymentDate'] ?? '') ?></dd>
        </dl>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
