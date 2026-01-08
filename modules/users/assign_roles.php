<?php
/**
 * User Role Assignment Interface
 * Allows admins to assign and remove roles for users
 */
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/Session.php';
require_once __DIR__ . '/../../includes/CSRF.php';
require_once __DIR__ . '/../../includes/Auth.php';

// Require admin access
Auth::requireLogin();
Auth::requireRole('admin');

require_once __DIR__ . '/../../modules/users/UsersModel.php';
require_once __DIR__ . '/../../modules/roles/RolesModel.php';
require_once __DIR__ . '/../../modules/teachers/TeacherModel.php';
require_once __DIR__ . '/../../modules/parents/ParentModel.php';

$usersModel = new UsersModel();
$rolesModel = new RolesModel();
$teacherModel = new TeacherModel();
$parentModel = new ParentModel();

$userID = $_GET['id'] ?? null;

if (!$userID) {
    Session::setFlash('error', 'No user selected');
    header('Location: users_list.php');
    exit;
}

$user = $usersModel->getById($userID);
if (!$user) {
    Session::setFlash('error', 'User not found');
    header('Location: users_list.php');
    exit;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && CSRF::requireToken()) {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'assign_role') {
            $roleID = $_POST['roleID'] ?? '';
            if ($roleID) {
                $rolesModel->assignRole($userID, $roleID, Auth::id());
                Session::setFlash('success', 'Role assigned successfully');
            }
        } elseif ($action === 'remove_role') {
            $roleID = $_POST['roleID'] ?? '';
            if ($roleID) {
                $rolesModel->removeRole($userID, $roleID);
                Session::setFlash('success', 'Role removed successfully');
            }
        } elseif ($action === 'link_teacher') {
            $teacherID = $_POST['teacherID'] ?? '';
            if ($teacherID) {
                $usersModel->linkToTeacher($userID, $teacherID);
                Session::setFlash('success', 'Linked to teacher record successfully');
            }
        } elseif ($action === 'link_parent') {
            $parentID = $_POST['parentID'] ?? '';
            if ($parentID) {
                $usersModel->linkToParent($userID, $parentID);
                Session::setFlash('success', 'Linked to parent record successfully');
            }
        } elseif ($action === 'unlink_teacher') {
            $teacherID = $_POST['teacherID'] ?? '';
            if ($teacherID) {
                $usersModel->unlinkFromTeacher($teacherID);
                Session::setFlash('success', 'Unlinked from teacher record');
            }
        } elseif ($action === 'unlink_parent') {
            $parentID = $_POST['parentID'] ?? '';
            if ($parentID) {
                $usersModel->unlinkFromParent($parentID);
                Session::setFlash('success', 'Unlinked from parent record');
            }
        }
        
        CSRF::regenerateToken();
        header('Location: assign_roles.php?id=' . $userID);
        exit;
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get user's current roles
$userRoles = $rolesModel->getUserRoles($userID);
$userRoleIDs = array_column($userRoles, 'roleID');

// Get all available roles
$allRoles = $rolesModel->getAllRoles();

// Get linked teacher/parent if any
$linkedTeacher = $teacherModel->getByUserID($userID);
$linkedParent = $parentModel->getByUserID($userID);

// Get teachers and parents without user accounts (for linking)
$availableTeachers = $teacherModel->getWithoutUserAccount();
$availableParents = $parentModel->getWithoutUserAccount();

$pageTitle = 'Assign Roles - ' . htmlspecialchars($user['username']);
$currentPage = 'users';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="mb-4">
    <a href="users_list.php" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back to Users
    </a>
</div>

