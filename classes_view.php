<?php
require_once 'includes/bootstrap.php';

RBAC::requireAuth();
RBAC::requirePermission('classes', 'read');

require_once 'modules/classes/ClassModel.php';

$classID = $_GET['id'] ?? null;
if (empty($classID)) {
    header('Location: classes_list.php');
    exit;
}

$classModel = new ClassModel();
$class = $classModel->getClassWithTeacher($classID) ?: $classModel->getById($classID);
$roster = method_exists($classModel, 'getClassRoster') ? $classModel->getClassRoster($classID) : [];

$pageTitle = 'Class Details';
$currentPage = 'classes';
require_once 'includes/header.php';
?>

<div class="mb-4">
    <a href="classes_list.php" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back to Classes
    </a>
</div>

<div class="card mb-3">
    <div class="card-header" style="background: linear-gradient(135deg, #2d5016 0%, #5cb85c 100%); color: white;">
        <h5 class="mb-0"><?= htmlspecialchars($class['className'] ?? '') ?></h5>
    </div>
    <div class="card-body">
        <p><strong>Teacher:</strong> <?= htmlspecialchars(($class['teacherFirstName'] ?? $class['fName'] ?? '') . ' ' . ($class['teacherLastName'] ?? $class['lName'] ?? '')) ?></p>
        <p><strong>Description:</strong> <?= nl2br(htmlspecialchars($class['description'] ?? '')) ?></p>
    </div>
</div>

<div class="card">
    <div class="card-header">Roster</div>
    <div class="card-body">
        <?php if (!empty($roster)): ?>
            <ul>
                <?php foreach ($roster as $p): ?>
                    <li><?= htmlspecialchars(($p['fName'] ?? $p['firstName'] ?? '') . ' ' . ($p['lName'] ?? $p['lastName'] ?? '')) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <em>No pupils assigned to this class.</em>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
