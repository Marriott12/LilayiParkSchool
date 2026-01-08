<?php
require_once 'includes/bootstrap.php';

RBAC::requireAuth();
RBAC::requirePermission('fees', 'read');

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
?>

<div class="mb-4">
    <a href="fees_list.php" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back to Fees
    </a>
</div>

<div class="card">
    <div class="card-header" style="background: linear-gradient(135deg, #2d5016 0%, #5cb85c 100%); color: white;">
        <h5 class="mb-0"><?= htmlspecialchars($fee['description'] ?? ('Fee ' . ($fee['feeID'] ?? ''))) ?></h5>
    </div>
    <div class="card-body">
        <dl class="row">
            <dt class="col-sm-3">Amount</dt>
            <dd class="col-sm-9"><?= htmlspecialchars($fee['feeAmount'] ?? $fee['amount'] ?? '') ?></dd>

            <dt class="col-sm-3">Term</dt>
            <dd class="col-sm-9"><?= htmlspecialchars($fee['term'] ?? '') ?></dd>

            <dt class="col-sm-3">Class</dt>
            <dd class="col-sm-9"><?= htmlspecialchars($className) ?></dd>
        </dl>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
