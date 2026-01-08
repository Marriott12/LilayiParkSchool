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
    $data = [
        'fName' => trim($_POST['fName'] ?? ''),
        'lName' => trim($_POST['lName'] ?? ''),
        'relation' => trim($_POST['relation'] ?? ''),
        'gender' => $_POST['gender'] ?? '',
        'NRC' => trim($_POST['NRC'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
        'email1' => trim($_POST['email1'] ?? ''),
        'email2' => trim($_POST['email2'] ?? ''),
        'occupation' => trim($_POST['occupation'] ?? ''),
        'workplace' => trim($_POST['workplace'] ?? '')
    ];
    
    // Validation
    if (empty($data['fName'])) {
        $error = 'First name is required';
    } elseif (empty($data['lName'])) {
        $error = 'Last name is required';
    } elseif (empty($data['relation'])) {
        $error = 'Relationship is required';
    } elseif (empty($data['gender'])) {
        $error = 'Gender is required';
    } elseif (empty($data['NRC'])) {
        $error = 'NRC is required';
    } elseif (empty($data['phone'])) {
        $error = 'Phone number is required';
    } elseif (empty($data['email1'])) {
        $error = 'Email is required';
    } elseif (!filter_var($data['email1'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format';
    } elseif (!empty($data['email2']) && !filter_var($data['email2'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid secondary email format';
    }
    
    if (!isset($error)) {
        CSRF::requireToken();
        
        try {
            if ($isEdit) {
                $parentModel->update($parentID, $data);
                Session::setFlash('success', 'Parent updated successfully');
            } else {
                $parentModel->create($data);
                Session::setFlash('success', 'Parent added successfully');
            }
            
            CSRF::regenerateToken();
            header('Location: parents_list.php');
            exit;
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
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
                    <label class="form-label">Forename <span class="text-danger">*</span></label>
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
                    <label class="form-label">Relationship <span class="text-danger">*</span></label>
                    <select class="form-select" name="relation" required>
                        <option value="">Select Relationship</option>
                        <option value="Father" <?= ($parent['relation'] ?? '') === 'Father' ? 'selected' : '' ?>>Father</option>
                        <option value="Mother" <?= ($parent['relation'] ?? '') === 'Mother' ? 'selected' : '' ?>>Mother</option>
                        <option value="Guardian" <?= ($parent['relation'] ?? '') === 'Guardian' ? 'selected' : '' ?>>Guardian</option>
                        <option value="Other" <?= ($parent['relation'] ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
                    </select>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Gender <span class="text-danger">*</span></label>
                    <div class="btn-group w-100" role="group">
                        <input type="radio" class="btn-check" name="gender" id="gender_m" value="M" 
                               <?= ($parent['gender'] ?? '') === 'M' ? 'checked' : '' ?> required>
                        <label class="btn btn-outline-primary" for="gender_m">
                            <i class="bi bi-gender-male"></i> Male
                        </label>
                        
                        <input type="radio" class="btn-check" name="gender" id="gender_f" value="F" 
                               <?= ($parent['gender'] ?? '') === 'F' ? 'checked' : '' ?> required>
                        <label class="btn btn-outline-primary" for="gender_f">
                            <i class="bi bi-gender-female"></i> Female
                        </label>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">NRC <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="NRC" 
                           value="<?= htmlspecialchars($parent['NRC'] ?? '') ?>" 
                           placeholder="e.g., 123456/78/9" required>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Phone <span class="text-danger">*</span></label>
                    <input type="tel" class="form-control" name="phone" 
                           value="<?= htmlspecialchars($parent['phone'] ?? '') ?>" 
                           placeholder="e.g., +260 97 1234567" required>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Email 1 <span class="text-danger">*</span></label>
                    <input type="email" class="form-control" name="email1" 
                           value="<?= htmlspecialchars($parent['email1'] ?? '') ?>" required>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Email 2</label>
                    <input type="email" class="form-control" name="email2" 
                           value="<?= htmlspecialchars($parent['email2'] ?? '') ?>" 
                           placeholder="Secondary email (optional)">
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Occupation</label>
                    <input type="text" class="form-control" name="occupation" 
                           value="<?= htmlspecialchars($parent['occupation'] ?? '') ?>">
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Workplace</label>
                    <input type="text" class="form-control" name="workplace" 
                           value="<?= htmlspecialchars($parent['workplace'] ?? '') ?>" 
                           placeholder="Employer/Company name">
                </div>
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
