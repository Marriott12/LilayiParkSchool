<?php
require_once 'includes/bootstrap.php';
require_once 'includes/Auth.php';
require_once 'includes/PermissionHelper.php';

Auth::requireLogin();

require_once 'modules/roles/RolesModel.php';
$rolesModel = new RolesModel();
if (!$rolesModel->userHasPermission(Auth::id(), 'view_grades')) {
    Session::setFlash('error', 'You do not have permission to view grades.');
    header('Location: /LilayiParkSchool/403.php');
    exit;
}

require_once 'modules/grades/GradesModel.php';
require_once 'modules/classes/ClassModel.php';
require_once 'modules/subjects/SubjectsModel.php';

$gradesModel = new GradesModel();
$classModel = new ClassModel();
$subjectsModel = new SubjectsModel();

// Get filters
$classID = $_GET['class'] ?? '';
$subjectID = $_GET['subject'] ?? '';
$term = $_GET['term'] ?? '';
$academicYear = $_GET['academic_year'] ?? '';
$examType = $_GET['exam_type'] ?? '';
$page = $_GET['page'] ?? 1;
$perPage = 50;

// Get current settings if not filtered
if (empty($term)) {
    require_once 'modules/settings/SettingsModel.php';
    $settingsModel = new SettingsModel();
    $term = $settingsModel->getSetting('current_term', '1');
}
if (empty($academicYear)) {
    $academicYear = $settingsModel->getSetting('current_academic_year', '2025-2026');
}

// Get data
if ($classID) {
    $totalRecords = $gradesModel->countGrades($classID, $term, $academicYear, $subjectID, $examType);
    $pagination = new Pagination($totalRecords, $perPage, $page);
    $grades = $gradesModel->getGradesByClass(
        $classID, $term, $academicYear, $subjectID, $examType,
        $pagination->getLimit(), $pagination->getOffset()
    );
    
    // Get class average if subject is selected
    if ($subjectID) {
        $average = $gradesModel->getClassAverage($classID, $subjectID, $term, $academicYear, $examType);
    }
} else {
    $grades = [];
    $totalRecords = 0;
    $pagination = new Pagination($totalRecords, $perPage, $page);
}

// Get filter options
$classes = $classModel->getAll();
$subjects = $subjectsModel->getAll();

