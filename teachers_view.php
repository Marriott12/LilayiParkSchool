<?php
require_once 'includes/bootstrap.php';
require_once 'includes/Auth.php';

Auth::requireLogin();

// Check permission via RBAC
require_once 'modules/roles/RolesModel.php';
$rolesModel = new RolesModel();
if (!$rolesModel->userHasPermission(Auth::id(), 'view_teachers')) {
    Session::setFlash('error', 'You do not have permission to view teachers.');
    header('Location: 403.php');
    exit;
}

require_once 'modules/teachers/TeacherModel.php';

$teacherID = $_GET['id'] ?? null;
if (empty($teacherID)) {
    header('Location: teachers_list.php');
    exit;
}

// Check if user can access this teacher
if (!Auth::canAccessTeacher($teacherID)) {
    Session::setFlash('error', 'You do not have permission to view this teacher.');
    header('Location: 403.php');
    exit;
}

$teacherModel = new TeacherModel();
$teacher = $teacherModel->getTeacherWithUser($teacherID) ?: $teacherModel->getById($teacherID);
$classes = method_exists($teacherModel, 'getTeacherClasses') ? $teacherModel->getTeacherClasses($teacherID) : [];

$pageTitle = 'Teacher Details';
$currentPage = 'teachers';
require_once 'includes/header.php';
require_once 'includes/PermissionHelper.php';
?>

