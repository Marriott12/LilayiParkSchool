<?php
require_once 'includes/bootstrap.php';
require_once 'includes/Auth.php';

Auth::requireLogin();

require_once 'modules/users/UsersModel.php';
$usersModel = new UsersModel();

$error = '';
$success = false;

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $error = 'All fields are required.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'New passwords do not match.';
    } elseif (strlen($newPassword) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } else {
        // Verify current password
        $user = $usersModel->find(Auth::id());
        
        if (!password_verify($currentPassword, $user['password'])) {
            $error = 'Current password is incorrect.';
        } else {
            // Update password
            try {
                $usersModel->update(Auth::id(), [
                    'password' => password_hash($newPassword, PASSWORD_BCRYPT),
                    'mustChangePassword' => 'N',
                    'updatedAt' => date('Y-m-d H:i:s')
                ]);
                
                $success = true;
                Session::setFlash('success', 'Password changed successfully!');
                
                // Redirect after 2 seconds
                header('refresh:2;url=' . BASE_URL . '/index.php');
            } catch (Exception $e) {
                $error = 'Failed to update password: ' . $e->getMessage();
            }
        }
    }
}

// Check if user must change password
$user = $usersModel->find(Auth::id());
$mustChange = isset($user['mustChangePassword']) && $user['mustChangePassword'] === 'Y';

$pageTitle = 'Change Password';
$currentPage = 'change-password';
require_once 'includes/header.php';
?>

<div class="row justify-content-center mt-5">
    <div class="col-md-6">
        <div class="card shadow">
            <div class="card-header" style="background: linear-gradient(135deg, #2d5016 0%, #5cb85c 100%); color: white;">
                <h4 class="mb-0">
                    <i class="bi bi-shield-lock me-2"></i>Change Password
                </h4>
            </div>
            <div class="card-body">
                <?php if ($mustChange): ?>
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <strong>Security Notice:</strong> You must change your password before continuing.
                </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle me-2"></i>
                    Password changed successfully! Redirecting...
                </div>
                <?php elseif ($error): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-x-circle me-2"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
                <?php endif; ?>
                
                <?php if (!$success): ?>
                <form method="POST" id="changePasswordForm">
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Current Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-key"></i></span>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" class="form-control" id="new_password" name="new_password" 
                                   required minlength="8">
                        </div>
                        <small class="text-muted">Minimum 8 characters</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm New Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                   required minlength="8">
                        </div>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-circle me-2"></i>Change Password
                        </button>
                        <?php if (!$mustChange): ?>
                        <a href="<?= BASE_URL ?>/index.php" class="btn btn-secondary">
                            <i class="bi bi-x-circle me-2"></i>Cancel
                        </a>
                        <?php endif; ?>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if (!$success): ?>
        <div class="card mt-3">
            <div class="card-body">
                <h6 class="card-title"><i class="bi bi-info-circle me-2"></i>Password Requirements</h6>
                <ul class="mb-0 small">
                    <li>Minimum 8 characters long</li>
                    <li>Use a mix of letters, numbers, and symbols for better security</li>
                    <li>Avoid using common words or personal information</li>
                </ul>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Password match validation
document.getElementById('changePasswordForm')?.addEventListener('submit', function(e) {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    if (newPassword !== confirmPassword) {
        e.preventDefault();
        alert('New passwords do not match!');
        return false;
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
