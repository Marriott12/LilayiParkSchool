<?php
require_once 'includes/bootstrap.php';
require_once 'includes/Auth.php';
require_once 'includes/PermissionHelper.php';

Auth::requireLogin();
Auth::requireAnyRole(['admin']);

require_once 'modules/teachers/TeacherModel.php';
require_once 'modules/users/UsersModel.php';

$teacherModel = new TeacherModel();
$usersModel = new UsersModel();

// Get teacher ID from URL
$teacherID = $_GET['id'] ?? null;
$showCredentials = isset($_GET['show']) && $_GET['show'] == '1';

if (!$teacherID) {
    Session::setFlash('error', 'Teacher ID is required.');
    header('Location: teachers_list.php');
    exit;
}

$teacher = $teacherModel->find($teacherID);

if (!$teacher) {
    Session::setFlash('error', 'Teacher not found.');
    header('Location: teachers_list.php');
    exit;
}

if (empty($teacher['userID'])) {
    Session::setFlash('error', 'This teacher does not have a user account yet.');
    header('Location: teachers_list.php');
    exit;
}

// Handle password reset
if (!$showCredentials) {
    // Generate new password
    $newPassword = Auth::generatePassword(12);
    $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);

    try {
        // Update user password
        $usersModel->update($teacher['userID'], [
            'password' => $hashedPassword,
            'mustChangePassword' => 'Y', // Flag for first-login password change
            'updatedAt' => date('Y-m-d H:i:s')
        ]);
        
        // Store the new password in session to display once
        $_SESSION['reset_credentials'] = [
            'teacher_name' => $teacher['fName'] . ' ' . $teacher['lName'],
            'username' => $usersModel->find($teacher['userID'])['username'],
            'password' => $newPassword
        ];
        
        Session::setFlash('success', 'Password has been reset successfully!');
        header('Location: teachers_reset_password.php?id=' . $teacherID . '&show=1');
        exit;
        
    } catch (Exception $e) {
        Session::setFlash('error', 'Failed to reset password: ' . $e->getMessage());
        header('Location: teachers_list.php');
        exit;
    }
}

// Display credentials
$credentials = $_SESSION['reset_credentials'] ?? null;
unset($_SESSION['reset_credentials']);

if (!$credentials) {
    Session::setFlash('error', 'Credentials not available.');
    header('Location: teachers_list.php');
    exit;
}

$pageTitle = 'Password Reset';
$currentPage = 'teachers';
require_once 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="teachers_list.php">Teachers</a></li>
                <li class="breadcrumb-item active">Password Reset</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card border-success shadow">
            <div class="card-header bg-success text-white">
                <h4 class="mb-0">
                    <i class="bi bi-key me-2"></i>Password Reset Successful
                </h4>
            </div>
            <div class="card-body">
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <strong>Important:</strong> Save these credentials now. They will not be shown again!
                </div>
                
                <table class="table table-bordered">
                    <tr>
                        <th width="30%">Teacher Name:</th>
                        <td><strong><?= htmlspecialchars($credentials['teacher_name']) ?></strong></td>
                    </tr>
                    <tr>
                        <th>Username:</th>
                        <td><code class="fs-5"><?= htmlspecialchars($credentials['username']) ?></code></td>
                    </tr>
                    <tr>
                        <th>New Password:</th>
                        <td>
                            <code class="fs-5 text-danger"><?= htmlspecialchars($credentials['password']) ?></code>
                            <button class="btn btn-sm btn-outline-secondary ms-2" onclick="copyPassword()">
                                <i class="bi bi-clipboard"></i> Copy
                            </button>
                        </td>
                    </tr>
                </table>
                
                <div class="alert alert-info mt-3">
                    <i class="bi bi-info-circle me-2"></i>
                    The teacher will be required to change this password on their first login.
                </div>
                
                <div class="d-flex gap-2 mt-4">
                    <button class="btn btn-primary" onclick="window.print()">
                        <i class="bi bi-printer me-2"></i>Print Credentials
                    </button>
                    <a href="teachers_list.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left me-2"></i>Back to Teachers
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function copyPassword() {
    const password = '<?= $credentials['password'] ?>';
    navigator.clipboard.writeText(password).then(() => {
        alert('Password copied to clipboard!');
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>

