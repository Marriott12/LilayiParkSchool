<?php
require_once 'includes/bootstrap.php';
require_once 'includes/Auth.php';
require_once 'includes/PermissionHelper.php';

Auth::requireLogin();

require_once 'modules/roles/RolesModel.php';
$rolesModel = new RolesModel();
if (!$rolesModel->userHasPermission(Auth::id(), 'view_classes')) {
    Session::setFlash('error', 'You do not have permission to view timetables.');
    header('Location: 403.php');
    exit;
}

require_once 'modules/timetable/TimetableModel.php';
require_once 'modules/classes/ClassModel.php';
require_once 'modules/teachers/TeacherModel.php';
require_once 'modules/subjects/SubjectModel.php';

$timetableModel = new TimetableModel();
$classModel = new ClassModel();
$teacherModel = new TeacherModel();
$subjectModel = new SubjectModel();

$timetableID = $_GET['id'] ?? null;
if (!$timetableID) {
    Session::setFlash('error', 'Timetable ID is required.');
    header('Location: timetable_list.php');
    exit;
}

// Get timetable entry
$timetable = $timetableModel->find($timetableID);
if (!$timetable) {
    Session::setFlash('error', 'Timetable entry not found.');
    header('Location: timetable_list.php');
    exit;
}

// Get related data
$class = $classModel->find($timetable['classID']);
$subject = $subjectModel->find($timetable['subjectID']);
$teacher = $teacherModel->find($timetable['teacherID']);

$pageTitle = 'Timetable Details';
$currentPage = 'timetable';
require_once 'includes/header.php';
?>

<!-- Page Header -->
<div class="row mb-4">
    <div class="col-md-8">
        <h2 class="mb-0">
            <i class="bi bi-calendar3 me-2" style="color: #2d5016;"></i>
            <span style="color: #2d5016; font-weight: 600;">Timetable Entry Details</span>
        </h2>
        <p class="text-muted mt-1">View timetable entry information</p>
    </div>
    <div class="col-md-4 text-end">
        <a href="timetable_list.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to List
        </a>
        <?php if ($rolesModel->userHasPermission(Auth::id(), 'edit_classes')): ?>
        <a href="timetable_form.php?id=<?= $timetable['timetableID'] ?>" class="btn btn-primary">
            <i class="bi bi-pencil me-1"></i> Edit
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- Timetable Details Card -->
<div class="row">
    <div class="col-md-8">
        <div class="card shadow-sm mb-4">
            <div class="card-header" style="background-color: #2d5016; color: white;">
                <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i> Timetable Information</h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Class:</strong>
                        <p class="text-muted"><?= htmlspecialchars($class['className'] ?? 'N/A') ?></p>
                    </div>
                    <div class="col-md-6">
                        <strong>Subject:</strong>
                        <p class="text-muted"><?= htmlspecialchars($subject['subjectName'] ?? 'N/A') ?></p>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Teacher:</strong>
                        <p class="text-muted">
                            <?php if ($teacher): ?>
                                <?= htmlspecialchars($teacher['fName'] . ' ' . $teacher['lName']) ?>
                                <br>
                                <small class="text-muted">
                                    <i class="bi bi-envelope me-1"></i><?= htmlspecialchars($teacher['email']) ?>
                                </small>
                            <?php else: ?>
                                N/A
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <strong>Day:</strong>
                        <p class="text-muted">
                            <span class="badge bg-primary"><?= htmlspecialchars($timetable['dayOfWeek']) ?></span>
                        </p>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Start Time:</strong>
                        <p class="text-muted">
                            <i class="bi bi-clock me-1"></i><?= date('h:i A', strtotime($timetable['startTime'])) ?>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <strong>End Time:</strong>
                        <p class="text-muted">
                            <i class="bi bi-clock me-1"></i><?= date('h:i A', strtotime($timetable['endTime'])) ?>
                        </p>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Duration:</strong>
                        <p class="text-muted">
                            <?php
                            $start = new DateTime($timetable['startTime']);
                            $end = new DateTime($timetable['endTime']);
                            $duration = $start->diff($end);
                            echo $duration->format('%h hours %i minutes');
                            ?>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <strong>Room/Location:</strong>
                        <p class="text-muted"><?= htmlspecialchars($timetable['room'] ?? 'Not specified') ?></p>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Term:</strong>
                        <p class="text-muted"><?= htmlspecialchars($timetable['term']) ?></p>
                    </div>
                    <div class="col-md-6">
                        <strong>Academic Year:</strong>
                        <p class="text-muted"><?= htmlspecialchars($timetable['academicYear']) ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions Card -->
    <div class="col-md-4">
        <div class="card shadow-sm mb-4">
            <div class="card-header" style="background-color: #2d5016; color: white;">
                <h5 class="mb-0"><i class="bi bi-lightning me-2"></i> Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <?php if ($class): ?>
                    <a href="classes_view.php?id=<?= $class['classID'] ?>" class="btn btn-outline-primary">
                        <i class="bi bi-mortarboard me-1"></i> View Class Details
                    </a>
                    <?php endif; ?>
                    
                    <?php if ($teacher): ?>
                    <a href="teachers_view.php?id=<?= $teacher['teacherID'] ?>" class="btn btn-outline-primary">
                        <i class="bi bi-person me-1"></i> View Teacher Details
                    </a>
                    <?php endif; ?>
                    
                    <?php if ($subject): ?>
                    <a href="subjects_view.php?id=<?= $subject['subjectID'] ?>" class="btn btn-outline-primary">
                        <i class="bi bi-book me-1"></i> View Subject Details
                    </a>
                    <?php endif; ?>
                    
                    <a href="timetable_list.php?class=<?= $timetable['classID'] ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-calendar-week me-1"></i> View Class Timetable
                    </a>
                    
                    <?php if ($rolesModel->userHasPermission(Auth::id(), 'edit_classes')): ?>
                    <a href="timetable_form.php?id=<?= $timetable['timetableID'] ?>" class="btn btn-outline-success">
                        <i class="bi bi-pencil me-1"></i> Edit Entry
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Metadata Card -->
        <div class="card shadow-sm">
            <div class="card-header bg-light">
                <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i> Metadata</h6>
            </div>
            <div class="card-body">
                <small class="text-muted">
                    <strong>Timetable ID:</strong><br>
                    <?= htmlspecialchars($timetable['timetableID']) ?><br><br>
                    
                    <strong>Created:</strong><br>
                    <?= date('M d, Y h:i A', strtotime($timetable['createdAt'])) ?><br><br>
                    
                    <strong>Last Updated:</strong><br>
                    <?= date('M d, Y h:i A', strtotime($timetable['updatedAt'])) ?>
                </small>
            </div>
        </div>
    </div>
