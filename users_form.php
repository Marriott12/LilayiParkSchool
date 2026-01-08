<?php
require_once 'includes/bootstrap.php';

RBAC::requireAuth();

$userID = $_GET['id'] ?? null;
$isEdit = !empty($userID);

if ($isEdit) {
    RBAC::requirePermission('users', 'update');
} else {
    RBAC::requirePermission('users', 'create');
}

require_once 'modules/users/UsersModel.php';

$usersModel = new UsersModel();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'username' => trim($_POST['username'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'roleID' => $_POST['roleID'] ?? '',
        'isActive' => isset($_POST['isActive']) ? 1 : 0
    ];
    
    // Validate username uniqueness
    if ($usersModel->usernameExists($data['username'], $userID)) {
        $error = 'Username already exists';
    }
    // Validate email uniqueness
    elseif ($usersModel->emailExists($data['email'], $userID)) {
        $error = 'Email already exists';
    }
    // Add password for new users
    elseif (!$isEdit && empty($_POST['password'])) {
        $error = 'Password is required for new users';
    }
    // Add password if provided
    elseif (!empty($_POST['password'])) {
        if ($_POST['password'] !== $_POST['password_confirm']) {
            $error = 'Passwords do not match';
        } else {
            $data['password'] = $_POST['password'];
        }
    }
    
    if (!isset($error)) {
        // Validate CSRF token only when all validation passes
        CSRF::requireToken();
        
        try {
            if ($isEdit) {
                // Don't update password unless provided
                if (empty($data['password'])) {
                    unset($data['password']);
                } else {
                    $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);
                }
                $usersModel->update($userID, $data);
                Session::setFlash('success', 'User updated successfully');
            } else {
                $usersModel->createUser($data);
                Session::setFlash('success', 'User created successfully');
            }
            header('Location: users_list.php');
            exit;
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

// Get user data if editing
$user = $isEdit ? $usersModel->getById($userID) : null;

$pageTitle = $isEdit ? 'Edit User' : 'Add New User';
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
        <h5 class="mb-0">
            <i class="bi bi-person-plus-fill"></i> <?= $pageTitle ?>
        </h5>
    </div>
    <div class="card-body">
        <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle-fill"></i> <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <?= CSRF::field() ?>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Username <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="username" 
                           value="<?= htmlspecialchars($user['username'] ?? '') ?>" required>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Email <span class="text-danger">*</span></label>
                    <input type="email" class="form-control" name="email" 
                           value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Role <span class="text-danger">*</span></label>
                    <select class="form-select" name="roleID" required>
                        <option value="">Select Role</option>
                        <option value="1" <?= ($user['roleID'] ?? '') == '1' ? 'selected' : '' ?>>Admin</option>
                        <option value="2" <?= ($user['roleID'] ?? '') == '2' ? 'selected' : '' ?>>Teacher</option>
                        <option value="3" <?= ($user['roleID'] ?? '') == '3' ? 'selected' : '' ?>>Parent</option>
                    </select>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Status</label>
                    <div class="form-check form-switch mt-2">
                        <input class="form-check-input" type="checkbox" name="isActive" id="isActive" 
                               <?= ($user['isActive'] ?? 1) == 1 ? 'checked' : '' ?>>
                        <label class="form-check-label" for="isActive">Active</label>
                    </div>
                </div>
            </div>
            
            <?php if (!$isEdit): ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> Password is required for new users
            </div>
            <?php else: ?>
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle"></i> Leave password fields empty to keep current password
            </div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Password <?= !$isEdit ? '<span class="text-danger">*</span>' : '' ?></label>
                    <input type="password" class="form-control" name="password" 
                           <?= !$isEdit ? 'required' : '' ?> minlength="6">
                    <small class="text-muted">Minimum 6 characters</small>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Confirm Password <?= !$isEdit ? '<span class="text-danger">*</span>' : '' ?></label>
                    <input type="password" class="form-control" name="password_confirm" 
                           <?= !$isEdit ? 'required' : '' ?> minlength="6">
                </div>
            </div>
            
            <div class="d-flex gap-2">
                <button type="submit" class="btn" style="background-color: #2d5016; color: white;">
                    <i class="bi bi-save"></i> <?= $isEdit ? 'Update' : 'Create' ?> User
                </button>
                <a href="users_list.php" class="btn btn-secondary">
                    <i class="bi bi-x-circle"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
