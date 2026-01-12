<?php
require_once 'includes/bootstrap.php';
require_once 'includes/Auth.php';

Auth::requireLogin();

require_once 'modules/roles/RolesModel.php';
$rolesModel = new RolesModel();
if (!$rolesModel->userHasPermission(Auth::id(), 'view_pupils')) {
    Session::setFlash('error', 'You do not have permission to view pupils.');
    header('Location: /LilayiParkSchool/403.php');
    exit;
}

require_once 'modules/pupils/PupilModel.php';

$pupilID = $_GET['id'] ?? null;
if (empty($pupilID)) {
    header('Location: pupils_list.php');
    exit;
}

$pupilModel = new PupilModel();
$pupil = $pupilModel->getPupilWithParent($pupilID) ?: $pupilModel->getById($pupilID);

$pageTitle = 'Pupil Details';
$currentPage = 'pupils';
require_once 'includes/header.php';
require_once 'includes/PermissionHelper.php';
?>

<!-- Page Header with Actions -->
<div class="row mb-4">
    <div class="col-md-8">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="pupils_list.php" class="text-decoration-none">Pupils</a></li>
                <li class="breadcrumb-item active" aria-current="page">View Details</li>
            </ol>
        </nav>
        <h2 class="mb-0">
            <i class="bi bi-mortarboard me-2" style="color: #2d5016;"></i>
            <span style="color: #2d5016; font-weight: 600;">Pupil Profile</span>
        </h2>
    </div>
    <div class="col-md-4 text-end align-self-end">
        <div class="btn-group" role="group">
            <a href="pupils_list.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
            <?php if (Auth::hasRole('admin')): ?>
            <a href="payments_form.php?pupil=<?= $pupilID ?>" class="btn btn-success">
                <i class="bi bi-cash-coin me-1"></i> Record Payment
            </a>
            <?php endif; ?>
            <?php if (PermissionHelper::canManage('pupils')): ?>
            <a href="pupils_form.php?id=<?= $pupilID ?>" class="btn btn-warning">
                <i class="bi bi-pencil me-1"></i> Edit
            </a>
            <a href="pupils_delete.php?id=<?= $pupilID ?>" 
               class="btn btn-danger"
               onclick="return confirm('Are you sure you want to delete this pupil? This action cannot be undone.');">
                <i class="bi bi-trash me-1"></i> Delete
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Pupil Profile -->
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
                <h4 class="mb-1"><?= htmlspecialchars($pupil['fName'] ?? $pupil['firstName'] ?? '') . ' ' . htmlspecialchars($pupil['lName'] ?? $pupil['lastName'] ?? '') ?></h4>
                <p class="text-muted mb-3">Student</p>
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
                        <span class="small"><?= htmlspecialchars($pupil['pupilID'] ?? 'N/A') ?></span>
                    </div>
                    <div class="d-flex align-items-center mb-2">
                        <i class="bi bi-calendar text-muted me-2"></i>
                        <span class="small"><?= htmlspecialchars($pupil['dateOfBirth'] ?? 'N/A') ?></span>
                    </div>
                    <div class="d-flex align-items-center">
                        <i class="bi bi-gender-ambiguous text-muted me-2"></i>
                        <span class="small"><?php 
                            $gender = $pupil['gender'] ?? '';
                            echo $gender === 'M' ? 'Male' : ($gender === 'F' ? 'Female' : 'N/A');
                        ?></span>
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
                        <p class="mb-0 fw-semibold"><?= htmlspecialchars($pupil['fName'] ?? 'N/A') ?></p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small mb-1">Last Name</label>
                        <p class="mb-0 fw-semibold"><?= htmlspecialchars($pupil['lName'] ?? 'N/A') ?></p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small mb-1">Pupil ID</label>
                        <p class="mb-0 fw-semibold"><?= htmlspecialchars($pupil['pupilID'] ?? 'N/A') ?></p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small mb-1">Date of Birth</label>
                        <p class="mb-0 fw-semibold"><?= htmlspecialchars($pupil['dateOfBirth'] ?? 'N/A') ?></p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small mb-1">Gender</label>
                        <p class="mb-0 fw-semibold">
                            <?php 
                            $gender = $pupil['gender'] ?? '';
                            echo $gender === 'M' ? 'Male' : ($gender === 'F' ? 'Female' : 'N/A');
                            ?>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small mb-1">Class ID</label>
                        <p class="mb-0 fw-semibold"><?= htmlspecialchars($pupil['classID'] ?? 'Not Assigned') ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Parent/Guardian Information -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">
                    <i class="bi bi-people-fill me-2" style="color: #2d5016;"></i>Parent / Guardian Information
                </h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="text-muted small mb-1">Guardian Name</label>
                        <p class="mb-0 fw-semibold"><?= htmlspecialchars(($pupil['parentFirstName'] ?? '') . ' ' . ($pupil['parentLastName'] ?? 'N/A')) ?></p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small mb-1">Contact Number</label>
                        <p class="mb-0">
                            <a href="tel:<?= htmlspecialchars($pupil['parentPhone'] ?? $pupil['parentPhoneNumber'] ?? '') ?>" class="text-decoration-none">
                                <i class="bi bi-telephone me-1"></i><?= htmlspecialchars($pupil['parentPhone'] ?? $pupil['parentPhoneNumber'] ?? 'N/A') ?>
                            </a>
                        </p>
                    </div>
                    <div class="col-md-12">
                        <label class="text-muted small mb-1">Address</label>
                        <p class="mb-0"><?= nl2br(htmlspecialchars($pupil['address'] ?? 'Not provided')) ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Medical Information -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">
                    <i class="bi bi-heart-pulse-fill me-2" style="color: #2d5016;"></i>Medical Information
                </h5>
            </div>
            <div class="card-body">
                <?php if (!empty($pupil['medicalInfo'])): ?>
                <p class="mb-0"><?= nl2br(htmlspecialchars($pupil['medicalInfo'])) ?></p>
                <?php else: ?>
                <div class="text-center py-3">
                    <i class="bi bi-file-medical" style="font-size: 2rem; color: #dee2e6;"></i>
                    <p class="text-muted mt-2 mb-0">No medical information on file</p>
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