<div class="row">
    <!-- User Info Card -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-person-circle"></i> User Information</h5>
            </div>
            <div class="card-body">
                <p><strong>Username:</strong> <?= htmlspecialchars($user['username']) ?></p>
                <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
                <p><strong>Status:</strong> 
                    <?php if ($user['isActive']): ?>
                        <span class="badge bg-success">Active</span>
                    <?php else: ?>
                        <span class="badge bg-danger">Inactive</span>
                    <?php endif; ?>
                </p>
                <p><strong>Last Login:</strong> 
                    <?= $user['lastLogin'] ? date('Y-m-d H:i', strtotime($user['lastLogin'])) : 'Never' ?>
                </p>
            </div>
        </div>
    </div>
    
    <!-- Roles Management Card -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bi bi-shield-check"></i> Role Management</h5>
            </div>
            <div class="card-body">
                <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                
                <?php 
                $flash = Session::getFlash();
                if ($flash): ?>
                <div class="alert alert-<?= $flash['type'] ?>"><?= htmlspecialchars($flash['message']) ?></div>
                <?php endif; ?>
                
                <h6 class="mb-3">Current Roles</h6>
                <?php if (empty($userRoles)): ?>
                    <p class="text-muted">No roles assigned yet</p>
                <?php else: ?>
                    <div class="d-flex flex-wrap gap-2 mb-4">
                        <?php foreach ($userRoles as $role): ?>
                            <div class="badge bg-primary p-2 d-flex align-items-center gap-2">
                                <span><?= htmlspecialchars($role['roleName']) ?></span>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Remove this role?')">
                                    <?= CSRF::field() ?>
                                    <input type="hidden" name="action" value="remove_role">
                                    <input type="hidden" name="roleID" value="<?= $role['roleID'] ?>">
                                    <button type="submit" class="btn btn-sm btn-link text-white p-0">
                                        <i class="bi bi-x-circle"></i>
                                    </button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <hr>
                
                <h6 class="mb-3">Assign New Role</h6>
                <form method="POST" class="row g-3">
                    <?= CSRF::field() ?>
                    <input type="hidden" name="action" value="assign_role">
                    <div class="col-md-9">
                        <select name="roleID" class="form-select" required>
                            <option value="">Select a role...</option>
                            <?php foreach ($allRoles as $role): ?>
                                <?php if (!in_array($role['roleID'], $userRoleIDs)): ?>
                                    <option value="<?= $role['roleID'] ?>">
                                        <?= htmlspecialchars($role['roleName']) ?> 
                                        - <?= htmlspecialchars($role['description']) ?>
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-success w-100">
                            <i class="bi bi-plus-circle"></i> Assign
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Teacher/Parent Linking -->
        <div class="card mt-3">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="bi bi-link-45deg"></i> Teacher/Parent Linking</h5>
            </div>
            <div class="card-body">
                <!-- Linked Teacher -->
                <h6 class="mb-3">Teacher Link</h6>
                <?php if ($linkedTeacher): ?>
                    <div class="alert alert-success">
                        <strong>Linked to:</strong> 
                        <?= htmlspecialchars($linkedTeacher['fName'] . ' ' . $linkedTeacher['lName']) ?>
                        (<?= htmlspecialchars($linkedTeacher['teacherID']) ?>)
                        <form method="POST" class="d-inline float-end" onsubmit="return confirm('Unlink this teacher?')">
                            <?= CSRF::field() ?>
                            <input type="hidden" name="action" value="unlink_teacher">
                            <input type="hidden" name="teacherID" value="<?= $linkedTeacher['teacherID'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger">Unlink</button>
                        </form>
                    </div>
                <?php elseif (!empty($availableTeachers)): ?>
                    <form method="POST" class="row g-2">
                        <?= CSRF::field() ?>
                        <input type="hidden" name="action" value="link_teacher">
                        <div class="col-md-9">
                            <select name="teacherID" class="form-select">
                                <option value="">Select teacher to link...</option>
                                <?php foreach ($availableTeachers as $teacher): ?>
                                    <option value="<?= $teacher['teacherID'] ?>">
                                        <?= htmlspecialchars($teacher['fName'] . ' ' . $teacher['lName']) ?>
                                        (<?= htmlspecialchars($teacher['teacherID']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-info w-100">Link</button>
                        </div>
                    </form>
                <?php else: ?>
                    <p class="text-muted">No teachers available for linking</p>
                <?php endif; ?>
                
                <hr class="my-3">
                
                <!-- Linked Parent -->
                <h6 class="mb-3">Parent Link</h6>
                <?php if ($linkedParent): ?>
                    <div class="alert alert-success">
                        <strong>Linked to:</strong> 
                        <?= htmlspecialchars($linkedParent['fName'] . ' ' . $linkedParent['lName']) ?>
                        (<?= htmlspecialchars($linkedParent['parentID']) ?>)
                        <form method="POST" class="d-inline float-end" onsubmit="return confirm('Unlink this parent?')">
                            <?= CSRF::field() ?>
                            <input type="hidden" name="action" value="unlink_parent">
                            <input type="hidden" name="parentID" value="<?= $linkedParent['parentID'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger">Unlink</button>
                        </form>
                    </div>
                <?php elseif (!empty($availableParents)): ?>
                    <form method="POST" class="row g-2">
                        <?= CSRF::field() ?>
                        <input type="hidden" name="action" value="link_parent">
                        <div class="col-md-9">
                            <select name="parentID" class="form-select">
                                <option value="">Select parent to link...</option>
                                <?php foreach ($availableParents as $parent): ?>
                                    <option value="<?= $parent['parentID'] ?>">
                                        <?= htmlspecialchars($parent['fName'] . ' ' . $parent['lName']) ?>
                                        (<?= htmlspecialchars($parent['parentID']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-info w-100">Link</button>
                        </div>
                    </form>
                <?php else: ?>
                    <p class="text-muted">No parents available for linking</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
