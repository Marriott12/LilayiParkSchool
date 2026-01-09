<?php
require_once 'includes/bootstrap.php';
require_once 'includes/Auth.php';

Auth::requireLogin();

require_once 'modules/roles/RolesModel.php';
$rolesModel = new RolesModel();
if (!$rolesModel->userHasPermission(Auth::id(), 'view_payments')) {
    Session::setFlash('error', 'You do not have permission to view payments.');
    header('Location: /LilayiParkSchool/403.php');
    exit;
}

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
require_once 'includes/PermissionHelper.php';
?>

<!-- Page Header with Actions -->
<div class="row mb-4">
    <div class="col-md-8">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="payments_list.php" class="text-decoration-none">Payments</a></li>
                <li class="breadcrumb-item active" aria-current="page">View Details</li>
            </ol>
        </nav>
        <h2 class="mb-0">
            <i class="bi bi-credit-card me-2" style="color: #2d5016;"></i>
            <span style="color: #2d5016; font-weight: 600;">Payment Details</span>
        </h2>
    </div>
    <div class="col-md-4 text-end align-self-end">
        <div class="btn-group" role="group">
            <a href="payments_list.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
            <?php if (PermissionHelper::canManage('payments')): ?>
            <a href="payments_form.php?id=<?= $paymentID ?>" class="btn btn-warning">
                <i class="bi bi-pencil me-1"></i> Edit
            </a>
            <a href="delete.php?module=payments&id=<?= $paymentID ?>" 
               class="btn btn-danger"
               onclick="return confirm('Are you sure you want to delete this payment? This action cannot be undone.');">
                <i class="bi bi-trash me-1"></i> Delete
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Payment Information -->
<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <div class="mb-3">
                    <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center" 
                         style="width: 120px; height: 120px;">
                        <i class="bi bi-credit-card-fill" style="font-size: 5rem; color: #2d5016;"></i>
                    </div>
                </div>
                <h4 class="mb-1">Payment Record</h4>
                <p class="text-muted mb-3">Payment #<?= htmlspecialchars($payment['paymentID'] ?? '') ?></p>
                <div class="mb-3">
                    <div class="display-6 fw-bold text-success">
                        K <?= number_format($payment['amount'] ?? $payment['feeAmount'] ?? 0, 2) ?>
                    </div>
                </div>
                <div class="mb-3">
                    <span class="badge bg-success px-3 py-2">
                        <i class="bi bi-check-circle me-1"></i>Paid
                    </span>
                </div>
                <hr>
                <div class="text-start">
                    <small class="text-muted d-block mb-2">Quick Info</small>
                    <div class="d-flex align-items-center mb-2">
                        <i class="bi bi-calendar text-muted me-2"></i>
                        <span class="small"><?= htmlspecialchars($payment['paymentDate'] ?? 'N/A') ?></span>
                    </div>
                    <div class="d-flex align-items-center mb-2">
                        <i class="bi bi-bookmark text-muted me-2"></i>
                        <span class="small">Term: <?= htmlspecialchars($payment['term'] ?? 'N/A') ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <!-- Payment Details -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">
                    <i class="bi bi-receipt me-2" style="color: #2d5016;"></i>Transaction Information
                </h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="text-muted small mb-1">Payment ID</label>
                        <p class="mb-0 fw-semibold"><?= htmlspecialchars($payment['paymentID'] ?? 'N/A') ?></p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small mb-1">Amount Paid</label>
                        <p class="mb-0 fw-semibold text-success">
                            K <?= number_format($payment['amount'] ?? $payment['feeAmount'] ?? 0, 2) ?>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small mb-1">Payment Date</label>
                        <p class="mb-0 fw-semibold">
                            <?= $payment['paymentDate'] ? date('M d, Y', strtotime($payment['paymentDate'])) : 'N/A' ?>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small mb-1">Term</label>
                        <p class="mb-0">
                            <span class="badge bg-primary px-3 py-2">
                                <?= htmlspecialchars($payment['term'] ?? 'N/A') ?>
                            </span>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Student Information -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">
                    <i class="bi bi-mortarboard me-2" style="color: #2d5016;"></i>Student Information
                </h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="text-muted small mb-1">Student Name</label>
                        <p class="mb-0 fw-semibold">
                            <?= htmlspecialchars(($payment['pupilFirstName'] ?? $payment['fName'] ?? '') . ' ' . ($payment['pupilLastName'] ?? $payment['lName'] ?? 'N/A')) ?>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small mb-1">Parent/Guardian</label>
                        <p class="mb-0 fw-semibold">
                            <?= htmlspecialchars(($payment['parentFirstName'] ?? '') . ' ' . ($payment['parentLastName'] ?? 'N/A')) ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment Status -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">
                    <i class="bi bi-check-circle me-2" style="color: #2d5016;"></i>Payment Status
                </h5>
            </div>
            <div class="card-body">
                <div class="alert alert-success mb-0">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    This payment has been successfully processed and recorded.
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .card {
        transition: all 0.3s ease;
    }
    .card:hover {
        transform: translateY(-2px);
    }
    .btn {
        transition: all 0.2s ease;
    }
    .btn:hover {
        transform: translateY(-1px);
    }
</style>

<?php require_once 'includes/footer.php'; ?>
