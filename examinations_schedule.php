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
require_once 'modules/classes/ClassModel.php';
require_once 'modules/subjects/SubjectsModel.php';
require_once 'modules/users/UsersModel.php';

$examinationsModel = new ExaminationsModel();
$classModel = new ClassModel();
$subjectsModel = new SubjectsModel();
$usersModel = new UsersModel();

$examID = $_GET['examID'] ?? null;

if (!$examID) {
    Session::setFlash('error', 'Exam ID is required');
    header('Location: examinations_list.php');
    exit;
}

$exam = $examinationsModel->getById($examID);
if (!$exam) {
    Session::setFlash('error', 'Examination not found');
    header('Location: examinations_list.php');
    exit;
}

// Handle schedule submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_schedule') {
        CSRF::requireToken();
        
        $scheduleData = [
            'examID' => $examID,
            'classID' => $_POST['classID'] ?? '',
            'subjectID' => $_POST['subjectID'] ?? '',
            'examDate' => $_POST['examDate'] ?? '',
            'startTime' => $_POST['startTime'] ?? '',
            'endTime' => $_POST['endTime'] ?? '',
            'room' => $_POST['room'] ?? '',
            'invigilator' => $_POST['invigilator'] ?? null,
            'maxMarks' => $_POST['maxMarks'] ?? $exam['totalMarks'],
            'duration' => $_POST['duration'] ?? 60,
            'specialInstructions' => $_POST['specialInstructions'] ?? ''
        ];
        
        try {
            $examinationsModel->scheduleExam($scheduleData);
            Session::setFlash('success', 'Exam scheduled successfully');
            CSRF::regenerateToken();
            header('Location: examinations_schedule.php?examID=' . $examID);
            exit;
        } catch (Exception $e) {
            Session::setFlash('error', $e->getMessage());
        }
    } elseif ($action === 'delete_schedule') {
        CSRF::requireToken();
        
        $scheduleID = $_POST['scheduleID'] ?? null;
        if ($scheduleID) {
            try {
                $examinationsModel->deleteSchedule($scheduleID);
                Session::setFlash('success', 'Schedule deleted successfully');
                CSRF::regenerateToken();
            } catch (Exception $e) {
                Session::setFlash('error', $e->getMessage());
            }
        }
        header('Location: examinations_schedule.php?examID=' . $examID);
        exit;
    }
}

// Get schedules and statistics
$schedules = $examinationsModel->getSchedulesByExam($examID);
$statistics = $examinationsModel->getStatistics($examID);
$classes = $classModel->getAll();
$subjects = $subjectsModel->getAll();
// Role 3 = Teacher; use getByRole if available, otherwise filter all users
if (method_exists($usersModel, 'getByRole')) {
    $teachers = $usersModel->getByRole(3); // Role 3 = Teacher
} else {
    $allUsers = $usersModel->getAll();
    $teachers = array_values(array_filter($allUsers, function($u) {
        if (isset($u['roleID'])) {
            return $u['roleID'] == 3;
        }
        if (isset($u['role'])) {
            return $u['role'] == 3;
        }
        return false;
    }));
}

