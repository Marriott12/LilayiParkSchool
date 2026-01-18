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

// Get accessible parent IDs based on user role
$accessibleParentIDs = Auth::getAccessibleParentIDs();

// Handle search and pagination
$searchTerm = $_GET['search'] ?? '';
$page = $_GET['page'] ?? 1;
$perPage = 20;

if ($searchTerm) {
    $allParents = $parentModel->search($searchTerm);
    
    // Filter by accessible parents if not admin/teacher
    if ($accessibleParentIDs !== null) {
        $allParents = array_filter($allParents, function($parent) use ($accessibleParentIDs) {
            return in_array($parent['parentID'], $accessibleParentIDs);
        });
    }
    
    $totalRecords = count($allParents);
    $pagination = new Pagination($totalRecords, $perPage, $page);
    $parents = array_slice($allParents, $pagination->getOffset(), $pagination->getLimit());
} else {
    // Filter parents based on user context
    if ($accessibleParentIDs === null) {
        // Admin/Teacher - all parents
        $totalRecords = $parentModel->count();
        $pagination = new Pagination($totalRecords, $perPage, $page);
        $parents = $parentModel->getAllWithChildrenCount($pagination->getLimit(), $pagination->getOffset());
    } elseif (empty($accessibleParentIDs)) {
        // No accessible parents
        $totalRecords = 0;
        $pagination = new Pagination($totalRecords, $perPage, $page);
        $parents = [];
    } else {
        // Parent viewing themselves only
        $parents = [];
        foreach ($accessibleParentIDs as $parentID) {
            $parent = $parentModel->getParentWithUser($parentID);
            if ($parent) {
                $parents[] = $parent;
            }
        }
        $totalRecords = count($parents);
        $pagination = new Pagination($totalRecords, $perPage, $page);
    }
}

$pageTitle = 'Parents Management';
$currentPage = 'parents';
require_once 'includes/header.php';
?>

<!-- Page Header -->
<div class="row mb-4">
    <div class="col-md-6">
        <h2 class="mb-0">
            <i class="bi bi-people me-2" style="color: #2d5016;"></i>
            <span style="color: #2d5016; font-weight: 600;">Parents Management</span>
        </h2>
        <p class="text-muted mt-1">Manage parents/guardians and their information</p>
    </div>
    <div class="col-md-6 text-end align-self-center">
        <?php if (PermissionHelper::canManage('parents')): ?>
        <a href="parents_form.php" class="btn btn-lg shadow-sm" style="background-color: #2d5016; color: white;">
            <i class="bi bi-plus-circle me-1"></i> Add New Parent
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- Stats Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1 small">Total Parents</p>
                        <h3 class="mb-0" style="color: #2d5016;"><?= $totalRecords ?></h3>
                    </div>
                    <div class="bg-light rounded-circle p-3">
                        <i class="bi bi-people" style="font-size: 1.5rem; color: #2d5016;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1 small">Active</p>
                        <h3 class="mb-0 text-success"><?= count($parents) ?></h3>
                    </div>
                    <div class="bg-light rounded-circle p-3">
                        <i class="bi bi-check-circle" style="font-size: 1.5rem; color: #5cb85c;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Search and Filter Bar -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="row g-3 align-items-center">
            <div class="col-md-12">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" class="form-control border-start-0 ps-0" id="liveSearchInput" 
                           placeholder="Start typing to search by name, email, phone, NRC, or parent ID..." 
                           value="<?= htmlspecialchars($searchTerm) ?>"
                           autocomplete="off">
                </div>
                <small class="text-muted">Results update as you type</small>
            </div>
        </div>
    </div>
</div>

