<?php
require_once 'includes/bootstrap.php';
require_once 'includes/Auth.php';

Auth::requireLogin();

$parentID = $_GET['id'] ?? null;
$isEdit = !empty($parentID);

require_once 'modules/roles/RolesModel.php';
$rolesModel = new RolesModel();
if (!$rolesModel->userHasPermission(Auth::id(), 'manage_parents')) {
    Session::setFlash('error', 'You do not have permission to manage parents.');
    header('Location: /LilayiParkSchool/403.php');
    exit;
}

require_once 'modules/parents/ParentModel.php';
$parentModel = new ParentModel();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token first
    if (!CSRF::requireToken()) {
        $error = $GLOBALS['csrf_error'] ?? 'Security validation failed. Please try again.';
    } else {
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
        }
        // NRC validation: Must be format XXXXXX/XX/X
        elseif (empty($data['NRC'])) {
            $error = 'NRC is required';
        } elseif (!preg_match('/^\d{6}\/\d{2}\/\d{1}$/', $data['NRC'])) {
            $error = 'NRC must be in format XXXXXX/XX/X (e.g., 123456/78/1)';
        } elseif ($parentModel->nrcExists($data['NRC'], $isEdit ? $parentID : null)) {
            $error = 'A parent with this NRC already exists';
        }
        // Phone validation: 10-13 characters, + only at start
        elseif (empty($data['phone'])) {
            $error = 'Phone number is required';
        } elseif (strlen($data['phone']) < 10) {
            $error = 'Phone number must be at least 10 digits';
        } elseif (strlen($data['phone']) > 13) {
            $error = 'Phone number cannot exceed 13 characters';
        } elseif (!preg_match('/^\+?\d+$/', $data['phone'])) {
            $error = 'Phone number can only contain digits and + at the beginning';
        } elseif (strpos($data['phone'], '+') === 0 && !preg_match('/^\+260\d{9}$/', $data['phone'])) {
            $error = 'Phone number starting with + must be in format +260XXXXXXXXX';
        } elseif ($parentModel->phoneExists($data['phone'], $isEdit ? $parentID : null)) {
            $error = 'A parent with this phone number already exists';
        }
        // Email validation
        elseif (empty($data['email1'])) {
            $error = 'Email is required';
        } elseif (!filter_var($data['email1'], FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email format';
        } elseif ($parentModel->emailExists($data['email1'], $isEdit ? $parentID : null)) {
            $error = 'A parent with this email already exists';
        } elseif (!empty($data['email2']) && !filter_var($data['email2'], FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid secondary email format';
        } elseif (!empty($data['email2']) && $parentModel->emailExists($data['email2'], $isEdit ? $parentID : null)) {
            $error = 'A parent with this secondary email already exists';
        }
        
        if (!isset($error)) {
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
                <div class="col-md-4 mb-3">
                    <label class="form-label">Forename <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="fName" 
                           value="<?= htmlspecialchars($parent['fName'] ?? '') ?>" required>
                </div>
                
                <div class="col-md-4 mb-3">
                    <label class="form-label">Last Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="lName" 
                           value="<?= htmlspecialchars($parent['lName'] ?? '') ?>" required>
                </div>
            
                <div class="col-md-4 mb-3">
                    <label class="form-label">Relationship <span class="text-danger">*</span></label>
                    <select class="form-select" name="relation" required>
                        <option value="">Select Relationship</option>
                        <option value="Father" <?= ($parent['relation'] ?? '') === 'Father' ? 'selected' : '' ?>>Father</option>
                        <option value="Mother" <?= ($parent['relation'] ?? '') === 'Mother' ? 'selected' : '' ?>>Mother</option>
                        <option value="Guardian" <?= ($parent['relation'] ?? '') === 'Guardian' ? 'selected' : '' ?>>Guardian</option>
                        <option value="Other" <?= ($parent['relation'] ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
                    </select>
                </div>
                
                <div class="col-md-4 mb-3">
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
            
                <div class="col-md-4 mb-3">
                    <label class="form-label">NRC <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="NRC" 
                           value="<?= htmlspecialchars($parent['NRC'] ?? '') ?>" 
                           pattern="\d{6}/\d{2}/\d{1}"
                           placeholder="123456/78/1"
                           title="Format: 6 digits/2 digits/1 digit (e.g., 123456/78/1)"
                           required>
                    <small class="text-muted">Format: XXXXXX/XX/X</small>
                </div>
                
                <div class="col-md-4 mb-3">
                    <label class="form-label">Phone <span class="text-danger">*</span></label>
                    <input type="tel" class="form-control" name="phone" 
                           value="<?= htmlspecialchars($parent['phone'] ?? '') ?>" 
                           pattern="(\+260\d{9}|\d{10,13})"
                           placeholder="0977123456 or +260977123456"
                           title="10-13 digits, or +260 followed by 9 digits"
                           minlength="10"
                           maxlength="13"
                           required>
                    <small class="text-muted">10-13 digits, or +260XXXXXXXXX</small>
                </div>
            
                <div class="col-md-4 mb-3">
                    <label class="form-label">Email 1 <span class="text-danger">*</span></label>
                    <input type="email" class="form-control" name="email1" 
                           value="<?= htmlspecialchars($parent['email1'] ?? '') ?>" required>
                </div>
                
                <div class="col-md-4 mb-3">
                    <label class="form-label">Email 2</label>
                    <input type="email" class="form-control" name="email2" 
                           value="<?= htmlspecialchars($parent['email2'] ?? '') ?>" 
                           placeholder="Secondary email (optional)">
                </div>
            
                <div class="col-md-4 mb-3">
                    <label class="form-label">Occupation</label>
                    <input type="text" class="form-control" name="occupation" 
                           value="<?= htmlspecialchars($parent['occupation'] ?? '') ?>">
                </div>
                
                <div class="col-md-4 mb-3">
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
