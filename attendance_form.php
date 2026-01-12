<?php
require_once 'includes/bootstrap.php';
require_once 'includes/Auth.php';

Auth::requireLogin();

$attendanceID = $_GET['id'] ?? null;
$isEdit = !empty($attendanceID);

require_once 'modules/roles/RolesModel.php';
$rolesModel = new RolesModel();
if (!$rolesModel->userHasPermission(Auth::id(), 'manage_attendance')) {
    Session::setFlash('error', 'You do not have permission to manage attendance.');
    header('Location: /LilayiParkSchool/403.php');
    exit;
}

require_once 'modules/attendance/AttendanceModel.php';
require_once 'modules/classes/ClassModel.php';
require_once 'modules/pupils/PupilModel.php';

$attendanceModel = new AttendanceModel();
$classModel = new ClassModel();
$pupilModel = new PupilModel();

// Get all classes for dropdown
$classes = $classModel->getAll();

// Get pupils by selected class if available
$selectedClassID = $_GET['classID'] ?? $_POST['classID'] ?? null;
$pupils = $selectedClassID ? $pupilModel->getPupilsByClass($selectedClassID) : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token first
    if (!CSRF::requireToken()) {
        $error = $GLOBALS['csrf_error'] ?? 'Security validation failed. Please try again.';
    } else {
        $data = [
            'pupilID' => intval($_POST['pupilID'] ?? 0),
            'classID' => intval($_POST['classID'] ?? 0),
            'attendanceDate' => $_POST['attendanceDate'] ?? date('Y-m-d'),
            'status' => $_POST['status'] ?? 'Present',
            'timeIn' => !empty($_POST['timeIn']) ? $_POST['timeIn'] : null,
            'timeOut' => !empty($_POST['timeOut']) ? $_POST['timeOut'] : null,
            'remarks' => trim($_POST['remarks'] ?? ''),
            'markedBy' => Session::get('user_id')
        ];
        
        // Validation
        if ($data['pupilID'] <= 0) {
            $error = 'Please select a pupil';
        } elseif ($data['classID'] <= 0) {
            $error = 'Please select a class';
        }
        
        if (!isset($error)) {
            try {
                if ($isEdit) {
                    $attendanceModel->update($attendanceID, $data);
                    Session::setFlash('success', 'Attendance updated successfully');
                } else {
                    $attendanceModel->create($data);
                    Session::setFlash('success', 'Attendance marked successfully');
                }
                
                CSRF::regenerateToken();
                header('Location: attendance_list.php');
                exit;
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
    }
}

$attendance = $isEdit ? $attendanceModel->getById($attendanceID) : null;
if ($attendance) {
    $selectedClassID = $attendance['classID'];
    $pupils = $pupilModel->getPupilsByClass($selectedClassID);
}

$pageTitle = $isEdit ? 'Edit Attendance' : 'Mark Attendance';
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
        <h5 class="mb-0">
            <i class="bi bi-clipboard-check-fill"></i> <?= $pageTitle ?>
        </h5>
    </div>
    <div class="card-body">
        <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle-fill"></i> <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <form method="POST" action="" id="attendanceForm">
            <?= CSRF::field() ?>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Class <span class="text-danger">*</span></label>
                    <select class="form-select" name="classID" id="classSelect" required 
                            onchange="window.location.href='?classID=' + this.value">
                        <option value="">Select Class</option>
                        <?php foreach ($classes as $class): ?>
                        <option value="<?= $class['classID'] ?>" 
                                <?= $selectedClassID == $class['classID'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($class['className'] . ' - ' . $class['grade']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" name="attendanceDate" 
                           value="<?= htmlspecialchars($attendance['attendanceDate'] ?? date('Y-m-d')) ?>" required>
                </div>
            </div>
            
            <?php if (!empty($pupils)): ?>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Pupil <span class="text-danger">*</span></label>
                    <select class="form-select" name="pupilID" required>
                        <option value="">Select Pupil</option>
                        <?php foreach ($pupils as $pupil): ?>
                        <option value="<?= $pupil['pupilID'] ?>" 
                                <?= ($attendance['pupilID'] ?? '') == $pupil['pupilID'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($pupil['fName'] . ' ' . $pupil['lName'] . ' (' . $pupil['pupilID'] . ')') ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Status <span class="text-danger">*</span></label>
                    <select class="form-select" name="status" required>
                        <option value="Present" <?= ($attendance['status'] ?? 'Present') === 'Present' ? 'selected' : '' ?>>Present</option>
                        <option value="Absent" <?= ($attendance['status'] ?? '') === 'Absent' ? 'selected' : '' ?>>Absent</option>
                        <option value="Late" <?= ($attendance['status'] ?? '') === 'Late' ? 'selected' : '' ?>>Late</option>
                        <option value="Excused" <?= ($attendance['status'] ?? '') === 'Excused' ? 'selected' : '' ?>>Excused</option>
                    </select>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Time In</label>
                    <input type="time" class="form-control" name="timeIn" 
                           value="<?= htmlspecialchars($attendance['timeIn'] ?? '') ?>">
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Time Out</label>
                    <input type="time" class="form-control" name="timeOut" 
                           value="<?= htmlspecialchars($attendance['timeOut'] ?? '') ?>">
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Remarks</label>
                <textarea class="form-control" name="remarks" rows="2" 
                          placeholder="Any additional notes..."><?= htmlspecialchars($attendance['remarks'] ?? '') ?></textarea>
            </div>
            
            <div class="d-flex gap-2">
                <button type="submit" class="btn" style="background-color: #2d5016; color: white;">
                    <i class="bi bi-save"></i> <?= $isEdit ? 'Update' : 'Mark' ?> Attendance
                </button>
                <a href="attendance_list.php" class="btn btn-secondary">
                    <i class="bi bi-x-circle"></i> Cancel
                </a>
            </div>
            <?php else: ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> Please select a class to view pupils and mark attendance.
            </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
