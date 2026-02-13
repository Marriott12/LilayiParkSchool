<?php
require_once 'includes/bootstrap.php';
require_once 'includes/Auth.php';

Auth::requireLogin();

require_once 'modules/roles/RolesModel.php';
$rolesModel = new RolesModel();
if (!$rolesModel->userHasPermission(Auth::id(), 'manage_users')) {
    Session::setFlash('error', 'You do not have permission to manage users.');
    header('Location: 403.php');
    exit;
}

require_once 'modules/users/UsersModel.php';

$userID = $_GET['id'] ?? null;
if (empty($userID)) {
    header('Location: users_list.php');
    exit;
}

$usersModel = new UsersModel();
$user = $usersModel->getById($userID);

if (!$user) {
    Session::setFlash('error', 'User not found');
    header('Location: users_list.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token first
    if (!CSRF::requireToken()) {
        $error = $GLOBALS['csrf_error'] ?? 'Security validation failed. Please try again.';
    } else {
        $password = trim($_POST['password'] ?? '');
        $confirmPassword = trim($_POST['password_confirm'] ?? '');
        
        if (empty($password)) {
            $error = 'Password is required';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters';
        } elseif ($password !== $confirmPassword) {
            $error = 'Passwords do not match';
        } else {
            try {
                $usersModel->updatePassword($userID, $password);
                Session::setFlash('success', 'Password updated successfully');
                CSRF::regenerateToken();
                header('Location: users_view.php?id=' . $userID);
                exit;
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
    }
}

$pageTitle = 'Reset Password';
$currentPage = 'users';
require_once 'includes/header.php';
?>

<div class="mb-4">
    <a href="users_view.php?id=<?= $userID ?>" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back to User
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header" style="background: linear-gradient(135deg, #2d5016 0%, #5cb85c 100%); color: white;">
                <h5 class="mb-0">
                    <i class="bi bi-key-fill"></i> Reset Password
                </h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> Resetting password for: <strong><?= htmlspecialchars($user['username']) ?></strong>
                </div>
                
                <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="bi bi-exclamation-triangle-fill"></i> <?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <?= CSRF::field() ?>
                    <div class="mb-3">
                        <label class="form-label">New Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" name="password" required minlength="6" autofocus>
                        <small class="text-muted">Minimum 6 characters</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Confirm Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" name="password_confirm" required minlength="6">
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn" style="background-color: #2d5016; color: white;">
                            <i class="bi bi-check-circle"></i> Update Password
                        </button>
                        <a href="users_view.php?id=<?= $userID ?>" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
