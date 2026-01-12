<?php
require_once 'includes/bootstrap.php';
require_once 'includes/Auth.php';
require_once 'includes/PermissionHelper.php';

Auth::requireLogin();

require_once 'modules/roles/RolesModel.php';
$rolesModel = new RolesModel();
if (!$rolesModel->userHasPermission(Auth::id(), 'view_classes')) {
    Session::setFlash('error', 'You do not have permission to view timetables.');
    header('Location: /LilayiParkSchool/403.php');
    exit;
}

require_once 'modules/timetable/TimetableModel.php';
require_once 'modules/classes/ClassModel.php';
require_once 'modules/settings/SettingsModel.php';

$timetableModel = new TimetableModel();
$classModel = new ClassModel();
$settingsModel = new SettingsModel();

// Get filters
$classID = $_GET['class'] ?? '';
$term = $_GET['term'] ?? $settingsModel->getSetting('current_term', '1');
$academicYear = $_GET['academic_year'] ?? $settingsModel->getSetting('current_academic_year', '2025-2026');

// Get all classes
$classes = $classModel->all();

// Get timetable
$timetable = [];
if ($classID) {
    $timetable = $timetableModel->getByClass($classID, $term, $academicYear);
}

// Organize by day
$schedule = [
    'Monday' => [],
    'Tuesday' => [],
    'Wednesday' => [],
    'Thursday' => [],
    'Friday' => []
];

foreach ($timetable as $entry) {
    $schedule[$entry['dayOfWeek']][] = $entry;
}

$pageTitle = 'Class Timetable';
$currentPage = 'timetable';
require_once 'includes/header.php';
?>

<!-- Page Header -->
<div class="row mb-4">
    <div class="col-md-8">
        <h2 class="mb-0">
            <i class="bi bi-calendar3 me-2" style="color: #2d5016;"></i>
            <span style="color: #2d5016; font-weight: 600;">Class Timetable</span>
        </h2>
        <p class="text-muted mt-1">View and manage class schedules</p>
    </div>
    <div class="col-md-4 text-end align-self-center">
        <?php if (Auth::hasRole('admin')): ?>
        <a href="timetable_form.php" class="btn btn-lg shadow-sm" style="background-color: #2d5016; color: white;">
            <i class="bi bi-plus-circle me-1"></i> Add Schedule
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- Filters -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label class="form-label fw-semibold">Class</label>
                <select class="form-select" name="class" required>
                    <option value="">Select Class</option>
                    <?php foreach ($classes as $class): ?>
                    <option value="<?= $class['classID'] ?>" <?= $classID == $class['classID'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($class['className']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold">Term</label>
                <select class="form-select" name="term">
                    <option value="1" <?= $term == '1' ? 'selected' : '' ?>>Term 1</option>
                    <option value="2" <?= $term == '2' ? 'selected' : '' ?>>Term 2</option>
                    <option value="3" <?= $term == '3' ? 'selected' : '' ?>>Term 3</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold">Academic Year</label>
                <input type="text" class="form-control" name="academic_year" value="<?= htmlspecialchars($academicYear) ?>">
            </div>
            <div class="col-md-2 align-self-end">
                <button type="submit" class="btn w-100" style="background-color: #2d5016; color: white;">
                    <i class="bi bi-filter me-1"></i> Filter
                </button>
            </div>
        </form>
    </div>
</div>

<?php if ($classID && !empty($timetable)): ?>
<!-- Timetable -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom">
        <h5 class="mb-0">
            <i class="bi bi-calendar-week me-2"></i>Weekly Schedule
        </h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead style="background-color: #f8f9fa;">
                    <tr>
                        <th width="15%">Time</th>
                        <th>Monday</th>
                        <th>Tuesday</th>
                        <th>Wednesday</th>
                        <th>Thursday</th>
                        <th>Friday</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $timeSlots = [];
                    foreach ($timetable as $entry) {
                        $timeSlot = date('H:i', strtotime($entry['startTime'])) . ' - ' . date('H:i', strtotime($entry['endTime']));
                        if (!in_array($timeSlot, $timeSlots)) {
                            $timeSlots[] = $timeSlot;
                        }
                    }
                    sort($timeSlots);
                    
                    foreach ($timeSlots as $timeSlot):
                        list($start, $end) = explode(' - ', $timeSlot);
                    ?>
                    <tr>
                        <td class="fw-semibold"><?= $timeSlot ?></td>
                        <?php foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'] as $day): ?>
                        <td>
                            <?php
                            $found = false;
                            foreach ($schedule[$day] as $entry) {
                                $entryTime = date('H:i', strtotime($entry['startTime'])) . ' - ' . date('H:i', strtotime($entry['endTime']));
                                if ($entryTime == $timeSlot) {
                                    $found = true;
                            ?>
                            <div class="p-2 rounded" style="background-color: #e8f5e9;">
                                <strong class="d-block" style="color: #2d5016;"><?= htmlspecialchars($entry['subjectName']) ?></strong>
                                <small class="text-muted">
                                    <i class="bi bi-person"></i> <?= htmlspecialchars($entry['teacherFirstName'] . ' ' . $entry['teacherLastName']) ?>
                                </small>
                                <?php if ($entry['room']): ?>
                                <br><small class="text-muted"><i class="bi bi-door-open"></i> <?= htmlspecialchars($entry['room']) ?></small>
                                <?php endif; ?>
                                <?php if (Auth::hasRole('admin')): ?>
                                <div class="mt-1">
                                    <a href="timetable_form.php?id=<?= $entry['timetableID'] ?>" class="btn btn-sm btn-outline-warning">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php
                                }
                            }
                            if (!$found) {
                                echo '<span class="text-muted">-</span>';
                            }
                            ?>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php elseif ($classID): ?>
<div class="alert alert-info">
    <i class="bi bi-info-circle me-2"></i>No timetable found for the selected class and term.
</div>
<?php else: ?>
<div class="alert alert-secondary">
    <i class="bi bi-funnel me-2"></i>Please select a class to view the timetable.
</div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
