<?php
require_once 'includes/bootstrap.php';
require_once 'includes/Auth.php';

Auth::requireLogin();

require_once 'modules/roles/RolesModel.php';
$rolesModel = new RolesModel();
if (!$rolesModel->userHasPermission(Auth::id(), 'manage_grades')) {
    Session::setFlash('error', 'You do not have permission to manage grades.');
    header('Location: /LilayiParkSchool/403.php');
    exit;
}

require_once 'modules/grades/GradesModel.php';
require_once 'modules/pupils/PupilModel.php';
require_once 'modules/subjects/SubjectsModel.php';
require_once 'modules/classes/ClassModel.php';
require_once 'modules/settings/SettingsModel.php';

$gradesModel = new GradesModel();
$pupilModel = new PupilModel();
$subjectsModel = new SubjectsModel();
$classModel = new ClassModel();
$settingsModel = new SettingsModel();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token first
    if (!CSRF::requireToken()) {
        $error = $GLOBALS['csrf_error'] ?? 'Security validation failed. Please try again.';
    } else {
        $classID = $_POST['classID'] ?? '';
        $subjectID = $_POST['subjectID'] ?? '';
        $term = $_POST['term'] ?? '';
        $academicYear = $_POST['academicYear'] ?? '';
        $examType = $_POST['examType'] ?? '';
        $maxMarks = $_POST['maxMarks'] ?? 100;
        
        $grades = [];
        $pupils = $_POST['pupils'] ?? [];
        
        foreach ($pupils as $pupilID => $marks) {
            if (!empty($marks) && is_numeric($marks)) {
                $grades[] = [
                    'pupilID' => $pupilID,
                    'subjectID' => $subjectID,
                    'classID' => $classID,
                    'term' => $term,
                    'academicYear' => $academicYear,
                    'examType' => $examType,
                    'marks' => $marks,
                    'maxMarks' => $maxMarks
                ];
            }
        }
        
        if (empty($grades)) {
            $error = 'Please enter marks for at least one student';
        } else {
            $result = $gradesModel->bulkSaveGrades($grades);
            if ($result['saved'] > 0) {
                $message = $result['saved'] . ' grades saved successfully';
                if (count($result['errors']) > 0) {
                    $message .= ', ' . count($result['errors']) . ' errors occurred';
                }
                Session::setFlash('success', $message);
                CSRF::regenerateToken();
                header('Location: grades_list.php?class=' . $classID);
                exit;
            } else {
                $error = 'Failed to save grades. ' . implode(', ', array_column($result['errors'], 'error'));
            }
        }
    }
}

// Get filter data
$classID = $_GET['class'] ?? ($_POST['classID'] ?? '');
$subjectID = $_GET['subject'] ?? ($_POST['subjectID'] ?? '');
$term = $_GET['term'] ?? $settingsModel->getSetting('current_term', '1');
$academicYear = $_GET['academic_year'] ?? $settingsModel->getSetting('current_academic_year', '2025-2026');
$examType = $_GET['exam_type'] ?? 'EndTerm';

// Get students in class
$students = [];
if ($classID) {
    $students = $pupilModel->getPupilsByClass($classID);
}

// Get options
$subjects = $subjectsModel->getAll();
$classes = $classModel->getAll();

