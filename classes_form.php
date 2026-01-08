<?php
require_once 'includes/bootstrap.php';

RBAC::requireAuth();

$classID = $_GET['id'] ?? null;
$isEdit = !empty($classID);

if ($isEdit) {
    RBAC::requirePermission('classes', 'update');
} else {
    RBAC::requirePermission('classes', 'create');
}

require_once 'modules/classes/ClassModel.php';
require_once 'modules/teachers/TeacherModel.php';

$classModel = new ClassModel();
$teacherModel = new TeacherModel();

// Get all teachers for dropdown
$teachers = $teacherModel->getAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'className' => trim($_POST['className'] ?? ''),
        'grade' => trim($_POST['grade'] ?? ''),
        'academicYear' => trim($_POST['academicYear'] ?? ''),
        'teacherID' => !empty($_POST['teacherID']) ? $_POST['teacherID'] : null,
        'capacity' => !empty($_POST['capacity']) ? intval($_POST['capacity']) : null,
        'room' => trim($_POST['room'] ?? '')
    ];
    
    // Validation
    if (empty($data['className'])) {
        $error = 'Class name is required';
    } elseif (empty($data['grade'])) {
        $error = 'Grade is required';
    }
    
    if (!isset($error)) {
        CSRF::requireToken();
        
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
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Grade/Level <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="grade" 
                           value="<?= htmlspecialchars($class['grade'] ?? '') ?>" 
                           placeholder="e.g., Grade 1, Reception" required>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Academic Year <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="academicYear" 
                           value="<?= htmlspecialchars($class['academicYear'] ?? '2025/2026') ?>" 
                           placeholder="2025/2026" required>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Class Teacher</label>
                    <select class="form-select" name="teacherID">
                        <option value="">No Teacher Assigned</option>
                        <?php foreach ($teachers as $teacher): ?>
                        <option value="<?= $teacher['teacherID'] ?>" 
                                <?= ($class['teacherID'] ?? '') == $teacher['teacherID'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($teacher['fName'] . ' ' . $teacher['lName'] . ' - ' . $teacher['subject']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Room/Location</label>
                    <input type="text" class="form-control" name="room" 
                           value="<?= htmlspecialchars($class['room'] ?? '') ?>" 
                           placeholder="e.g., Room 101, Building A">
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Capacity</label>
                    <input type="number" class="form-control" name="capacity" 
                           value="<?= htmlspecialchars($class['capacity'] ?? '') ?>" 
                           placeholder="Maximum number of pupils" min="1">
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
