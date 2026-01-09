<?php
require_once 'includes/bootstrap.php';
require_once 'includes/Auth.php';
require_once 'includes/PermissionHelper.php';

Auth::requireLogin();

require_once 'modules/roles/RolesModel.php';
$rolesModel = new RolesModel();
if (!$rolesModel->userHasPermission(Auth::id(), 'view_parents')) {
    Session::setFlash('error', 'You do not have permission to view parents.');
    header('Location: /LilayiParkSchool/403.php');
    exit;
}

require_once 'modules/parents/ParentModel.php';

$parentModel = new ParentModel();

// Handle search and pagination
$searchTerm = $_GET['search'] ?? '';
$page = $_GET['page'] ?? 1;
$perPage = 20;

if ($searchTerm) {
    $allParents = $parentModel->search($searchTerm);
    $totalRecords = count($allParents);
    $pagination = new Pagination($totalRecords, $perPage, $page);
    $parents = array_slice($allParents, $pagination->getOffset(), $pagination->getLimit());
} else {
    $totalRecords = $parentModel->count();
    $pagination = new Pagination($totalRecords, $perPage, $page);
    $parents = $parentModel->getAllWithChildrenCount($pagination->getLimit(), $pagination->getOffset());
}

$pageTitle = 'Parents Management';
$currentPage = 'parents';
require_once 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-people"></i> Parents</h2>
    <?php if (PermissionHelper::canManage('parents')): ?>
    <a href="parents_form.php" class="btn btn-sm" style="background-color: #2d5016; color: white;">
        <i class="bi bi-plus-circle"></i> Add New Parent
    </a>
    <?php endif; ?>
</div>

<!-- Search Bar -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-10">
                <input type="text" class="form-control" name="search" placeholder="Search by name, email or phone..." value="<?= htmlspecialchars($searchTerm) ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn w-100" style="background-color: #2d5016; color: white;">
                    <i class="bi bi-search"></i> Search
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Parents Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead style="background-color: #f8f9fa;">
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Address</th>
                        <th>Children</th>
                        <th>Account Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($parents)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4">
                            <i class="bi bi-inbox" style="font-size: 3rem; opacity: 0.3;"></i>
                            <p class="text-muted mt-2">No parents found</p>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($parents as $parent): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($parent['fName'] . ' ' . $parent['lName']) ?></strong>
                        </td>
                        <td><?= htmlspecialchars($parent['email1'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($parent['phone'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($parent['address'] ?? 'N/A') ?></td>
                        <td>
                            <span class="badge" style="background-color: #f0ad4e;">
                                <?= $parent['childrenCount'] ?? 0 ?> child(ren)
                            </span>
                        </td>
                        <td class="text-center">
                            <?php if (!empty($parent['userID'])): ?>
                                <?php if ($parent['userIsActive'] === 'Y'): ?>
                                    <span class="badge bg-success">
                                        <i class="bi bi-check-circle me-1"></i>Active Account
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-warning">
                                        <i class="bi bi-exclamation-triangle me-1"></i>Inactive
                                    </span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="badge bg-danger">
                                    <i class="bi bi-x-circle me-1"></i>No Account
                                </span>
                                <?php if (PermissionHelper::canManage('parents')): ?>
                                <br>
                                <button type="button" 
                                        class="btn btn-sm btn-success mt-1 create-account-btn" 
                                        data-parent-id="<?= $parent['parentID'] ?>"
                                        data-parent-name="<?= htmlspecialchars($parent['fName'] . ' ' . $parent['lName']) ?>"
                                        title="Create Account">
                                    <i class="bi bi-plus-circle me-1"></i>Create Account
                                </button>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm" role="group">
                                <a href="parents_view.php?id=<?= $parent['parentID'] ?>" class="btn btn-outline-info btn-sm" title="View Details">
                                    <i class="bi bi-eye"></i> View
                                </a>
                                <?php if (PermissionHelper::canManage('parents')): ?>
                                <a href="parents_form.php?id=<?= $parent['parentID'] ?>" class="btn btn-outline-warning btn-sm" title="Edit Parent">
                                    <i class="bi bi-pencil"></i> Edit
                                </a>
                                <a href="delete.php?module=parents&id=<?= $parent['parentID'] ?>" 
                                   class="btn btn-outline-danger btn-sm" 
                                   title="Delete Parent"
                                   onclick="return confirm('Are you sure you want to delete this parent? This will also affect their children.');">
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

<!-- Account Credentials Modal -->
<div class="modal fade" id="credentialsModal" tabindex="-1" aria-labelledby="credentialsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="credentialsModalLabel">
                    <i class="bi bi-check-circle me-2"></i>Account Created Successfully
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning mb-3">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <strong>Important:</strong> Save these credentials. They won't be shown again.
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-bold">Account For:</label>
                    <p class="form-control-plaintext" id="modalAccountName"></p>
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-bold">Username:</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="modalUsername" readonly>
                        <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('modalUsername')">
                            <i class="bi bi-clipboard"></i> Copy
                        </button>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-bold">Password:</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="modalPassword" readonly>
                        <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('modalPassword')">
                            <i class="bi bi-clipboard"></i> Copy
                        </button>
                    </div>
                </div>
                
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>
                    The user will be required to change their password on first login.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="window.print()">
                    <i class="bi bi-printer me-2"></i>Print
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const createButtons = document.querySelectorAll('.create-account-btn');
    
    createButtons.forEach(button => {
        button.addEventListener('click', function() {
            const parentID = this.dataset.parentId;
            const parentName = this.dataset.parentName;
            
            if (!confirm(`Create account for ${parentName}?`)) {
                return;
            }
            
            // Disable button and show loading
            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span>';
            
            // Make AJAX request
            fetch(`parents_create_account.php?id=${parentID}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show credentials modal
                        document.getElementById('modalAccountName').textContent = data.name;
                        document.getElementById('modalUsername').value = data.username;
                        document.getElementById('modalPassword').value = data.password;
                        
                        const modal = new bootstrap.Modal(document.getElementById('credentialsModal'));
                        modal.show();
                        
                        // Reload page when modal is closed
                        document.getElementById('credentialsModal').addEventListener('hidden.bs.modal', function() {
                            location.reload();
                        }, { once: true });
                    } else {
                        alert('Error: ' + (data.error || 'Failed to create account'));
                        this.disabled = false;
                        this.innerHTML = '<i class="bi bi-plus-circle"></i>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while creating the account');
                    this.disabled = false;
                    this.innerHTML = '<i class="bi bi-plus-circle"></i>';
                });
        });
    });
});

function copyToClipboard(elementId) {
    const element = document.getElementById(elementId);
    element.select();
    document.execCommand('copy');
    
    // Show feedback
    const button = event.target.closest('button');
    const originalHTML = button.innerHTML;
    button.innerHTML = '<i class="bi bi-check"></i> Copied!';
    setTimeout(() => {
        button.innerHTML = originalHTML;
    }, 2000);
}
</script>

<?php require_once 'includes/footer.php'; ?>