$pageTitle = 'Bulk Grade Entry';
$currentPage = 'grades';
require_once 'includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="bi bi-table"></i> Bulk Grade Entry
        </h5>
    </div>
    <div class="card-body">
        <?php if (isset($error)): ?>
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>
        
        <!-- Filter Form -->
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
            
            <div class="col-md-3">
                <label class="form-label">Subject *</label>
                <select name="subject" class="form-select" required onchange="this.form.submit()">
                    <option value="">-- Select Subject --</option>
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
            
            <div class="col-md-2">
                <label class="form-label">Exam Type</label>
                <select name="exam_type" class="form-select" onchange="this.form.submit()">
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
                       value="<?= htmlspecialchars($academicYear) ?>" onchange="this.form.submit()">
            </div>
        </form>
        
        <?php if (empty($classID) || empty($subjectID)): ?>
        <div class="alert alert-warning">
            <i class="bi bi-info-circle"></i> Please select both class and subject to begin entry.
        </div>
        <?php elseif (empty($students)): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> No students found in this class.
        </div>
        <?php else: ?>
        
        <!-- Grade Entry Form -->
        <form method="POST" id="bulkForm">
            <?= CSRF::field() ?>
            <input type="hidden" name="classID" value="<?= $classID ?>">
            <input type="hidden" name="subjectID" value="<?= $subjectID ?>">
            <input type="hidden" name="term" value="<?= $term ?>">
            <input type="hidden" name="academicYear" value="<?= $academicYear ?>">
            <input type="hidden" name="examType" value="<?= $examType ?>">
            
            <div class="row mb-3">
                <div class="col-md-3">
                    <label for="maxMarks" class="form-label">Maximum Marks *</label>
                    <input type="number" class="form-control" id="maxMarks" name="maxMarks" 
                           value="100" min="1" step="0.01" required>
                </div>
                <div class="col-md-9">
                    <label class="form-label">&nbsp;</label>
                    <div>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="fillAll()">
                            Fill All Present
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="clearAll()">
                            Clear All
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th width="5%">#</th>
                            <th width="15%">Adm No</th>
                            <th width="40%">Student Name</th>
                            <th width="20%">Marks Obtained</th>
                            <th width="10%">Grade</th>
                            <th width="10%">GPA</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $index => $student): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><?= htmlspecialchars($student['admNo']) ?></td>
                            <td><?= htmlspecialchars($student['fName'] . ' ' . $student['lName']) ?></td>
                            <td>
                                <input type="number" class="form-control marks-input" 
                                       name="pupils[<?= $student['pupilID'] ?>]" 
                                       min="0" max="100" step="0.01"
                                       onchange="calculateGrade(this, <?= $student['pupilID'] ?>)">
                            </td>
                            <td>
                                <span class="badge bg-secondary" id="grade-<?= $student['pupilID'] ?>">-</span>
                            </td>
                            <td>
                                <span id="gpa-<?= $student['pupilID'] ?>">-</span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="mt-4">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="bi bi-check-circle"></i> Save All Grades
                </button>
                <a href="grades_list.php?class=<?= $classID ?>" class="btn btn-secondary">
                    <i class="bi bi-x-circle"></i> Cancel
                </a>
            </div>
        </form>
        <?php endif; ?>
    </div>
</div>

<script>
// Grade scale (can be loaded from database)
const gradeScale = [
    { min: 80, max: 100, grade: 'A', gpa: 4.00, class: 'success' },
    { min: 70, max: 79, grade: 'B', gpa: 3.00, class: 'primary' },
    { min: 60, max: 69, grade: 'C', gpa: 2.00, class: 'info' },
    { min: 50, max: 59, grade: 'D', gpa: 1.00, class: 'warning' },
    { min: 40, max: 49, grade: 'E', gpa: 0.50, class: 'secondary' },
    { min: 0, max: 39, grade: 'F', gpa: 0.00, class: 'danger' }
];

function calculateGrade(input, pupilID) {
    const marks = parseFloat(input.value);
    const maxMarks = parseFloat(document.getElementById('maxMarks').value) || 100;
    
    if (isNaN(marks) || marks === '') {
        document.getElementById('grade-' + pupilID).innerHTML = '-';
        document.getElementById('gpa-' + pupilID).innerHTML = '-';
        return;
    }
    
    // Update max attribute
    input.max = maxMarks;
    
    const percentage = (marks / maxMarks) * 100;
    const gradeInfo = gradeScale.find(g => percentage >= g.min && percentage <= g.max) || gradeScale[gradeScale.length - 1];
    
    const gradeBadge = document.getElementById('grade-' + pupilID);
    gradeBadge.innerHTML = gradeInfo.grade;
    gradeBadge.className = 'badge bg-' + gradeInfo.class;
    
    document.getElementById('gpa-' + pupilID).innerHTML = gradeInfo.gpa.toFixed(2);
}

function fillAll() {
    const value = prompt('Enter marks for all students:', '');
    if (value !== null && value !== '') {
        document.querySelectorAll('.marks-input').forEach(input => {
            input.value = value;
            calculateGrade(input, input.name.match(/\d+/)[0]);
        });
    }
}

function clearAll() {
    if (confirm('Clear all entered marks?')) {
        document.querySelectorAll('.marks-input').forEach(input => {
            input.value = '';
            const pupilID = input.name.match(/\d+/)[0];
            document.getElementById('grade-' + pupilID).innerHTML = '-';
            document.getElementById('gpa-' + pupilID).innerHTML = '-';
        });
    }
}

// Update all grade calculations when max marks changes
document.getElementById('maxMarks')?.addEventListener('change', function() {
    document.querySelectorAll('.marks-input').forEach(input => {
        if (input.value) {
            calculateGrade(input, input.name.match(/\d+/)[0]);
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
