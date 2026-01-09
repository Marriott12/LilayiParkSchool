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
    <?php if (PermissionHelper::canManage('pupils')): ?>
    <a href="pupils_form.php" class="btn btn-sm" style="background-color: #2d5016; color: white;">
        <i class="bi bi-plus-circle"></i> Add New Pupil
    </a>
    <?php endif; ?>
</div>

<!-- Search Bar -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-10">
                <input type="text" class="form-control" name="search" placeholder="Search by name or student number..." value="<?= htmlspecialchars($searchTerm) ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn w-100" style="background-color: #2d5016; color: white;">
                    <i class="bi bi-search"></i> Search
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Pupils Table -->
<div class="card">
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
                        <td><?= htmlspecialchars($pupil['studentNumber']) ?></td>
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

<?php require_once 'includes/footer.php'; ?>