$pageTitle = 'Grades Management';
$currentPage = 'grades';
require_once 'includes/header.php';
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="bi bi-award"></i> Grades & Marks
        </h5>
        <div>
            <?php if (PermissionHelper::canManage('grades')): ?>
            <a href="grades_form.php" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-circle"></i> Enter Grades
            </a>
            <a href="grades_bulk.php" class="btn btn-success btn-sm">
                <i class="bi bi-table"></i> Bulk Entry
            </a>
            <?php endif; ?>
            <?php if (!empty($grades)): ?>
            <button onclick="exportTableToCSV('grades_export.csv')" class="btn btn-secondary btn-sm">
                <i class="bi bi-download"></i> Export CSV
            </button>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="card-body">
        <form method="GET" class="row g-3 mb-4 p-3 bg-light rounded">
            <div class="col-md-3">
                <label class="form-label">Class *</label>
                <select name="class" class="form-select" required onchange="this.form.submit()">
                    <option value="">-- Select Class --</option>
                    <?php foreach ($classes as $class): ?>
                    <option value="<?= $class['classID'] ?>" <?= $classID == $class['classID'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($class['className']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-2">
                <label class="form-label">Subject</label>
                <select name="subject" class="form-select" onchange="this.form.submit()">
                    <option value="">All Subjects</option>
                    <?php foreach ($subjects as $subject): ?>
                    <option value="<?= $subject['subjectID'] ?>" <?= $subjectID == $subject['subjectID'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($subject['subjectName']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-2">
                <label class="form-label">Term</label>
                <select name="term" class="form-select" onchange="this.form.submit()">
                    <option value="1" <?= $term == '1' ? 'selected' : '' ?>>Term 1</option>
                    <option value="2" <?= $term == '2' ? 'selected' : '' ?>>Term 2</option>
                    <option value="3" <?= $term == '3' ? 'selected' : '' ?>>Term 3</option>
                </select>
            </div>
            
            <div class="col-md-3">
                <label class="form-label">Exam Type</label>
                <select name="exam_type" class="form-select" onchange="this.form.submit()">
                    <option value="">All Exams</option>
                    <option value="CAT" <?= $examType == 'CAT' ? 'selected' : '' ?>>CAT</option>
                    <option value="MidTerm" <?= $examType == 'MidTerm' ? 'selected' : '' ?>>Mid Term</option>
                    <option value="EndTerm" <?= $examType == 'EndTerm' ? 'selected' : '' ?>>End Term</option>
                    <option value="Mock" <?= $examType == 'Mock' ? 'selected' : '' ?>>Mock</option>
                    <option value="Final" <?= $examType == 'Final' ? 'selected' : '' ?>>Final</option>
                </select>
            </div>
            
            <div class="col-md-2">
                <label class="form-label">Academic Year</label>
                <input type="text" name="academic_year" class="form-control" 
                       value="<?= htmlspecialchars($academicYear) ?>" 
                       placeholder="2025-2026" onchange="this.form.submit()">
            </div>
        </form>
        
        <?php if (isset($average) && $subjectID): ?>
        <div class="alert alert-info mb-3">
            <strong>Class Statistics:</strong>
            Average: <strong><?= number_format($average['average'], 2) ?>%</strong> |
            GPA: <strong><?= number_format($average['avgGPA'], 2) ?></strong> |
            Highest: <strong><?= $average['highestMark'] ?></strong> |
            Lowest: <strong><?= $average['lowestMark'] ?></strong> |
            Students: <strong><?= $average['totalStudents'] ?></strong>
        </div>
        <?php endif; ?>
        
        <?php if (empty($classID)): ?>
        <div class="alert alert-warning">
            <i class="bi bi-info-circle"></i> Please select a class to view grades.
        </div>
        <?php elseif (empty($grades)): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> No grades found for the selected filters.
        </div>
        <?php else: ?>
        
        <div class="table-responsive">
            <table class="table table-hover table-sm" id="dataTable">
                <thead class="table-light">
                    <tr>
                        <th>Adm No</th>
                        <th>Student Name</th>
                        <?php if (!$subjectID): ?>
                        <th>Subject</th>
                        <?php endif; ?>
                        <th>Marks</th>
                        <th>Max</th>
                        <th>%</th>
                        <th>Grade</th>
                        <th>GPA</th>
                        <?php if (!$examType): ?>
                        <th>Exam Type</th>
                        <?php endif; ?>
                        <th>Recorded By</th>
                        <th>Date</th>
                        <?php if (PermissionHelper::canManage('grades')): ?>
                        <th>Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($grades as $grade): 
                        $percentage = ($grade['marks'] / $grade['maxMarks']) * 100;
                        $badgeClass = match($grade['grade']) {
                            'A' => 'success',
                            'B' => 'primary',
                            'C' => 'info',
                            'D' => 'warning',
                            'E' => 'secondary',
                            'F' => 'danger',
                            default => 'secondary'
                        };
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($grade['admNo']) ?></td>
                        <td><?= htmlspecialchars($grade['fName'] . ' ' . $grade['lName']) ?></td>
                        <?php if (!$subjectID): ?>
                        <td><?= htmlspecialchars($grade['subjectName']) ?></td>
                        <?php endif; ?>
                        <td><strong><?= $grade['marks'] ?></strong></td>
                        <td><?= $grade['maxMarks'] ?></td>
                        <td><?= number_format($percentage, 1) ?>%</td>
                        <td><span class="badge bg-<?= $badgeClass ?>"><?= $grade['grade'] ?></span></td>
                        <td><?= number_format($grade['gradePoint'], 2) ?></td>
                        <?php if (!$examType): ?>
                        <td><?= $grade['examType'] ?></td>
                        <?php endif; ?>
                        <td><small><?= htmlspecialchars($grade['recordedByName'] ?? 'N/A') ?></small></td>
                        <td><small><?= date('M d, Y', strtotime($grade['recordedAt'])) ?></small></td>
                        <?php if (PermissionHelper::canManage('grades')): ?>
                        <td>
                            <div class="btn-group btn-group-sm" role="group">
                                <a href="grades_form.php?id=<?= $grade['gradeID'] ?>" 
                                   class="btn btn-outline-warning btn-sm">
                                    <i class="bi bi-pencil"></i> Edit
                                </a>
                                <a href="delete.php?module=grades&id=<?= $grade['gradeID'] ?>" 
                                   class="btn btn-outline-danger btn-sm" 
                                   onclick="return confirm('Are you sure you want to delete this grade?');">
                                    <i class="bi bi-trash"></i> Delete
                                </a>
                            </div>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php endif; ?>
    </div>
    
    <?php if ($pagination->hasPages()): ?>
    <div class="card-footer">
        <?= $pagination->render() ?>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
