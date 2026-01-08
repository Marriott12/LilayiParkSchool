<?php
require_once 'includes/bootstrap.php';

RBAC::requireAuth();
RBAC::requirePermission('pupils', 'read');

require_once 'modules/pupils/PupilModel.php';
require_once 'modules/parents/ParentModel.php';

$pupilModel = new PupilModel();
$parentModel = new ParentModel();

// Handle search
$searchTerm = $_GET['search'] ?? '';
$pupils = $searchTerm ? $pupilModel->search($searchTerm) : $pupilModel->getAllWithParents();

$pageTitle = 'Pupils Management';
$currentPage = 'pupils';
require_once 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-people-fill"></i> Pupils</h2>
    <?php if (RBAC::hasPermission(Session::getUserRole(), 'pupils', 'create')): ?>
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
                        <td><?= date('M d, Y', strtotime($pupil['dateOfBirth'])) ?></td></td>
                        <td><?= htmlspecialchars($pupil['gender']) ?></td>
                        <td>
                            <?= htmlspecialchars(($pupil['parentFirstName'] ?? '') . ' ' . ($pupil['parentLastName'] ?? '')) ?>
                        </td>
                        <td><?= htmlspecialchars($pupil['address'] ?? 'N/A') ?></td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="pupils_view.php?id=<?= $pupil['pupilID'] ?>" class="btn btn-info" title="View">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <?php if (RBAC::hasPermission('pupils', 'update', null)): ?>
                                <a href="pupils_form.php?id=<?= $pupil['pupilID'] ?>" class="btn btn-warning" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <?php endif; ?>
                                <?php if (RBAC::hasPermission('pupils', 'delete', null)): ?>
                                <a href="pupils_delete.php?id=<?= $pupil['pupilID'] ?>" class="btn btn-danger" title="Delete" onclick="return confirm('Are you sure?')">
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
</div>

<?php require_once 'includes/footer.php'; ?>
