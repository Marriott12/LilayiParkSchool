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
        <div class="row g-3">
            <div class="col-md-12">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" class="form-control border-start-0 ps-0" id="liveSearchInput" 
                           placeholder="Start typing to search by subject name or code..." 
                           value="<?= htmlspecialchars($searchTerm) ?>"
                           autocomplete="off">
                </div>
                <small class="text-muted">Results update as you type</small>
            </div>
        </div>
    </div>
</div>

<!-- Subjects Table -->
<div class="card" id="resultsTable">
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

<script src="assets/js/live-search.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const canManage = <?= PermissionHelper::canManage('subjects') ? 'true' : 'false' ?>;
    
    new LiveSearch({
        searchInput: '#liveSearchInput',
        resultsContainer: '#resultsTable',
        apiEndpoint: 'api/search_subjects.php',
        emptyMessage: 'No subjects found',
        debounceDelay: 300,
        renderRow: function(subject) {
            const teacherName = (subject.teacherFirstName && subject.teacherLastName) 
                ? `${subject.teacherFirstName} ${subject.teacherLastName}`
                : 'Not Assigned';
            const credits = subject.credits || 1;
            const subjectCode = subject.subjectCode || 'N/A';
            
            return `
                <tr>
                    <td>
                        <span class="badge" style="background-color: #2d5016;">
                            ${escapeHtml(subjectCode)}
                        </span>
                    </td>
                    <td><strong>${escapeHtml(subject.subjectName)}</strong></td>
                    <td>${escapeHtml(teacherName)}</td>
                    <td>${credits}</td>
                    <td>
                        <div class="btn-group btn-group-sm" role="group">
                            <a href="subjects_view.php?id=${subject.subjectID}" class="btn btn-outline-info btn-sm">
                                <i class="bi bi-eye"></i> View
                            </a>
                            ${canManage ? `
                            <a href="subjects_form.php?id=${subject.subjectID}" class="btn btn-outline-warning btn-sm">
                                <i class="bi bi-pencil"></i> Edit
                            </a>
                            <a href="delete.php?module=subjects&id=${subject.subjectID}" 
                               class="btn btn-outline-danger btn-sm" 
                               onclick="return confirm('Are you sure you want to delete this subject?');">
                                <i class="bi bi-trash"></i> Delete
                            </a>
                            ` : ''}
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
