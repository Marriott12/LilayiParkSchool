<?php
require_once 'includes/bootstrap.php';
require_once 'includes/Auth.php';
require_once 'includes/PermissionHelper.php';

Auth::requireLogin();

// Check permission via RBAC
require_once 'modules/roles/RolesModel.php';
$rolesModel = new RolesModel();
if (!$rolesModel->userHasPermission(Auth::id(), 'view_teachers')) {
    Session::setFlash('error', 'You do not have permission to view teachers.');
    header('Location: /LilayiParkSchool/403.php');
    exit;
}

require_once 'modules/teachers/TeacherModel.php';

$teacherModel = new TeacherModel();

// Handle search and pagination
$searchTerm = $_GET['search'] ?? '';
$page = $_GET['page'] ?? 1;
$perPage = 20;

if ($searchTerm) {
    $allTeachers = $teacherModel->search($searchTerm);
    $totalRecords = count($allTeachers);
    $pagination = new Pagination($totalRecords, $perPage, $page);
    $teachers = array_slice($allTeachers, $pagination->getOffset(), $pagination->getLimit());
} else {
    $totalRecords = $teacherModel->count();
    $pagination = new Pagination($totalRecords, $perPage, $page);
    $teachers = $teacherModel->all(null, $pagination->getLimit(), $pagination->getOffset());
}

$pageTitle = 'Teachers Management';
$currentPage = 'teachers';
require_once 'includes/header.php';
?>

<!-- Page Header -->
<div class="row mb-4">
    <div class="col-md-6">
        <h2 class="mb-0">
            <i class="bi bi-person-workspace me-2" style="color: #2d5016;"></i>
            <span style="color: #2d5016; font-weight: 600;">Teachers Management</span>
        </h2>
        <p class="text-muted mt-1">Manage teaching staff and their information</p>
    </div>
    <div class="col-md-6 text-end align-self-center">
        <?php if (PermissionHelper::canManage('teachers')): ?>
        <a href="teachers_form.php" class="btn btn-lg shadow-sm" style="background-color: #2d5016; color: white;">
            <i class="bi bi-plus-circle me-1"></i> Add New Teacher
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
                        <p class="text-muted mb-1 small">Total Teachers</p>
                        <h3 class="mb-0" style="color: #2d5016;"><?= $totalRecords ?></h3>
                    </div>
                    <div class="bg-light rounded-circle p-3">
                        <i class="bi bi-person-workspace" style="font-size: 1.5rem; color: #2d5016;"></i>
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
                        <h3 class="mb-0 text-success"><?= count($teachers) ?></h3>
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
                           placeholder="Start typing to search by name, email, phone, TCZ, or NRC..." 
                           value="<?= htmlspecialchars($searchTerm) ?>"
                           autocomplete="off">
                </div>
                <small class="text-muted">Results update as you type</small>
            </div>
        </div>
    </div>
</div>

