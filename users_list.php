<?php
require_once 'includes/bootstrap.php';
require_once 'includes/Auth.php';
require_once 'includes/PermissionHelper.php';

Auth::requireLogin();
Auth::requireAnyRole(['admin']);

require_once 'modules/users/UsersModel.php';

$usersModel = new UsersModel();

// Handle search and pagination
$searchTerm = $_GET['search'] ?? '';
$page = $_GET['page'] ?? 1;
$perPage = 20;

if ($searchTerm) {
    $allUsers = $usersModel->search($searchTerm);
    $totalRecords = count($allUsers);
    $pagination = new Pagination($totalRecords, $perPage, $page);
    $users = array_slice($allUsers, $pagination->getOffset(), $pagination->getLimit());
} else {
    $totalRecords = $usersModel->count();
    $pagination = new Pagination($totalRecords, $perPage, $page);
    $users = $usersModel->getAllWithRoles($pagination->getLimit(), $pagination->getOffset());
}

$pageTitle = 'User Management';
$currentPage = 'users';
require_once 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-people-fill"></i> System Users</h2>
    <?php if (PermissionHelper::canManage('users')): ?>
    <a href="users_form.php" class="btn btn-sm" style="background-color: #2d5016; color: white;">
        <i class="bi bi-plus-circle"></i> Add New User
    </a>
    <?php endif; ?>
</div>

<!-- Search Bar -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-12">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" class="form-control border-start-0 ps-0" id="liveSearchInput" 
                           placeholder="Start typing to search by username, email, or user ID..." 
                           value="<?= htmlspecialchars($searchTerm) ?>"
                           autocomplete="off">
                </div>
                <small class="text-muted">Results update as you type</small>
            </div>
        </div>
    </div>
</div>

<!-- Users Table -->
<div class="card" id="resultsTable">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead style="background-color: #f8f9fa;">
                    <tr>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4">
                            <i class="bi bi-inbox" style="font-size: 3rem; opacity: 0.3;"></i>
                            <p class="text-muted mt-2">No users found</p>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($user['username']) ?></strong>
                        </td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td>
                            <?php 
                            $roleColors = ['Admin' => '#2d5016', 'Teacher' => '#5cb85c', 'Parent' => '#f0ad4e'];
                            $roleColor = $roleColors[$user['roleName']] ?? '#6c757d';
                            ?>
                            <span class="badge" style="background-color: <?= $roleColor ?>;">
                                <?= htmlspecialchars($user['roleName']) ?>
                            </span>
                        </td>
                        <td>
                            <?php if (($user['isActive'] ?? 'Y') === 'Y'): ?>
                                <span class="badge bg-success"><i class="bi bi-check-circle"></i> Active</span>
                            <?php else: ?>
                                <span class="badge bg-secondary"><i class="bi bi-x-circle"></i> Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td><?= $user['createdAt'] ? date('M d, Y', strtotime($user['createdAt'])) : 'N/A' ?></td>
                        <td>
                            <div class="btn-group btn-group-sm" role="group">
                                <a href="users_view.php?id=<?= $user['userID'] ?>" class="btn btn-outline-info btn-sm">
                                    <i class="bi bi-eye"></i> View
                                </a>
                                <?php if (PermissionHelper::canManage('users')): ?>
                                <a href="users_form.php?id=<?= $user['userID'] ?>" class="btn btn-outline-warning btn-sm">
                                    <i class="bi bi-pencil"></i> Edit
                                </a>
                                <a href="users_password.php?id=<?= $user['userID'] ?>" class="btn btn-outline-secondary btn-sm">
                                    <i class="bi bi-key"></i> Reset
                                </a>
                                <?php endif; ?>
                                <?php if (PermissionHelper::canManage('users') && $user['userID'] != Session::get('user_id')): ?>
                                <a href="delete.php?module=users&id=<?= $user['userID'] ?>" 
                                   class="btn btn-outline-danger btn-sm" 
                                   onclick="return confirm('Are you sure you want to delete this user?');">
                                    <i class="bi bi-trash"></i> Delete
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if ($pagination->hasPages()): ?>
    <div class="card-footer">
        <?= $pagination->render() ?>
    </div>
    <?php endif; ?>
</div>

<script src="assets/js/live-search.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    new LiveSearch({
        searchInput: '#liveSearchInput',
        resultsContainer: '#resultsTable',
        apiEndpoint: '/LilayiParkSchool/api/search_users.php',
        emptyMessage: 'No users found',
        debounceDelay: 300,
        renderRow: function(user) {
            const roleColors = {
                'Admin': '#2d5016',
                'Teacher': '#5cb85c',
                'Parent': '#f0ad4e'
            };
            const roleColor = roleColors[user.roleName] || '#6c757d';
            const isActive = (user.isActive || 'Y') === 'Y';
            
            return `
                <tr>
                    <td><strong>${escapeHtml(user.username)}</strong></td>
                    <td>${escapeHtml(user.email)}</td>
                    <td>
                        <span class="badge" style="background-color: ${roleColor};">
                            ${escapeHtml(user.roleName || 'No Role')}
                        </span>
                    </td>
                    <td>
                        ${isActive 
                            ? '<span class="badge bg-success"><i class="bi bi-check-circle"></i> Active</span>'
                            : '<span class="badge bg-secondary"><i class="bi bi-x-circle"></i> Inactive</span>'}
                    </td>
                    <td>${new Date(user.createdAt).toLocaleDateString()}</td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <a href="users_view.php?id=${user.userID}" class="btn btn-outline-primary" title="View">
                                <i class="bi bi-eye"></i>
                            </a>
                            <?php if (PermissionHelper::canManage('users')): ?>
                            <a href="users_form.php?id=${user.userID}" class="btn btn-outline-success" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <a href="delete.php?module=users&id=${user.userID}" 
                               class="btn btn-outline-danger" 
                               onclick="return confirm('Are you sure you want to delete this user?');"
                               title="Delete">
                                <i class="bi bi-trash"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            `;
        }
    });
    
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
