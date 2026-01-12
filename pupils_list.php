<?php
require_once 'includes/bootstrap.php';
require_once 'includes/Auth.php';
require_once 'includes/PermissionHelper.php';

Auth::requireLogin();

// Check permission via RBAC
require_once 'modules/roles/RolesModel.php';
$rolesModel = new RolesModel();
if (!$rolesModel->userHasPermission(Auth::id(), 'view_pupils')) {
    Session::setFlash('error', 'You do not have permission to view pupils.');
    header('Location: /LilayiParkSchool/403.php');
    exit;
}

require_once 'modules/pupils/PupilModel.php';
require_once 'modules/parents/ParentModel.php';

$pupilModel = new PupilModel();
$parentModel = new ParentModel();

// Handle search and pagination
$searchTerm = $_GET['search'] ?? '';
$page = $_GET['page'] ?? 1;
$perPage = 20;

// Get accessible pupil IDs based on user role
$accessiblePupilIDs = Auth::getAccessiblePupilIDs();

if ($searchTerm) {
    $allPupils = $pupilModel->search($searchTerm);
    
    // Filter by accessible pupils for teachers/parents
    if ($accessiblePupilIDs !== null) {
        $allPupils = array_filter($allPupils, function($pupil) use ($accessiblePupilIDs) {
            return in_array($pupil['pupilID'], $accessiblePupilIDs);
        });
    }
    
    $totalRecords = count($allPupils);
    $pagination = new Pagination($totalRecords, $perPage, $page);
    $pupils = array_slice($allPupils, $pagination->getOffset(), $pagination->getLimit());
} else {
    // Filter pupils based on user context
    if ($accessiblePupilIDs === null) {
        // Admin - all pupils
        $totalRecords = $pupilModel->count();
        $pagination = new Pagination($totalRecords, $perPage, $page);
        $pupils = $pupilModel->getAllWithParents($pagination->getLimit(), $pagination->getOffset());
    } elseif (empty($accessiblePupilIDs)) {
        // No accessible pupils
        $totalRecords = 0;
        $pagination = new Pagination($totalRecords, $perPage, $page);
        $pupils = [];
    } else {
        // Teachers or parents - filtered pupils
        $pupils = $pupilModel->getByIDs($accessiblePupilIDs, $pagination->getLimit(), $pagination->getOffset());
        $totalRecords = count($accessiblePupilIDs);
        $pagination = new Pagination($totalRecords, $perPage, $page);
    }
}

$pageTitle = 'Pupils Management';
$currentPage = 'pupils';
require_once 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2><i class="bi bi-people-fill"></i> Pupils
        <?php if (Auth::isTeacher()): ?>
            <small class="text-muted">(My Classes)</small>
        <?php elseif (Auth::isParent()): ?>
            <small class="text-muted">(My Children)</small>
        <?php endif; ?>
        </h2>
        <p class="text-muted mb-0">Viewing: <?= PermissionHelper::getContextDescription() ?></p>
    </div>
    <div>
        <!-- Export Buttons -->
        <div class="btn-group me-2" role="group">
            <a href="api/export_pupils.php?format=csv" class="btn btn-sm btn-outline-success" title="Export to CSV">
                <i class="bi bi-file-earmark-spreadsheet"></i> CSV
            </a>
            <a href="api/export_pupils.php?format=excel" class="btn btn-sm btn-outline-success" title="Export to Excel">
                <i class="bi bi-file-earmark-excel"></i> Excel
            </a>
        </div>
        <?php if (PermissionHelper::canManage('pupils')): ?>
        <a href="pupils_form.php" class="btn btn-sm" style="background-color: #2d5016; color: white;">
            <i class="bi bi-plus-circle"></i> Add New Pupil
        </a>
        <?php endif; ?>
    </div>
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
                           placeholder="Start typing to search by name, admission number, or pupil ID..." 
                           value="<?= htmlspecialchars($searchTerm) ?>"
                           autocomplete="off">
                </div>
                <small class="text-muted">Results update as you type</small>
            </div>
        </div>
    </div>
</div>

