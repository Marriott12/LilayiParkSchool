<?php
require_once 'includes/bootstrap.php';
require_once 'includes/Auth.php';

Auth::requireLogin();

$userID = $_GET['id'] ?? null;
$isEdit = !empty($userID);

require_once 'modules/users/UsersModel.php';
require_once 'modules/roles/RolesModel.php';
require_once 'modules/teachers/TeacherModel.php';
require_once 'modules/parents/ParentModel.php';

$usersModel = new UsersModel();
$rolesModel = new RolesModel();
$teacherModel = new TeacherModel();
$parentModel = new ParentModel();

if (!$rolesModel->userHasPermission(Auth::id(), 'manage_users')) {
    Session::setFlash('error', 'You do not have permission to manage users.');
    header('Location: ' . BASE_URL . '/403.php');
    exit;
}

// Get all available roles
$allRoles = $rolesModel->getAllRoles();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token first
    if (!CSRF::requireToken()) {
        $error = $GLOBALS['csrf_error'] ?? 'Security validation failed. Please try again.';
    } else {
        $accountType = $_POST['account_type'] ?? 'new';
        
        // Handle existing teacher account creation
        if ($accountType === 'teacher' && !empty($_POST['teacher_id'])) {
            try {
                $teacherID = $_POST['teacher_id'];
                $teacher = $teacherModel->find($teacherID);
                
                if (!$teacher) {
                    throw new Exception('Teacher not found');
                }
                
                if (!empty($teacher['userID'])) {
                    throw new Exception('This teacher already has a user account');
                }
                
                // Generate username
                $username = strtolower($teacher['fName'] . '.' . $teacher['lName']);
                $baseUsername = $username;
                $counter = 1;
                while ($usersModel->usernameExists($username)) {
                    $username = $baseUsername . $counter;
                    $counter++;
                }
                
                // Generate password
                $generatedPassword = Auth::generatePassword(12);
                
                // Generate email and ensure uniqueness
                $email = $teacher['email'] ?? $username . '@lilayipark.edu.zm';
                $baseEmail = $email;
                $emailCounter = 1;
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
                    'mustChangePassword' => 'Y',
                    'createdAt' => date('Y-m-d H:i:s')
                ];
                
                $newUserID = $usersModel->create($userData);
                
                if ($newUserID) {
                    // Assign teacher role
                    $teacherRole = $rolesModel->getRoleByName('teacher');
                    if ($teacherRole) {
                        $rolesModel->assignRole($newUserID, $teacherRole['roleID'], Auth::id());
                    }
                    
                    // Link user to teacher
                    $teacherModel->update($teacherID, ['userID' => $newUserID]);
                    
                    // Store credentials for display
                    $_SESSION['new_user_credentials'] = [
                        'name' => $teacher['fName'] . ' ' . $teacher['lName'],
                        'username' => $username,
                        'password' => $generatedPassword,
                        'type' => 'Teacher'
                    ];
                    
                    Session::setFlash('success', 'Teacher account created successfully!');
                    header('Location: users_form.php?show_credentials=1');
                    exit;
                }
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
        // Handle existing parent account creation
        elseif ($accountType === 'parent' && !empty($_POST['parent_id'])) {
            try {
                $parentID = $_POST['parent_id'];
                $parent = $parentModel->find($parentID);
                
                if (!$parent) {
                    throw new Exception('Parent not found');
                }
                
                if (!empty($parent['userID'])) {
                    throw new Exception('This parent already has a user account');
                }
                
                // Generate username
                $username = strtolower($parent['fName'] . '.' . $parent['lName']);
                $baseUsername = $username;
                $counter = 1;
                while ($usersModel->usernameExists($username)) {
                    $username = $baseUsername . $counter;
                    $counter++;
                }
                
                // Generate password
                $generatedPassword = Auth::generatePassword(12);
                
                // Generate email and ensure uniqueness
                $email = $parent['email1'] ?? $username . '@lilayipark.edu.zm';
                $baseEmail = $email;
                $emailCounter = 1;
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
                    'firstName' => $parent['fName'],
                    'lastName' => $parent['lName'],
                    'isActive' => 'Y',
                    'mustChangePassword' => 'Y',
                    'createdAt' => date('Y-m-d H:i:s')
                ];
                
                $newUserID = $usersModel->create($userData);
                
                if ($newUserID) {
                    // Assign parent role
                    $parentRole = $rolesModel->getRoleByName('parent');
                    if ($parentRole) {
                        $rolesModel->assignRole($newUserID, $parentRole['roleID'], Auth::id());
                    }
                    
                    // Link user to parent
                    $parentModel->update($parentID, ['userID' => $newUserID]);
                    
                    // Store credentials for display
                    $_SESSION['new_user_credentials'] = [
                        'name' => $parent['fName'] . ' ' . $parent['lName'],
                        'username' => $username,
                        'password' => $generatedPassword,
                        'type' => 'Parent'
                    ];
                    
                    Session::setFlash('success', 'Parent account created successfully!');
                    header('Location: users_form.php?show_credentials=1');
                    exit;
                }
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
        // Handle new user creation
        else {
            $data = [
                'username' => trim($_POST['username'] ?? ''),
                'email' => trim($_POST['email'] ?? ''),
                'firstName' => trim($_POST['firstName'] ?? ''),
                'lastName' => trim($_POST['lastName'] ?? ''),
                'isActive' => isset($_POST['isActive']) ? 'Y' : 'N'
            ];
            
            // Get selected roles (array of role IDs)
            $selectedRoles = $_POST['roles'] ?? [];
            
            // Validate at least one role is selected
            if (empty($selectedRoles)) {
                $error = 'Please select at least one role';
            }
            // Validate username uniqueness
            elseif ($usersModel->usernameExists($data['username'], $userID)) {
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
                try {
                    if ($isEdit) {
                        // Don't update password unless provided
                        if (empty($data['password'])) {
                            unset($data['password']);
                        } else {
                            $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);
                        }
                        $usersModel->update($userID, $data);
                        
                        // Update roles: Remove all existing roles, then add selected ones
                        $currentRoles = $rolesModel->getUserRoles($userID);
                        foreach ($currentRoles as $role) {
                            $rolesModel->removeRole($userID, $role['roleID']);
                        }
                        foreach ($selectedRoles as $roleID) {
                            $rolesModel->assignRole($userID, $roleID, Auth::id());
                        }
                        
                        Session::setFlash('success', 'User updated successfully');
                    } else {
                        // Hash password for new user
                        $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
                        $data['createdAt'] = date('Y-m-d H:i:s');
                        
                        // Create user
                        $newUserID = $usersModel->create($data);
                        
                        // Assign roles
                        foreach ($selectedRoles as $roleID) {
                            $rolesModel->assignRole($newUserID, $roleID, Auth::id());
                        }
                        
                        Session::setFlash('success', 'User created successfully with ' . count($selectedRoles) . ' role(s)');
                    }
                    
                    // Regenerate CSRF token after successful submission
                    CSRF::regenerateToken();
                    
                    header('Location: users_list.php');
                    exit;
                } catch (Exception $e) {
                    $error = $e->getMessage();
                }
            }
        }
    }
}

