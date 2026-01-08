<?php
require_once 'includes/bootstrap.php';

RBAC::requireAuth();
RBAC::requirePermission('pupils', 'read');

require_once 'modules/pupils/PupilModel.php';

$pupilID = $_GET['id'] ?? null;
if (empty($pupilID)) {
    header('Location: pupils_list.php');
    exit;
}

$pupilModel = new PupilModel();
$pupil = $pupilModel->getPupilWithParent($pupilID) ?: $pupilModel->getById($pupilID);

$pageTitle = 'Pupil Details';
$currentPage = 'pupils';
require_once 'includes/header.php';
?>

<div class="mb-4">
    <a href="pupils_list.php" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back to Pupils
    </a>
</div>

<div class="card">
    <div class="card-header" style="background: linear-gradient(135deg, #2d5016 0%, #5cb85c 100%); color: white;">
        <h5 class="mb-0"><?= htmlspecialchars($pupil['fName'] ?? $pupil['firstName'] ?? '') . ' ' . htmlspecialchars($pupil['lName'] ?? $pupil['lastName'] ?? '') ?></h5>
    </div>
    <div class="card-body">
        <dl class="row">
            <dt class="col-sm-3">Student Number</dt>
            <dd class="col-sm-9"><?= htmlspecialchars($pupil['studentNumber'] ?? '') ?></dd>

            <dt class="col-sm-3">Date of Birth</dt>
            <dd class="col-sm-9"><?= htmlspecialchars($pupil['dateOfBirth'] ?? '') ?></dd>

            <dt class="col-sm-3">Gender</dt>
            <dd class="col-sm-9"><?= htmlspecialchars($pupil['gender'] ?? '') ?></dd>

            <dt class="col-sm-3">Parent / Guardian</dt>
            <dd class="col-sm-9"><?= htmlspecialchars(($pupil['parentFirstName'] ?? '') . ' ' . ($pupil['parentLastName'] ?? '')) ?></dd>

            <dt class="col-sm-3">Contact</dt>
            <dd class="col-sm-9"><?= htmlspecialchars($pupil['parentPhone'] ?? $pupil['parentPhoneNumber'] ?? '') ?></dd>

            <dt class="col-sm-3">Address</dt>
            <dd class="col-sm-9"><?= nl2br(htmlspecialchars($pupil['address'] ?? '')) ?></dd>

            <dt class="col-sm-3">Medical Info</dt>
            <dd class="col-sm-9"><?= nl2br(htmlspecialchars($pupil['medicalInfo'] ?? '')) ?></dd>
        </dl>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