<!-- Pupils Table -->
<div class="card" id="resultsTable">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead style="background-color: #f8f9fa;">
                    <tr>
                        <th>Student #</th>
                        <th>Name</th>
                        <th>Date of Birth</th>
                        <th>Gender</th>
                        <th>Parent</th>
                        <th>Contact</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($pupils)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4">
                            <i class="bi bi-inbox" style="font-size: 3rem; opacity: 0.3;"></i>
                            <p class="text-muted mt-2">No pupils found</p>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($pupils as $pupil): ?>
                    <tr>
                        <td><?= htmlspecialchars($pupil['pupilID']) ?></td>
                        <td>
                            <strong><?= htmlspecialchars($pupil['fName'] . ' ' . $pupil['lName']) ?></strong>
                        </td>
                        <td><?= date('M d, Y', strtotime($pupil['dateOfBirth'])) ?></td>
                        <td><?= htmlspecialchars($pupil['gender']) ?></td>
                        <td>
                            <?= htmlspecialchars(($pupil['parentFirstName'] ?? '') . ' ' . ($pupil['parentLastName'] ?? '')) ?>
                        </td>
                        <td><?= htmlspecialchars($pupil['address'] ?? 'N/A') ?></td>
                        <td>
                            <div class="btn-group btn-group-sm" role="group">
                                <a href="pupils_view.php?id=<?= $pupil['pupilID'] ?>" class="btn btn-outline-info btn-sm" title="View Details">
                                    <i class="bi bi-eye"></i> View
                                </a>
                                <?php if (PermissionHelper::canManage('pupils')): ?>
                                <a href="pupils_form.php?id=<?= $pupil['pupilID'] ?>" class="btn btn-outline-warning btn-sm" title="Edit Pupil">
                                    <i class="bi bi-pencil"></i> Edit
                                </a>
                                <a href="delete.php?module=pupils&id=<?= $pupil['pupilID'] ?>" 
                                   class="btn btn-outline-danger btn-sm" 
                                   title="Delete Pupil"
                                   onclick="return confirm('Are you sure you want to delete this pupil?');">
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
        
        <!-- Pagination -->
        <?php if ($pagination->hasPages()): ?>
        <div class="card-footer">
            <?= $pagination->render() ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<script src="assets/js/live-search.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    new LiveSearch({
        searchInput: '#liveSearchInput',
        resultsContainer: '#resultsTable',
        apiEndpoint: '/LilayiParkSchool/api/search_pupils.php',
        emptyMessage: 'No pupils found',
        debounceDelay: 300,
        renderRow: function(pupil) {
            const fullName = `${pupil.fName} ${pupil.lName}`;
            const dob = pupil.DOB ? new Date(pupil.DOB).toLocaleDateString() : 'N/A';
            const parentName = (pupil.parentFirstName && pupil.parentLastName) 
                ? `${pupil.parentFirstName} ${pupil.parentLastName}` 
                : 'No Parent';
            const phone = pupil.parentPhone || pupil.phone || 'N/A';
            
            return `
                <tr>
                    <td><strong>${escapeHtml(pupil.admNo || pupil.pupilID)}</strong></td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="avatar-circle me-2 bg-primary text-white">
                                ${pupil.fName.charAt(0)}${pupil.lName.charAt(0)}
                            </div>
                            <div>
                                <div class="fw-bold">${escapeHtml(fullName)}</div>
                                <small class="text-muted">ID: ${escapeHtml(pupil.pupilID)}</small>
                            </div>
                        </div>
                    </td>
                    <td>${dob}</td>
                    <td>
                        ${pupil.gender === 'M' 
                            ? '<span class="badge bg-primary">Male</span>' 
                            : '<span class="badge bg-danger">Female</span>'}
                    </td>
                    <td>${escapeHtml(parentName)}</td>
                    <td>
                        <i class="bi bi-telephone text-muted me-1"></i>
                        ${escapeHtml(phone)}
                    </td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <a href="pupils_view.php?id=${pupil.pupilID}" class="btn btn-outline-primary" title="View">
                                <i class="bi bi-eye"></i>
                            </a>
                            <?php if (PermissionHelper::canManage('pupils')): ?>
                            <a href="pupils_form.php?id=${pupil.pupilID}" class="btn btn-outline-success" title="Edit">
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
