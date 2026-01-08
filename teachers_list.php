<?php
require_once 'includes/bootstrap.php';

RBAC::requireAuth();
RBAC::requirePermission('teachers', 'read');

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

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-person-workspace"></i> Teachers</h2>
    <?php if (RBAC::hasPermission(Session::getUserRole(), 'teachers', 'create')): ?>
    <a href="teachers_form.php" class="btn btn-sm" style="background-color: #2d5016; color: white;">
        <i class="bi bi-plus-circle"></i> Add New Teacher
    </a>
    <?php endif; ?>
</div>

<!-- Search Bar -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-10">
                <input type="text" class="form-control" name="search" placeholder="Search by name or email..." value="<?= htmlspecialchars($searchTerm) ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn w-100" style="background-color: #2d5016; color: white;">
                    <i class="bi bi-search"></i> Search
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Teachers Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead style="background-color: #f8f9fa;">
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>TCZ Number</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($teachers)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4">
                            <i class="bi bi-inbox" style="font-size: 3rem; opacity: 0.3;"></i>
                            <p class="text-muted mt-2">No teachers found</p>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($teachers as $teacher): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($teacher['fName'] . ' ' . $teacher['lName']) ?></strong>
                        </td>
                        <td><?= htmlspecialchars($teacher['email']) ?></td>
                        <td><?= htmlspecialchars($teacher['phone'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($teacher['tczNo'] ?? 'N/A') ?></td>
                        <td>
                            <span class="badge" style="background-color: #5cb85c;">Active</span>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="teachers_view.php?id=<?= $teacher['teacherID'] ?>" class="btn btn-info btn-sm" title="View">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <?php if (RBAC::hasPermission(Session::getUserRole(), 'teachers', 'update')): ?>
                                <a href="teachers_form.php?id=<?= $teacher['teacherID'] ?>" class="btn btn-warning btn-sm" title="Edit">
                                    <i class="bi bi-pencil"></i>
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

<?php require_once 'includes/footer.php'; ?>
