<?php
require_once 'includes/bootstrap.php';
require_once 'includes/Auth.php';

// Require login
Auth::requireLogin();

// Check permission via RBAC
require_once 'modules/roles/RolesModel.php';
$rolesModel = new RolesModel();
if (!$rolesModel->userHasPermission(Auth::id(), 'manage_parents')) {
    Session::setFlash('error', 'You do not have permission to manage parents.');
    header('Location: /LilayiParkSchool/403.php');
    exit;
}

$parentID = $_GET['id'] ?? null;
$isEdit = !empty($parentID);

require_once 'modules/parents/ParentModel.php';
require_once 'modules/users/UsersModel.php';

$parentModel = new ParentModel();
$usersModel = new UsersModel();
$rolesModel = new RolesModel();

// Get all parents for dropdown
$allParents = $parentModel->getAll();

// Get all roles for multi-role assignment
$allRoles = $rolesModel->getAllRoles();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token first
    if (!CSRF::requireToken()) {
        $error = $GLOBALS['csrf_error'] ?? 'Security validation failed. Please try again.';
    } else {
        // Check if using existing parent
        $parentMode = $_POST['parent_mode'] ?? 'new';
        $existingParentID = $_POST['existing_parent_id'] ?? null;
        
        // User account creation data
        $createUserAccount = isset($_POST['create_user_account']);
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $selectedRoles = $_POST['roles'] ?? []; // Multi-role support
        
        if ($parentMode === 'existing' && !$isEdit) {
            // Mode: Create user account for existing parent
            if (empty($existingParentID)) {
                $error = 'Please select a parent';
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
                    
                    // Get parent data
                    $parent = $parentModel->getById($existingParentID);
                    if (!$parent) {
                        throw new Exception('Parent not found');
                    }
                    
                    // Check if parent already has a user account
                    if (!empty($parent['userID'])) {
                        throw new Exception('This parent already has a user account');
                    }
                    
                    // Create user account
                    $userData = [
                        'username' => $username,
                        'email' => $parent['email1'],
                        'password' => $password,
                        'isActive' => 'Y'
                    ];
                    
                    $userID = $usersModel->createWithRoles($userData, $selectedRoles);
                    
                    if (!$userID) {
                        throw new Exception('Failed to create user account');
                    }
                    
                    // Link user to parent
                    $usersModel->linkToParent($userID, $existingParentID);
                    
                    $db->commit();
                    Session::setFlash('success', 'User account created for ' . $parent['fName'] . ' ' . $parent['lName'] . ' with ' . count($selectedRoles) . ' role(s)');
                    
                    // Regenerate CSRF token
                    CSRF::regenerateToken();
                    
                    header('Location: parents_list.php');
                    exit;
                } catch (Exception $e) {
                    if (isset($db) && $db->inTransaction()) {
                        $db->rollBack();
                    }
                    $error = $e->getMessage();
                }
            }
        } else {
            // Mode: Create new parent (with optional user account) OR Edit existing parent
        $data = [
            'fName' => trim($_POST['fName'] ?? ''),
            'lName' => trim($_POST['lName'] ?? ''),
            'relation' => trim($_POST['relation'] ?? ''),
            'gender' => $_POST['gender'] ?? '',
            'NRC' => trim($_POST['NRC'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'email1' => trim($_POST['email1'] ?? ''),
            'email2' => trim($_POST['email2'] ?? ''),
            'occupation' => trim($_POST['occupation'] ?? ''),
            'workplace' => trim($_POST['workplace'] ?? '')
        ];
        
        // Validation
        if (empty($data['fName'])) {
            $error = 'First name is required';
        } elseif (empty($data['lName'])) {
            $error = 'Last name is required';
        } elseif (empty($data['relation'])) {
            $error = 'Relationship is required';
        } elseif (empty($data['gender'])) {
            $error = 'Gender is required';
        }
        // NRC validation: Must be format XXXXXX/XX/X
        elseif (empty($data['NRC'])) {
            $error = 'NRC is required';
        } elseif (!preg_match('/^\d{6}\/\d{2}\/\d{1}$/', $data['NRC'])) {
            $error = 'NRC must be in format XXXXXX/XX/X (e.g., 123456/78/1)';
        } elseif ($parentModel->nrcExists($data['NRC'], $isEdit ? $parentID : null)) {
            $error = 'A parent with this NRC already exists';
        }
        // Phone validation: 10-13 characters, + only at start
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
        } elseif ($parentModel->phoneExists($data['phone'], $isEdit ? $parentID : null)) {
            $error = 'A parent with this phone number already exists';
        }
        // Email validation
        elseif (empty($data['email1'])) {
            $error = 'Email is required';
        } elseif (!filter_var($data['email1'], FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email format';
        } elseif ($parentModel->emailExists($data['email1'], $isEdit ? $parentID : null)) {
            $error = 'A parent with this email already exists';
        } elseif (!empty($data['email2']) && !filter_var($data['email2'], FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid secondary email format';
        } elseif (!empty($data['email2']) && $parentModel->emailExists($data['email2'], $isEdit ? $parentID : null)) {
            $error = 'A parent with this secondary email already exists';
        }
        
        if (!isset($error)) {
            try {
                global $db;
                $db->beginTransaction();
                
                $success = false;
                $message = '';
                $finalParentID = $parentID;
                
                if ($isEdit) {
                    // Update existing parent
                    $success = $parentModel->update($parentID, $data);
                    $message = 'Parent updated successfully';
                    
                    // Handle role updates for existing user
                    $parent = $parentModel->getById($parentID);
                    if (!empty($parent['userID']) && !empty($selectedRoles)) {
                        // Remove existing roles
                        $existingRoles = $rolesModel->getUserRoles($parent['userID']);
                        foreach ($existingRoles as $role) {
                            $rolesModel->removeRole($parent['userID'], $role['roleID']);
                        }
                        // Add selected roles
                        foreach ($selectedRoles as $roleID) {
                            $rolesModel->assignRole($parent['userID'], $roleID, Auth::id());
                        }
                        $message .= ' and roles updated';
                    }
                } else {
                    // Create new parent
                    $finalParentID = $parentModel->create($data);
                    $success = $finalParentID !== false;
                    $message = 'Parent created successfully';
                }
                
                if (!$success) {
                    throw new Exception('Failed to save parent data');
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
                        'email' => $data['email1'],
                        'password' => $password,
                        'isActive' => 'Y'
                    ];
                    
                    $userID = $usersModel->createWithRoles($userData, $selectedRoles);
                    
                    if (!$userID) {
                        throw new Exception('Failed to create user account');
                    }
                    
                    // Link user to parent
                    $usersModel->linkToParent($userID, $finalParentID);
                    
                    $message .= ' and user account created with ' . count($selectedRoles) . ' role(s)';
                }
                
                $db->commit();
                Session::setFlash('success', $message);
                
                // Regenerate CSRF token after successful submission
                CSRF::regenerateToken();
                
                header('Location: parents_list.php');
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
        } // End of new parent mode
    }
}

$parent = $isEdit ? $parentModel->getById($parentID) : null;

// Get existing user account and roles if editing
$existingUser = null;
$existingUserRoles = [];
if ($isEdit && $parent && !empty($parent['userID'])) {
    $existingUser = $usersModel->getById($parent['userID']);
    $existingUserRoles = $rolesModel->getUserRoles($parent['userID']);
}
$existingRoleIDs = array_column($existingUserRoles, 'roleID');

$pageTitle = $isEdit ? 'Edit Parent' : 'Add New Parent';
$currentPage = 'parents';
require_once 'includes/header.php';
?>

<!-- Page Header -->
<div class="row mb-4">
    <div class="col-md-8">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="parents_list.php" class="text-decoration-none">Parents</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?= $isEdit ? 'Edit' : 'Add New' ?></li>
            </ol>
        </nav>
        <h2 class="mb-0">
            <i class="bi bi-people me-2" style="color: #2d5016;"></i>
            <span style="color: #2d5016; font-weight: 600;"><?= $pageTitle ?></span>
        </h2>
    </div>
    <div class="col-md-4 text-end align-self-end">
        <a href="parents_list.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Parents
        </a>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom">
        <h5 class="mb-0">
            <i class="bi bi-people-fill me-2" style="color: #2d5016;"></i><?= $pageTitle ?>
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
            <!-- Parent Mode Selection - Always Visible -->
            <div class="card mb-4 border-primary" style="border-width: 2px;">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0">
                        <i class="bi bi-person-plus-fill me-2"></i>Choose an Option
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-check p-3 border rounded" style="cursor: pointer;" onclick="document.getElementById('mode_new').click();">
                                <input class="form-check-input" type="radio" name="parent_mode" id="mode_new" 
                                       value="new" checked onchange="toggleParentMode()">
                                <label class="form-check-label w-100" for="mode_new" style="cursor: pointer;">
                                    <div class="d-flex align-items-start">
                                        <i class="bi bi-person-plus-fill text-success me-2" style="font-size: 1.5rem;"></i>
                                        <div>
                                            <strong class="d-block">Create a New Parent</strong>
                                            <small class="text-muted">Add a brand new parent record to the system</small>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check p-3 border rounded" style="cursor: pointer;" onclick="document.getElementById('mode_existing').click();">
                                <input class="form-check-input" type="radio" name="parent_mode" id="mode_existing" 
                                       value="existing" onchange="toggleParentMode()">
                                <label class="form-check-label w-100" for="mode_existing" style="cursor: pointer;">
                                    <div class="d-flex align-items-start">
                                        <i class="bi bi-person-check-fill text-info me-2" style="font-size: 1.5rem;"></i>
                                        <div>
                                            <strong class="d-block">Select Existing Parent</strong>
                                            <small class="text-muted">Create a user account for an existing parent</small>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div id="existing_parent_section" style="display: none;" class="mb-4">
                <div class="card border-info">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0">
                            <i class="bi bi-person-lines-fill me-2"></i>Select Parent
                        </h6>
                    </div>
                    <div class="card-body">
                        <label class="form-label fw-semibold">Choose Parent <span class="text-danger">*</span></label>
                        <select class="form-select form-select-lg" name="existing_parent_id" id="existing_parent_select">
                            <option value="">-- Select a Parent --</option>
                            <?php foreach ($allParents as $p): ?>
                            <option value="<?= $p['parentID'] ?>" 
                                    data-fname="<?= htmlspecialchars($p['fName']) ?>"
                                    data-lname="<?= htmlspecialchars($p['lName']) ?>"
                                    data-email="<?= htmlspecialchars($p['email1']) ?>"
                                    <?= !empty($p['userID']) ? 'disabled' : '' ?>>
                                <?= htmlspecialchars($p['fName'] . ' ' . $p['lName']) ?> 
                                - <?= htmlspecialchars($p['email1']) ?>
                                <?= !empty($p['userID']) ? ' ✓ (Already has account)' : '' ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="alert alert-info mt-3 mb-0">
                            <i class="bi bi-info-circle me-1"></i>
                            <strong>Note:</strong> Parents who already have user accounts are disabled in the list.
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <div id="parent_info_section">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Forename <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="fName" 
                           value="<?= htmlspecialchars($parent['fName'] ?? '') ?>" required>
                </div>
                
                <div class="col-md-4 mb-3">
                    <label class="form-label">Last Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="lName" 
                           value="<?= htmlspecialchars($parent['lName'] ?? '') ?>" required>
                </div>
            
                <div class="col-md-4 mb-3">
                    <label class="form-label">Relationship <span class="text-danger">*</span></label>
                    <select class="form-select" name="relation" required>
                        <option value="">Select Relationship</option>
                        <option value="Father" <?= ($parent['relation'] ?? '') === 'Father' ? 'selected' : '' ?>>Father</option>
                        <option value="Mother" <?= ($parent['relation'] ?? '') === 'Mother' ? 'selected' : '' ?>>Mother</option>
                        <option value="Guardian" <?= ($parent['relation'] ?? '') === 'Guardian' ? 'selected' : '' ?>>Guardian</option>
                        <option value="Other" <?= ($parent['relation'] ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
                    </select>
                </div>
                
                <div class="col-md-4 mb-3">
                    <label class="form-label">Gender <span class="text-danger">*</span></label>
                    <div class="btn-group w-100" role="group">
                        <input type="radio" class="btn-check" name="gender" id="gender_m" value="M" 
                               <?= ($parent['gender'] ?? '') === 'M' ? 'checked' : '' ?> required>
                        <label class="btn btn-outline-primary" for="gender_m">
                            <i class="bi bi-gender-male"></i> Male
                        </label>
                        
                        <input type="radio" class="btn-check" name="gender" id="gender_f" value="F" 
                               <?= ($parent['gender'] ?? '') === 'F' ? 'checked' : '' ?> required>
                        <label class="btn btn-outline-primary" for="gender_f">
                            <i class="bi bi-gender-female"></i> Female
                        </label>
                    </div>
                </div>
            
                <div class="col-md-4 mb-3">
                    <label class="form-label">NRC <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="NRC" 
                           value="<?= htmlspecialchars($parent['NRC'] ?? '') ?>" 
                           pattern="\d{6}/\d{2}/\d{1}"
                           placeholder="123456/78/1"
                           title="Format: 6 digits/2 digits/1 digit (e.g., 123456/78/1)"
                           required>
                    <small class="text-muted">Format: XXXXXX/XX/X</small>
                </div>
                
                <div class="col-md-4 mb-3">
                    <label class="form-label">Phone <span class="text-danger">*</span></label>
                    <input type="tel" class="form-control" name="phone" 
                           value="<?= htmlspecialchars($parent['phone'] ?? '') ?>" 
                           pattern="(\+260\d{9}|\d{10,13})"
                           placeholder="0977123456 or +260977123456"
                           title="10-13 digits, or +260 followed by 9 digits"
                           minlength="10"
                           maxlength="13"
                           required>
                    <small class="text-muted">10-13 digits, or +260XXXXXXXXX</small>
                </div>
            
                <div class="col-md-4 mb-3">
                    <label class="form-label">Email 1 <span class="text-danger">*</span></label>
                    <input type="email" class="form-control" name="email1" 
                           value="<?= htmlspecialchars($parent['email1'] ?? '') ?>" required>
                </div>
                
                <div class="col-md-4 mb-3">
                    <label class="form-label">Email 2</label>
                    <input type="email" class="form-control" name="email2" 
                           value="<?= htmlspecialchars($parent['email2'] ?? '') ?>" 
                           placeholder="Secondary email (optional)">
                </div>
            
                <div class="col-md-4 mb-3">
                    <label class="form-label">Occupation</label>
                    <input type="text" class="form-control" name="occupation" 
                           value="<?= htmlspecialchars($parent['occupation'] ?? '') ?>">
                </div>
                
                <div class="col-md-4 mb-3">
                    <label class="form-label">Workplace</label>
                    <input type="text" class="form-control" name="workplace" 
                           value="<?= htmlspecialchars($parent['workplace'] ?? '') ?>" 
                           placeholder="Employer/Company name">
                </div>
            </div>
            </div>
            
            <?php if (!$isEdit): ?>
            <!-- User Account Creation Section -->
            <div id="user_account_section">
                <hr class="my-4">
                <div class="card border-success">
                    <div class="card-header bg-success text-white">
                        <div class="form-check mb-0">
                            <input class="form-check-input bg-white border-2" type="checkbox" 
                                   name="create_user_account" id="create_user_account" 
                                   onchange="toggleUserAccountFields()">
                            <label class="form-check-label fw-semibold" for="create_user_account">
                                <i class="bi bi-person-plus-fill me-2"></i>Create User Account for Portal Access
                            </label>
                        </div>
                    </div>
                    <div class="card-body" id="user_account_fields" style="display: none;">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-1"></i>
                            <strong>Note:</strong> Creating a user account will allow this parent to log in to the system.
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Username <span class="text-danger user-required">*</span></label>
                                <input type="text" class="form-control" name="username" id="username" 
                                       placeholder="Enter username">
                                <small class="text-muted">Used for logging in</small>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Password <span class="text-danger user-required">*</span></label>
                                <input type="password" class="form-control" name="password" id="password" 
                                       placeholder="Enter password">
                                <small class="text-muted">Minimum 8 characters</small>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label d-block">Assign Roles <span class="text-danger user-required">*</span></label>
                            <div class="alert alert-warning mb-2">
                                <i class="bi bi-shield-lock-fill"></i> <strong>Role Assignment</strong>
                                <p class="mb-0 small">Select one or more roles for this parent. Typically, parents are assigned the "parent" role.</p>
                            </div>
                            <div class="row">
                                <?php foreach ($allRoles as $role): ?>
                                <div class="col-md-6 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="roles[]" 
                                               value="<?= $role['roleID'] ?>" id="role_<?= $role['roleID'] ?>"
                                               <?= $role['roleName'] === 'parent' ? 'checked' : '' ?>>
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
                    </div>
                </div>
            </div>
            <?php else: ?>
            <!-- Edit Mode: Role Management for Existing User -->
            <?php if (!empty($existingUser)): ?>
            <hr class="my-4">
            <div class="card border-info">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0">
                        <i class="bi bi-person-badge-fill me-2"></i>User Account Management
                    </h6>
                </div>
                <div class="card-body">
                
                <div class="alert alert-info mb-3">
                    <i class="bi bi-info-circle"></i> This parent has a user account: <strong><?= htmlspecialchars($existingUser['username']) ?></strong>
                    <br><small>User Status: <?= $existingUser['isActive'] === 'Y' ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>' ?></small>
                </div>
                
                <div class="mb-3">
                    <label class="form-label d-block">Assigned Roles <span class="text-danger">*</span></label>
                    <div class="alert alert-warning mb-2">
                        <i class="bi bi-shield-lock-fill"></i> <strong>Update Role Assignment</strong>
                        <p class="mb-0 small">Select one or more roles for this parent.</p>
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
            </div>
            <?php endif; ?>
            <?php endif; ?>
            
            <hr class="my-4">
            
            <div class="d-flex gap-2">
                <button type="submit" class="btn" style="background-color: #2d5016; color: white;">
                    <i class="bi bi-save"></i> <?= $isEdit ? 'Update' : 'Create' ?> Parent
                </button>
                <a href="parents_list.php" class="btn btn-secondary">
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
function toggleParentMode() {
    const modeNew = document.getElementById('mode_new');
    const modeExisting = document.getElementById('mode_existing');
    const existingSection = document.getElementById('existing_parent_section');
    const parentInfoSection = document.getElementById('parent_info_section');
    const userAccountSection = document.getElementById('user_account_section');
    
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
        
        // New parent mode
        existingSection.style.display = 'none';
        parentInfoSection.style.display = 'block';
        userAccountSection.style.display = 'block';
        
        // Make parent info fields required
        document.querySelectorAll('#parent_info_section input[required], #parent_info_section select[required]').forEach(input => {
            input.disabled = false;
        });
        
        // Make existing parent select not required
        const existingSelect = document.getElementById('existing_parent_select');
        if (existingSelect) {
            existingSelect.required = false;
        }
    } else {
        // Highlight selected card
        modeExisting.closest('.form-check.p-3.border').style.borderColor = '#0dcaf0';
        modeExisting.closest('.form-check.p-3.border').style.backgroundColor = '#eff8fb';
        
        // Existing parent mode
        existingSection.style.display = 'block';
        parentInfoSection.style.display = 'none';
        userAccountSection.style.display = 'block';
        
        // Disable and clear parent info fields
        document.querySelectorAll('#parent_info_section input, #parent_info_section select').forEach(input => {
            input.disabled = true;
        });
        
        // Make existing parent select required
        const existingSelect = document.getElementById('existing_parent_select');
        if (existingSelect) {
            existingSelect.required = true;
        }
        
        // Force show user account section and check the checkbox
        const userAccountCheckbox = document.getElementById('create_user_account');
        if (userAccountCheckbox) {
            userAccountCheckbox.checked = true;
            userAccountCheckbox.disabled = true;
            toggleUserAccountFields();
        }
    }
}

function toggleUserAccountFields() {
    const checkbox = document.getElementById('create_user_account');
    const fields = document.getElementById('user_account_fields');
    const usernameInput = document.getElementById('username');
    const passwordInput = document.getElementById('password');
    
    if (checkbox && checkbox.checked) {
        fields.style.display = 'block';
        if (usernameInput) usernameInput.required = true;
        if (passwordInput) passwordInput.required = true;
    } else {
        fields.style.display = 'none';
        if (usernameInput) {
            usernameInput.required = false;
            usernameInput.value = '';
        }
        if (passwordInput) {
            passwordInput.required = false;
            passwordInput.value = '';
        }
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    toggleParentMode();
});
</script>

<?php require_once 'includes/footer.php'; ?>
