<?php
require_once 'includes/bootstrap.php';
require_once 'includes/Auth.php';
require_once 'includes/PermissionHelper.php';

Auth::requireLogin();

require_once 'modules/roles/RolesModel.php';
$rolesModel = new RolesModel();
if (!$rolesModel->userHasPermission(Auth::id(), 'view_classes')) {
    Session::setFlash('error', 'You do not have permission to view classes.');
    header('Location: /LilayiParkSchool/403.php');
    exit;
}

require_once 'modules/classes/ClassModel.php';

$classModel = new ClassModel();

// Pagination
$page = $_GET['page'] ?? 1;
$perPage = 20;
$totalRecords = $classModel->count();
$pagination = new Pagination($totalRecords, $perPage, $page);
$classes = $classModel->getAllWithDetails($pagination->getLimit(), $pagination->getOffset());

$pageTitle = 'Classes Management';
$currentPage = 'classes';
require_once 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-building"></i> Classes</h2>
    <?php if (PermissionHelper::canManage('classes')): ?>
    <a href="classes_form.php" class="btn btn-sm" style="background-color: #2d5016; color: white;">
        <i class="bi bi-plus-circle"></i> Add New Class
    </a>
    <?php endif; ?>
</div>

<!-- Classes Grid -->
<div class="row">
    <?php if (empty($classes)): ?>
    <div class="col-12">
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-inbox" style="font-size: 3rem; opacity: 0.3;"></i>
                <p class="text-muted mt-2">No classes found</p>
            </div>
        </div>
    </div>
    <?php else: ?>
    <?php foreach ($classes as $class): ?>
    <div class="col-md-6 col-lg-4 mb-4">
        <div class="card h-100 shadow-sm">
            <div class="card-body">
                <h5 class="card-title" style="color: #2d5016;">
                    <i class="bi bi-building me-2"></i><?= htmlspecialchars($class['className']) ?>
                </h5>
                <hr>
                <p class="card-text">
                    <strong>Teacher:</strong><br>
                    <?= htmlspecialchars(($class['teacherFirstName'] ?? '') . ' ' . ($class['teacherLastName'] ?? 'Not Assigned')) ?>
                </p>
                <p class="card-text">
                    <strong>Students:</strong>
                    <span class="badge" style="background-color: #5cb85c;">
                        <?= $class['pupilCount'] ?? 0 ?> pupils
                    </span>
                </p>
            </div>
            <div class="card-footer bg-white">
                <div class="btn-group btn-group-sm w-100" role="group">
                    <a href="classes_view.php?id=<?= $class['classID'] ?>" class="btn btn-outline-info btn-sm">
                        <i class="bi bi-eye"></i> View
                    </a>
                    <?php if (PermissionHelper::canManage('classes')): ?>
                    <a href="classes_form.php?id=<?= $class['classID'] ?>" class="btn btn-outline-warning btn-sm">
                        <i class="bi bi-pencil"></i> Edit
                    </a>
                    <a href="delete.php?module=classes&id=<?= $class['classID'] ?>" 
                       class="btn btn-outline-danger btn-sm" 
                       onclick="return confirm('Are you sure you want to delete this class?');">
                        <i class="bi bi-trash"></i> Delete
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php if ($pagination->hasPages()): ?>
<div class="card">
    <div class="card-footer">
        <?= $pagination->render() ?>
    </div>
</div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
