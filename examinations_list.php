<?php
require_once 'includes/bootstrap.php';
require_once 'includes/Auth.php';
require_once 'includes/PermissionHelper.php';

Auth::requireLogin();

require_once 'modules/roles/RolesModel.php';
$rolesModel = new RolesModel();
if (!$rolesModel->userHasPermission(Auth::id(), 'view_examinations')) {
    Session::setFlash('error', 'You do not have permission to view examinations.');
    header('Location: /LilayiParkSchool/403.php');
    exit;
}

require_once 'modules/examinations/ExaminationsModel.php';

$examinationsModel = new ExaminationsModel();

// Update statuses based on dates
$examinationsModel->updateExamStatuses();

// Handle filters
$filters = [
    'term' => $_GET['term'] ?? '',
    'academicYear' => $_GET['academicYear'] ?? '',
    'examType' => $_GET['examType'] ?? '',
    'status' => $_GET['status'] ?? '',
    'search' => $_GET['search'] ?? ''
];

// Pagination
$page = $_GET['page'] ?? 1;
$perPage = 20;
$totalRecords = $examinationsModel->count($filters);
$pagination = new Pagination($totalRecords, $perPage, $page);

// Get examinations
$examinations = $examinationsModel->getAll($filters, $pagination->getLimit(), $pagination->getOffset());

// Get upcoming exams for widget
$upcomingExams = $examinationsModel->getUpcoming(5);

