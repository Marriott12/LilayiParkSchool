<?php
require_once 'includes/bootstrap.php';

RBAC::requireAuth();
RBAC::requirePermission('teachers', 'read');

require_once 'modules/teachers/TeacherModel.php';

$teacherID = $_GET['id'] ?? null;
if (empty($teacherID)) {
    header('Location: teachers_list.php');
    exit;
}

$teacherModel = new TeacherModel();
$teacher = $teacherModel->getTeacherWithUser($teacherID) ?: $teacherModel->getById($teacherID);
$classes = method_exists($teacherModel, 'getTeacherClasses') ? $teacherModel->getTeacherClasses($teacherID) : [];

$pageTitle = 'Teacher Details';
$currentPage = 'teachers';
require_once 'includes/header.php';
?>

<div class="mb-4">
    <a href="teachers_list.php" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back to Teachers
    </a>
</div>

<div class="card">
    <div class="card-header" style="background: linear-gradient(135deg, #2d5016 0%, #5cb85c 100%); color: white;">
        <h5 class="mb-0"><?= htmlspecialchars($teacher['fName'] ?? $teacher['firstName'] ?? '') . ' ' . htmlspecialchars($teacher['lName'] ?? $teacher['lastName'] ?? '') ?></h5>
    </div>
    <div class="card-body">
        <dl class="row">
            <dt class="col-sm-3">Email / Username</dt>
            <dd class="col-sm-9"><?= htmlspecialchars($teacher['userEmail'] ?? $teacher['email'] ?? $teacher['username'] ?? '') ?></dd>

            <dt class="col-sm-3">Status</dt>
            <dd class="col-sm-9"><?= htmlspecialchars($teacher['isActive'] ?? $teacher['status'] ?? '') ?></dd>

            <dt class="col-sm-3">Phone</dt>
            <dd class="col-sm-9"><?= htmlspecialchars($teacher['phoneNumber'] ?? $teacher['phone'] ?? '') ?></dd>

            <dt class="col-sm-3">Classes</dt>
            <dd class="col-sm-9">
                <?php if (!empty($classes)): ?>
                    <ul class="mb-0">
                        <?php foreach ($classes as $c): ?>
                            <li><?= htmlspecialchars($c['className'] ?? $c['name'] ?? ('Class ' . ($c['classID'] ?? ''))) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <em>No classes assigned</em>
                <?php endif; ?>
            </dd>
        </dl>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
