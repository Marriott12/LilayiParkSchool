<?php
require_once 'includes/bootstrap.php';

RBAC::requireAuth();

$feeID = $_GET['id'] ?? null;
$isEdit = !empty($feeID);

if ($isEdit) {
    RBAC::requirePermission('fees', 'update');
} else {
    RBAC::requirePermission('fees', 'create');
}

require_once 'modules/fees/FeesModel.php';
$feesModel = new FeesModel();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'feeName' => trim($_POST['feeName'] ?? ''),
        'feeAmount' => floatval($_POST['feeAmount'] ?? 0),
        'term' => $_POST['term'] ?? '',
        'academicYear' => trim($_POST['academicYear'] ?? ''),
        'dueDate' => !empty($_POST['dueDate']) ? $_POST['dueDate'] : null,
        'description' => trim($_POST['description'] ?? '')
    ];
    
    try {
        if ($isEdit) {
            $feesModel->update($feeID, $data);
            $_SESSION['success_message'] = 'Fee updated successfully';
        } else {
            $feesModel->create($data);
            $_SESSION['success_message'] = 'Fee created successfully';
        }
        header('Location: fees_list.php');
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$fee = $isEdit ? $feesModel->getById($feeID) : null;

$pageTitle = $isEdit ? 'Edit Fee' : 'Add New Fee';
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
        <h5 class="mb-0">
            <i class="bi bi-cash-stack"></i> <?= $pageTitle ?>
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
                    <label class="form-label">Fee Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="feeName" 
                           value="<?= htmlspecialchars($fee['feeName'] ?? '') ?>" 
                           placeholder="e.g., Tuition Fee, Transport Fee" required>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Amount (K) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" class="form-control" name="feeAmount" 
                           value="<?= htmlspecialchars($fee['feeAmount'] ?? '') ?>" 
                           placeholder="0.00" required>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Term <span class="text-danger">*</span></label>
                    <select class="form-select" name="term" required>
                        <option value="">Select Term</option>
                        <option value="1" <?= ($fee['term'] ?? '') == '1' ? 'selected' : '' ?>>Term 1</option>
                        <option value="2" <?= ($fee['term'] ?? '') == '2' ? 'selected' : '' ?>>Term 2</option>
                        <option value="3" <?= ($fee['term'] ?? '') == '3' ? 'selected' : '' ?>>Term 3</option>
                    </select>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Academic Year <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="academicYear" 
                           value="<?= htmlspecialchars($fee['academicYear'] ?? '2025/2026') ?>" 
                           placeholder="2025/2026" required>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Due Date</label>
                <input type="date" class="form-control" name="dueDate" 
                       value="<?= htmlspecialchars($fee['dueDate'] ?? '') ?>">
            </div>
            
            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea class="form-control" name="description" rows="3" 
                          placeholder="Additional details about this fee..."><?= htmlspecialchars($fee['description'] ?? '') ?></textarea>
            </div>
            
            <div class="d-flex gap-2">
                <button type="submit" class="btn" style="background-color: #2d5016; color: white;">
                    <i class="bi bi-save"></i> <?= $isEdit ? 'Update' : 'Create' ?> Fee
                </button>
                <a href="fees_list.php" class="btn btn-secondary">
                    <i class="bi bi-x-circle"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
