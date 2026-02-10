<?php
require_once 'includes/bootstrap.php';
require_once 'includes/Auth.php';
require_once 'includes/PermissionHelper.php';

Auth::requireLogin();

require_once 'modules/roles/RolesModel.php';
$rolesModel = new RolesModel();
if (!$rolesModel->userHasPermission(Auth::id(), 'view_attendance')) {
    Session::setFlash('error', 'You do not have permission to view attendance.');
    header('Location: 403.php');
    exit;
}

require_once 'modules/attendance/AttendanceModel.php';
require_once 'modules/classes/ClassModel.php';

$attendanceModel = new AttendanceModel();
$classModel = new ClassModel();

$classes = $classModel->all();
$selectedDate = $_GET['date'] ?? date('Y-m-d');
$selectedClass = $_GET['class_id'] ?? ($classes[0]['classID'] ?? null);

$attendanceRecords = [];
if ($selectedClass) {
    $attendanceRecords = $attendanceModel->getByClassAndDate($selectedClass, $selectedDate);
}

$pageTitle = 'Attendance Management';
$currentPage = 'attendance';
require_once 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-calendar-check"></i> Attendance</h2>
    <?php if (PermissionHelper::canManage('attendance')): ?>
    <a href="attendance_form.php?class_id=<?= $selectedClass ?>&date=<?= $selectedDate ?>" class="btn btn-sm" style="background-color: #2d5016; color: white;">
        <i class="bi bi-plus-circle"></i> Mark Attendance
    </a>
    <?php endif; ?>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-5">
                <label for="class_id" class="form-label">Class</label>
                <select name="class_id" id="class_id" class="form-select" onchange="this.form.submit()">
                    <option value="">Select Class</option>
                    <?php foreach ($classes as $class): ?>
                    <option value="<?= $class['classID'] ?>" <?= $selectedClass == $class['classID'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($class['className']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-5">
                <label for="date" class="form-label">Date</label>
                <input type="date" name="date" id="date" class="form-control" value="<?= $selectedDate ?>" onchange="this.form.submit()">
            </div>
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn w-100" style="background-color: #2d5016; color: white;">
                    <i class="bi bi-filter"></i> Filter
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Attendance Table -->
<div class="card">
    <div class="card-body">
        <?php if (!$selectedClass): ?>
        <div class="text-center py-5">
            <i class="bi bi-calendar-x" style="font-size: 3rem; opacity: 0.3;"></i>
            <p class="text-muted mt-2">Please select a class to view attendance</p>
        </div>
        <?php else: ?>
        <h5 class="mb-3">
            Attendance for <?= date('F d, Y', strtotime($selectedDate)) ?>
        </h5>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead style="background-color: #f8f9fa;">
                    <tr>
                        <th>Student Number</th>
                        <th>Student Name</th>
                        <th>Status</th>
                        <th>Remarks</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($attendanceRecords)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-4">
                            <i class="bi bi-inbox" style="font-size: 3rem; opacity: 0.3;"></i>
                            <p class="text-muted mt-2">No attendance records for this date</p>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($attendanceRecords as $record): ?>
                    <tr>
                        <td><?= htmlspecialchars($record['pupilID']) ?></td>
                        <td>
                            <strong><?= htmlspecialchars($record['firstName'] . ' ' . $record['lastName']) ?></strong>
                        </td>
                        <td>
                            <?php 
                            $status = $record['status'] ?? 'Present';
                            $badgeClass = 'bg-success';
                            if ($status === 'Absent') $badgeClass = 'bg-danger';
                            elseif ($status === 'Late') $badgeClass = 'bg-warning';
                            ?>
                            <span class="badge <?= $badgeClass ?>"><?= $status ?></span>
                        </td>
                        <td><?= htmlspecialchars($record['remarks'] ?? '-') ?></td>
                        <td>
                            <div class="btn-group btn-group-sm" role="group">
                                <a href="attendance_view.php?id=<?= $record['attendanceID'] ?>" class="btn btn-outline-info btn-sm">
                                    <i class="bi bi-eye"></i> View
                                </a>
                                <?php if (PermissionHelper::canManage('attendance')): ?>
                                <a href="attendance_form.php?id=<?= $record['attendanceID'] ?>" class="btn btn-outline-warning btn-sm">
                                    <i class="bi bi-pencil"></i> Edit
                                </a>
                                <a href="delete.php?module=attendance&id=<?= $record['attendanceID'] ?>" 
                                   class="btn btn-outline-danger btn-sm" 
                                   onclick="return confirm('Are you sure you want to delete this attendance record?');">
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
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
