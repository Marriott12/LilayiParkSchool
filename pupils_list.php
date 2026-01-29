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
    header('Location: 403.php');
    exit;
}

require_once 'modules/pupils/PupilModel.php';

$pupilModel = new PupilModel();

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
        $totalRecords = count($accessiblePupilIDs);
        $pagination = new Pagination($totalRecords, $perPage, $page);
        $pupils = $pupilModel->getByIDs($accessiblePupilIDs, $pagination->getLimit(), $pagination->getOffset());
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
                        <th>Name</th>
                        <th>Date of Birth</th>
                        <th>Gender</th>
                        <th>Parent / Guardian</th>
                        <th>Enrollment Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($pupils)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4">
                            <i class="bi bi-inbox" style="font-size: 3rem; opacity: 0.3;"></i>
                            <p class="text-muted mt-2">No pupils found</p>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($pupils as $pupil): ?>
                    <tr>
                        <td>
                            <strong><?= ucwords(strtolower(htmlspecialchars($pupil['fName'] . ' ' . $pupil['lName']))) ?></strong>
                        </td>
                        <td><?= !empty($pupil['DoB']) ? date('M d, Y', strtotime($pupil['DoB'])) : 'N/A' ?></td>
                        <td><?= htmlspecialchars($pupil['gender']) ?></td>
                        <td>
                            <?php
                            $parentName = htmlspecialchars($pupil['parent1'] ?? 'N/A');
                            $parentPhone = $pupil['phone'] ?? '';
                            $parentEmail = $pupil['parentEmail'] ?? '';
                            ?>
                            <div class="fw-bold"><?= $parentName ?></div>
                            <?php if ($parentPhone || $parentEmail): ?>
                                <div class="small text-muted">
                                    <?php if ($parentPhone): ?>
                                        <i class="bi bi-telephone me-1"></i><?= htmlspecialchars($parentPhone) ?>
                                    <?php endif; ?>
                                    <?php if ($parentPhone && $parentEmail): ?> &nbsp;&bull;&nbsp; <?php endif; ?>
                                    <?php if ($parentEmail): ?>
                                        <i class="bi bi-envelope me-1"></i><?= htmlspecialchars($parentEmail) ?>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td><?= !empty($pupil['enrollDate']) ? date('M d, Y', strtotime($pupil['enrollDate'])) : 'N/A' ?></td>
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
        apiEndpoint: 'api/search_pupils.php',
        emptyMessage: 'No pupils found',
        debounceDelay: 300,
            renderRow: function(pupil) {
                const fullName = `${pupil.fName || ''} ${pupil.lName || ''}`.trim();
                const dob = pupil.DoB ? new Date(pupil.DoB).toLocaleDateString() : 'N/A';
                const parentName = pupil.parent1 || pupil.parentName || (pupil.parent && (pupil.parent.fName + ' ' + pupil.parent.lName)) || 'No Parent';
                const phone = pupil.phone || pupil.parentPhone || (pupil.parent && pupil.parent.phone) || '';
                const email = pupil.parentEmail || pupil.parentEmail || (pupil.parent && (pupil.parent.email1 || pupil.parent.email)) || '';
                const enroll = pupil.enrollDate ? new Date(pupil.enrollDate).toLocaleDateString() : 'N/A';

                return `
                    <tr>
                        <td><strong>${escapeHtml(fullName || pupil.pupilID)}</strong></td>
                        <td>${escapeHtml(dob)}</td>
                        <td>
                            ${pupil.gender === 'M' 
                                ? '<span class="badge bg-primary">Male</span>' 
                                : (pupil.gender === 'F' ? '<span class="badge bg-danger">Female</span>' : 'N/A')}
                        </td>
                        <td>
                            <div class="fw-bold">${escapeHtml(parentName)}</div>
                            ${ (phone || email) ? `<div class="small text-muted">${ phone ? '<i class="bi bi-telephone me-1"></i>' + escapeHtml(phone) : '' } ${ (phone && email) ? '&nbsp;&bull;&nbsp;' : '' } ${ email ? '<i class="bi bi-envelope me-1"></i>' + escapeHtml(email) : '' }</div>` : '' }
                        </td>
                        <td>${escapeHtml(enroll)}</td>
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
