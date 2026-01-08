<?php
require_once 'includes/bootstrap.php';

RBAC::requireAuth();

$parentID = $_GET['id'] ?? null;
$isEdit = !empty($parentID);

if ($isEdit) {
    RBAC::requirePermission('parents', 'update');
} else {
    RBAC::requirePermission('parents', 'create');
}

require_once 'modules/parents/ParentModel.php';
$parentModel = new ParentModel();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::requireToken();
    
    $data = [
        'fName' => trim($_POST['fName'] ?? ''),
        'lName' => trim($_POST['lName'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'phoneNumber' => trim($_POST['phoneNumber'] ?? ''),
        'address' => trim($_POST['address'] ?? ''),
        'occupation' => trim($_POST['occupation'] ?? ''),
        'emergencyContact' => trim($_POST['emergencyContact'] ?? '')
    ];
    
    try {
        if ($isEdit) {
            $parentModel->update($parentID, $data);
            Session::setFlash('success', 'Parent updated successfully');
        } else {
            $parentModel->create($data);
            Session::setFlash('success', 'Parent added successfully');
        }
        header('Location: parents_list.php');
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$parent = $isEdit ? $parentModel->getById($parentID) : null;

$pageTitle = $isEdit ? 'Edit Parent' : 'Add New Parent';
$currentPage = 'parents';
require_once 'includes/header.php';
?>

<div class="mb-4">
    <a href="parents_list.php" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back to Parents
    </a>
</div>

<div class="card">
    <div class="card-header" style="background: linear-gradient(135deg, #2d5016 0%, #5cb85c 100%); color: white;">
        <h5 class="mb-0">
            <i class="bi bi-person-hearts"></i> <?= $pageTitle ?>
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
                    <label class="form-label">First Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="fName" 
                           value="<?= htmlspecialchars($parent['fName'] ?? '') ?>" required>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Last Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="lName" 
                           value="<?= htmlspecialchars($parent['lName'] ?? '') ?>" required>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Email <span class="text-danger">*</span></label>
                    <input type="email" class="form-control" name="email" 
                           value="<?= htmlspecialchars($parent['email'] ?? '') ?>" required>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Phone Number <span class="text-danger">*</span></label>
                    <input type="tel" class="form-control" name="phoneNumber" 
                           value="<?= htmlspecialchars($parent['phoneNumber'] ?? '') ?>" required>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Occupation</label>
                    <input type="text" class="form-control" name="occupation" 
                           value="<?= htmlspecialchars($parent['occupation'] ?? '') ?>">
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Emergency Contact</label>
                    <input type="tel" class="form-control" name="emergencyContact" 
                           value="<?= htmlspecialchars($parent['emergencyContact'] ?? '') ?>" 
                           placeholder="Alternative contact number">
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Address</label>
                <textarea class="form-control" name="address" rows="3"><?= htmlspecialchars($parent['address'] ?? '') ?></textarea>
            </div>
            
            <div class="d-flex gap-2">
                <button type="submit" class="btn" style="background-color: #2d5016; color: white;">
                    <i class="bi bi-save"></i> <?= $isEdit ? 'Update' : 'Create' ?> Parent
                </button>
                <a href="parents_list.php" class="btn btn-secondary">
                    <i class="bi bi-x-circle"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
