<?php
require_once 'includes/bootstrap.php';
require_once 'includes/Auth.php';

Auth::requireLogin();

require_once 'modules/roles/RolesModel.php';
$rolesModel = new RolesModel();
if (!$rolesModel->userHasPermission(Auth::id(), 'view_fees')) {
    Session::setFlash('error', 'You do not have permission to view fees.');
    header('Location: /LilayiParkSchool/403.php');
    exit;
}

require_once 'modules/fees/FeesModel.php';
require_once 'modules/classes/ClassModel.php';

$feeID = $_GET['id'] ?? null;
if (empty($feeID)) {
    header('Location: fees_list.php');
    exit;
}

$feesModel = new FeesModel();
$fee = $feesModel->getById($feeID);

$className = '';
if (!empty($fee['classID'])) {
    $classModel = new ClassModel();
    $class = $classModel->getById($fee['classID']);
    $className = $class['className'] ?? '';
}

$pageTitle = 'Fee Details';
$currentPage = 'fees';
require_once 'includes/header.php';
require_once 'includes/PermissionHelper.php';
?>

<!-- Page Header with Actions -->
<div class="row mb-4">
    <div class="col-md-8">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="fees_list.php" class="text-decoration-none">Fees</a></li>
                <li class="breadcrumb-item active" aria-current="page">View Details</li>
            </ol>
        </nav>
        <h2 class="mb-0">
            <i class="bi bi-cash-coin me-2" style="color: #2d5016;"></i>
            <span style="color: #2d5016; font-weight: 600;">Fee Details</span>
        </h2>
    </div>
    <div class="col-md-4 text-end align-self-end">
        <div class="btn-group" role="group">
            <a href="fees_list.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
            <?php if (PermissionHelper::canManage('fees')): ?>
            <a href="fees_form.php?id=<?= $feeID ?>" class="btn btn-warning">
                <i class="bi bi-pencil me-1"></i> Edit
            </a>
            <a href="delete.php?module=fees&id=<?= $feeID ?>" 
               class="btn btn-danger"
               onclick="return confirm('Are you sure you want to delete this fee? This action cannot be undone.');">
                <i class="bi bi-trash me-1"></i> Delete
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Fee Information -->
<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <div class="mb-3">
                    <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center" 
                         style="width: 120px; height: 120px;">
                        <i class="bi bi-cash-coin" style="font-size: 5rem; color: #2d5016;"></i>
                    </div>
                </div>
                <h4 class="mb-1">Fee Structure</h4>
                <p class="text-muted mb-3"><?= htmlspecialchars($fee['description'] ?? 'Fee') ?></p>
                <div class="mb-3">
                    <div class="display-6 fw-bold" style="color: #2d5016;">
                        K <?= number_format($fee['feeAmount'] ?? $fee['amount'] ?? 0, 2) ?>
                    </div>
                </div>
                <hr>
                <div class="text-start">
                    <small class="text-muted d-block mb-2">Quick Info</small>
                    <div class="d-flex align-items-center mb-2">
                        <i class="bi bi-hash text-muted me-2"></i>
                        <span class="small">Fee ID: <?= htmlspecialchars($fee['feeID'] ?? 'N/A') ?></span>
                    </div>
                    <div class="d-flex align-items-center mb-2">
                        <i class="bi bi-calendar text-muted me-2"></i>
                        <span class="small">Term: <?= htmlspecialchars($fee['term'] ?? 'N/A') ?></span>
                    </div>
                    <div class="d-flex align-items-center">
                        <i class="bi bi-building text-muted me-2"></i>
                        <span class="small"><?= htmlspecialchars($className ?: 'All Classes') ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <!-- Fee Details -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">
                    <i class="bi bi-info-circle-fill me-2" style="color: #2d5016;"></i>Fee Information
                </h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="text-muted small mb-1">Fee ID</label>
                        <p class="mb-0 fw-semibold"><?= htmlspecialchars($fee['feeID'] ?? 'N/A') ?></p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small mb-1">Amount</label>
                        <p class="mb-0 fw-semibold text-success">
                            K <?= number_format($fee['feeAmount'] ?? $fee['amount'] ?? 0, 2) ?>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small mb-1">Term</label>
                        <p class="mb-0">
                            <span class="badge bg-primary px-3 py-2">
                                <?= htmlspecialchars($fee['term'] ?? 'N/A') ?>
                            </span>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small mb-1">Associated Class</label>
                        <p class="mb-0 fw-semibold">
                            <?= htmlspecialchars($className ?: 'All Classes') ?>
                        </p>
                    </div>
                    <div class="col-md-12">
                        <label class="text-muted small mb-1">Description</label>
                        <p class="mb-0"><?= nl2br(htmlspecialchars($fee['description'] ?? 'No description available')) ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Additional Information -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">
                    <i class="bi bi-calendar-event me-2" style="color: #2d5016;"></i>Payment Schedule
                </h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info mb-0">
                    <i class="bi bi-info-circle me-2"></i>
                    This fee is applicable for <strong><?= htmlspecialchars($fee['term'] ?? 'the specified term') ?></strong>
                    <?php if ($className): ?>
                    for <strong><?= htmlspecialchars($className) ?></strong>
                    <?php else: ?>
                    for <strong>all classes</strong>
                    <?php endif; ?>
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
