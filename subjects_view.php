<?php
require_once 'includes/bootstrap.php';

RBAC::requireAuth();
RBAC::requirePermission('subjects', 'read');

require_once 'modules/subjects/SubjectsModel.php';

$subjectID = $_GET['id'] ?? null;
if (empty($subjectID)) {
    header('Location: subjects_list.php');
    exit;
}

$subjectsModel = new SubjectsModel();
$subject = $subjectsModel->getSubjectWithTeacher($subjectID) ?: $subjectsModel->getById($subjectID);
$assignedClasses = $subjectsModel->getAssignedClasses($subjectID);

$pageTitle = 'Subject Details';
$currentPage = 'subjects';
require_once 'includes/header.php';
?>

<div class="mb-4">
    <a href="subjects_list.php" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back to Subjects
    </a>
</div>

<div class="card mb-3">
    <div class="card-header" style="background: linear-gradient(135deg, #2d5016 0%, #5cb85c 100%); color: white;">
        <h5 class="mb-0"><?= htmlspecialchars($subject['subjectName']) ?></h5>
    </div>
    <div class="card-body">
        <dl class="row">
            <dt class="col-sm-3">Subject Code</dt>
            <dd class="col-sm-9"><span class="badge" style="background-color: #2d5016;"><?= htmlspecialchars($subject['subjectCode']) ?></span></dd>

            <dt class="col-sm-3">Assigned Teacher</dt>
            <dd class="col-sm-9"><?= htmlspecialchars(($subject['teacherFirstName'] ?? '') . ' ' . ($subject['teacherLastName'] ?? 'Not Assigned')) ?></dd>

            <dt class="col-sm-3">Credits</dt>
            <dd class="col-sm-9"><?= htmlspecialchars($subject['credits'] ?? 1) ?></dd>

            <dt class="col-sm-3">Description</dt>
            <dd class="col-sm-9"><?= nl2br(htmlspecialchars($subject['description'] ?? 'No description')) ?></dd>
        </dl>
    </div>
</div>

<div class="card">
    <div class="card-header">Assigned Classes</div>
    <div class="card-body">
        <?php if (!empty($assignedClasses)): ?>
            <ul class="list-group">
                <?php foreach ($assignedClasses as $class): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <?= htmlspecialchars($class['className']) ?>
                        <span class="badge bg-secondary">Assigned: <?= date('M d, Y', strtotime($class['assignedDate'])) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <em>Not assigned to any classes</em>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
