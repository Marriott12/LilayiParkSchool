<?php
require_once 'includes/bootstrap.php';
require_once 'includes/Auth.php';
require_once 'includes/PermissionHelper.php';

Auth::requireLogin();

require_once 'modules/subjects/SubjectsModel.php';

$subjectsModel = new SubjectsModel();

// Handle search and pagination
$searchTerm = $_GET['search'] ?? '';
$page = $_GET['page'] ?? 1;
$perPage = 20;

if ($searchTerm) {
    $allSubjects = $subjectsModel->search($searchTerm);
    $totalRecords = count($allSubjects);
    $pagination = new Pagination($totalRecords, $perPage, $page);
    $subjects = array_slice($allSubjects, $pagination->getOffset(), $pagination->getLimit());
} else {
    $totalRecords = $subjectsModel->count();
    $pagination = new Pagination($totalRecords, $perPage, $page);
    $subjects = $subjectsModel->getAllWithTeachers($pagination->getLimit(), $pagination->getOffset());
}

$pageTitle = 'Subjects Management';
$currentPage = 'subjects';
require_once 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-book-fill"></i> Subjects</h2>
    <?php if (PermissionHelper::canManage('subjects')): ?>
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
                            <div class="btn-group btn-group-sm" role="group">
                                <a href="subjects_view.php?id=<?= $subject['subjectID'] ?>" class="btn btn-outline-info btn-sm">
                                    <i class="bi bi-eye"></i> View
                                </a>
                                <?php if (PermissionHelper::canManage('subjects')): ?>
                                <a href="subjects_form.php?id=<?= $subject['subjectID'] ?>" class="btn btn-outline-warning btn-sm">
                                    <i class="bi bi-pencil"></i> Edit
                                </a>
                                <a href="delete.php?module=subjects&id=<?= $subject['subjectID'] ?>" 
                                   class="btn btn-outline-danger btn-sm" 
                                   onclick="return confirm('Are you sure you want to delete this subject?');">
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

<?php require_once 'includes/footer.php'; ?>
