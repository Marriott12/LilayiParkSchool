<?php
require_once 'includes/bootstrap.php';

RBAC::requireAuth();
RBAC::requirePermission('users', 'read');

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

<div class="mb-4">
    <a href="users_list.php" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back to Users
    </a>
</div>

<div class="card">
    <div class="card-header" style="background: linear-gradient(135deg, #2d5016 0%, #5cb85c 100%); color: white;">
        <h5 class="mb-0"><?= htmlspecialchars($user['username']) ?></h5>
    </div>
    <div class="card-body">
        <dl class="row">
            <dt class="col-sm-3">Username</dt>
            <dd class="col-sm-9"><?= htmlspecialchars($user['username']) ?></dd>

            <dt class="col-sm-3">Email</dt>
            <dd class="col-sm-9"><?= htmlspecialchars($user['email']) ?></dd>

            <dt class="col-sm-3">Role</dt>
            <dd class="col-sm-9">
                <?php 
                $roleColors = ['Admin' => '#2d5016', 'Teacher' => '#5cb85c', 'Parent' => '#f0ad4e'];
                $roleColor = $roleColors[$user['roleName']] ?? '#6c757d';
                ?>
                <span class="badge" style="background-color: <?= $roleColor ?>;">
                    <?= htmlspecialchars($user['roleName']) ?>
                </span>
            </dd>

            <dt class="col-sm-3">Status</dt>
            <dd class="col-sm-9">
                <?php if (($user['isActive'] ?? 1) == 1): ?>
                    <span class="badge bg-success">Active</span>
                <?php else: ?>
                    <span class="badge bg-secondary">Inactive</span>
                <?php endif; ?>
            </dd>

            <dt class="col-sm-3">Created</dt>
            <dd class="col-sm-9"><?= $user['createdAt'] ? date('M d, Y H:i', strtotime($user['createdAt'])) : 'N/A' ?></dd>

            <dt class="col-sm-3">Last Updated</dt>
            <dd class="col-sm-9"><?= $user['updatedAt'] ? date('M d, Y H:i', strtotime($user['updatedAt'])) : 'N/A' ?></dd>
        </dl>
        
        <div class="mt-4">
            <?php if (RBAC::hasPermission(Session::getUserRole(), 'users', 'update')): ?>
            <a href="users_form.php?id=<?= $user['userID'] ?>" class="btn btn-warning">
                <i class="bi bi-pencil"></i> Edit User
            </a>
            <a href="users_password.php?id=<?= $user['userID'] ?>" class="btn btn-secondary">
                <i class="bi bi-key"></i> Reset Password
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
