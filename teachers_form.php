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
    $data = [
        'fName' => trim($_POST['fName'] ?? ''),
        'lName' => trim($_POST['lName'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'phoneNumber' => trim($_POST['phoneNumber'] ?? ''),
        'subject' => trim($_POST['subject'] ?? ''),
        'dateOfBirth' => $_POST['dateOfBirth'] ?? '',
        'gender' => $_POST['gender'] ?? '',
        'address' => trim($_POST['address'] ?? ''),
        'qualifications' => trim($_POST['qualifications'] ?? ''),
        'hireDate' => $_POST['hireDate'] ?? date('Y-m-d')
    ];
    
    try {
        if ($isEdit) {
            $teacherModel->update($teacherID, $data);
            $_SESSION['success_message'] = 'Teacher updated successfully';
        } else {
            $teacherModel->create($data);
            $_SESSION['success_message'] = 'Teacher added successfully';
        }
        header('Location: teachers_list.php');
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
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
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">First Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="fName" 
                           value="<?= htmlspecialchars($teacher['fName'] ?? '') ?>" required>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Last Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="lName" 
                           value="<?= htmlspecialchars($teacher['lName'] ?? '') ?>" required>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Email <span class="text-danger">*</span></label>
                    <input type="email" class="form-control" name="email" 
                           value="<?= htmlspecialchars($teacher['email'] ?? '') ?>" required>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Phone Number <span class="text-danger">*</span></label>
                    <input type="tel" class="form-control" name="phoneNumber" 
                           value="<?= htmlspecialchars($teacher['phoneNumber'] ?? '') ?>" required>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Subject/Specialization <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="subject" 
                           value="<?= htmlspecialchars($teacher['subject'] ?? '') ?>" 
                           placeholder="e.g., Mathematics, English, Science" required>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Gender <span class="text-danger">*</span></label>
                    <select class="form-select" name="gender" required>
                        <option value="">Select Gender</option>
                        <option value="Male" <?= ($teacher['gender'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
                        <option value="Female" <?= ($teacher['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                    </select>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Date of Birth</label>
                    <input type="date" class="form-control" name="dateOfBirth" 
                           value="<?= htmlspecialchars($teacher['dateOfBirth'] ?? '') ?>">
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Hire Date</label>
                    <input type="date" class="form-control" name="hireDate" 
                           value="<?= htmlspecialchars($teacher['hireDate'] ?? date('Y-m-d')) ?>">
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Address</label>
                <textarea class="form-control" name="address" rows="2"><?= htmlspecialchars($teacher['address'] ?? '') ?></textarea>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Qualifications</label>
                <textarea class="form-control" name="qualifications" rows="3" 
                          placeholder="Educational background, certifications, etc."><?= htmlspecialchars($teacher['qualifications'] ?? '') ?></textarea>
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