// Get user data if editing
$user = $isEdit ? $usersModel->getById($userID) : null;
$userRoles = $isEdit ? $rolesModel->getUserRoles($userID) : [];
$userRoleIDs = array_column($userRoles, 'roleID');

// Get teachers and parents without accounts
$allTeachers = $teacherModel->all();
$teachersWithoutAccounts = array_filter($allTeachers, function($teacher) {
    return empty($teacher['userID']);
});

$allParents = $parentModel->all();
$parentsWithoutAccounts = array_filter($allParents, function($parent) {
    return empty($parent['userID']);
});

// Check if showing credentials
$showCredentials = isset($_GET['show_credentials']) && $_GET['show_credentials'] == '1';
$credentials = $_SESSION['new_user_credentials'] ?? null;
unset($_SESSION['new_user_credentials']);

$pageTitle = $isEdit ? 'Edit User' : 'Add New User';
$currentPage = 'users';
require_once 'includes/header.php';
?>

<div class="mb-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="users_list.php">Users</a></li>
            <li class="breadcrumb-item active"><?= $pageTitle ?></li>
        </ol>
    </nav>
</div>

<?php if ($showCredentials && $credentials): ?>
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card border-success">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bi bi-check-circle me-2"></i>Account Created Successfully</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <strong>Important:</strong> Save these credentials now. They will not be shown again!
                </div>
                <table class="table table-bordered">
                    <tr>
                        <th width="30%">Account Type:</th>
                        <td><strong><?= htmlspecialchars($credentials['type']) ?></strong></td>
                    </tr>
                    <tr>
                        <th>Name:</th>
                        <td><?= htmlspecialchars($credentials['name']) ?></td>
                    </tr>
                    <tr>
                        <th>Username:</th>
                        <td><code class="fs-5"><?= htmlspecialchars($credentials['username']) ?></code></td>
                    </tr>
                    <tr>
                        <th>Password:</th>
                        <td>
                            <code class="fs-5 text-danger"><?= htmlspecialchars($credentials['password']) ?></code>
                            <button class="btn btn-sm btn-outline-secondary ms-2" onclick="copyPassword('<?= htmlspecialchars($credentials['password']) ?>')">
                                <i class="bi bi-clipboard"></i> Copy
                            </button>
                        </td>
                    </tr>
                </table>
                <div class="alert alert-info mt-3">
                    <i class="bi bi-info-circle me-2"></i>
                    The user will be required to change this password on their first login.
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary" onclick="window.print()">
                        <i class="bi bi-printer me-2"></i>Print Credentials
                    </button>
                    <a href="users_form.php" class="btn btn-success">
                        <i class="bi bi-plus-circle me-2"></i>Create Another Account
                    </a>
                    <a href="users_list.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left me-2"></i>Back to Users
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (!$isEdit && !$showCredentials): ?>
<!-- Account Type Selector -->
<div class="card mb-4 shadow-sm">
    <div class="card-header" style="background: linear-gradient(135deg, #2d5016 0%, #5cb85c 100%); color: white;">
        <h5 class="mb-0"><i class="bi bi-person-plus-fill me-2"></i>Choose Account Type</h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <!-- Option 1: Create New User -->
            <div class="col-md-4">
                <div class="card h-100 account-type-card" onclick="selectAccountType('new')" style="cursor: pointer; border: 2px solid #e0e0e0; transition: all 0.3s;">
                    <div class="card-body text-center">
                        <div class="mb-3" style="font-size: 3rem; color: #2d5016;">
                            <i class="bi bi-person-plus-fill"></i>
                        </div>
                        <h5 class="card-title">Create New User</h5>
                        <p class="card-text text-muted">Manually create a user account with custom details</p>
                        <div class="form-check mt-3">
                            <input class="form-check-input" type="radio" name="account_type" value="new" id="type_new" checked>
                            <label class="form-check-label fw-bold" for="type_new">Select</label>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Option 2: Existing Teacher -->
            <div class="col-md-4">
                <div class="card h-100 account-type-card" onclick="selectAccountType('teacher')" style="cursor: pointer; border: 2px solid #e0e0e0; transition: all 0.3s;">
                    <div class="card-body text-center">
                        <div class="mb-3" style="font-size: 3rem; color: #2d5016;">
                            <i class="bi bi-person-workspace"></i>
                        </div>
                        <h5 class="card-title">Existing Teacher</h5>
                        <p class="card-text text-muted">Create account for a teacher who doesn't have one</p>
                        <div class="badge bg-info mb-2"><?= count($teachersWithoutAccounts) ?> available</div>
                        <div class="form-check mt-3">
                            <input class="form-check-input" type="radio" name="account_type" value="teacher" id="type_teacher">
                            <label class="form-check-label fw-bold" for="type_teacher">Select</label>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Option 3: Existing Parent -->
            <div class="col-md-4">
                <div class="card h-100 account-type-card" onclick="selectAccountType('parent')" style="cursor: pointer; border: 2px solid #e0e0e0; transition: all 0.3s;">
                    <div class="card-body text-center">
                        <div class="mb-3" style="font-size: 3rem; color: #2d5016;">
                            <i class="bi bi-people-fill"></i>
                        </div>
                        <h5 class="card-title">Existing Parent</h5>
                        <p class="card-text text-muted">Create account for a parent who doesn't have one</p>
                        <div class="badge bg-info mb-2"><?= count($parentsWithoutAccounts) ?> available</div>
                        <div class="form-check mt-3">
                            <input class="form-check-input" type="radio" name="account_type" value="parent" id="type_parent">
                            <label class="form-check-label fw-bold" for="type_parent">Select</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-header" style="background: linear-gradient(135deg, #2d5016 0%, #5cb85c 100%); color: white;">
        <h5 class="mb-0">
            <i class="bi bi-person-plus-fill me-2"></i><?= $pageTitle ?>
        </h5>
    </div>
    <div class="card-body">
        <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle-fill"></i> <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <form method="POST" action="" id="userForm">
            <?= CSRF::field() ?>
            <input type="hidden" name="account_type" id="account_type_field" value="new">
            
            <!-- New User Form -->
            <div id="newUserForm" style="display: none;">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">First Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="firstName" 
                               value="<?= htmlspecialchars($user['firstName'] ?? '') ?>" required>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Last Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="lastName" 
                               value="<?= htmlspecialchars($user['lastName'] ?? '') ?>" required>
                    </div>
                </div>
                
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
                
                <div class="mb-3">
                    <label class="form-label d-block">Roles <span class="text-danger">*</span></label>
                    <div class="alert alert-info mb-2">
                        <i class="bi bi-info-circle"></i> Select one or more roles. Users can have multiple roles.
                    </div>
                    <div class="row">
                        <?php foreach ($allRoles as $role): ?>
                        <div class="col-md-4 mb-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="roles[]" 
                                       value="<?= $role['roleID'] ?>" id="role_<?= $role['roleID'] ?>"
                                       <?= in_array($role['roleID'], $userRoleIDs) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="role_<?= $role['roleID'] ?>">
                                    <strong><?= htmlspecialchars($role['roleName']) ?></strong>
                                    <?php if (!empty($role['description'])): ?>
                                    <br><small class="text-muted"><?= htmlspecialchars($role['description']) ?></small>
                                    <?php endif; ?>
                                </label>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Status</label>
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" name="isActive" id="isActive" 
                                   <?= ($user['isActive'] ?? 'Y') === 'Y' ? 'checked' : '' ?>>
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
            </div>
            
            <!-- Existing Teacher Form -->
            <div id="teacherForm" style="display: none;">
                <div class="mb-3">
                    <label class="form-label">Select Teacher <span class="text-danger">*</span></label>
                    <select class="form-select" name="teacher_id" id="teacher_id">
                        <option value="">-- Select a Teacher --</option>
                        <?php foreach ($teachersWithoutAccounts as $teacher): ?>
                        <option value="<?= $teacher['teacherID'] ?>">
                            <?= htmlspecialchars($teacher['fName'] . ' ' . $teacher['lName']) ?>
                            <?php if (!empty($teacher['email'])): ?>
                                (<?= htmlspecialchars($teacher['email']) ?>)
                            <?php endif; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (empty($teachersWithoutAccounts)): ?>
                    <div class="alert alert-info mt-2">
                        <i class="bi bi-info-circle me-2"></i>All teachers already have user accounts!
                    </div>
                    <?php else: ?>
                    <small class="text-muted">A username and random password will be generated automatically</small>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Existing Parent Form -->
            <div id="parentForm" style="display: none;">
                <div class="mb-3">
                    <label class="form-label">Select Parent <span class="text-danger">*</span></label>
                    <select class="form-select" name="parent_id" id="parent_id">
                        <option value="">-- Select a Parent --</option>
                        <?php foreach ($parentsWithoutAccounts as $parent): ?>
                        <option value="<?= $parent['parentID'] ?>">
                            <?= htmlspecialchars($parent['fName'] . ' ' . $parent['lName']) ?>
                            <?php if (!empty($parent['email1'])): ?>
                                (<?= htmlspecialchars($parent['email1']) ?>)
                            <?php endif; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (empty($parentsWithoutAccounts)): ?>
                    <div class="alert alert-info mt-2">
                        <i class="bi bi-info-circle me-2"></i>All parents already have user accounts!
                    </div>
                    <?php else: ?>
                    <small class="text-muted">A username and random password will be generated automatically</small>
                    <?php endif; ?>
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

<script>
function selectAccountType(type) {
    // Update radio buttons
    document.querySelectorAll('input[name="account_type"]').forEach(radio => {
        radio.checked = radio.value === type;
    });
    
    // Update hidden field
    document.getElementById('account_type_field').value = type;
    
    // Update card borders
    document.querySelectorAll('.account-type-card').forEach(card => {
        card.style.border = '2px solid #e0e0e0';
        card.style.backgroundColor = 'white';
    });
    event.currentTarget.style.border = '2px solid #2d5016';
    event.currentTarget.style.backgroundColor = '#f0f8f0';
    
    // Show/hide forms
    document.getElementById('newUserForm').style.display = type === 'new' ? 'block' : 'none';
    document.getElementById('teacherForm').style.display = type === 'teacher' ? 'block' : 'none';
    document.getElementById('parentForm').style.display = type === 'parent' ? 'block' : 'none';
    
    // Update form requirements
    const newUserInputs = document.querySelectorAll('#newUserForm input[required]');
    const teacherSelect = document.getElementById('teacher_id');
    const parentSelect = document.getElementById('parent_id');
    
    if (type === 'new') {
        newUserInputs.forEach(input => input.required = true);
        teacherSelect.required = false;
        parentSelect.required = false;
    } else if (type === 'teacher') {
        newUserInputs.forEach(input => input.required = false);
        teacherSelect.required = true;
        parentSelect.required = false;
    } else if (type === 'parent') {
        newUserInputs.forEach(input => input.required = false);
        teacherSelect.required = false;
        parentSelect.required = true;
    }
}

function copyPassword(password) {
    navigator.clipboard.writeText(password).then(() => {
        alert('Password copied to clipboard!');
    });
}

// Set initial state based on edit mode
<?php if ($isEdit): ?>
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('newUserForm').style.display = 'block';
    document.getElementById('teacherForm').style.display = 'none';
    document.getElementById('parentForm').style.display = 'none';
});
<?php else: ?>
document.addEventListener('DOMContentLoaded', function() {
    selectAccountType('new');
});
<?php endif; ?>

// Form validation before submit
document.getElementById('userForm').addEventListener('submit', function(e) {
    const accountType = document.getElementById('account_type_field').value;
    
    if (accountType === 'teacher') {
        const teacherSelect = document.getElementById('teacher_id');
        if (!teacherSelect.value) {
            e.preventDefault();
            alert('Please select a teacher');
            return false;
        }
    } else if (accountType === 'parent') {
        const parentSelect = document.getElementById('parent_id');
        if (!parentSelect.value) {
            e.preventDefault();
            alert('Please select a parent');
            return false;
        }
    }
});
</script>

<style>
.account-type-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}
</style>

<?php require_once 'includes/footer.php'; ?>
