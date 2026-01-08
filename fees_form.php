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
require_once 'modules/classes/ClassModel.php';

$feesModel = new FeesModel();
$classModel = new ClassModel();

// Get all classes for dropdown
$classes = $classModel->getAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token first
    CSRF::requireToken();
    
    $data = [
        'classID' => trim($_POST['classID'] ?? ''),
        'feeAmt' => floatval($_POST['feeAmt'] ?? 0),
        'term' => $_POST['term'] ?? '',
        'year' => intval($_POST['year'] ?? date('Y'))
    ];
    
    // Validation
    if (empty($data['classID'])) {
        $error = 'Class is required';
    } elseif ($data['feeAmt'] <= 0) {
        $error = 'Fee amount must be greater than zero';
    } elseif (empty($data['term'])) {
        $error = 'Term is required';
    } elseif (empty($data['year'])) {
        $error = 'Academic year is required';
    }
    
    if (!isset($error)) {
        try {
            if ($isEdit) {
                $feesModel->update($feeID, $data);
                Session::setFlash('success', 'Fee updated successfully');
            } else {
                $feesModel->create($data);
                Session::setFlash('success', 'Fee created successfully');
            }
            
            CSRF::regenerateToken();
            header('Location: fees_list.php');
            exit;
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    } else {
        CSRF::regenerateToken();
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
            <?= CSRF::field() ?>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Class <span class="text-danger">*</span></label>
                    <select class="form-select" name="classID" required>
                        <option value="">-- Select Class --</option>
                        <?php foreach ($classes as $class): ?>
                        <option value="<?= $class['classID'] ?>" 
                                <?= ($fee['classID'] ?? '') === $class['classID'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($class['className']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Amount (K) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" class="form-control" name="feeAmt" 
                           value="<?= htmlspecialchars($fee['feeAmt'] ?? '') ?>" 
                           placeholder="0.00" required>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Term <span class="text-danger">*</span></label>
                    <select class="form-select" name="term" required>
                        <option value="">Select Term</option>
                        <option value="Term 1" <?= ($fee['term'] ?? '') === 'Term 1' ? 'selected' : '' ?>>Term 1</option>
                        <option value="Term 2" <?= ($fee['term'] ?? '') === 'Term 2' ? 'selected' : '' ?>>Term 2</option>
                        <option value="Term 3" <?= ($fee['term'] ?? '') === 'Term 3' ? 'selected' : '' ?>>Term 3</option>
                    </select>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Academic Year <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" name="year" 
                           value="<?= htmlspecialchars($fee['year'] ?? date('Y')) ?>" 
                           placeholder="<?= date('Y') ?>" min="2020" max="2099" required>
                </div>
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
