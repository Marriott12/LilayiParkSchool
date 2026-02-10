<?php
require_once 'includes/bootstrap.php';
require_once 'includes/Auth.php';

Auth::requireLogin();

require_once 'modules/roles/RolesModel.php';
$rolesModel = new RolesModel();
if (!$rolesModel->userHasPermission(Auth::id(), 'view_attendance')) {
    Session::setFlash('error', 'You do not have permission to view attendance.');
    header('Location: 403.php');
    exit;
}

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
require_once 'includes/PermissionHelper.php';
?>

<!-- Page Header with Actions -->
<div class="row mb-4">
    <div class="col-md-8">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="attendance_list.php" class="text-decoration-none">Attendance</a></li>
                <li class="breadcrumb-item active" aria-current="page">View Details</li>
            </ol>
        </nav>
        <h2 class="mb-0">
            <i class="bi bi-calendar-check me-2" style="color: #2d5016;"></i>
            <span style="color: #2d5016; font-weight: 600;">Attendance Record</span>
        </h2>
    </div>
    <div class="col-md-4 text-end align-self-end">
        <div class="btn-group" role="group">
            <a href="attendance_list.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
            <?php if (PermissionHelper::canManage('attendance')): ?>
            <a href="attendance_form.php?id=<?= $attendanceID ?>" class="btn btn-warning">
                <i class="bi bi-pencil me-1"></i> Edit
            </a>
            <a href="delete.php?module=attendance&id=<?= $attendanceID ?>" 
               class="btn btn-danger"
               onclick="return confirm('Are you sure you want to delete this attendance record? This action cannot be undone.');">
                <i class="bi bi-trash me-1"></i> Delete
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Attendance Record -->
<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <div class="mb-3">
                    <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center" 
                         style="width: 120px; height: 120px;">
                        <?php 
                        $status = strtolower($att['status'] ?? '');
                        $statusIcon = $status === 'present' ? 'check-circle' : ($status === 'absent' ? 'x-circle' : 'exclamation-circle');
                        $statusColor = $status === 'present' ? '#28a745' : ($status === 'absent' ? '#dc3545' : '#ffc107');
                        ?>
                        <i class="bi bi-<?= $statusIcon ?>" style="font-size: 5rem; color: <?= $statusColor ?>;"></i>
                    </div>
                </div>
                <h4 class="mb-1"><?= htmlspecialchars(($att['fName'] ?? $att['firstName'] ?? '') . ' ' . ($att['lName'] ?? $att['lastName'] ?? '')) ?></h4>
                <p class="text-muted mb-3">Student</p>
                <div class="mb-3">
                    <?php 
                    $badgeClass = $status === 'present' ? 'bg-success' : ($status === 'absent' ? 'bg-danger' : 'bg-warning');
                    ?>
                    <span class="badge <?= $badgeClass ?> px-3 py-2">
                        <i class="bi bi-<?= $statusIcon ?> me-1"></i><?= ucfirst($att['status'] ?? 'Unknown') ?>
                    </span>
                </div>
                <hr>
                <div class="text-start">
                    <small class="text-muted d-block mb-2">Quick Info</small>
                    <div class="d-flex align-items-center mb-2">
                        <i class="bi bi-calendar text-muted me-2"></i>
                        <span class="small"><?= htmlspecialchars($att['attendanceDate'] ?? 'N/A') ?></span>
                    </div>
                    <div class="d-flex align-items-center">
                        <i class="bi bi-hash text-muted me-2"></i>
                        <span class="small">Record #<?= htmlspecialchars($att['attendanceID'] ?? 'N/A') ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <!-- Attendance Details -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">
                    <i class="bi bi-info-circle-fill me-2" style="color: #2d5016;"></i>Attendance Information
                </h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="text-muted small mb-1">Student Name</label>
                        <p class="mb-0 fw-semibold">
                            <?= htmlspecialchars(($att['fName'] ?? $att['firstName'] ?? '') . ' ' . ($att['lName'] ?? $att['lastName'] ?? 'N/A')) ?>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small mb-1">Attendance Date</label>
                        <p class="mb-0 fw-semibold">
                            <?= $att['attendanceDate'] ? date('l, M d, Y', strtotime($att['attendanceDate'])) : 'N/A' ?>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small mb-1">Status</label>
                        <p class="mb-0">
                            <?php 
                            $badgeClass = $status === 'present' ? 'bg-success' : ($status === 'absent' ? 'bg-danger' : 'bg-warning');
                            ?>
                            <span class="badge <?= $badgeClass ?> px-3 py-2">
                                <i class="bi bi-<?= $statusIcon ?> me-1"></i><?= ucfirst($att['status'] ?? 'Unknown') ?>
                            </span>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small mb-1">Record ID</label>
                        <p class="mb-0 fw-semibold"><?= htmlspecialchars($att['attendanceID'] ?? 'N/A') ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Remarks & Notes -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">
                    <i class="bi bi-chat-left-text me-2" style="color: #2d5016;"></i>Remarks & Notes
                </h5>
            </div>
            <div class="card-body">
                <?php if (!empty($att['remarks'])): ?>
                <p class="mb-0"><?= nl2br(htmlspecialchars($att['remarks'])) ?></p>
                <?php else: ?>
                <div class="text-center py-3">
                    <i class="bi bi-chat-left-dots" style="font-size: 2rem; color: #dee2e6;"></i>
                    <p class="text-muted mt-2 mb-0">No remarks recorded</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
    .card {
        transition: all 0.3s ease;
    }
    .card:hover {
        transform: translateY(-2px);
    }
    .btn {
        transition: all 0.2s ease;
    }
    .btn:hover {
        transform: translateY(-1px);
    }
</style>

<?php require_once 'includes/footer.php'; ?>
