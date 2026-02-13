<?php
require_once 'includes/bootstrap.php';
require_once 'includes/Auth.php';

Auth::requireLogin();

$classID = $_GET['id'] ?? null;
$isEdit = !empty($classID);

require_once 'modules/roles/RolesModel.php';
$rolesModel = new RolesModel();
if (!$rolesModel->userHasPermission(Auth::id(), 'manage_classes')) {
    Session::setFlash('error', 'You do not have permission to manage classes.');
    header('Location: 403.php');
    exit;
}

require_once 'modules/classes/ClassModel.php';
require_once 'modules/teachers/TeacherModel.php';

$classModel = new ClassModel();
$teacherModel = new TeacherModel();

// Get all teachers for dropdown
$teachers = $teacherModel->getAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token first
    if (!CSRF::requireToken()) {
        $error = $GLOBALS['csrf_error'] ?? 'Security validation failed. Please try again.';
    } else {
        $data = [
            'className' => trim($_POST['className'] ?? ''),
            'teacherID' => !empty($_POST['teacherID']) ? $_POST['teacherID'] : null
        ];
        
        // Validation
        if (empty($data['className'])) {
            $error = 'Class name is required';
        }
        
        if (!isset($error)) {
            try {
                if ($isEdit) {
                    $classModel->update($classID, $data);
                    Session::setFlash('success', 'Class updated successfully');
                } else {
                    $classModel->create($data);
                    Session::setFlash('success', 'Class created successfully');
                }
                
                CSRF::regenerateToken();
                header('Location: classes_list.php');
                exit;
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
    }
}

$class = $isEdit ? $classModel->getById($classID) : null;

$pageTitle = $isEdit ? 'Edit Class' : 'Add New Class';
$currentPage = 'classes';
require_once 'includes/header.php';
?>

<div class="mb-4">
    <a href="classes_list.php" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back to Classes
    </a>
</div>

<div class="card">
    <div class="card-header" style="background: linear-gradient(135deg, #2d5016 0%, #5cb85c 100%); color: white;">
        <h5 class="mb-0">
            <i class="bi bi-door-open-fill"></i> <?= $pageTitle ?>
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
                    <label class="form-label">Class Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="className" 
                           value="<?= htmlspecialchars($class['className'] ?? '') ?>" 
                           placeholder="e.g., Grade 1A, Reception" required>
                    <small class="text-muted">Enter the class name (e.g., Grade 1A, Grade 2B, Reception)</small>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Class Teacher</label>
                    <select class="form-select" name="teacherID">
                        <option value="">No Teacher Assigned</option>
                        <?php foreach ($teachers as $teacher): ?>
                        <option value="<?= $teacher['teacherID'] ?>" 
                                <?= ($class['teacherID'] ?? '') == $teacher['teacherID'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($teacher['fName'] . ' ' . $teacher['lName']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted">Select the teacher responsible for this class</small>
                </div>
            </div>
            
            <div class="d-flex gap-2">
                <button type="submit" class="btn" style="background-color: #2d5016; color: white;">
                    <i class="bi bi-save"></i> <?= $isEdit ? 'Update' : 'Create' ?> Class
                </button>
                <a href="classes_list.php" class="btn btn-secondary">
                    <i class="bi bi-x-circle"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