$pageTitle = 'Examinations Management';
$currentPage = 'examinations';
require_once 'includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2><i class="bi bi-clipboard-check me-2"></i>Examinations Management</h2>
            <p class="text-muted">Schedule and manage school examinations</p>
        </div>
        <div class="col-md-4 text-end">
            <?php if (PermissionHelper::canManage('examinations')): ?>
                <a href="examinations_form.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-1"></i> Schedule New Exam
                </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($upcomingExams)): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-primary">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-calendar-event me-2"></i>Upcoming Examinations</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach ($upcomingExams as $exam): ?>
                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="border-start border-primary border-3 ps-3">
                                <h6 class="mb-1"><?= htmlspecialchars($exam['examName']) ?></h6>
                                <p class="mb-1 text-muted small">
                                    <i class="bi bi-calendar3 me-1"></i>
                                    <?= date('M d, Y', strtotime($exam['startDate'])) ?> - 
                                    <?= date('M d, Y', strtotime($exam['endDate'])) ?>
                                </p>
                                <span class="badge bg-secondary"><?= $exam['examType'] ?></span>
                                <span class="badge bg-info">Term <?= $exam['term'] ?></span>
                                <span class="badge bg-success"><?= $exam['scheduledClasses'] ?> Classes</span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-2">
                    <label class="form-label">Term</label>
                    <select name="term" class="form-select">
                        <option value="">All Terms</option>
                        <option value="1" <?= $filters['term'] == '1' ? 'selected' : '' ?>>Term 1</option>
                        <option value="2" <?= $filters['term'] == '2' ? 'selected' : '' ?>>Term 2</option>
                        <option value="3" <?= $filters['term'] == '3' ? 'selected' : '' ?>>Term 3</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Academic Year</label>
                    <select name="academicYear" class="form-select">
                        <option value="">All Years</option>
                        <option value="2025/2026" <?= $filters['academicYear'] == '2025/2026' ? 'selected' : '' ?>>2025/2026</option>
                        <option value="2024/2025" <?= $filters['academicYear'] == '2024/2025' ? 'selected' : '' ?>>2024/2025</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Exam Type</label>
                    <select name="examType" class="form-select">
                        <option value="">All Types</option>
                        <option value="CAT" <?= $filters['examType'] == 'CAT' ? 'selected' : '' ?>>CAT</option>
                        <option value="MidTerm" <?= $filters['examType'] == 'MidTerm' ? 'selected' : '' ?>>Mid-Term</option>
                        <option value="EndTerm" <?= $filters['examType'] == 'EndTerm' ? 'selected' : '' ?>>End-Term</option>
                        <option value="Mock" <?= $filters['examType'] == 'Mock' ? 'selected' : '' ?>>Mock</option>
                        <option value="Final" <?= $filters['examType'] == 'Final' ? 'selected' : '' ?>>Final</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="Scheduled" <?= $filters['status'] == 'Scheduled' ? 'selected' : '' ?>>Scheduled</option>
                        <option value="Ongoing" <?= $filters['status'] == 'Ongoing' ? 'selected' : '' ?>>Ongoing</option>
                        <option value="Completed" <?= $filters['status'] == 'Completed' ? 'selected' : '' ?>>Completed</option>
                        <option value="Cancelled" <?= $filters['status'] == 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" placeholder="Exam name..." value="<?= htmlspecialchars($filters['search']) ?>">
                </div>
                <div class="col-md-1">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Examinations List -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">All Examinations (<?= number_format($totalRecords) ?>)</h5>
        </div>
        <div class="card-body">
            <?php if (empty($examinations)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-inbox display-1 text-muted"></i>
                    <p class="text-muted mt-3">No examinations found</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Exam Name</th>
                                <th>Type</th>
                                <th>Term</th>
                                <th>Academic Year</th>
                                <th>Date Range</th>
                                <th>Status</th>
                                <th>Scheduled</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($examinations as $exam): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($exam['examName']) ?></strong>
                                    <?php if ($exam['instructions']): ?>
                                        <i class="bi bi-info-circle text-info" 
                                           data-bs-toggle="tooltip" 
                                           title="<?= htmlspecialchars($exam['instructions']) ?>"></i>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge bg-secondary"><?= $exam['examType'] ?></span></td>
                                <td>Term <?= $exam['term'] ?></td>
                                <td><?= $exam['academicYear'] ?></td>
                                <td>
                                    <small>
                                        <?= date('M d', strtotime($exam['startDate'])) ?> - 
                                        <?= date('M d, Y', strtotime($exam['endDate'])) ?>
                                    </small>
                                </td>
                                <td>
                                    <?php
                                    $statusColors = [
                                        'Scheduled' => 'primary',
                                        'Ongoing' => 'warning',
                                        'Completed' => 'success',
                                        'Cancelled' => 'danger'
                                    ];
                                    $color = $statusColors[$exam['status']] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?= $color ?>"><?= $exam['status'] ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-info"><?= $exam['scheduledClasses'] ?> Classes</span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="examinations_schedule.php?examID=<?= $exam['examID'] ?>" 
                                           class="btn btn-outline-info btn-sm">
                                            <i class="bi bi-calendar3"></i> View
                                        </a>
                                        <?php if (PermissionHelper::canManage('examinations')): ?>
                                        <a href="examinations_form.php?examID=<?= $exam['examID'] ?>" 
                                           class="btn btn-outline-warning btn-sm">
                                            <i class="bi bi-pencil"></i> Edit
                                        </a>
                                        <?php endif; ?>
                                        <?php if (PermissionHelper::canManage('examinations') && $exam['scheduledClasses'] == 0): ?>
                                        <a href="delete.php?module=examinations&id=<?= $exam['examID'] ?>" 
                                           class="btn btn-outline-danger btn-sm" 
                                           onclick="return confirm('Are you sure you want to delete this examination?');">
                                            <i class="bi bi-trash"></i> Delete
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($pagination->getTotalPages() > 1): ?>
                    <?= $pagination->render('examinations_list.php', array_filter($filters)); ?>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="examinations_delete.php">
                <?= CSRF::field() ?>
                <input type="hidden" name="examID" id="deleteExamID">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete <strong id="deleteExamName"></strong>?</p>
                    <p class="text-danger">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function deleteExam(examID, examName) {
    document.getElementById('deleteExamID').value = examID;
    document.getElementById('deleteExamName').textContent = examName;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
