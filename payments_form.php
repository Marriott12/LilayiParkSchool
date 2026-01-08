<?php
require_once 'includes/bootstrap.php';

RBAC::requireAuth();

$paymentID = $_GET['id'] ?? null;
$isEdit = !empty($paymentID);

if ($isEdit) {
    RBAC::requirePermission('payments', 'update');
} else {
    RBAC::requirePermission('payments', 'create');
}

require_once 'modules/payments/PaymentModel.php';
require_once 'modules/pupils/PupilModel.php';
require_once 'modules/fees/FeesModel.php';

$paymentModel = new PaymentModel();
$pupilModel = new PupilModel();
$feesModel = new FeesModel();

// Get all pupils and fees for dropdowns
$pupils = $pupilModel->getAllWithParents();
$fees = $feesModel->getAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'pupilID' => intval($_POST['pupilID'] ?? 0),
        'feeID' => !empty($_POST['feeID']) ? intval($_POST['feeID']) : null,
        'amount' => floatval($_POST['amount'] ?? 0),
        'paymentDate' => $_POST['paymentDate'] ?? date('Y-m-d'),
        'paymentMethod' => $_POST['paymentMethod'] ?? '',
        'referenceNumber' => trim($_POST['referenceNumber'] ?? ''),
        'term' => $_POST['term'] ?? '',
        'academicYear' => trim($_POST['academicYear'] ?? '2025/2026'),
        'status' => $_POST['status'] ?? 'Completed',
        'notes' => trim($_POST['notes'] ?? ''),
        'createdBy' => Session::get('user_id')
    ];
    
    try {
        if ($isEdit) {
            $paymentModel->update($paymentID, $data);
            $_SESSION['success_message'] = 'Payment updated successfully';
        } else {
            $paymentModel->create($data);
            $_SESSION['success_message'] = 'Payment recorded successfully';
        }
        header('Location: payments_list.php');
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$payment = $isEdit ? $paymentModel->getById($paymentID) : null;

$pageTitle = $isEdit ? 'Edit Payment' : 'Record New Payment';
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
        <h5 class="mb-0">
            <i class="bi bi-credit-card-fill"></i> <?= $pageTitle ?>
        </h5>
    </div>
    <div class="card-body">
        <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle-fill"></i> <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Pupil <span class="text-danger">*</span></label>
                    <select class="form-select" name="pupilID" required>
                        <option value="">Select Pupil</option>
                        <?php foreach ($pupils as $pupil): ?>
                        <option value="<?= $pupil['pupilID'] ?>" 
                                <?= ($payment['pupilID'] ?? '') == $pupil['pupilID'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($pupil['fName'] . ' ' . $pupil['lName'] . ' (' . $pupil['studentNumber'] . ')') ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Fee Type</label>
                    <select class="form-select" name="feeID">
                        <option value="">Other/Custom Payment</option>
                        <?php foreach ($fees as $fee): ?>
                        <option value="<?= $fee['feeID'] ?>" 
                                data-amount="<?= $fee['feeAmount'] ?>"
                                <?= ($payment['feeID'] ?? '') == $fee['feeID'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($fee['feeName'] . ' - K' . number_format($fee['feeAmount'], 2) . ' (Term ' . $fee['term'] . ')') ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Amount (K) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" class="form-control" name="amount" 
                           value="<?= htmlspecialchars($payment['amount'] ?? '') ?>" 
                           placeholder="0.00" required>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Payment Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" name="paymentDate" 
                           value="<?= htmlspecialchars($payment['paymentDate'] ?? date('Y-m-d')) ?>" required>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Payment Method <span class="text-danger">*</span></label>
                    <select class="form-select" name="paymentMethod" required>
                        <option value="">Select Method</option>
                        <option value="Cash" <?= ($payment['paymentMethod'] ?? '') === 'Cash' ? 'selected' : '' ?>>Cash</option>
                        <option value="Bank Transfer" <?= ($payment['paymentMethod'] ?? '') === 'Bank Transfer' ? 'selected' : '' ?>>Bank Transfer</option>
                        <option value="Mobile Money" <?= ($payment['paymentMethod'] ?? '') === 'Mobile Money' ? 'selected' : '' ?>>Mobile Money</option>
                        <option value="Cheque" <?= ($payment['paymentMethod'] ?? '') === 'Cheque' ? 'selected' : '' ?>>Cheque</option>
                    </select>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Reference Number</label>
                    <input type="text" class="form-control" name="referenceNumber" 
                           value="<?= htmlspecialchars($payment['referenceNumber'] ?? '') ?>" 
                           placeholder="Transaction/Receipt number">
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Term <span class="text-danger">*</span></label>
                    <select class="form-select" name="term" required>
                        <option value="">Select Term</option>
                        <option value="Term 1" <?= ($payment['term'] ?? '') === 'Term 1' ? 'selected' : '' ?>>Term 1</option>
                        <option value="Term 2" <?= ($payment['term'] ?? '') === 'Term 2' ? 'selected' : '' ?>>Term 2</option>
                        <option value="Term 3" <?= ($payment['term'] ?? '') === 'Term 3' ? 'selected' : '' ?>>Term 3</option>
                    </select>
                </div>
                
                <div class="col-md-4 mb-3">
                    <label class="form-label">Academic Year</label>
                    <input type="text" class="form-control" name="academicYear" 
                           value="<?= htmlspecialchars($payment['academicYear'] ?? '2025/2026') ?>" 
                           placeholder="2025/2026">
                </div>
                
                <div class="col-md-4 mb-3">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="Completed" <?= ($payment['status'] ?? 'Completed') === 'Completed' ? 'selected' : '' ?>>Completed</option>
                        <option value="Pending" <?= ($payment['status'] ?? '') === 'Pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="Failed" <?= ($payment['status'] ?? '') === 'Failed' ? 'selected' : '' ?>>Failed</option>
                        <option value="Refunded" <?= ($payment['status'] ?? '') === 'Refunded' ? 'selected' : '' ?>>Refunded</option>
                    </select>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Notes</label>
                <textarea class="form-control" name="notes" rows="2" 
                          placeholder="Additional payment information..."><?= htmlspecialchars($payment['notes'] ?? '') ?></textarea>
            </div>
            
            <div class="d-flex gap-2">
                <button type="submit" class="btn" style="background-color: #2d5016; color: white;">
                    <i class="bi bi-save"></i> <?= $isEdit ? 'Update' : 'Record' ?> Payment
                </button>
                <a href="payments_list.php" class="btn btn-secondary">
                    <i class="bi bi-x-circle"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<script>
// Auto-fill amount when fee type is selected
document.querySelector('select[name="feeID"]').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const amount = selectedOption.dataset.amount;
    if (amount) {
        document.querySelector('input[name="amount"]').value = amount;
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
