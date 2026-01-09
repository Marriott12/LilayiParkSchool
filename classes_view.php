<?php
require_once 'includes/bootstrap.php';
require_once 'includes/Auth.php';

Auth::requireLogin();

require_once 'modules/roles/RolesModel.php';
$rolesModel = new RolesModel();
if (!$rolesModel->userHasPermission(Auth::id(), 'view_classes')) {
    Session::setFlash('error', 'You do not have permission to view classes.');
    header('Location: /LilayiParkSchool/403.php');
    exit;
}

require_once 'modules/classes/ClassModel.php';

$classID = $_GET['id'] ?? null;
if (empty($classID)) {
    header('Location: classes_list.php');
    exit;
}

$classModel = new ClassModel();
$class = $classModel->getClassWithTeacher($classID) ?: $classModel->getById($classID);
$roster = method_exists($classModel, 'getClassRoster') ? $classModel->getClassRoster($classID) : [];

$pageTitle = 'Class Details';
$currentPage = 'classes';
require_once 'includes/header.php';
require_once 'includes/PermissionHelper.php';
?>

<!-- Page Header with Actions -->
<div class="row mb-4">
    <div class="col-md-8">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="classes_list.php" class="text-decoration-none">Classes</a></li>
                <li class="breadcrumb-item active" aria-current="page">View Details</li>
            </ol>
        </nav>
        <h2 class="mb-0">
            <i class="bi bi-building me-2" style="color: #2d5016;"></i>
            <span style="color: #2d5016; font-weight: 600;">Class Details</span>
        </h2>
    </div>
    <div class="col-md-4 text-end align-self-end">
        <div class="btn-group" role="group">
            <a href="classes_list.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
            <?php if (PermissionHelper::canManage('classes')): ?>
            <a href="classes_form.php?id=<?= $classID ?>" class="btn btn-warning">
                <i class="bi bi-pencil me-1"></i> Edit
            </a>
            <a href="delete.php?module=classes&id=<?= $classID ?>" 
               class="btn btn-danger"
               onclick="return confirm('Are you sure you want to delete this class? This action cannot be undone.');">
                <i class="bi bi-trash me-1"></i> Delete
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Class Profile -->
<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <div class="mb-3">
                    <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center" 
                         style="width: 120px; height: 120px;">
                        <i class="bi bi-building" style="font-size: 5rem; color: #2d5016;"></i>
                    </div>
                </div>
                <h4 class="mb-1"><?= htmlspecialchars($class['className'] ?? '') ?></h4>
                <p class="text-muted mb-3">Class</p>
                <div class="mb-3">
                    <span class="badge bg-success px-3 py-2">
                        <i class="bi bi-check-circle me-1"></i>Active
                    </span>
                </div>
                <hr>
                <div class="text-start">
                    <small class="text-muted d-block mb-2">Quick Info</small>
                    <div class="d-flex align-items-center mb-2">
                        <i class="bi bi-hash text-muted me-2"></i>
                        <span class="small">Class ID: <?= htmlspecialchars($class['classID'] ?? 'N/A') ?></span>
                    </div>
                    <div class="d-flex align-items-center mb-2">
                        <i class="bi bi-person-badge text-muted me-2"></i>
                        <span class="small"><?= htmlspecialchars(($class['teacherFirstName'] ?? $class['fName'] ?? '') . ' ' . ($class['teacherLastName'] ?? $class['lName'] ?? 'No Teacher')) ?></span>
                    </div>
                    <div class="d-flex align-items-center">
                        <i class="bi bi-people text-muted me-2"></i>
                        <span class="small"><?= count($roster) ?> Student<?= count($roster) !== 1 ? 's' : '' ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <!-- Class Information -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">
                    <i class="bi bi-info-circle-fill me-2" style="color: #2d5016;"></i>Class Information
                </h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="text-muted small mb-1">Class Name</label>
                        <p class="mb-0 fw-semibold"><?= htmlspecialchars($class['className'] ?? 'N/A') ?></p>
                    </div>
                    <div class="col-md-12">
                        <label class="text-muted small mb-1">Assigned Teacher</label>
                        <p class="mb-0 fw-semibold">
                            <?= htmlspecialchars(($class['teacherFirstName'] ?? $class['fName'] ?? '') . ' ' . ($class['teacherLastName'] ?? $class['lName'] ?? 'Not Assigned')) ?>
                        </p>
                    </div>
                    <div class="col-md-12">
                        <label class="text-muted small mb-1">Description</label>
                        <p class="mb-0"><?= nl2br(htmlspecialchars($class['description'] ?? 'No description available')) ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Class Roster -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">
                    <i class="bi bi-people-fill me-2" style="color: #2d5016;"></i>Class Roster
                    <span class="badge bg-primary ms-2"><?= count($roster) ?></span>
                </h5>
            </div>
            <div class="card-body">
                <?php if (!empty($roster)): ?>
                <div class="row g-3">
                    <?php foreach ($roster as $p): ?>
                    <div class="col-md-6">
                        <div class="card bg-light border-0">
                            <div class="card-body py-2">
                                <div class="d-flex align-items-center">
                                    <div class="rounded-circle bg-white d-flex align-items-center justify-content-center me-2" 
                                         style="width: 40px; height: 40px;">
                                        <i class="bi bi-person-fill" style="color: #2d5016;"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">
                                            <?= htmlspecialchars(($p['fName'] ?? $p['firstName'] ?? '') . ' ' . ($p['lName'] ?? $p['lastName'] ?? '')) ?>
                                        </h6>
                                        <small class="text-muted">
                                            <i class="bi bi-hash"></i><?= htmlspecialchars($p['studentNumber'] ?? $p['pupilID'] ?? '') ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="text-center py-4">
                    <i class="bi bi-people" style="font-size: 3rem; color: #dee2e6;"></i>
                    <p class="text-muted mt-2 mb-0">No pupils assigned to this class</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
    .card {
        transition: all 0.3s ease;
    }
    .card:hover {
        transform: translateY(-2px);
    }
    .btn {
        transition: all 0.2s ease;
    }
    .btn:hover {
        transform: translateY(-1px);
    }
</style>

<?php require_once 'includes/footer.php'; ?>
