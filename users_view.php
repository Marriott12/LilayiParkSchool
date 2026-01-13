<?php
require_once 'includes/bootstrap.php';
require_once 'includes/Auth.php';
require_once 'includes/PermissionHelper.php';

Auth::requireLogin();

require_once 'modules/roles/RolesModel.php';
$rolesModel = new RolesModel();
if (!$rolesModel->userHasPermission(Auth::id(), 'view_users')) {
    Session::setFlash('error', 'You do not have permission to view users.');
    header('Location: /LilayiParkSchool/403.php');
    exit;
}

require_once 'modules/users/UsersModel.php';

$userID = $_GET['id'] ?? null;
if (empty($userID)) {
    header('Location: users_list.php');
    exit;
}

$usersModel = new UsersModel();
$user = $usersModel->getUserWithRole($userID) ?: $usersModel->getById($userID);

$pageTitle = 'User Details';
$currentPage = 'users';
require_once 'includes/header.php';
?>

<!-- Page Header with Actions -->
<div class="row mb-4">
    <div class="col-md-8">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="users_list.php" class="text-decoration-none">Users</a></li>
                <li class="breadcrumb-item active" aria-current="page">View Details</li>
            </ol>
        </nav>
        <h2 class="mb-0">
            <i class="bi bi-person-circle me-2" style="color: #2d5016;"></i>
            <span style="color: #2d5016; font-weight: 600;">User Profile</span>
        </h2>
    </div>
    <div class="col-md-4 text-end align-self-end">
        <div class="btn-group" role="group">
            <a href="users_list.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
            <?php if (PermissionHelper::canManage('users')): ?>
            <a href="users_form.php?id=<?= $user['userID'] ?>" class="btn btn-warning">
                <i class="bi bi-pencil me-1"></i> Edit
            </a>
            <a href="users_password.php?id=<?= $user['userID'] ?>" class="btn btn-secondary">
                <i class="bi bi-key me-1"></i> Reset Password
            </a>
            <a href="delete.php?module=users&id=<?= $user['userID'] ?>" 
               class="btn btn-danger"
               onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.');">
                <i class="bi bi-trash me-1"></i> Delete
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- User Profile -->
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
                <h4 class="mb-1"><?= htmlspecialchars($user['username']) ?></h4>
                <p class="text-muted mb-3">
                    <?php 
                    $roleColors = ['Admin' => '#2d5016', 'Teacher' => '#5cb85c', 'Parent' => '#f0ad4e'];
                    $roleColor = $roleColors[$user['roleName']] ?? '#6c757d';
                    ?>
                    <span class="badge px-3 py-2" style="background-color: <?= $roleColor ?>;">
                        <?= htmlspecialchars($user['roleName']) ?>
                    </span>
                </p>
                <div class="mb-3">
                    <?php if (($user['isActive'] ?? 'Y') == 'Y'): ?>
                        <span class="badge bg-success px-3 py-2">
                            <i class="bi bi-check-circle me-1"></i>Active
                        </span>
                    <?php else: ?>
                        <span class="badge bg-secondary px-3 py-2">
                            <i class="bi bi-x-circle me-1"></i>Inactive
                        </span>
                    <?php endif; ?>
                </div>
                <hr>
                <div class="text-start">
                    <small class="text-muted d-block mb-2">Quick Info</small>
                    <div class="d-flex align-items-center mb-2">
                        <i class="bi bi-envelope text-muted me-2"></i>
                        <a href="mailto:<?= htmlspecialchars($user['email']) ?>" class="text-decoration-none small">
                            <?= htmlspecialchars($user['email']) ?>
                        </a>
                    </div>
                    <div class="d-flex align-items-center">
                        <i class="bi bi-hash text-muted me-2"></i>
                        <span class="small">ID: <?= htmlspecialchars($user['userID']) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <!-- Account Information -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">
                    <i class="bi bi-person-lines-fill me-2" style="color: #2d5016;"></i>Account Information
                </h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="text-muted small mb-1">Username</label>
                        <p class="mb-0 fw-semibold"><?= htmlspecialchars($user['username']) ?></p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small mb-1">Email Address</label>
                        <p class="mb-0">
                            <a href="mailto:<?= htmlspecialchars($user['email']) ?>" class="text-decoration-none">
                                <i class="bi bi-envelope me-1"></i><?= htmlspecialchars($user['email']) ?>
                            </a>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small mb-1">Role</label>
                        <p class="mb-0">
                            <span class="badge px-3 py-2" style="background-color: <?= $roleColor ?>;">
                                <i class="bi bi-shield-check me-1"></i><?= htmlspecialchars($user['roleName']) ?>
                            </span>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small mb-1">Status</label>
                        <p class="mb-0">
                            <?php if (($user['isActive'] ?? 'Y') == 'Y'): ?>
                                <span class="badge bg-success px-3 py-2">
                                    <i class="bi bi-check-circle me-1"></i>Active
                                </span>
                            <?php else: ?>
                                <span class="badge bg-secondary px-3 py-2">
                                    <i class="bi bi-x-circle me-1"></i>Inactive
                                </span>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Activity Information -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">
                    <i class="bi bi-clock-history me-2" style="color: #2d5016;"></i>Activity Information
                </h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="text-muted small mb-1">Account Created</label>
                        <p class="mb-0 fw-semibold">
                            <i class="bi bi-calendar-plus me-1"></i>
                            <?= $user['createdAt'] ? date('M d, Y H:i', strtotime($user['createdAt'])) : 'N/A' ?>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small mb-1">Last Updated</label>
                        <p class="mb-0 fw-semibold">
                            <i class="bi bi-calendar-check me-1"></i>
                            <?= $user['updatedAt'] ? date('M d, Y H:i', strtotime($user['updatedAt'])) : 'N/A' ?>
                        </p>
                    </div>
                </div>
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
