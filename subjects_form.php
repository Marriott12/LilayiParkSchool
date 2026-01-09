<?php
require_once 'includes/bootstrap.php';
require_once 'includes/Auth.php';

Auth::requireLogin();

$subjectID = $_GET['id'] ?? null;
$isEdit = !empty($subjectID);

require_once 'modules/roles/RolesModel.php';
$rolesModel = new RolesModel();
if (!$rolesModel->userHasPermission(Auth::id(), 'manage_subjects')) {
    Session::setFlash('error', 'You do not have permission to manage subjects.');
    header('Location: /LilayiParkSchool/403.php');
    exit;
}

require_once 'modules/subjects/SubjectsModel.php';
require_once 'modules/teachers/TeacherModel.php';

$subjectsModel = new SubjectsModel();
$teacherModel = new TeacherModel();

// Get all teachers for dropdown
$teachers = $teacherModel->all();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token first
    if (!CSRF::requireToken()) {
        $error = $GLOBALS['csrf_error'] ?? 'Security validation failed. Please try again.';
    } else {
        $data = [
            'subjectName' => trim($_POST['subjectName'] ?? ''),
            'subjectCode' => trim($_POST['subjectCode'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'teacherID' => !empty($_POST['teacherID']) ? $_POST['teacherID'] : null,
            'credits' => $_POST['credits'] ?? 1
        ];
        
        // Validation
        if (empty($data['subjectName'])) {
            $error = 'Subject name is required';
        }
        
        if (!isset($error)) {
            try {
                if ($isEdit) {
                    $subjectsModel->update($subjectID, $data);
                    Session::setFlash('success', 'Subject updated successfully');
                } else {
                    $subjectsModel->create($data);
                    Session::setFlash('success', 'Subject created successfully');
                }
                
                CSRF::regenerateToken();
                header('Location: subjects_list.php');
                exit;
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
    }
}

// Get subject data if editing
$subject = $isEdit ? $subjectsModel->getById($subjectID) : null;

$pageTitle = $isEdit ? 'Edit Subject' : 'Add New Subject';
$currentPage = 'subjects';
require_once 'includes/header.php';
?>

<div class="mb-4">
    <a href="subjects_list.php" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back to Subjects
    </a>
</div>

<div class="card">
    <div class="card-header" style="background: linear-gradient(135deg, #2d5016 0%, #5cb85c 100%); color: white;">
        <h5 class="mb-0">
            <i class="bi bi-book-fill"></i> <?= $pageTitle ?>
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
                    <label class="form-label">Subject Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="subjectName" 
                           value="<?= htmlspecialchars($subject['subjectName'] ?? '') ?>" required>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Subject Code <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="subjectCode" 
                           value="<?= htmlspecialchars($subject['subjectCode'] ?? '') ?>" required>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Assigned Teacher</label>
                    <select class="form-select" name="teacherID">
                        <option value="">No Teacher Assigned</option>
                        <?php foreach ($teachers as $teacher): ?>
                        <option value="<?= $teacher['teacherID'] ?>" 
                                <?= ($subject['teacherID'] ?? '') == $teacher['teacherID'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars(($teacher['fName'] ?? $teacher['firstName']) . ' ' . ($teacher['lName'] ?? $teacher['lastName'])) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Credits</label>
                    <input type="number" class="form-control" name="credits" min="1" max="10"
                           value="<?= htmlspecialchars($subject['credits'] ?? '1') ?>">
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea class="form-control" name="description" rows="4"><?= htmlspecialchars($subject['description'] ?? '') ?></textarea>
            </div>
            
            <div class="d-flex gap-2">
                <button type="submit" class="btn" style="background-color: #2d5016; color: white;">
                    <i class="bi bi-save"></i> <?= $isEdit ? 'Update' : 'Create' ?> Subject
                </button>
                <a href="subjects_list.php" class="btn btn-secondary">
                    <i class="bi bi-x-circle"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