<!-- Page Header with Actions -->
<div class="row mb-4">
    <div class="col-md-8">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="teachers_list.php" class="text-decoration-none">Teachers</a></li>
                <li class="breadcrumb-item active" aria-current="page">View Details</li>
            </ol>
        </nav>
        <h2 class="mb-0">
            <i class="bi bi-person-badge me-2" style="color: #2d5016;"></i>
            <span style="color: #2d5016; font-weight: 600;">Teacher Profile</span>
        </h2>
    </div>
    <div class="col-md-4 text-end align-self-end">
        <div class="btn-group" role="group">
            <a href="teachers_list.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
            <?php if (PermissionHelper::canManage('teachers')): ?>
            <a href="teachers_form.php?id=<?= $teacherID ?>" class="btn btn-warning">
                <i class="bi bi-pencil me-1"></i> Edit
            </a>
            <a href="delete.php?module=teachers&id=<?= $teacherID ?>" 
               class="btn btn-danger"
               onclick="return confirm('Are you sure you want to delete this teacher? This action cannot be undone.');">
                <i class="bi bi-trash me-1"></i> Delete
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Teacher Profile Card -->
<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <div class="mb-3">
                    <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center" 
                         style="width: 120px; height: 120px;">
                        <i class="bi bi-person-circle" style="font-size: 6rem; color: #2d5016;"></i>
                    </div>
                </div>
                <h4 class="mb-1"><?= htmlspecialchars($teacher['fName'] ?? $teacher['firstName'] ?? '') . ' ' . htmlspecialchars($teacher['lName'] ?? $teacher['lastName'] ?? '') ?></h4>
                <p class="text-muted mb-3">Teacher</p>
                <div class="mb-3">
                    <span class="badge bg-success px-3 py-2">
                        <i class="bi bi-check-circle me-1"></i>Active
                    </span>
                </div>
                <hr>
                <div class="text-start">
                    <small class="text-muted d-block mb-2">Quick Info</small>
                    <div class="d-flex align-items-center mb-2">
                        <i class="bi bi-envelope text-muted me-2"></i>
                        <a href="mailto:<?= htmlspecialchars($teacher['email'] ?? '') ?>" class="text-decoration-none small">
                            <?= htmlspecialchars($teacher['email'] ?? 'N/A') ?>
                        </a>
                    </div>
                    <div class="d-flex align-items-center mb-2">
                        <i class="bi bi-telephone text-muted me-2"></i>
                        <a href="tel:<?= htmlspecialchars($teacher['phone'] ?? '') ?>" class="text-decoration-none small">
                            <?= htmlspecialchars($teacher['phone'] ?? 'N/A') ?>
                        </a>
                    </div>
                    <div class="d-flex align-items-center">
                        <i class="bi bi-award text-muted me-2"></i>
                        <span class="small"><?= htmlspecialchars($teacher['tczNo'] ?? 'No TCZ Number') ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <!-- Personal Information -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">
                    <i class="bi bi-person-lines-fill me-2" style="color: #2d5016;"></i>Personal Information
                </h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="text-muted small mb-1">First Name</label>
                        <p class="mb-0 fw-semibold"><?= htmlspecialchars($teacher['fName'] ?? 'N/A') ?></p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small mb-1">Last Name</label>
                        <p class="mb-0 fw-semibold"><?= htmlspecialchars($teacher['lName'] ?? 'N/A') ?></p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small mb-1">Gender</label>
                        <p class="mb-0 fw-semibold">
                            <?php 
                            $gender = $teacher['gender'] ?? '';
                            echo $gender === 'M' ? 'Male' : ($gender === 'F' ? 'Female' : 'N/A');
                            ?>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small mb-1">NRC Number</label>
                        <p class="mb-0 fw-semibold"><?= htmlspecialchars($teacher['NRC'] ?? 'N/A') ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contact & Professional Details -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">
                    <i class="bi bi-telephone-fill me-2" style="color: #2d5016;"></i>Contact & Professional Details
                </h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="text-muted small mb-1">Email Address</label>
                        <p class="mb-0">
                            <a href="mailto:<?= htmlspecialchars($teacher['email'] ?? '') ?>" class="text-decoration-none">
                                <i class="bi bi-envelope me-1"></i><?= htmlspecialchars($teacher['email'] ?? 'N/A') ?>
                            </a>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small mb-1">Phone Number</label>
                        <p class="mb-0">
                            <a href="tel:<?= htmlspecialchars($teacher['phone'] ?? '') ?>" class="text-decoration-none">
                                <i class="bi bi-telephone me-1"></i><?= htmlspecialchars($teacher['phone'] ?? 'N/A') ?>
                            </a>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small mb-1">SSN</label>
                        <p class="mb-0 fw-semibold"><?= htmlspecialchars($teacher['SSN'] ?? 'N/A') ?></p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small mb-1">TPIN</label>
                        <p class="mb-0 fw-semibold"><?= htmlspecialchars($teacher['Tpin'] ?? 'N/A') ?></p>
                    </div>
                    <div class="col-md-12">
                        <label class="text-muted small mb-1">TCZ Number</label>
                        <p class="mb-0">
                            <span class="badge bg-secondary px-3 py-2">
                                <i class="bi bi-award me-1"></i><?= htmlspecialchars($teacher['tczNo'] ?? 'Not Assigned') ?>
                            </span>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Classes Assigned -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">
                    <i class="bi bi-book me-2" style="color: #2d5016;"></i>Classes Assigned
                    <span class="badge bg-primary ms-2"><?= count($classes) ?></span>
                </h5>
            </div>
            <div class="card-body">
                <?php if (!empty($classes)): ?>
                <div class="row g-3">
                    <?php foreach ($classes as $c): ?>
                    <div class="col-md-6">
                        <div class="card bg-light border-0">
                            <div class="card-body">
                                <h6 class="mb-1">
                                    <i class="bi bi-mortarboard-fill me-1" style="color: #2d5016;"></i>
                                    <?= htmlspecialchars($c['className'] ?? $c['name'] ?? ('Class ' . ($c['classID'] ?? ''))) ?>
                                </h6>
                                <small class="text-muted">
                                    <i class="bi bi-hash"></i><?= htmlspecialchars($c['classID'] ?? '') ?>
                                </small>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="text-center py-4">
                    <i class="bi bi-book" style="font-size: 3rem; color: #dee2e6;"></i>
                    <p class="text-muted mt-2 mb-0">No classes assigned yet</p>
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
