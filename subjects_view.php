<?php
require_once 'includes/bootstrap.php';
require_once 'includes/Auth.php';

Auth::requireLogin();

require_once 'modules/roles/RolesModel.php';
$rolesModel = new RolesModel();
if (!$rolesModel->userHasPermission(Auth::id(), 'view_subjects')) {
    Session::setFlash('error', 'You do not have permission to view subjects.');
    header('Location: /LilayiParkSchool/403.php');
    exit;
}

require_once 'modules/subjects/SubjectsModel.php';

$subjectID = $_GET['id'] ?? null;
if (empty($subjectID)) {
    header('Location: subjects_list.php');
    exit;
}

$subjectsModel = new SubjectsModel();
$subject = $subjectsModel->getSubjectWithTeacher($subjectID) ?: $subjectsModel->getById($subjectID);
$assignedClasses = $subjectsModel->getAssignedClasses($subjectID);

$pageTitle = 'Subject Details';
$currentPage = 'subjects';
require_once 'includes/header.php';
require_once 'includes/PermissionHelper.php';
?>

<!-- Page Header with Actions -->
<div class="row mb-4">
    <div class="col-md-8">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="subjects_list.php" class="text-decoration-none">Subjects</a></li>
                <li class="breadcrumb-item active" aria-current="page">View Details</li>
            </ol>
        </nav>
        <h2 class="mb-0">
            <i class="bi bi-book me-2" style="color: #2d5016;"></i>
            <span style="color: #2d5016; font-weight: 600;">Subject Details</span>
        </h2>
    </div>
    <div class="col-md-4 text-end align-self-end">
        <div class="btn-group" role="group">
            <a href="subjects_list.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
            <?php if (PermissionHelper::canManage('subjects')): ?>
            <a href="subjects_form.php?id=<?= $subjectID ?>" class="btn btn-warning">
                <i class="bi bi-pencil me-1"></i> Edit
            </a>
            <a href="delete.php?module=subjects&id=<?= $subjectID ?>" 
               class="btn btn-danger"
               onclick="return confirm('Are you sure you want to delete this subject? This action cannot be undone.');">
                <i class="bi bi-trash me-1"></i> Delete
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Subject Profile -->
<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <div class="mb-3">
                    <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center" 
                         style="width: 120px; height: 120px;">
                        <i class="bi bi-book-fill" style="font-size: 5rem; color: #2d5016;"></i>
                    </div>
                </div>
                <h4 class="mb-1"><?= htmlspecialchars($subject['subjectName']) ?></h4>
                <p class="text-muted mb-3">Subject</p>
                <div class="mb-3">
                    <span class="badge px-3 py-2" style="background-color: #2d5016;">
                        <?= htmlspecialchars($subject['subjectCode']) ?>
                    </span>
                </div>
                <hr>
                <div class="text-start">
                    <small class="text-muted d-block mb-2">Quick Info</small>
                    <div class="d-flex align-items-center mb-2">
                        <i class="bi bi-person-badge text-muted me-2"></i>
                        <span class="small"><?= htmlspecialchars(($subject['teacherFirstName'] ?? '') . ' ' . ($subject['teacherLastName'] ?? 'Not Assigned')) ?></span>
                    </div>
                    <div class="d-flex align-items-center mb-2">
                        <i class="bi bi-award text-muted me-2"></i>
                        <span class="small"><?= htmlspecialchars($subject['credits'] ?? 1) ?> Credit<?= ($subject['credits'] ?? 1) != 1 ? 's' : '' ?></span>
                    </div>
                    <div class="d-flex align-items-center">
                        <i class="bi bi-building text-muted me-2"></i>
                        <span class="small"><?= count($assignedClasses) ?> Class<?= count($assignedClasses) !== 1 ? 'es' : '' ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <!-- Subject Information -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">
                    <i class="bi bi-info-circle-fill me-2" style="color: #2d5016;"></i>Subject Information
                </h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="text-muted small mb-1">Subject Name</label>
                        <p class="mb-0 fw-semibold"><?= htmlspecialchars($subject['subjectName']) ?></p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small mb-1">Subject Code</label>
                        <p class="mb-0">
                            <span class="badge px-3 py-2" style="background-color: #2d5016;">
                                <?= htmlspecialchars($subject['subjectCode']) ?>
                            </span>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small mb-1">Assigned Teacher</label>
                        <p class="mb-0 fw-semibold">
                            <?= htmlspecialchars(($subject['teacherFirstName'] ?? '') . ' ' . ($subject['teacherLastName'] ?? 'Not Assigned')) ?>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small mb-1">Credits</label>
                        <p class="mb-0 fw-semibold">
                            <i class="bi bi-award me-1" style="color: #2d5016;"></i><?= htmlspecialchars($subject['credits'] ?? 1) ?>
                        </p>
                    </div>
                    <div class="col-md-12">
                        <label class="text-muted small mb-1">Description</label>
                        <p class="mb-0"><?= nl2br(htmlspecialchars($subject['description'] ?? 'No description available')) ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Assigned Classes -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">
                    <i class="bi bi-building me-2" style="color: #2d5016;"></i>Assigned Classes
                    <span class="badge bg-primary ms-2"><?= count($assignedClasses) ?></span>
                </h5>
            </div>
            <div class="card-body">
                <?php if (!empty($assignedClasses)): ?>
                <div class="list-group">
                    <?php foreach ($assignedClasses as $class): ?>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <i class="bi bi-building-fill me-2" style="color: #2d5016;"></i>
                            <strong><?= htmlspecialchars($class['className']) ?></strong>
                        </div>
                        <span class="badge bg-secondary">
                            <i class="bi bi-calendar-event me-1"></i>
                            Assigned: <?= date('M d, Y', strtotime($class['assignedDate'])) ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="text-center py-4">
                    <i class="bi bi-building" style="font-size: 3rem; color: #dee2e6;"></i>
                    <p class="text-muted mt-2 mb-0">Not assigned to any classes</p>
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