$pageTitle = 'Exam Schedule - ' . $exam['examName'];
$currentPage = 'examinations';
require_once 'includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2><i class="bi bi-calendar3 me-2"></i><?= htmlspecialchars($exam['examName']) ?></h2>
            <p class="text-muted">
                <?= $exam['examType'] ?> • Term <?= $exam['term'] ?> • <?= $exam['academicYear'] ?>
                <span class="ms-2 badge bg-<?= $exam['status'] == 'Scheduled' ? 'primary' : ($exam['status'] == 'Ongoing' ? 'warning' : ($exam['status'] == 'Completed' ? 'success' : 'danger')) ?>">
                    <?= $exam['status'] ?>
                </span>
            </p>
        </div>
        <div class="col-md-4 text-end">
            <?php if (Auth::hasRole('admin')): ?>
            <a href="examinations_schedule_form.php?examID=<?= $examID ?>" class="btn shadow-sm" 
               style="background-color: #2d5016; color: white;">
                <i class="bi bi-plus-circle me-1"></i> Add Schedule
            </a>
            <?php endif; ?>
            <a href="examinations_list.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to List
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2">Total Schedules</h6>
                    <h2 class="mb-0"><?= $statistics['totalSchedules'] ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2">Classes</h6>
                    <h2 class="mb-0"><?= $statistics['totalClasses'] ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2">Subjects</h6>
                    <h2 class="mb-0"><?= $statistics['totalSubjects'] ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2">Completed</h6>
                    <h2 class="mb-0"><?= $statistics['completedSchedules'] ?></h2>
                </div>
            </div>
        </div>
    </div>

    <?php if (PermissionHelper::canManage('examinations')): ?>
    <!-- Add Schedule Form -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-plus-circle me-2"></i>Schedule Exam for Class</h5>
        </div>
        <div class="card-body">
            <form method="POST" id="scheduleForm">
                <?= CSRF::field() ?>
                <input type="hidden" name="action" value="add_schedule">
                
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Class</label>
                        <select name="classID" class="form-select" required>
                            <option value="">Select Class</option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?= $class['classID'] ?>"><?= htmlspecialchars($class['className']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Subject</label>
                        <select name="subjectID" class="form-select" required>
                            <option value="">Select Subject</option>
                            <?php foreach ($subjects as $subject): ?>
                                <option value="<?= $subject['subjectID'] ?>"><?= htmlspecialchars($subject['subjectName']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Date</label>
                        <input type="date" name="examDate" class="form-control" 
                               min="<?= $exam['startDate'] ?>" 
                               max="<?= $exam['endDate'] ?>" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Start Time</label>
                        <input type="time" name="startTime" class="form-control" value="08:00" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">End Time</label>
                        <input type="time" name="endTime" class="form-control" value="10:00" required>
                    </div>
                </div>
                
                <div class="row g-3 mt-2">
                    <div class="col-md-2">
                        <label class="form-label">Room</label>
                        <input type="text" name="room" class="form-control" placeholder="e.g., Lab 1">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Invigilator</label>
                        <select name="invigilator" class="form-select">
                            <option value="">Select Teacher</option>
                            <?php foreach ($teachers as $teacher): ?>
                                <option value="<?= $teacher['userID'] ?>"><?= htmlspecialchars($teacher['username']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Max Marks</label>
                        <input type="number" name="maxMarks" class="form-control" value="<?= $exam['totalMarks'] ?>" min="1" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Duration (mins)</label>
                        <input type="number" name="duration" class="form-control" value="60" min="1" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-plus-circle me-1"></i> Add Schedule
                        </button>
                    </div>
                </div>
                
                <div class="row mt-2">
                    <div class="col-12">
                        <label class="form-label">Special Instructions (Optional)</label>
                        <input type="text" name="specialInstructions" class="form-control" 
                               placeholder="e.g., Bring calculator, graph paper required">
                    </div>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Schedule List -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Exam Schedule</h5>
        </div>
        <div class="card-body">
            <?php if (empty($schedules)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-calendar-x display-1 text-muted"></i>
                    <p class="text-muted mt-3">No schedules created yet. Add schedules using the form above.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date & Time</th>
                                <th>Class</th>
                                <th>Subject</th>
                                <th>Room</th>
                                <th>Invigilator</th>
                                <th>Duration</th>
                                <th>Max Marks</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($schedules as $schedule): ?>
                            <tr>
                                <td>
                                    <strong><?= date('D, M d, Y', strtotime($schedule['examDate'])) ?></strong><br>
                                    <small class="text-muted">
                                        <?= date('g:i A', strtotime($schedule['startTime'])) ?> - 
                                        <?= date('g:i A', strtotime($schedule['endTime'])) ?>
                                    </small>
                                </td>
                                <td><?= htmlspecialchars($schedule['className']) ?></td>
                                <td><?= htmlspecialchars($schedule['subjectName']) ?></td>
                                <td><?= htmlspecialchars($schedule['room'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($schedule['invigilatorName'] ?? '-') ?></td>
                                <td><?= $schedule['duration'] ?> min</td>
                                <td><?= $schedule['maxMarks'] ?></td>
                                <td>
                                    <span class="badge bg-<?= $schedule['status'] == 'Scheduled' ? 'primary' : ($schedule['status'] == 'Completed' ? 'success' : 'warning') ?>">
                                        <?= $schedule['status'] ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (PermissionHelper::canManage('examinations')): ?>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Delete this schedule?')">
                                        <?= CSRF::field() ?>
                                        <input type="hidden" name="action" value="delete_schedule">
                                        <input type="hidden" name="scheduleID" value="<?= $schedule['scheduleID'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php if ($schedule['specialInstructions']): ?>
                            <tr class="table-light">
                                <td colspan="9">
                                    <small><i class="bi bi-info-circle me-1"></i><strong>Note:</strong> <?= htmlspecialchars($schedule['specialInstructions']) ?></small>
                                </td>
                            </tr>
                            <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Validate time range
document.getElementById('scheduleForm').addEventListener('submit', function(e) {
    const startTime = document.querySelector('input[name="startTime"]').value;
    const endTime = document.querySelector('input[name="endTime"]').value;
    
    if (startTime >= endTime) {
        e.preventDefault();
        alert('End time must be after start time');
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
