<?php
require_once 'includes/db.php';
require_once 'includes/Session.php';
require_once 'includes/CSRF.php';
require_once 'includes/Auth.php';

// Require login
Auth::requireLogin();
Auth::requireAnyRole(['admin', 'teacher']);

$teacherID = $_GET['id'] ?? null;
$isEdit = !empty($teacherID);

require_once 'modules/teachers/TeacherModel.php';
require_once 'modules/users/UsersModel.php';
require_once 'modules/roles/RolesModel.php';

$teacherModel = new TeacherModel();
$usersModel = new UsersModel();
$rolesModel = new RolesModel();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token first
    if (!CSRF::requireToken()) {
        $error = $GLOBALS['csrf_error'] ?? 'Security validation failed. Please try again.';
    } else {
        $data = [
            'fName' => trim($_POST['fName'] ?? ''),
            'lName' => trim($_POST['lName'] ?? ''),
            'NRC' => trim($_POST['NRC'] ?? ''),
            'SSN' => trim($_POST['SSN'] ?? ''),
            'Tpin' => trim($_POST['Tpin'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'gender' => $_POST['gender'] ?? '',
            'tczNo' => trim($_POST['tczNo'] ?? '')
        ];
        
        // User account creation data
        $createUserAccount = isset($_POST['create_user_account']);
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $assignTeacherRole = isset($_POST['assign_teacher_role']);
        
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
                    
                    // Create user account
                    $userData = [
                        'username' => $username,
                        'email' => $data['email'],
                        'password' => $password,
                        'isActive' => 1
                    ];
                    
                    $roleIDs = [];
                    if ($assignTeacherRole) {
                        $teacherRole = $rolesModel->getRoleByName('teacher');
                        if ($teacherRole) {
                            $roleIDs[] = $teacherRole['roleID'];
                        }
                    }
                    
                    $userID = $usersModel->createWithRoles($userData, $roleIDs);
                    
                    if (!$userID) {
                        throw new Exception('Failed to create user account');
                    }
                    
                    // Link user to teacher
                    $usersModel->linkToTeacher($userID, $finalTeacherID);
                    
                    $message .= ' and user account created';
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
    }
}

$teacher = $isEdit ? $teacherModel->getById($teacherID) : null;

$pageTitle = $isEdit ? 'Edit Teacher' : 'Add New Teacher';
$currentPage = 'teachers';
require_once 'includes/header.php';
?>

<div class="mb-4">
    <a href="teachers_list.php" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back to Teachers
    </a>
</div>

<div class="card">
    <div class="card-header" style="background: linear-gradient(135deg, #2d5016 0%, #5cb85c 100%); color: white;">
        <h5 class="mb-0">
            <i class="bi bi-person-badge-fill"></i> <?= $pageTitle ?>
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
            </div>
            
            <div class="mb-3">
                <label class="form-label">TCZ Number</label>
                <input type="text" class="form-control" name="tczNo" 
                       value="<?= htmlspecialchars($teacher['tczNo'] ?? '') ?>" 
                       placeholder="Teachers Council of Zambia Number">
            </div>
            
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
                    
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="assign_teacher_role" 
                               name="assign_teacher_role" checked>
                        <label class="form-check-label" for="assign_teacher_role">
                            Assign "Teacher" role (recommended)
                        </label>
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

<script>
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
