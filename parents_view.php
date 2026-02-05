<?php
require_once 'includes/bootstrap.php';
require_once 'includes/Auth.php';

Auth::requireLogin();

require_once 'modules/roles/RolesModel.php';
$rolesModel = new RolesModel();
if (!$rolesModel->userHasPermission(Auth::id(), 'view_parents')) {
    Session::setFlash('error', 'You do not have permission to view parents.');
    header('Location: /LilayiParkSchool/403.php');
    exit;
}

require_once 'modules/parents/ParentModel.php';

$parentID = $_GET['id'] ?? null;
if (empty($parentID)) {
    header('Location: parents_list.php');
    exit;
}

// Check if user can access this parent
if (!Auth::canAccessParent($parentID)) {
    Session::setFlash('error', 'You do not have permission to view this parent.');
    header('Location: /LilayiParkSchool/403.php');
    exit;
}

$parentModel = new ParentModel();
$parent = $parentModel->getParentWithUser($parentID) ?: $parentModel->getById($parentID);
$children = method_exists($parentModel, 'getChildren') ? $parentModel->getChildren($parentID) : [];

$pageTitle = 'Parent Details';
$currentPage = 'parents';
require_once 'includes/header.php';
require_once 'includes/PermissionHelper.php';
?>

<!-- Page Header with Actions -->
<div class="row mb-4">
    <div class="col-md-8">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="parents_list.php" class="text-decoration-none">Parents</a></li>
                <li class="breadcrumb-item active" aria-current="page">View Details</li>
            </ol>
        </nav>
        <h2 class="mb-0">
            <i class="bi bi-people me-2" style="color: #2d5016;"></i>
            <span style="color: #2d5016; font-weight: 600;">Parent Profile</span>
        </h2>
    </div>
    <div class="col-md-4 text-end align-self-end">
        <div class="btn-group" role="group">
            <a href="parents_list.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
            <?php if (PermissionHelper::canManage('parents')): ?>
            <a href="parents_form.php?id=<?= $parentID ?>" class="btn btn-warning">
                <i class="bi bi-pencil me-1"></i> Edit
            </a>
            <a href="delete.php?module=parents&id=<?= $parentID ?>" 
               class="btn btn-danger"
               onclick="return confirm('Are you sure you want to delete this parent? This action cannot be undone.');">
                <i class="bi bi-trash me-1"></i> Delete
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Parent Profile -->
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
                <h4 class="mb-1"><?php
                    function sentence_case($str) {
                        $str = strtolower($str);
                        return ucfirst($str);
                    }
                    $firstName = $parent['fName'] ?? $parent['firstName'] ?? '';
                    $lastName = $parent['lName'] ?? $parent['lastName'] ?? '';
                    echo sentence_case($firstName) . ' ' . sentence_case($lastName);
                ?></h4>
                <p class="text-muted mb-3">Parent / Guardian</p>
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
                        <a href="mailto:<?= htmlspecialchars($parent['email1'] ?? '') ?>" class="text-decoration-none small">
                            <?= htmlspecialchars($parent['email1'] ?? 'N/A') ?>
                        </a>
                    </div>
                    <div class="d-flex align-items-center mb-2">
                        <i class="bi bi-telephone text-muted me-2"></i>
                        <a href="tel:<?= htmlspecialchars($parent['phone'] ?? '') ?>" class="text-decoration-none small">
                            <?= htmlspecialchars($parent['phone'] ?? 'N/A') ?>
                        </a>
                    </div>
                    <div class="d-flex align-items-center">
                        <i class="bi bi-people text-muted me-2"></i>
                        <span class="small"><?= count($children) ?> Child<?= count($children) !== 1 ? 'ren' : '' ?></span>
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
                        <p class="mb-0 fw-semibold"><?php echo sentence_case($parent['fName'] ?? 'N/A'); ?></p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small mb-1">Last Name</label>
                        <p class="mb-0 fw-semibold"><?php echo sentence_case($parent['lName'] ?? 'N/A'); ?></p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small mb-1">Gender</label>
                        <p class="mb-0 fw-semibold">
                            <?php 
                            $gender = $parent['gender'] ?? '';
                            echo $gender === 'M' ? 'Male' : ($gender === 'F' ? 'Female' : 'N/A');
                            ?>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small mb-1">NRC Number</label>
                        <p class="mb-0 fw-semibold"><?= htmlspecialchars($parent['NRC'] ?? 'N/A') ?></p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small mb-1">Relation</label>
                        <p class="mb-0 fw-semibold"><?php echo sentence_case($parent['relation'] ?? 'N/A'); ?></p>
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
                        <label class="text-muted small mb-1">Primary Email</label>
                        <p class="mb-0">
                            <a href="mailto:<?= htmlspecialchars($parent['email1'] ?? '') ?>" class="text-decoration-none">
                                <i class="bi bi-envelope me-1"></i><?= htmlspecialchars($parent['email1'] ?? 'N/A') ?>
                            </a>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small mb-1">Secondary Email</label>
                        <p class="mb-0">
                            <?php if (!empty($parent['email2'])): ?>
                            <a href="mailto:<?= htmlspecialchars($parent['email2']) ?>" class="text-decoration-none">
                                <i class="bi bi-envelope me-1"></i><?= htmlspecialchars($parent['email2']) ?>
                            </a>
                            <?php else: ?>
                            <span class="text-muted">Not provided</span>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small mb-1">Phone Number</label>
                        <p class="mb-0">
                            <a href="tel:<?= htmlspecialchars($parent['phone'] ?? '') ?>" class="text-decoration-none">
                                <i class="bi bi-telephone me-1"></i><?= htmlspecialchars($parent['phone'] ?? 'N/A') ?>
                            </a>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small mb-1">Occupation</label>
                        <p class="mb-0 fw-semibold"><?= htmlspecialchars($parent['occupation'] ?? 'Not specified') ?></p>
                    </div>
                    <div class="col-md-12">
                        <label class="text-muted small mb-1">Workplace</label>
                        <p class="mb-0 fw-semibold"><?= htmlspecialchars($parent['workplace'] ?? 'Not specified') ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Children / Dependents -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">
                    <i class="bi bi-mortarboard me-2" style="color: #2d5016;"></i>Children / Dependents
                    <span class="badge bg-primary ms-2"><?= count($children) ?></span>
                </h5>
            </div>
            <div class="card-body">
                <?php if (!empty($children)): ?>
                <div class="row g-3">
                    <?php foreach ($children as $child): ?>
                    <div class="col-md-6">
                        <div class="card bg-light border-0">
                            <div class="card-body">
                                <h6 class="mb-1">
                                    <i class="bi bi-person-fill me-1" style="color: #2d5016;"></i>
                                    <?php
                                        $cf = $child['fName'] ?? $child['firstName'] ?? '';
                                        $cl = $child['lName'] ?? $child['lastName'] ?? '';
                                        echo sentence_case($cf) . ' ' . sentence_case($cl);
                                    ?>
                                </h6>
                                <small class="text-muted">
                                    <i class="bi bi-hash"></i><?= htmlspecialchars($child['pupilID'] ?? '') ?>
                                </small>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="text-center py-4">
                    <i class="bi bi-people" style="font-size: 3rem; color: #dee2e6;"></i>
                    <p class="text-muted mt-2 mb-0">No children linked yet</p>
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
