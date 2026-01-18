<?php
/**
 * Generic Delete Handler
 * Handles deletion for Teachers, Parents, Classes, Fees, Payments, Attendance
 */
require_once 'includes/bootstrap.php';
require_once 'includes/Auth.php';

Auth::requireLogin();

// Determine which module we're deleting from
$module = $_GET['module'] ?? '';
$id = $_GET['id'] ?? null;

// Module configuration
$moduleConfig = [
    'pupils' => [
        'model' => 'PupilModel',
        'path' => 'modules/pupils/PupilModel.php',
        'permission' => 'pupils',
        'primaryKey' => 'pupilID',
        'nameFields' => ['fName', 'lName'],
        'listPage' => 'pupils_list.php',
        'title' => 'Pupil'
    ],
    'teachers' => [
        'model' => 'TeacherModel',
        'path' => 'modules/teachers/TeacherModel.php',
        'permission' => 'teachers',
        'primaryKey' => 'teacherID',
        'nameFields' => ['fName', 'lName'],
        'listPage' => 'teachers_list.php',
        'title' => 'Teacher'
    ],
    'parents' => [
        'model' => 'ParentModel',
        'path' => 'modules/parents/ParentModel.php',
        'permission' => 'parents',
        'primaryKey' => 'parentID',
        'nameFields' => ['fName', 'lName'],
        'listPage' => 'parents_list.php',
        'title' => 'Parent'
    ],
    'classes' => [
        'model' => 'ClassModel',
        'path' => 'modules/classes/ClassModel.php',
        'permission' => 'classes',
        'primaryKey' => 'classID',
        'nameFields' => ['className'],
        'listPage' => 'classes_list.php',
        'title' => 'Class'
    ],
    'fees' => [
        'model' => 'FeesModel',
        'path' => 'modules/fees/FeesModel.php',
        'permission' => 'fees',
        'primaryKey' => 'feeID',
        'nameFields' => ['feeName'],
        'listPage' => 'fees_list.php',
        'title' => 'Fee'
    ],
    'payments' => [
        'model' => 'PaymentModel',
        'path' => 'modules/payments/PaymentModel.php',
        'permission' => 'payments',
        'primaryKey' => 'paymentID',
        'nameFields' => ['paymentID'],
        'listPage' => 'payments_list.php',
        'title' => 'Payment'
    ],
    'attendance' => [
        'model' => 'AttendanceModel',
        'path' => 'modules/attendance/AttendanceModel.php',
        'permission' => 'attendance',
        'primaryKey' => 'attendanceID',
        'nameFields' => ['attendanceID'],
        'listPage' => 'attendance_list.php',
        'title' => 'Attendance Record'
    ],
    'examinations' => [
        'model' => 'ExaminationsModel',
        'path' => 'modules/examinations/ExaminationsModel.php',
        'permission' => 'examinations',
        'primaryKey' => 'examID',
        'nameFields' => ['examName'],
        'listPage' => 'examinations_list.php',
        'title' => 'Examination'
    ]
];

// Validate module
if (!isset($moduleConfig[$module])) {
    $_SESSION['error_message'] = 'Invalid module';
    header('Location: index.php');
    exit;
}

$config = $moduleConfig[$module];

// Check delete permission
require_once 'modules/roles/RolesModel.php';
$rolesModel = new RolesModel();
$permissionName = 'manage_' . $config['permission'];
if (!$rolesModel->userHasPermission(Auth::id(), $permissionName)) {
    Session::setFlash('error', 'You do not have permission to delete this item.');
    header('Location: /LilayiParkSchool/403.php');
    exit;
}

if (!$id) {
    $_SESSION['error_message'] = 'Invalid ID';
    header('Location: ' . $config['listPage']);
    exit;
}

// Load model
require_once $config['path'];
$modelClass = $config['model'];
$model = new $modelClass();

// Get record
$record = $model->getById($id);

if (!$record) {
    $_SESSION['error_message'] = $config['title'] . ' not found';
    header('Location: ' . $config['listPage']);
    exit;
}

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    try {
        $model->delete($id);
        $_SESSION['success_message'] = $config['title'] . ' deleted successfully';
        header('Location: ' . $config['listPage']);
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get display name
$displayName = '';
foreach ($config['nameFields'] as $field) {
    $displayName .= ($record[$field] ?? '') . ' ';
}
$displayName = trim($displayName);

$pageTitle = 'Delete ' . $config['title'];
$currentPage = $module;
require_once 'includes/header.php';
?>

<div class="mb-4">
    <a href="<?= $config['listPage'] ?>" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back to <?= $config['title'] ?>s
    </a>
</div>

<div class="card border-danger">
    <div class="card-header bg-danger text-white">
        <h5 class="mb-0">
            <i class="bi bi-exclamation-triangle-fill"></i> Confirm Deletion
        </h5>
    </div>
    <div class="card-body">
        <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle-fill"></i> <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <strong>Warning:</strong> This action cannot be undone. All related records may also be deleted.
        </div>
        
        <p class="mb-3">Are you sure you want to delete this <?= strtolower($config['title']) ?>?</p>
        
        <div class="card bg-light">
            <div class="card-body">
                <h6 class="card-title"><?= htmlspecialchars($displayName) ?></h6>
                <?php foreach ($record as $key => $value): ?>
                    <?php if ($key !== $config['primaryKey'] && !in_array($key, ['createdAt', 'updatedAt', 'password'])): ?>
                    <p class="card-text mb-1"><strong><?= ucfirst($key) ?>:</strong> <?= htmlspecialchars($value ?? 'N/A') ?></p>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
        
        <form method="POST" class="mt-4">
            <div class="d-flex gap-2">
                <button type="submit" name="confirm_delete" class="btn btn-danger">
                    <i class="bi bi-trash"></i> Yes, Delete <?= $config['title'] ?>
                </button>
                <a href="<?= $config['listPage'] ?>" class="btn btn-secondary">
                    <i class="bi bi-x-circle"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
