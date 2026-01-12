<?php
require_once 'includes/bootstrap.php';
require_once 'includes/Auth.php';
require_once 'includes/PermissionHelper.php';
require_once 'includes/CSRF.php';

Auth::requireLogin();
Auth::requireAnyRole(['admin', 'teacher']);

require_once 'modules/examinations/ExaminationsModel.php';
require_once 'modules/classes/ClassModel.php';
require_once 'modules/subjects/SubjectModel.php';
require_once 'modules/teachers/TeacherModel.php';

$examinationsModel = new ExaminationsModel();
$classModel = new ClassModel();
$subjectModel = new SubjectModel();
$teacherModel = new TeacherModel();

$scheduleID = $_GET['id'] ?? null;
$examID = $_GET['examID'] ?? null;

// Get schedule if editing
$schedule = null;
if ($scheduleID) {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT * FROM examschedule WHERE scheduleID = ?");
    $stmt->execute([$scheduleID]);
    $schedule = $stmt->fetch(PDO::FETCH_ASSOC);
    $examID = $schedule['examID'];
}

if (!$examID) {
    Session::setFlash('error', 'Exam ID is required.');
    header('Location: examinations_list.php');
    exit;
}

// Get exam details
$exam = $examinationsModel->getById($examID);
if (!$exam) {
    Session::setFlash('error', 'Examination not found.');
    header('Location: examinations_list.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::validate($_POST['csrf_token']);
    
    $data = [
        'examID' => $examID,
        'classID' => $_POST['classID'],
        'subjectID' => $_POST['subjectID'],
        'examDate' => $_POST['examDate'],
        'startTime' => $_POST['startTime'],
        'endTime' => $_POST['endTime'],
        'room' => $_POST['room'] ?? null,
        'invigilator' => $_POST['invigilator'] ?? null
    ];
    
    // Check for conflicts
    $db = Database::getInstance()->getConnection();
    $conflictSql = "SELECT COUNT(*) FROM examschedule 
                    WHERE examID = ? AND examDate = ? AND room = ?
                    AND ((startTime < ? AND endTime > ?) OR (startTime < ? AND endTime > ?))";
    $params = [$examID, $data['examDate'], $data['room'], 
               $data['endTime'], $data['startTime'], $data['endTime'], $data['startTime']];
    
    if ($scheduleID) {
        $conflictSql .= " AND scheduleID != ?";
        $params[] = $scheduleID;
    }
    
    $stmt = $db->prepare($conflictSql);
    $stmt->execute($params);
    $hasConflict = $stmt->fetchColumn() > 0;
    
    if ($hasConflict) {
        Session::setFlash('error', 'Room conflict detected! This room is already booked for this time slot.');
    } else {
        if ($scheduleID) {
            $stmt = $db->prepare("UPDATE examschedule SET classID = ?, subjectID = ?, examDate = ?, 
                                 startTime = ?, endTime = ?, room = ?, invigilator = ? WHERE scheduleID = ?");
            $stmt->execute([$data['classID'], $data['subjectID'], $data['examDate'], 
                          $data['startTime'], $data['endTime'], $data['room'], $data['invigilator'], $scheduleID]);
            Session::setFlash('success', 'Exam schedule updated successfully!');
        } else {
            $stmt = $db->prepare("INSERT INTO examschedule (examID, classID, subjectID, examDate, startTime, endTime, room, invigilator) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$data['examID'], $data['classID'], $data['subjectID'], $data['examDate'], 
                          $data['startTime'], $data['endTime'], $data['room'], $data['invigilator']]);
            Session::setFlash('success', 'Exam schedule created successfully!');
        }
        header("Location: examinations_schedule.php?examID=$examID");
        exit;
    }
}

$classes = $classModel->all();
$subjects = $subjectModel->all();
$teachers = $teacherModel->all();

$pageTitle = $scheduleID ? 'Edit Exam Schedule' : 'Add Exam Schedule';
$currentPage = 'examinations';
require_once 'includes/header.php';
?>

<!-- Page Header -->
<div class="row mb-4">
    <div class="col-md-12">
        <h2 class="mb-0">
            <i class="bi bi-calendar-event me-2" style="color: #2d5016;"></i>
            <span style="color: #2d5016; font-weight: 600;"><?= $pageTitle ?></span>
        </h2>
        <p class="text-muted mt-1">
            Exam: <strong><?= htmlspecialchars($exam['examName']) ?></strong> 
            (<?= htmlspecialchars($exam['term']) ?> - <?= htmlspecialchars($exam['academicYear']) ?>)
        </p>
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
                        <option value="<?= $class['classID'] ?>" 
                                <?= ($schedule && $schedule['classID'] == $class['classID']) ? 'selected' : '' ?>>
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
                        <option value="<?= $subject['subjectID'] ?>" 
                                <?= ($schedule && $schedule['subjectID'] == $subject['subjectID']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($subject['subjectName']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label fw-semibold">
                        <i class="bi bi-calendar3 me-1"></i>Exam Date <span class="text-danger">*</span>
                    </label>
                    <input type="date" class="form-control" name="examDate" 
                           value="<?= $schedule['examDate'] ?? '' ?>" required>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label fw-semibold">
                        <i class="bi bi-clock me-1"></i>Start Time <span class="text-danger">*</span>
                    </label>
                    <input type="time" class="form-control" name="startTime" 
                           value="<?= $schedule['startTime'] ?? '' ?>" required>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label fw-semibold">
                        <i class="bi bi-clock-fill me-1"></i>End Time <span class="text-danger">*</span>
                    </label>
                    <input type="time" class="form-control" name="endTime" 
                           value="<?= $schedule['endTime'] ?? '' ?>" required>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label fw-semibold">
                        <i class="bi bi-door-open me-1"></i>Room/Venue
                    </label>
                    <input type="text" class="form-control" name="room" 
                           value="<?= htmlspecialchars($schedule['room'] ?? '') ?>" 
                           placeholder="e.g., Exam Hall, Room 101" list="room-list">
                    <datalist id="room-list">
                        <option value="Exam Hall">
                        <option value="Main Hall">
                        <option value="Room 101">
                        <option value="Room 102">
                        <option value="Room 103">
                        <option value="Science Lab">
                        <option value="Computer Lab">
                    </datalist>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label fw-semibold">
                        <i class="bi bi-person-badge me-1"></i>Invigilator
                    </label>
                    <select class="form-select" name="invigilator">
                        <option value="">Select Invigilator</option>
                        <?php foreach ($teachers as $teacher): ?>
                        <option value="<?= $teacher['teacherID'] ?>" 
                                <?= ($schedule && $schedule['invigilator'] == $teacher['teacherID']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($teacher['firstName'] . ' ' . $teacher['lastName']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="alert alert-info mt-3">
                <i class="bi bi-info-circle me-2"></i>
                <strong>Note:</strong> The system will check for room conflicts to prevent double-booking.
            </div>
            
            <div class="mt-4">
                <button type="submit" class="btn btn-lg shadow-sm" style="background-color: #2d5016; color: white;">
                    <i class="bi bi-check-circle me-1"></i>
                    <?= $scheduleID ? 'Update' : 'Create' ?> Schedule
                </button>
                <a href="examinations_schedule.php?examID=<?= $examID ?>" class="btn btn-lg btn-outline-secondary">
                    <i class="bi bi-x-circle me-1"></i>Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
