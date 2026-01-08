<?php
require_once 'includes/bootstrap.php';

RBAC::requireAuth();
RBAC::requirePermission('subjects', 'read');

require_once 'modules/subjects/SubjectsModel.php';

$subjectsModel = new SubjectsModel();

// Handle search
$searchTerm = $_GET['search'] ?? '';
$subjects = $searchTerm ? $subjectsModel->search($searchTerm) : $subjectsModel->getAllWithTeachers();

$pageTitle = 'Subjects Management';
$currentPage = 'subjects';
require_once 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-book-fill"></i> Subjects</h2>
    <?php if (RBAC::hasPermission(Session::getUserRole(), 'subjects', 'create')): ?>
    <a href="subjects_form.php" class="btn btn-sm" style="background-color: #2d5016; color: white;">
        <i class="bi bi-plus-circle"></i> Add New Subject
    </a>
    <?php endif; ?>
</div>

<!-- Search Bar -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-10">
                <input type="text" class="form-control" name="search" placeholder="Search by subject name or code..." value="<?= htmlspecialchars($searchTerm) ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn w-100" style="background-color: #2d5016; color: white;">
                    <i class="bi bi-search"></i> Search
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Subjects Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead style="background-color: #f8f9fa;">
                    <tr>
                        <th>Subject Code</th>
                        <th>Subject Name</th>
                        <th>Teacher</th>
                        <th>Credits</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($subjects)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-4">
                            <i class="bi bi-inbox" style="font-size: 3rem; opacity: 0.3;"></i>
                            <p class="text-muted mt-2">No subjects found</p>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($subjects as $subject): ?>
                    <tr>
                        <td><span class="badge" style="background-color: #2d5016;"><?= htmlspecialchars($subject['subjectCode'] ?? 'N/A') ?></span></td>
                        <td><strong><?= htmlspecialchars($subject['subjectName']) ?></strong></td>
                        <td><?= htmlspecialchars(($subject['teacherFirstName'] ?? '') . ' ' . ($subject['teacherLastName'] ?? 'Not Assigned')) ?></td>
                        <td><?= $subject['credits'] ?? 1 ?></td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="subjects_view.php?id=<?= $subject['subjectID'] ?>" class="btn btn-info" title="View">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <?php if (RBAC::hasPermission(Session::getUserRole(), 'subjects', 'update')): ?>
                                <a href="subjects_form.php?id=<?= $subject['subjectID'] ?>" class="btn btn-warning" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <?php endif; ?>
                                <?php if (RBAC::hasPermission(Session::getUserRole(), 'subjects', 'delete')): ?>
                                <a href="delete.php?module=subjects&id=<?= $subject['subjectID'] ?>" class="btn btn-danger" title="Delete" onclick="return confirm('Are you sure?')">
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
