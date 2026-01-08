<?php
require_once 'includes/bootstrap.php';

RBAC::requireAuth();
RBAC::requirePermission('attendance', 'read');

require_once 'modules/attendance/AttendanceModel.php';

$attendanceID = $_GET['id'] ?? null;
if (empty($attendanceID)) {
    header('Location: attendance_list.php');
    exit;
}

$attendanceModel = new AttendanceModel();
$att = $attendanceModel->getAttendanceWithPupil($attendanceID) ?: $attendanceModel->getById($attendanceID);

$pageTitle = 'Attendance Details';
$currentPage = 'attendance';
require_once 'includes/header.php';
?>

<div class="mb-4">
    <a href="attendance_list.php" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back to Attendance
    </a>
</div>

<div class="card">
    <div class="card-header" style="background: linear-gradient(135deg, #2d5016 0%, #5cb85c 100%); color: white;">
        <h5 class="mb-0"><?= htmlspecialchars(($att['fName'] ?? $att['firstName'] ?? '') . ' ' . ($att['lName'] ?? $att['lastName'] ?? '')) ?></h5>
    </div>
    <div class="card-body">
        <dl class="row">
            <dt class="col-sm-3">Date</dt>
            <dd class="col-sm-9"><?= htmlspecialchars($att['attendanceDate'] ?? '') ?></dd>

            <dt class="col-sm-3">Status</dt>
            <dd class="col-sm-9"><?= htmlspecialchars($att['status'] ?? '') ?></dd>

            <dt class="col-sm-3">Remarks</dt>
            <dd class="col-sm-9"><?= nl2br(htmlspecialchars($att['remarks'] ?? '')) ?></dd>
        </dl>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
