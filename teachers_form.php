<?php
require_once 'includes/bootstrap.php';

RBAC::requireAuth();

$teacherID = $_GET['id'] ?? null;
$isEdit = !empty($teacherID);

if ($isEdit) {
    RBAC::requirePermission('teachers', 'update');
} else {
    RBAC::requirePermission('teachers', 'create');
}

require_once 'modules/teachers/TeacherModel.php';
$teacherModel = new TeacherModel();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token first
    if (!CSRF::requireToken()) {
        $error = $GLOBALS['csrf_error'] ?? 'Security validation failed. Please try again.';
    } else {
        $data = [
            'fName' => trim($_POST['fName'] ?? ''),
            'lName' => trim($_POST['lName'] ?? ''),
            'NRC' => trim($_POST['NRC'] ?? ''),
            'SSN' => trim($_POST['SSN'] ?? ''),
            'Tpin' => trim($_POST['Tpin'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'gender' => $_POST['gender'] ?? '',
            'tczNo' => trim($_POST['tczNo'] ?? '')
        ];
        
        // Validation
        if (empty($data['fName'])) {
            $error = 'First name is required';
        } elseif (empty($data['lName'])) {
            $error = 'Last name is required';
        } elseif (empty($data['NRC'])) {
            $error = 'NRC is required';
        } elseif (empty($data['SSN'])) {
            $error = 'Social Security Number is required';
        } elseif (empty($data['Tpin'])) {
            $error = 'TPIN is required';
        } elseif (empty($data['phone'])) {
            $error = 'Phone number is required';
        } elseif (empty($data['email'])) {
            $error = 'Email is required';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email format';
        } elseif (empty($data['gender'])) {
            $error = 'Gender is required';
        }
        
        if (!isset($error)) {
            try {
                if ($isEdit) {
                    $teacherModel->update($teacherID, $data);
                    Session::setFlash('success', 'Teacher updated successfully');
                } else {
                    $newId = $teacherModel->create($data);
                    Session::setFlash('success', 'Teacher added successfully (ID: ' . $newId . ')');
                }
                
                // Regenerate CSRF token after successful submission
                CSRF::regenerateToken();
                
                header('Location: teachers_list.php');
                exit;
            } catch (PDOException $e) {
                $error = 'Database error: ' . $e->getMessage();
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
    }
}

$teacher = $isEdit ? $teacherModel->getById($teacherID) : null;

$pageTitle = $isEdit ? 'Edit Teacher' : 'Add New Teacher';
$currentPage = 'teachers';
require_once 'includes/header.php';
?>

<div class="mb-4">
    <a href="teachers_list.php" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back to Teachers
    </a>
</div>

<div class="card">
    <div class="card-header" style="background: linear-gradient(135deg, #2d5016 0%, #5cb85c 100%); color: white;">
        <h5 class="mb-0">
            <i class="bi bi-person-badge-fill"></i> <?= $pageTitle ?>
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
                           value="<?= htmlspecialchars($teacher['fName'] ?? '') ?>" required>
                </div>
                
                <div class="col-md-4 mb-3">
                    <label class="form-label">Last Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="lName" 
                           value="<?= htmlspecialchars($teacher['lName'] ?? '') ?>" required>
                </div>
            
                <div class="col-md-4 mb-3">
                    <label class="form-label">NRC <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="NRC" 
                           value="<?= htmlspecialchars($teacher['NRC'] ?? '') ?>" 
                           placeholder="e.g., 123456/78/9" required>
                </div>
                
                <div class="col-md-4 mb-3">
                    <label class="form-label">Social Security Number (SSN) <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="SSN" 
                           value="<?= htmlspecialchars($teacher['SSN'] ?? '') ?>" required>
                </div>
            
                <div class="col-md-4 mb-3">
                    <label class="form-label">TPIN <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="Tpin" 
                           value="<?= htmlspecialchars($teacher['Tpin'] ?? '') ?>" 
                           placeholder="Taxpayer Identification Number" required>
                </div>
                
                <div class="col-md-4 mb-3">
                    <label class="form-label">Phone <span class="text-danger">*</span></label>
                    <input type="tel" class="form-control" name="phone" 
                           value="<?= htmlspecialchars($teacher['phone'] ?? '') ?>" 
                           placeholder="e.g., +260 97 1234567" required>
                </div>
            
                <div class="col-md-4 mb-3">
                    <label class="form-label">Email <span class="text-danger">*</span></label>
                    <input type="email" class="form-control" name="email" 
                           value="<?= htmlspecialchars($teacher['email'] ?? '') ?>" required>
                </div>
                
                <div class="col-md-4 mb-3">
                    <label class="form-label">Gender <span class="text-danger">*</span></label>
                    <div class="btn-group w-100" role="group">
                        <input type="radio" class="btn-check" name="gender" id="gender_m" value="M" 
                               <?= ($teacher['gender'] ?? '') === 'M' ? 'checked' : '' ?> required>
                        <label class="btn btn-outline-primary" for="gender_m">
                            <i class="bi bi-gender-male"></i> Male
                        </label>
                        
                        <input type="radio" class="btn-check" name="gender" id="gender_f" value="F" 
                               <?= ($teacher['gender'] ?? '') === 'F' ? 'checked' : '' ?> required>
                        <label class="btn btn-outline-primary" for="gender_f">
                            <i class="bi bi-gender-female"></i> Female
                        </label>
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">TCZ Number</label>
                <input type="text" class="form-control" name="tczNo" 
                       value="<?= htmlspecialchars($teacher['tczNo'] ?? '') ?>" 
                       placeholder="Teachers Council of Zambia Number">
            </div>
            
            <div class="d-flex gap-2">
                <button type="submit" class="btn" style="background-color: #2d5016; color: white;">
                    <i class="bi bi-save"></i> <?= $isEdit ? 'Update' : 'Create' ?> Teacher
                </button>
                <a href="teachers_list.php" class="btn btn-secondary">
                    <i class="bi bi-x-circle"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
