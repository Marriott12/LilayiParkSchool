<?php
require_once 'includes/bootstrap.php';
require_once 'includes/Auth.php';
require_once 'includes/PermissionHelper.php';

Auth::requireLogin();
Auth::requireAnyRole(['admin']);

require_once 'modules/teachers/TeacherModel.php';
require_once 'modules/users/UsersModel.php';
require_once 'modules/roles/RolesModel.php';

$teacherModel = new TeacherModel();
$usersModel = new UsersModel();
$rolesModel = new RolesModel();

// Handle bulk account creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_accounts'])) {
    $selectedTeachers = $_POST['teachers'] ?? [];
    $createdCount = 0;
    $errors = [];
    $credentials = [];
    
    foreach ($selectedTeachers as $teacherID) {
        try {
            $teacher = $teacherModel->find($teacherID);
            
            if (!$teacher || !empty($teacher['userID'])) {
                continue; // Skip if teacher not found or already has account
            }
            
            // Generate username from teacher name
            $username = strtolower($teacher['fName'] . '.' . $teacher['lName']);
            
            // Check if username exists, append number if needed
            $baseUsername = $username;
            $counter = 1;
            while ($usersModel->usernameExists($username)) {
                $username = $baseUsername . $counter;
                $counter++;
            }
            
            // Generate random password
            $generatedPassword = Auth::generatePassword(12);
            
            // Generate email and ensure uniqueness
            $email = $teacher['email'] ?? $username . '@lilayipark.edu.zm';
            $baseEmail = $email;
            $emailCounter = 1;
            
            // Ensure email is unique (always check, even if teacher has email)
            while ($usersModel->emailExists($email)) {
                $emailParts = explode('@', $baseEmail);
                $email = $emailParts[0] . $emailCounter . '@' . $emailParts[1];
                $emailCounter++;
            }
            
            // Create user account
            $userData = [
                'username' => $username,
                'password' => password_hash($generatedPassword, PASSWORD_BCRYPT),
                'email' => $email,
                'firstName' => $teacher['fName'],
                'lastName' => $teacher['lName'],
                'isActive' => 'Y',
                'createdAt' => date('Y-m-d H:i:s')
            ];
            
            $userID = $usersModel->create($userData);
            
            if ($userID) {
                // Assign teacher role
                $teacherRole = $rolesModel->getRoleByName('teacher');
                if ($teacherRole) {
                    $rolesModel->assignRole($userID, $teacherRole['roleID'], Auth::id());
                }
                
                // Link user to teacher
                $teacherModel->update($teacherID, ['userID' => $userID]);
                
                $createdCount++;
                $credentials[] = [
                    'name' => $teacher['fName'] . ' ' . $teacher['lName'],
                    'username' => $username,
                    'password' => $generatedPassword
                ];
            }
        } catch (Exception $e) {
            $errors[] = "Error creating account for " . ($teacher['fName'] ?? 'teacher') . ": " . $e->getMessage();
        }
    }
    
    if ($createdCount > 0) {
        Session::setFlash('success', "Successfully created $createdCount user account(s)!");
        $_SESSION['new_credentials'] = $credentials;
    }
    
    if (!empty($errors)) {
        Session::setFlash('error', implode('<br>', $errors));
    }
    
    header('Location: teachers_bulk_accounts.php');
    exit;
}

// Get teachers without user accounts
$allTeachers = $teacherModel->all();
$teachersWithoutAccounts = array_filter($allTeachers, function($teacher) {
    return empty($teacher['userID']);
});

$pageTitle = 'Bulk Account Creation';
$currentPage = 'teachers';
require_once 'includes/header.php';

// Display credentials if accounts were just created
$newCredentials = $_SESSION['new_credentials'] ?? [];
unset($_SESSION['new_credentials']);
?>

<div class="row mb-4">
    <div class="col-md-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="teachers_list.php">Teachers</a></li>
                <li class="breadcrumb-item active">Bulk Account Creation</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-12">
        <h2 class="mb-0">
            <i class="bi bi-people-fill me-2" style="color: #2d5016;"></i>
            <span style="color: #2d5016; font-weight: 600;">Bulk Teacher Account Creation</span>
        </h2>
        <p class="text-muted mt-1">Create user accounts for teachers who don't have login credentials</p>
    </div>
</div>

<?php if (!empty($newCredentials)): ?>
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card border-success">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bi bi-check-circle me-2"></i>Account Credentials Created</h5>
            </div>
            <div class="card-body">
                <p class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <strong>Important:</strong> Save these credentials now. They will not be shown again!
                </p>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Teacher Name</th>
                                <th>Username</th>
                                <th>Password</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($newCredentials as $cred): ?>
                            <tr>
                                <td><?= htmlspecialchars($cred['name']) ?></td>
                                <td><code><?= htmlspecialchars($cred['username']) ?></code></td>
                                <td><code><?= htmlspecialchars($cred['password']) ?></code></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <button class="btn btn-primary" onclick="window.print()">
                    <i class="bi bi-printer me-2"></i>Print Credentials
                </button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="row">
    <div class="col-md-12">
        <div class="card shadow-sm">
            <div class="card-header" style="background: linear-gradient(135deg, #2d5016 0%, #5cb85c 100%); color: white;">
                <h5 class="mb-0"><i class="bi bi-person-plus me-2"></i>Teachers Without Accounts (<?= count($teachersWithoutAccounts) ?>)</h5>
            </div>
            <div class="card-body">
                <?php if (empty($teachersWithoutAccounts)): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        All teachers have user accounts. No action needed!
                    </div>
                    <a href="teachers_list.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left me-2"></i>Back to Teachers List
                    </a>
                <?php else: ?>
                    <form method="POST" id="bulkAccountForm">
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="selectAll">
                                <label class="form-check-label" for="selectAll">
                                    <strong>Select All Teachers</strong>
                                </label>
                            </div>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th width="50">
                                            <input type="checkbox" class="form-check-input" id="selectAllTable">
                                        </th>
                                        <th>Teacher Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>TCZ No.</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($teachersWithoutAccounts as $teacher): ?>
                                    <tr>
                                        <td>
                                            <input type="checkbox" class="form-check-input teacher-checkbox" 
                                                   name="teachers[]" value="<?= $teacher['teacherID'] ?>">
                                        </td>
                                        <td>
                                            <strong><?= htmlspecialchars($teacher['fName'] . ' ' . $teacher['lName']) ?></strong>
                                        </td>
                                        <td><?= htmlspecialchars($teacher['email'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($teacher['phone'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($teacher['tczNo'] ?? 'Not Set') ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <a href="teachers_list.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left me-2"></i>Back to Teachers
                            </a>
                            <button type="submit" name="create_accounts" class="btn btn-success" 
                                    onclick="return confirm('Create user accounts for selected teachers? Random passwords will be generated.');">
                                <i class="bi bi-plus-circle me-2"></i>Create Selected Accounts
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Select all functionality
document.getElementById('selectAll')?.addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.teacher-checkbox');
    checkboxes.forEach(cb => cb.checked = this.checked);
});

document.getElementById('selectAllTable')?.addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.teacher-checkbox');
    checkboxes.forEach(cb => cb.checked = this.checked);
    document.getElementById('selectAll').checked = this.checked;
});
</script>

<?php require_once 'includes/footer.php'; ?>