<!-- Parents Table -->
<div class="card border-0 shadow-sm" id="resultsTable">
    <div class="card-header bg-white border-bottom">
        <h5 class="mb-0">
            <i class="bi bi-list-ul me-2"></i>Parents List
        </h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="border-0">
                            <i class="bi bi-person me-1"></i>Name
                        </th>
                        <th class="border-0">
                            <i class="bi bi-envelope me-1"></i>Email
                        </th>
                        <th class="border-0">
                            <i class="bi bi-telephone me-1"></i>Phone
                        </th>
                        <th class="border-0">
                            <i class="bi bi-people me-1"></i>Children
                        </th>
                        <th class="border-0 text-center">
                            <i class="bi bi-info-circle me-1"></i>Status
                        </th>
                        <th class="border-0 text-center">
                            <i class="bi bi-gear me-1"></i>Actions
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($parents)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-5">
                            <div class="text-center">
                                <i class="bi bi-people" style="font-size: 4rem; color: #dee2e6;"></i>
                                <h5 class="text-muted mt-3">No Parents Found</h5>
                                <p class="text-muted mb-3">
                                    <?php if ($searchTerm): ?>
                                        No parents match your search criteria. Try a different search term.
                                    <?php else: ?>
                                        Start by adding your first parent to the system.
                                    <?php endif; ?>
                                </p>
                                <?php if (PermissionHelper::canManage('parents') && !$searchTerm): ?>
                                <a href="parents_form.php" class="btn btn-sm" style="background-color: #2d5016; color: white;">
                                    <i class="bi bi-plus-circle me-1"></i> Add First Parent
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($parents as $parent): ?>
                    <tr class="align-middle">
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="rounded-circle bg-light d-flex align-items-center justify-content-center me-2" 
                                     style="width: 40px; height: 40px;">
                                    <i class="bi bi-person-circle" style="font-size: 1.5rem; color: #2d5016;"></i>
                                </div>
                                <div>
                                    <strong class="d-block"><?= htmlspecialchars($parent['fName'] . ' ' . $parent['lName']) ?></strong>
                                    <small class="text-muted"><?= htmlspecialchars($parent['relation'] ?? 'N/A') ?></small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <i class="bi bi-envelope text-muted me-1"></i>
                            <a href="mailto:<?= htmlspecialchars($parent['email1']) ?>" class="text-decoration-none">
                                <?= htmlspecialchars($parent['email1']) ?>
                            </a>
                        </td>
                        <td>
                            <i class="bi bi-telephone text-muted me-1"></i>
                            <a href="tel:<?= htmlspecialchars($parent['phone']) ?>" class="text-decoration-none">
                                <?= htmlspecialchars($parent['phone']) ?>
                            </a>
                        </td>
                        <td>
                            <span class="badge" style="background-color: #f0ad4e;">
                                <i class="bi bi-people-fill me-1"></i><?= $parent['childrenCount'] ?? 0 ?> child(ren)
                            </span>
                        </td>
                        <td class="text-center">
                            <?php if (!empty($parent['userID'])): ?>
                                <?php if ($parent['userIsActive'] === 'Y'): ?>
                                    <span class="badge bg-success">
                                        <i class="bi bi-check-circle me-1"></i>Active Account
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark">
                                        <i class="bi bi-exclamation-triangle me-1"></i>Inactive
                                    </span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="badge bg-secondary">
                                    <i class="bi bi-x-circle me-1"></i>No Account
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm" role="group">
                                <a href="parents_view.php?id=<?= $parent['parentID'] ?>" class="btn btn-outline-info" title="View Details">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <?php if (PermissionHelper::canManage('parents')): ?>
                                <?php if (empty($parent['userID'])): ?>
                                <a href="parents_create_account.php?id=<?= $parent['parentID'] ?>" 
                                   class="btn btn-outline-success" 
                                   title="Create Account">
                                    <i class="bi bi-person-plus"></i>
                                </a>
                                <?php endif; ?>
                                <a href="parents_form.php?id=<?= $parent['parentID'] ?>" class="btn btn-outline-warning" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="delete.php?module=parents&id=<?= $parent['parentID'] ?>" 
                                   class="btn btn-outline-danger" 
                                   title="Delete"
                                   onclick="return confirm('Are you sure you want to delete this parent?');">
                                    <i class="bi bi-trash"></i>
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
                
                <div id="emailStatusDiv" style="display: none;">
                    <hr>
                    <div id="emailStatusMessage"></div>
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
                        
                        // Show email status if available
                        if (data.emailSent !== undefined) {
                            const emailStatusDiv = document.getElementById('emailStatusDiv');
                            const emailStatusMessage = document.getElementById('emailStatusMessage');
                            
                            if (data.emailSent) {
                                emailStatusMessage.innerHTML = `
                                    <div class="alert alert-success mb-0">
                                        <i class="bi bi-envelope-check me-2"></i>
                                        <strong>Email Sent!</strong> Login credentials have been sent to ${data.email}
                                    </div>
                                `;
                            } else {
                                emailStatusMessage.innerHTML = `
                                    <div class="alert alert-warning mb-0">
                                        <i class="bi bi-exclamation-triangle me-2"></i>
                                        <strong>Email Not Sent:</strong> ${data.emailMessage || 'Email sending is disabled in settings'}
                                    </div>
                                `;
                            }
                            
                            emailStatusDiv.style.display = 'block';
                        }
                        
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

<script src="assets/js/live-search.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    new LiveSearch({
        searchInput: '#liveSearchInput',
        resultsContainer: '#resultsTable',
        apiEndpoint: '/LilayiParkSchool/api/search_parents.php',
        emptyMessage: 'No parents found',
        debounceDelay: 300,
        renderRow: function(parent) {
            const hasAccount = parent.userID;
            const isActive = parent.userIsActive === 'Y';
            const fullName = `${parent.fName} ${parent.lName}`;
            const childrenCount = parent.childrenCount || 0;
            
            let accountBadge = '';
            if (hasAccount) {
                accountBadge = isActive 
                    ? '<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Active</span>'
                    : '<span class="badge bg-warning"><i class="bi bi-exclamation-triangle me-1"></i>Inactive</span>';
            } else {
                accountBadge = '<span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>No Account</span>';
            }
            
            return `
                <tr>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="avatar-circle me-2 bg-primary text-white">
                                ${parent.fName.charAt(0)}${parent.lName.charAt(0)}
                            </div>
                            <div>
                                <div class="fw-bold">${escapeHtml(fullName)}</div>
                                <small class="text-muted">ID: ${escapeHtml(parent.parentID)}</small>
                            </div>
                        </div>
                    </td>
                    <td>
                        <i class="bi bi-envelope text-muted me-1"></i>
                        ${escapeHtml(parent.email1 || 'N/A')}<br>
                        ${parent.email2 ? '<small class="text-muted">' + escapeHtml(parent.email2) + '</small>' : ''}
                    </td>
                    <td>
                        <i class="bi bi-telephone text-muted me-1"></i>
                        ${escapeHtml(parent.phone || 'N/A')}
                    </td>
                    <td class="text-center">
                        <span class="badge bg-info">${childrenCount} ${childrenCount === 1 ? 'child' : 'children'}</span>
                    </td>
                    <td class="text-center">${accountBadge}</td>
                    <td class="text-center">
                        <div class="btn-group btn-group-sm" role="group">
                            <a href="parents_view.php?id=${parent.parentID}" 
                               class="btn btn-outline-info" title="View Details">
                                <i class="bi bi-eye"></i>
                            </a>
                            <?php if (PermissionHelper::canManage('parents')): ?>
                            <a href="parents_form.php?id=${parent.parentID}" 
                               class="btn btn-outline-warning" title="Edit Parent">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            `;
        }
    });
    
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
