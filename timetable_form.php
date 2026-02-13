<?php
require_once 'includes/bootstrap.php';
require_once 'includes/Auth.php';
require_once 'includes/PermissionHelper.php';
require_once 'includes/CSRF.php';

Auth::requireLogin();
Auth::requireAnyRole(['admin']);

require_once 'modules/timetable/TimetableModel.php';
require_once 'modules/classes/ClassModel.php';
require_once 'modules/subjects/SubjectModel.php';
require_once 'modules/teachers/TeacherModel.php';
require_once 'modules/settings/SettingsModel.php';

$timetableModel = new TimetableModel();
$classModel = new ClassModel();
$subjectModel = new SubjectModel();
$teacherModel = new TeacherModel();
$settingsModel = new SettingsModel();

$timetableID = $_GET['id'] ?? null;
$timetable = $timetableID ? $timetableModel->find($timetableID) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::validate($_POST['csrf_token']);
    
    $data = [
        'classID' => $_POST['classID'],
        'subjectID' => $_POST['subjectID'],
        'teacherID' => $_POST['teacherID'],
        'dayOfWeek' => $_POST['dayOfWeek'],
        'startTime' => $_POST['startTime'],
        'endTime' => $_POST['endTime'],
        'room' => $_POST['room'] ?? null,
        'term' => $_POST['term'],
        'academicYear' => $_POST['academicYear']
    ];
    
    // Check for conflicts
    $hasConflict = $timetableModel->hasConflict(
        $data['classID'],
        $data['dayOfWeek'],
        $data['startTime'],
        $data['endTime'],
        $data['term'],
        $data['academicYear'],
        $timetableID
    );
    
    if ($hasConflict) {
        Session::setFlash('error', 'Time conflict detected! This class already has a lesson scheduled at this time.');
    } else {
        if ($timetableID) {
            $timetableModel->update($timetableID, $data);
            Session::setFlash('success', 'Timetable updated successfully!');
        } else {
            $timetableModel->create($data);
            Session::setFlash('success', 'Timetable created successfully!');
        }
        header('Location: timetable_list.php?class=' . $data['classID']);
        exit;
    }
}

$classes = $classModel->all();
$subjects = $subjectModel->all();
$teachers = $teacherModel->all();

$pageTitle = $timetableID ? 'Edit Timetable' : 'Add Timetable';
$currentPage = 'timetable';
require_once 'includes/header.php';
?>

<!-- Page Header -->
<div class="row mb-4">
    <div class="col-md-12">
        <h2 class="mb-0">
            <i class="bi bi-calendar-plus me-2" style="color: #2d5016;"></i>
            <span style="color: #2d5016; font-weight: 600;"><?= $pageTitle ?></span>
        </h2>
        <p class="text-muted mt-1">Schedule a class lesson</p>
    </div>
</div>

<!-- Form Card -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <form method="POST">
            <?= CSRF::field(); ?>
            
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">
                        <i class="bi bi-book me-1"></i>Class <span class="text-danger">*</span>
                    </label>
                    <select class="form-select" name="classID" required>
                        <option value="">Select Class</option>
                        <?php foreach ($classes as $class): ?>
                        <option value="<?= $class['classID'] ?>" <?= ($timetable && $timetable['classID'] == $class['classID']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($class['className']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label fw-semibold">
                        <i class="bi bi-journal-text me-1"></i>Subject <span class="text-danger">*</span>
                    </label>
                    <select class="form-select" name="subjectID" required>
                        <option value="">Select Subject</option>
                        <?php foreach ($subjects as $subject): ?>
                        <option value="<?= $subject['subjectID'] ?>" <?= ($timetable && $timetable['subjectID'] == $subject['subjectID']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($subject['subjectName']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label fw-semibold">
                        <i class="bi bi-person me-1"></i>Teacher <span class="text-danger">*</span>
                    </label>
                    <select class="form-select" name="teacherID" required>
                        <option value="">Select Teacher</option>
                        <?php foreach ($teachers as $teacher): ?>
                        <option value="<?= $teacher['teacherID'] ?>" <?= ($timetable && $timetable['teacherID'] == $teacher['teacherID']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($teacher['firstName'] . ' ' . $teacher['lastName']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label fw-semibold">
                        <i class="bi bi-calendar-day me-1"></i>Day of Week <span class="text-danger">*</span>
                    </label>
                    <select class="form-select" name="dayOfWeek" required>
                        <option value="">Select Day</option>
                        <?php 
                        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
                        foreach ($days as $day): 
                        ?>
                        <option value="<?= $day ?>" <?= ($timetable && $timetable['dayOfWeek'] == $day) ? 'selected' : '' ?>>
                            <?= $day ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label fw-semibold">
                        <i class="bi bi-clock me-1"></i>Start Time <span class="text-danger">*</span>
                    </label>
                    <input type="time" class="form-control" name="startTime" 
                           value="<?= $timetable['startTime'] ?? '' ?>" required>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label fw-semibold">
                        <i class="bi bi-clock-fill me-1"></i>End Time <span class="text-danger">*</span>
                    </label>
                    <input type="time" class="form-control" name="endTime" 
                           value="<?= $timetable['endTime'] ?? '' ?>" required>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label fw-semibold">
                        <i class="bi bi-door-open me-1"></i>Room/Venue
                    </label>
                    <input type="text" class="form-control" name="room" 
                           value="<?= htmlspecialchars($timetable['room'] ?? '') ?>" placeholder="e.g., Room 101">
                </div>
                
                <div class="col-md-6">
                    <label class="form-label fw-semibold">
                        <i class="bi bi-calendar3 me-1"></i>Term <span class="text-danger">*</span>
                    </label>
                    <select class="form-select" name="term" required>
                        <option value="1" <?= ($timetable && $timetable['term'] == '1') ? 'selected' : '' ?>>Term 1</option>
                        <option value="2" <?= ($timetable && $timetable['term'] == '2') ? 'selected' : '' ?>>Term 2</option>
                        <option value="3" <?= ($timetable && $timetable['term'] == '3') ? 'selected' : '' ?>>Term 3</option>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label fw-semibold">
                        <i class="bi bi-calendar-range me-1"></i>Academic Year <span class="text-danger">*</span>
                    </label>
                    <input type="text" class="form-control" name="academicYear" 
                           value="<?= htmlspecialchars($timetable['academicYear'] ?? $settingsModel->getSetting('current_academic_year', '2025-2026')) ?>" 
                           placeholder="2025-2026" required>
                </div>
            </div>
            
            <div class="mt-4">
                <button type="submit" class="btn btn-lg shadow-sm" style="background-color: #2d5016; color: white;">
                    <i class="bi bi-check-circle me-1"></i>
                    <?= $timetableID ? 'Update' : 'Create' ?> Timetable
                </button>
                <a href="timetable_list.php" class="btn btn-lg btn-outline-secondary">
                    <i class="bi bi-x-circle me-1"></i>Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