</div>

<!-- Weekly Schedule Context -->
<div class="card shadow-sm">
    <div class="card-header" style="background-color: #2d5016; color: white;">
        <h5 class="mb-0"><i class="bi bi-calendar-week me-2"></i> Weekly Schedule for <?= htmlspecialchars($class['className'] ?? '') ?></h5>
    </div>
    <div class="card-body">
        <?php
        // Get full week schedule for this class
        $weekSchedule = $timetableModel->getByClass($timetable['classID'], $timetable['term'], $timetable['academicYear']);
        
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
        $scheduleByDay = [];
        foreach ($weekSchedule as $entry) {
            $scheduleByDay[$entry['dayOfWeek']][] = $entry;
        }
        
        // Sort each day by start time
        foreach ($scheduleByDay as $day => $entries) {
            usort($scheduleByDay[$day], function($a, $b) {
                return strcmp($a['startTime'], $b['startTime']);
            });
        }
        ?>
        
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="table-light">
                    <tr>
                        <th width="15%">Day</th>
                        <th width="15%">Time</th>
                        <th width="30%">Subject</th>
                        <th width="25%">Teacher</th>
                        <th width="15%">Room</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($days as $day): ?>
                        <?php if (isset($scheduleByDay[$day]) && count($scheduleByDay[$day]) > 0): ?>
                            <?php foreach ($scheduleByDay[$day] as $index => $entry): ?>
                                <tr class="<?= $entry['timetableID'] == $timetableID ? 'table-primary' : '' ?>">
                                    <?php if ($index === 0): ?>
                                        <td rowspan="<?= count($scheduleByDay[$day]) ?>" class="align-middle">
                                            <strong><?= $day ?></strong>
                                        </td>
                                    <?php endif; ?>
                                    <td>
                                        <small>
                                            <?= date('h:i A', strtotime($entry['startTime'])) ?><br>
                                            <?= date('h:i A', strtotime($entry['endTime'])) ?>
                                        </small>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($entry['subjectName'] ?? 'N/A') ?>
                                        <?php if ($entry['timetableID'] == $timetableID): ?>
                                            <span class="badge bg-success ms-2">Current</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><small><?= htmlspecialchars($entry['teacherName'] ?? 'N/A') ?></small></td>
                                    <td><small><?= htmlspecialchars($entry['room'] ?? '-') ?></small></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td><strong><?= $day ?></strong></td>
                                <td colspan="4" class="text-muted text-center">
                                    <em>No classes scheduled</em>
                                </td>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
