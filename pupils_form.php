<?php
require_once 'includes/bootstrap.php';

RBAC::requireAuth();

// Check if editing or creating
$pupilID = $_GET['id'] ?? null;
$isEdit = !empty($pupilID);

// Require appropriate permissions
if ($isEdit) {
    RBAC::requirePermission('pupils', 'update');
} else {
    RBAC::requirePermission('pupils', 'create');
}

require_once 'modules/pupils/PupilModel.php';
require_once 'modules/parents/ParentModel.php';

$pupilModel = new PupilModel();
$parentModel = new ParentModel();

// Get all parents for dropdown
$parents = $parentModel->getAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'fName' => trim($_POST['fName'] ?? ''),
        'lName' => trim($_POST['lName'] ?? ''),
        'studentNumber' => trim($_POST['studentNumber'] ?? ''),
        'dateOfBirth' => $_POST['dateOfBirth'] ?? '',
        'gender' => $_POST['gender'] ?? '',
        'address' => trim($_POST['address'] ?? ''),
        'parentID' => !empty($_POST['parentID']) ? $_POST['parentID'] : null,
        'medicalInfo' => trim($_POST['medicalInfo'] ?? '')
    ];
    
    // Validation
    if (empty($data['fName'])) {
        $error = 'First name is required';
    } elseif (empty($data['lName'])) {
        $error = 'Last name is required';
    }
    
    if (!isset($error)) {
        CSRF::requireToken();
        
        try {
            if ($isEdit) {
                $pupilModel->update($pupilID, $data);
                Session::setFlash('success', 'Pupil updated successfully');
            } else {
                $pupilModel->create($data);
                Session::setFlash('success', 'Pupil added successfully');
            }
            
            CSRF::regenerateToken();
            header('Location: pupils_list.php');
            exit;
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

// Get pupil data if editing
$pupil = $isEdit ? $pupilModel->getById($pupilID) : null;

$pageTitle = $isEdit ? 'Edit Pupil' : 'Add New Pupil';
$currentPage = 'pupils';
require_once 'includes/header.php';
?>

<div class="mb-4">
    <a href="pupils_list.php" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back to Pupils
    </a>
</div>

<div class="card">
    <div class="card-header" style="background: linear-gradient(135deg, #2d5016 0%, #5cb85c 100%); color: white;">
        <h5 class="mb-0">
            <i class="bi bi-person-plus-fill"></i> <?= $pageTitle ?>
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
                           value="<?= htmlspecialchars($pupil['fName'] ?? '') ?>" required>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Last Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="lName" 
                           value="<?= htmlspecialchars($pupil['lName'] ?? '') ?>" required>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Student Number <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="studentNumber" 
                           value="<?= htmlspecialchars($pupil['studentNumber'] ?? '') ?>" required>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Date of Birth <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" name="dateOfBirth" 
                           value="<?= htmlspecialchars($pupil['dateOfBirth'] ?? '') ?>" required>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Gender <span class="text-danger">*</span></label>
                    <select class="form-select" name="gender" required>
                        <option value="">Select Gender</option>
                        <option value="Male" <?= ($pupil['gender'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
                        <option value="Female" <?= ($pupil['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                    </select>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Parent/Guardian</label>
                    <select class="form-select" name="parentID">
                        <option value="">No Parent Assigned</option>
                        <?php foreach ($parents as $parent): ?>
                        <option value="<?= $parent['parentID'] ?>" 
                                <?= ($pupil['parentID'] ?? '') == $parent['parentID'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($parent['fName'] . ' ' . $parent['lName']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Address</label>
                <textarea class="form-control" name="address" rows="2"><?= htmlspecialchars($pupil['address'] ?? '') ?></textarea>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Medical Information</label>
                <textarea class="form-control" name="medicalInfo" rows="3" 
                          placeholder="Any allergies, conditions, or medical notes..."><?= htmlspecialchars($pupil['medicalInfo'] ?? '') ?></textarea>
            </div>
            
            <div class="d-flex gap-2">
                <button type="submit" class="btn" style="background-color: #2d5016; color: white;">
                    <i class="bi bi-save"></i> <?= $isEdit ? 'Update' : 'Create' ?> Pupil
                </button>
                <a href="pupils_list.php" class="btn btn-secondary">
                    <i class="bi bi-x-circle"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
