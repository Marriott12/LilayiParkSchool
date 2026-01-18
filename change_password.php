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
    // Validate CSRF token first
    if (!CSRF::requireToken()) {
        $error = $GLOBALS['csrf_error'] ?? 'Security validation failed. Please try again.';
    } else {
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
        <div class="card border-0 shadow-sm">
            <div class="card-header" style="background: linear-gradient(135deg, #2d5016 0%, #5cb85c 100%); color: white;">
                <h4 class="mb-0">
                    <i class="bi bi-shield-lock me-2"></i>Change Password
                </h4>
                <small class="text-white-50">Update your account password</small>
            </div>
            <div class="card-body p-4">
                <?php if ($mustChange): ?>
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <strong>Security Notice:</strong> You must change your password before continuing.
                </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle me-2"></i>
                    Password changed successfully! Redirecting to dashboard...
                </div>
                <?php elseif ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="bi bi-x-circle me-2"></i>
                    <?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <?php if (!$success): ?>
                <form method="POST" id="changePasswordForm">
                    <?= CSRF::field() ?>
                    
                    <div class="mb-4">
                        <label for="current_password" class="form-label fw-semibold">
                            Current Password <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-light">
                                <i class="bi bi-key"></i>
                            </span>
                            <input type="password" class="form-control" id="current_password" 
                                   name="current_password" required 
                                   placeholder="Enter your current password">
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="new_password" class="form-label fw-semibold">
                            New Password <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-light">
                                <i class="bi bi-lock"></i>
                            </span>
                            <input type="password" class="form-control" id="new_password" 
                                   name="new_password" required minlength="8"
                                   placeholder="Enter new password">
                        </div>
                        <small class="text-muted">
                            <i class="bi bi-info-circle me-1"></i>Minimum 8 characters
                        </small>
                    </div>
                    
                    <div class="mb-4">
                        <label for="confirm_password" class="form-label fw-semibold">
                            Confirm New Password <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-light">
                                <i class="bi bi-lock-fill"></i>
                            </span>
                            <input type="password" class="form-control" id="confirm_password" 
                                   name="confirm_password" required minlength="8"
                                   placeholder="Re-enter new password">
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-lg" style="background-color: #2d5016; color: white;">
                            <i class="bi bi-check-circle me-2"></i>Change Password
                        </button>
                        <?php if (!$mustChange): ?>
                        <a href="<?= BASE_URL ?>/index.php" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle me-2"></i>Cancel
                        </a>
                        <?php endif; ?>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if (!$success): ?>
        <div class="card border-0 shadow-sm mt-3">
            <div class="card-body">
                <h6 class="card-title">
                    <i class="bi bi-shield-check me-2" style="color: #2d5016;"></i>Password Security Tips
                </h6>
                <ul class="mb-0 small text-muted">
                    <li>Use at least 8 characters (longer is better)</li>
                    <li>Mix uppercase and lowercase letters</li>
                    <li>Include numbers and special characters (!@#$%^&*)</li>
                    <li>Avoid using personal information or common words</li>
                    <li>Don't reuse passwords from other accounts</li>
                    <li>Consider using a password manager</li>
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
        document.getElementById('confirm_password').focus();
        return false;
    }
    
    if (newPassword.length < 8) {
        e.preventDefault();
        alert('Password must be at least 8 characters long!');
        document.getElementById('new_password').focus();
        return false;
    }
});

// Show password strength indicator
document.getElementById('new_password')?.addEventListener('input', function(e) {
    const password = e.target.value;
    let strength = 0;
    
    if (password.length >= 8) strength++;
    if (password.length >= 12) strength++;
    if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
    if (/\d/.test(password)) strength++;
    if (/[^a-zA-Z\d]/.test(password)) strength++;
    
    // You can add visual feedback here if needed
});
</script>

<?php require_once 'includes/footer.php'; ?>