<!-- Teachers Table -->
<div class="card border-0 shadow-sm" id="resultsTable">
    <div class="card-header bg-white border-bottom">
        <h5 class="mb-0">
            <i class="bi bi-list-ul me-2"></i>Teachers List
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
                            <i class="bi bi-award me-1"></i>TCZ Number
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
                    <?php if (empty($teachers)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-5">
                            <div class="text-center">
                                <i class="bi bi-people" style="font-size: 4rem; color: #dee2e6;"></i>
                                <h5 class="text-muted mt-3">No Teachers Found</h5>
                                <p class="text-muted mb-3">
                                    <?php if ($searchTerm): ?>
                                        No teachers match your search criteria. Try a different search term.
                                    <?php else: ?>
                                        Start by adding your first teacher to the system.
                                    <?php endif; ?>
                                </p>
                                <?php if (PermissionHelper::canManage('teachers') && !$searchTerm): ?>
                                <a href="teachers_form.php" class="btn btn-sm" style="background-color: #2d5016; color: white;">
                                    <i class="bi bi-plus-circle me-1"></i> Add First Teacher
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($teachers as $teacher): ?>
                    <tr class="align-middle">
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="rounded-circle bg-light d-flex align-items-center justify-content-center me-2" 
                                     style="width: 40px; height: 40px;">
                                    <i class="bi bi-person-circle" style="font-size: 1.5rem; color: #2d5016;"></i>
                                </div>
                                <div>
                                    <strong class="d-block"><?= htmlspecialchars($teacher['fName'] . ' ' . $teacher['lName']) ?></strong>
                                    <small class="text-muted"><?= htmlspecialchars($teacher['SSN'] ?? 'N/A') ?></small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <i class="bi bi-envelope text-muted me-1"></i>
                            <a href="mailto:<?= htmlspecialchars($teacher['email']) ?>" class="text-decoration-none">
                                <?= htmlspecialchars($teacher['email']) ?>
                            </a>
                        </td>
                        <td>
                            <i class="bi bi-telephone text-muted me-1"></i>
                            <a href="tel:<?= htmlspecialchars($teacher['phone'] ?? '') ?>" class="text-decoration-none">
                                <?= htmlspecialchars($teacher['phone'] ?? 'N/A') ?>
                            </a>
                        </td>
                        <td>
                            <span class="badge bg-secondary"><?= htmlspecialchars($teacher['tczNo'] ?? 'Not Set') ?></span>
                        </td>
                        <td class="text-center">
                            <?php if (!empty($teacher['userID'])): ?>
                                <?php if ($teacher['userIsActive'] === 'Y'): ?>
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
                                <?php if (PermissionHelper::canManage('teachers')): ?>
                                <br>
                                <button type="button" 
                                        class="btn btn-sm btn-success mt-1 create-account-btn" 
                                        data-teacher-id="<?= $teacher['teacherID'] ?>"
                                        data-teacher-name="<?= htmlspecialchars($teacher['fName'] . ' ' . $teacher['lName']) ?>"
                                        title="Create Account">
                                    <i class="bi bi-plus-circle me-1"></i>Create Account
                                </button>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm" role="group">
                                <a href="teachers_view.php?id=<?= $teacher['teacherID'] ?>" 
                                   class="btn btn-outline-info" title="View Details">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <?php if (PermissionHelper::canManage('teachers')): ?>
                                <a href="teachers_form.php?id=<?= $teacher['teacherID'] ?>" 
                                   class="btn btn-outline-warning" title="Edit Teacher">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <?php if (!empty($teacher['userID'])): ?>
                                <a href="teachers_reset_password.php?id=<?= $teacher['teacherID'] ?>" 
                                   class="btn btn-outline-primary" 
                                   title="Reset Password"
                                   onclick="return confirm('Reset password for <?= htmlspecialchars($teacher['fName'] . ' ' . $teacher['lName']) ?>?');">
                                    <i class="bi bi-key"></i>
                                </a>
                                <?php endif; ?>
                                <a href="delete.php?module=teachers&id=<?= $teacher['teacherID'] ?>" 
                                   class="btn btn-outline-danger" 
                                   title="Delete Teacher"
                                   onclick="return confirm('Are you sure you want to delete <?= htmlspecialchars($teacher['fName'] . ' ' . $teacher['lName']) ?>? This action cannot be undone.');">
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
    <div class="card-footer bg-white border-top">
        <div class="d-flex justify-content-between align-items-center">
            <div class="text-muted small">
                Showing <?= $pagination->getOffset() + 1 ?> to 
                <?= min($pagination->getOffset() + $perPage, $totalRecords) ?> 
                of <?= $totalRecords ?> teachers
            </div>
            <div>
                <?= $pagination->render() ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
    .table tbody tr {
        transition: all 0.2s ease;
    }
    .table tbody tr:hover {
        background-color: #f8f9fa;
        transform: translateY(-2px);
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    .btn-group .btn {
        transition: all 0.2s ease;
    }
    .btn-group .btn:hover {
        transform: translateY(-1px);
    }
    .card {
        transition: all 0.3s ease;
    }
    .card:hover {
        transform: translateY(-2px);
    }
</style>

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
            const teacherID = this.dataset.teacherId;
            const teacherName = this.dataset.teacherName;
            
            if (!confirm(`Create account for ${teacherName}?`)) {
                return;
            }
            
            // Disable button and show loading
            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span>';
            
            // Make AJAX request
            fetch(`teachers_create_account.php?id=${teacherID}`)
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
        apiEndpoint: '/LilayiParkSchool/api/search_teachers.php',
        emptyMessage: 'No teachers found',
        debounceDelay: 300,
        renderRow: function(teacher) {
            const hasAccount = teacher.userID;
            const isActive = teacher.userIsActive === 'Y';
            const fullName = `${teacher.fName} ${teacher.lName}`;
            
            let accountBadge = '';
            if (hasAccount) {
                accountBadge = isActive 
                    ? '<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Active Account</span>'
                    : '<span class="badge bg-warning"><i class="bi bi-exclamation-triangle me-1"></i>Inactive</span>';
            } else {
                accountBadge = '<span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>No Account</span>';
            }
            
            return `
                <tr>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="avatar-circle me-2 bg-primary text-white">
                                ${teacher.fName.charAt(0)}${teacher.lName.charAt(0)}
                            </div>
                            <div>
                                <div class="fw-bold">${escapeHtml(fullName)}</div>
                                <small class="text-muted">ID: ${escapeHtml(teacher.teacherID)}</small>
                            </div>
                        </div>
                    </td>
                    <td>
                        <i class="bi bi-envelope text-muted me-1"></i>
                        <a href="mailto:${escapeHtml(teacher.email || '')}" class="text-decoration-none">
                            ${escapeHtml(teacher.email || 'N/A')}
                        </a>
                    </td>
                    <td>
                        <i class="bi bi-telephone text-muted me-1"></i>
                        <a href="tel:${escapeHtml(teacher.phone || '')}" class="text-decoration-none">
                            ${escapeHtml(teacher.phone || 'N/A')}
                        </a>
                    </td>
                    <td>
                        <span class="badge bg-secondary">${escapeHtml(teacher.tczNo || 'Not Set')}</span>
                    </td>
                    <td class="text-center">${accountBadge}</td>
                    <td class="text-center">
                        <div class="btn-group btn-group-sm" role="group">
                            <a href="teachers_view.php?id=${teacher.teacherID}" 
                               class="btn btn-outline-info" title="View Details">
                                <i class="bi bi-eye"></i>
                            </a>
                            <?php if (PermissionHelper::canManage('teachers')): ?>
                            <a href="teachers_form.php?id=${teacher.teacherID}" 
                               class="btn btn-outline-warning" title="Edit Teacher">
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
