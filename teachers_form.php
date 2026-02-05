<?php
require_once 'includes/bootstrap.php';
require_once 'includes/Auth.php';

// Require login
Auth::requireLogin();

// Check permission via RBAC
require_once 'modules/roles/RolesModel.php';
$rolesModel = new RolesModel();
if (!$rolesModel->userHasPermission(Auth::id(), 'manage_teachers')) {
    Session::setFlash('error', 'You do not have permission to manage teachers.');
    header('Location: /LilayiParkSchool/403.php');
    exit;
}

$teacherID = $_GET['id'] ?? null;
$isEdit = !empty($teacherID);

require_once 'modules/teachers/TeacherModel.php';
require_once 'modules/users/UsersModel.php';

$teacherModel = new TeacherModel();
$usersModel = new UsersModel();
$rolesModel = new RolesModel();

// Get all teachers for dropdown
$allTeachers = $teacherModel->getAll();

// Get all roles for multi-role assignment
$allRoles = $rolesModel->getAllRoles();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token first
    if (!CSRF::requireToken()) {
        $error = $GLOBALS['csrf_error'] ?? 'Security validation failed. Please try again.';
    } else {
        // Check if using existing teacher
        $teacherMode = $_POST['teacher_mode'] ?? 'new';
        $existingTeacherID = $_POST['existing_teacher_id'] ?? null;
        
        // User account creation data
        $createUserAccount = isset($_POST['create_user_account']);
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $selectedRoles = $_POST['roles'] ?? []; // Multi-role support
        
        if ($teacherMode === 'existing' && !$isEdit) {
            // Mode: Create user account for existing teacher
            if (empty($existingTeacherID)) {
                $error = 'Please select a teacher';
            } elseif (empty($username)) {
                $error = 'Username is required';
            } elseif (empty($password)) {
                $error = 'Password is required';
            } elseif (empty($selectedRoles)) {
                $error = 'Please select at least one role';
            } elseif ($usersModel->usernameExists($username)) {
                $error = 'Username already exists. Please choose a different username.';
            } else {
                try {
                    global $db;
                    $db->beginTransaction();
                    
                    // Get teacher data
                    $teacher = $teacherModel->getById($existingTeacherID);
                    if (!$teacher) {
                        throw new Exception('Teacher not found');
                    }
                    
                    // Check if teacher already has a user account
                    if (!empty($teacher['userID'])) {
                        throw new Exception('This teacher already has a user account');
                    }
                    
                    // Create user account
                    $userData = [
                        'username' => $username,
                        'email' => $teacher['email'],
                        'password' => $password,
                        'isActive' => 'Y'
                    ];
                    
                    $userID = $usersModel->createWithRoles($userData, $selectedRoles);
                    
                    if (!$userID) {
                        throw new Exception('Failed to create user account');
                    }
                    
                    // Link user to teacher
                    $usersModel->linkToTeacher($userID, $existingTeacherID);
                    
                    $db->commit();
                    Session::setFlash('success', 'User account created for ' . $teacher['fName'] . ' ' . $teacher['lName'] . ' with ' . count($selectedRoles) . ' role(s)');
                    
                    // Regenerate CSRF token
                    CSRF::regenerateToken();
                    
                    header('Location: teachers_list.php');
                    exit;
                } catch (Exception $e) {
                    if (isset($db) && $db->inTransaction()) {
                        $db->rollBack();
                    }
                    $error = $e->getMessage();
                }
            }
        } else {
            // Mode: Create new teacher (existing logic)
        $data = [
            'fName' => trim($_POST['fName'] ?? ''),
            'lName' => trim($_POST['lName'] ?? ''),
            'NRC' => trim($_POST['NRC'] ?? ''),
            'SSN' => trim($_POST['SSN'] ?? ''),
            'Tpin' => trim($_POST['Tpin'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'gender' => $_POST['gender'] ?? '',
            'tczNo' => trim($_POST['tczNo'] ?? ''),
            'dateEmployed' => $_POST['dateEmployed'] ?? date('Y-m-d')
        ];
        
        // User account creation data
        $createUserAccount = isset($_POST['create_user_account']);
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $selectedRoles = $_POST['roles'] ?? []; // Multi-role support
        
        // Validation
        if (empty($data['fName'])) {
            $error = 'First name is required';
        } elseif (empty($data['lName'])) {
            $error = 'Last name is required';
        } 
        // NRC validation: Must be format XXXXXX/XX/X (6 digits/2 digits/1 digit)
        elseif (empty($data['NRC'])) {
            $error = 'NRC is required';
        } elseif (!preg_match('/^\d{6}\/\d{2}\/\d{1}$/', $data['NRC'])) {
            $error = 'NRC must be in format XXXXXX/XX/X (e.g., 123456/78/1)';
        } elseif ($teacherModel->nrcExists($data['NRC'], $isEdit ? $teacherID : null)) {
            $error = 'A teacher with this NRC already exists';
        }
        // SSN validation: Must be exactly 9 digits
        elseif (empty($data['SSN'])) {
            $error = 'Social Security Number is required';
        } elseif (!preg_match('/^\d{9}$/', $data['SSN'])) {
            $error = 'SSN must be exactly 9 digits with no special characters';
        } elseif ($teacherModel->ssnExists($data['SSN'], $isEdit ? $teacherID : null)) {
            $error = 'A teacher with this SSN already exists';
        }
        // TPIN validation: Must be exactly 10 digits
        elseif (empty($data['Tpin'])) {
            $error = 'TPIN is required';
        } elseif (!preg_match('/^\d{10}$/', $data['Tpin'])) {
            $error = 'TPIN must be exactly 10 digits with no special characters';
        } elseif ($teacherModel->tpinExists($data['Tpin'], $isEdit ? $teacherID : null)) {
            $error = 'A teacher with this TPIN already exists';
        }
        // Phone validation: 10-13 characters, + only at start, if starts with + must be +260
        elseif (empty($data['phone'])) {
            $error = 'Phone number is required';
        } elseif (strlen($data['phone']) < 10) {
            $error = 'Phone number must be at least 10 digits';
        } elseif (strlen($data['phone']) > 13) {
            $error = 'Phone number cannot exceed 13 characters';
        } elseif (!preg_match('/^\+?\d+$/', $data['phone'])) {
            $error = 'Phone number can only contain digits and + at the beginning';
        } elseif (strpos($data['phone'], '+') === 0 && !preg_match('/^\+260\d{9}$/', $data['phone'])) {
            $error = 'Phone number starting with + must be in format +260XXXXXXXXX';
        }
        // Email validation
        elseif (empty($data['email'])) {
            $error = 'Email is required';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email format';
        } elseif ($teacherModel->emailExists($data['email'], $isEdit ? $teacherID : null)) {
            $error = 'A teacher with this email already exists';
        }
        // Gender validation
        elseif (empty($data['gender'])) {
            $error = 'Gender is required';
        }
        
        if (!isset($error)) {
            try {
                global $db;
                $db->beginTransaction();
                
                if ($isEdit) {
                    // Update existing teacher
                    $success = $teacherModel->update($teacherID, $data);
                    $message = 'Teacher updated successfully';
                    $finalTeacherID = $teacherID;
                    
                    // Update roles if teacher has existing user account
                    if ($teacher && !empty($teacher['userID']) && !empty($selectedRoles)) {
                        // Remove all existing roles
                        $currentRoles = $rolesModel->getUserRoles($teacher['userID']);
                        foreach ($currentRoles as $role) {
                            $rolesModel->removeRole($teacher['userID'], $role['roleID']);
                        }
                        // Add selected roles
                        foreach ($selectedRoles as $roleID) {
                            $rolesModel->assignRole($teacher['userID'], $roleID, Auth::id());
                        }
                        $message .= ' and roles updated';
                    }
                } else {
                    // Create new teacher
                    $finalTeacherID = $teacherModel->create($data);
                    $success = $finalTeacherID !== false;
                    $message = 'Teacher created successfully';
                }
                
                if (!$success) {
                    throw new Exception('Failed to save teacher data');
                }
                
                // Create user account if requested
                if ($createUserAccount && !$isEdit) {
                    // Additional validation for user account
                    if (empty($username)) {
                        throw new Exception('Username is required when creating a user account');
                    }
                    if (empty($password)) {
                        throw new Exception('Password is required when creating a user account');
                    }
                    if ($usersModel->usernameExists($username)) {
                        throw new Exception('Username already exists. Please choose a different username.');
                    }
                    
                    // Validate at least one role is selected
                    if (empty($selectedRoles)) {
                        throw new Exception('Please select at least one role for the user account');
                    }
                    
                    // Create user account
                    $userData = [
                        'username' => $username,
                        'email' => $data['email'],
                        'password' => $password,
                        'isActive' => 'Y'
                    ];
                    
                    $userID = $usersModel->createWithRoles($userData, $selectedRoles);
                    
                    if (!$userID) {
                        throw new Exception('Failed to create user account');
                    }
                    
                    // Link user to teacher
                    $usersModel->linkToTeacher($userID, $finalTeacherID);
                    
                    $message .= ' and user account created with ' . count($selectedRoles) . ' role(s)';
                }
                
                $db->commit();
                Session::setFlash('success', $message);
                
                // Regenerate CSRF token after successful submission
                CSRF::regenerateToken();
                
                header('Location: teachers_list.php');
                exit;
            } catch (PDOException $e) {
                if (isset($db) && $db->inTransaction()) {
                    $db->rollBack();
                }
                $error = 'Database error: ' . $e->getMessage();
            } catch (Exception $e) {
                if (isset($db) && $db->inTransaction()) {
                    $db->rollBack();
                }
                $error = $e->getMessage();
            }
        }
        } // End of new teacher mode
    }
}

$teacher = $isEdit ? $teacherModel->getById($teacherID) : null;

// Get existing user account and roles if editing
$existingUser = null;
$existingUserRoles = [];
if ($isEdit && $teacher && !empty($teacher['userID'])) {
    $existingUser = $usersModel->getById($teacher['userID']);
    $existingUserRoles = $rolesModel->getUserRoles($teacher['userID']);
}
$existingRoleIDs = array_column($existingUserRoles, 'roleID');

$pageTitle = $isEdit ? 'Edit Teacher' : 'Add New Teacher';
$currentPage = 'teachers';
require_once 'includes/header.php';
?>

<!-- Page Header -->
<div class="row mb-4">
    <div class="col-md-8">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="teachers_list.php" class="text-decoration-none">Teachers</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?= $isEdit ? 'Edit' : 'Add New' ?></li>
            </ol>
        </nav>
        <h2 class="mb-0">
            <i class="bi bi-person-badge me-2" style="color: #2d5016;"></i>
            <span style="color: #2d5016; font-weight: 600;"><?= $pageTitle ?></span>
        </h2>
    </div>
    <div class="col-md-4 text-end align-self-end">
        <a href="teachers_list.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Teachers
        </a>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom">
        <h5 class="mb-0">
            <i class="bi bi-person-badge-fill me-2" style="color: #2d5016;"></i><?= $pageTitle ?>
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
            
            <?php if (!$isEdit): ?>
            <!-- Teacher Mode Selection - Always Visible -->
            <!--<div class="card mb-4 border-primary" style="border-width: 2px;">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0">
                        <i class="bi bi-person-plus-fill me-2"></i>Choose an Option
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-check p-3 border rounded" style="cursor: pointer;" onclick="document.getElementById('mode_new').click();">
                                <input class="form-check-input" type="radio" name="teacher_mode" id="mode_new" 
                                       value="new" checked onchange="toggleTeacherMode()">
                                <label class="form-check-label w-100" for="mode_new" style="cursor: pointer;">
                                    <div class="d-flex align-items-start">
                                        <i class="bi bi-person-plus-fill text-success me-2" style="font-size: 1.5rem;"></i>
                                        <div>
                                            <strong class="d-block">Create a New Teacher</strong>
                                            <small class="text-muted">Add a brand new teacher record to the system</small>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check p-3 border rounded" style="cursor: pointer;" onclick="document.getElementById('mode_existing').click();">
                                <input class="form-check-input" type="radio" name="teacher_mode" id="mode_existing" 
                                       value="existing" onchange="toggleTeacherMode()">
                                <label class="form-check-label w-100" for="mode_existing" style="cursor: pointer;">
                                    <div class="d-flex align-items-start">
                                        <i class="bi bi-person-check-fill text-info me-2" style="font-size: 1.5rem;"></i>
                                        <div>
                                            <strong class="d-block">Select Existing Teacher</strong>
                                            <small class="text-muted">Create a user account for an existing teacher</small>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div id="existing_teacher_section" style="display: none;" class="mb-4">
                <div class="card border-info">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0">
                            <i class="bi bi-person-lines-fill me-2"></i>Select Teacher
                        </h6>
                    </div>
                    <div class="card-body">
                        <label class="form-label fw-semibold">Choose Teacher <span class="text-danger">*</span></label>
                        <select class="form-select form-select-lg" name="existing_teacher_id" id="existing_teacher_select">
                            <option value="">-- Select a Teacher --</option>
                            <?php foreach ($allTeachers as $t): ?>
                            <option value="<?= $t['teacherID'] ?>" 
                                    data-fname="<?= htmlspecialchars($t['fName']) ?>"
                                    data-lname="<?= htmlspecialchars($t['lName']) ?>"
                                    data-email="<?= htmlspecialchars($t['email']) ?>"
                                    <?= !empty($t['userID']) ? 'disabled' : '' ?>>
                                <?= htmlspecialchars($t['fName'] . ' ' . $t['lName']) ?> 
                                - <?= htmlspecialchars($t['email']) ?>
                                <?= !empty($t['userID']) ? ' ✓ (Already has account)' : '' ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="alert alert-info mt-3 mb-0">
                            <i class="bi bi-info-circle me-1"></i>
                            <strong>Note:</strong> Teachers who already have user accounts are disabled in the list.
                        </div>
                    </div>
                </div>
            </div>-->
            <?php endif; ?>
            
            <div id="teacher_info_section">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Forename <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="fName" 
                           value="<?= htmlspecialchars($teacher['fName'] ?? '') ?>" required>
                </div>
                
                <div class="col-md-4 mb-3">
                    <label class="form-label">Last Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="lName" 
                           value="<?= htmlspecialchars($teacher['lName'] ?? '') ?>" required>
                </div>
            
                <div class="col-md-4 mb-3">
                    <label class="form-label">NRC <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="NRC" 
                           value="<?= htmlspecialchars($teacher['NRC'] ?? '') ?>" 
                           pattern="\d{6}/\d{2}/\d{1}"
                           placeholder="123456/78/1" 
                           title="Format: 6 digits/2 digits/1 digit (e.g., 123456/78/1)"
                           required>
                    <small class="text-muted">Format: XXXXXX/XX/X</small>
                </div>
                
                <div class="col-md-4 mb-3">
                    <label class="form-label">Social Security Number (SSN) <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="SSN" 
                           value="<?= htmlspecialchars($teacher['SSN'] ?? '') ?>" 
                           pattern="\d{9}"
                           placeholder="123456789"
                           title="Must be exactly 9 digits"
                           maxlength="9"
                           required>
                    <small class="text-muted">9 digits only</small>
                </div>
            
                <div class="col-md-4 mb-3">
                    <label class="form-label">TPIN <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="Tpin" 
                           value="<?= htmlspecialchars($teacher['Tpin'] ?? '') ?>" 
                           pattern="\d{10}"
                           placeholder="1234567890"
                           title="Must be exactly 10 digits"
                           maxlength="10"
                           required>
                    <small class="text-muted">10 digits only</small>
                </div>
                
                <div class="col-md-4 mb-3">
                    <label class="form-label">Phone <span class="text-danger">*</span></label>
                    <input type="tel" class="form-control" name="phone" 
                           value="<?= htmlspecialchars($teacher['phone'] ?? '') ?>" 
                           pattern="(\+260\d{9}|\d{10,13})"
                           placeholder="0977123456 or +260977123456"
                           title="10-13 digits, or +260 followed by 9 digits"
                           minlength="10"
                           maxlength="13"
                           required>
                    <small class="text-muted">10-13 digits, or +260XXXXXXXXX</small>
                </div>
            
                <div class="col-md-4 mb-3">
                    <label class="form-label">Email <span class="text-danger">*</span></label>
                    <input type="email" class="form-control" name="email" 
                           value="<?= htmlspecialchars($teacher['email'] ?? '') ?>" required>
                </div>
                
                <div class="col-md-4 mb-3">
                    <label class="form-label">Gender <span class="text-danger">*</span></label>
                    <div class="btn-group w-100" role="group">
                        <input type="radio" class="btn-check" name="gender" id="gender_m" value="M" 
                               <?= ($teacher['gender'] ?? '') === 'M' ? 'checked' : '' ?> required>
                        <label class="btn btn-outline-primary" for="gender_m">
                            <i class="bi bi-gender-male"></i> Male
                        </label>
                        
                        <input type="radio" class="btn-check" name="gender" id="gender_f" value="F" 
                               <?= ($teacher['gender'] ?? '') === 'F' ? 'checked' : '' ?> required>
                        <label class="btn btn-outline-primary" for="gender_f">
                            <i class="bi bi-gender-female"></i> Female
                        </label>
                    </div>
                </div>
                
                <div class="col-md-4 mb-3">
                    <label class="form-label">Date Employed <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" name="dateEmployed" 
                           value="<?= htmlspecialchars($teacher['dateEmployed'] ?? date('Y-m-d')) ?>" 
                           max="<?= date('Y-m-d') ?>"
                           required>
                    <small class="text-muted">Date when teacher started employment</small>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">TCZ Number</label>
                <input type="text" class="form-control" name="tczNo" 
                       value="<?= htmlspecialchars($teacher['tczNo'] ?? '') ?>" 
                       placeholder="Teachers Council of Zambia Number">
            </div>
            </div><!-- End teacher_info_section -->
            
            <?php if (!$isEdit): ?>
            <!-- User Account Creation Section -->
            <hr class="my-4">
            <div class="bg-light p-3 rounded">
                <h6 class="mb-3">
                    <i class="bi bi-person-check-fill"></i> User Account Creation
                </h6>
                
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="create_user_account" 
                           name="create_user_account" checked onchange="toggleUserAccountFields()">
                    <label class="form-check-label" for="create_user_account">
                        <strong>Create user account for this teacher</strong>
                        <br>
                        <small class="text-muted">This allows the teacher to login to the system</small>
                    </label>
                </div>
                
                <div id="user_account_fields">
                    <div class="alert alert-warning mb-3">
                        <i class="bi bi-shield-lock-fill"></i> <strong>Role Assignment</strong>
                        <p class="mb-0 small">Select one or more roles. The teacher role is recommended but you can assign multiple roles.</p>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label d-block">Assign Roles <span class="text-danger">*</span></label>
                        <div class="row">
                            <?php foreach ($allRoles as $role): ?>
                            <div class="col-md-6 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="roles[]" 
                                           value="<?= $role['roleID'] ?>" id="role_<?= $role['roleID'] ?>"
                                           <?= $role['roleName'] === 'teacher' ? 'checked' : '' ?>>
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
                            <label class="form-label">Username <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" class="form-control" name="username" id="username"
                                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" 
                                       placeholder="e.g., jdoe">
                                <button class="btn btn-outline-secondary" type="button" 
                                        onclick="generateUsername()" title="Generate username">
                                    <i class="bi bi-arrow-repeat"></i>
                                </button>
                            </div>
                            <small class="text-muted">Will be used for login</small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" class="form-control" name="password" id="password"
                                       value="<?= htmlspecialchars($_POST['password'] ?? '') ?>" 
                                       placeholder="Temporary password">
                                <button class="btn btn-outline-secondary" type="button" 
                                        onclick="generatePassword()" title="Generate password">
                                    <i class="bi bi-key-fill"></i>
                                </button>
                            </div>
                            <small class="text-muted">User should change this on first login</small>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($isEdit && $existingUser): ?>
            <!-- Role Management for Existing User Account -->
            <hr class="my-4">
            <div class="bg-light p-3 rounded">
                <h6 class="mb-3">
                    <i class="bi bi-shield-lock-fill"></i> User Account & Role Management
                </h6>
                
                <div class="alert alert-info mb-3">
                    <i class="bi bi-info-circle"></i> This teacher has a user account: <strong><?= htmlspecialchars($existingUser['username']) ?></strong>
                    <br><small>User Status: <?= $existingUser['isActive'] === 'Y' ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>' ?></small>
                </div>
                
                <div class="mb-3">
                    <label class="form-label d-block">Assigned Roles <span class="text-danger">*</span></label>
                    <div class="alert alert-warning mb-2">
                        <i class="bi bi-shield-lock-fill"></i> <strong>Update Role Assignment</strong>
                        <p class="mb-0 small">Select one or more roles for this teacher.</p>
                    </div>
                    <div class="row">
                        <?php foreach ($allRoles as $role): ?>
                        <div class="col-md-6 mb-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="roles[]" 
                                       value="<?= $role['roleID'] ?>" id="edit_role_<?= $role['roleID'] ?>"
                                       <?= in_array($role['roleID'], $existingRoleIDs) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="edit_role_<?= $role['roleID'] ?>">
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
            </div>
            <?php endif; ?>
            
            <hr class="my-4">
            
            <div class="d-flex gap-2">
                <button type="submit" class="btn" style="background-color: #2d5016; color: white;">
                    <i class="bi bi-save"></i> <?= $isEdit ? 'Update' : 'Create' ?> Teacher
                </button>
                <a href="teachers_list.php" class="btn btn-secondary">
                    <i class="bi bi-x-circle"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<style>
.form-check.p-3.border {
    transition: all 0.3s ease;
}
.form-check.p-3.border:hover {
    background-color: #f8f9fa;
    border-color: #2d5016 !important;
    box-shadow: 0 0 10px rgba(45, 80, 22, 0.1);
}
.form-check-input:checked + .form-check-label {
    color: #2d5016;
}
.card {
    transition: all 0.3s ease;
}
</style>

<script>
function toggleTeacherMode() {
    const modeNew = document.getElementById('mode_new');
    const modeExisting = document.getElementById('mode_existing');
    const existingSection = document.getElementById('existing_teacher_section');
    const teacherInfoSection = document.getElementById('teacher_info_section');
    const userAccountCheckbox = document.getElementById('create_user_account');
    
    // Update visual selection
    const allCards = document.querySelectorAll('.form-check.p-3.border');
    allCards.forEach(card => {
        card.style.borderColor = '#dee2e6';
        card.style.backgroundColor = 'white';
    });
    
    if (modeNew.checked) {
        // Highlight selected card
        modeNew.closest('.form-check.p-3.border').style.borderColor = '#198754';
        modeNew.closest('.form-check.p-3.border').style.backgroundColor = '#f0f9f4';
        
        // New teacher mode
        existingSection.style.display = 'none';
        teacherInfoSection.style.display = 'block';
        
        // Make teacher info fields required
        document.querySelectorAll('#teacher_info_section input[required], #teacher_info_section select[required]').forEach(input => {
            input.disabled = false;
        });
        
        // Make existing teacher select not required
        const existingSelect = document.getElementById('existing_teacher_select');
        if (existingSelect) {
            existingSelect.required = false;
        }
    } else {
        // Highlight selected card
        modeExisting.closest('.form-check.p-3.border').style.borderColor = '#0dcaf0';
        modeExisting.closest('.form-check.p-3.border').style.backgroundColor = '#f0f9ff';
        
        // Existing teacher mode
        existingSection.style.display = 'block';
        teacherInfoSection.style.display = 'none';
        
        // Disable teacher info fields
        document.querySelectorAll('#teacher_info_section input, #teacher_info_section select').forEach(input => {
            input.disabled = true;
        });
        
        // Make existing teacher select required
        const existingSelect = document.getElementById('existing_teacher_select');
        if (existingSelect) {
            existingSelect.required = true;
        }
        
        // Force user account creation for existing teachers
        if (userAccountCheckbox) {
            userAccountCheckbox.checked = true;
            toggleUserAccountFields();
        }
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    toggleTeacherMode();
});

// Populate email when existing teacher is selected
document.addEventListener('DOMContentLoaded', function() {
    const existingSelect = document.getElementById('existing_teacher_select');
    if (existingSelect) {
        existingSelect.addEventListener('change', function() {
            const selected = this.options[this.selectedIndex];
            const fname = selected.dataset.fname || '';
            const lname = selected.dataset.lname || '';
            const email = selected.dataset.email || '';
            
            // Auto-generate username from selected teacher
            if (fname && lname) {
                const username = (fname[0] + lname).toLowerCase().replace(/[^a-z0-9]/g, '');
                const usernameInput = document.getElementById('username');
                if (usernameInput) {
                    usernameInput.value = username;
                }
            }
        });
    }
});

function toggleUserAccountFields() {
    const checkbox = document.getElementById('create_user_account');
    const fields = document.getElementById('user_account_fields');
    const usernameInput = document.getElementById('username');
    const passwordInput = document.getElementById('password');
    
    if (checkbox.checked) {
        fields.style.display = 'block';
        usernameInput.required = true;
        passwordInput.required = true;
    } else {
        fields.style.display = 'none';
        usernameInput.required = false;
        passwordInput.required = false;
    }
}

function generateUsername() {
    const fName = document.querySelector('input[name="fName"]').value.trim();
    const lName = document.querySelector('input[name="lName"]').value.trim();
    
    if (!fName || !lName) {
        alert('Please enter first name and last name first');
        return;
    }
    
    // Create username: first initial + last name (lowercase, no spaces)
    const username = (fName[0] + lName).toLowerCase().replace(/[^a-z0-9]/g, '');
    document.getElementById('username').value = username;
}

function generatePassword() {
    const chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%';
    let password = '';
    for (let i = 0; i < 12; i++) {
        password += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    document.getElementById('password').value = password;
}

// Auto-generate username when first/last name changes
document.addEventListener('DOMContentLoaded', function() {
    const fNameInput = document.querySelector('input[name="fName"]');
    const lNameInput = document.querySelector('input[name="lName"]');
    const usernameInput = document.getElementById('username');
    
    if (fNameInput && lNameInput && usernameInput) {
        function autoGenerateUsername() {
            const createAccount = document.getElementById('create_user_account');
            if (createAccount && createAccount.checked && !usernameInput.value) {
                generateUsername();
            }
        }
        
        fNameInput.addEventListener('blur', autoGenerateUsername);
        lNameInput.addEventListener('blur', autoGenerateUsername);
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
