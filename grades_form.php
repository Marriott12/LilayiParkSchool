<?php
require_once 'includes/bootstrap.php';
require_once 'includes/Auth.php';

Auth::requireLogin();

$gradeID = $_GET['id'] ?? null;
$isEdit = !empty($gradeID);

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
        $data = [
            'pupilID' => $_POST['pupilID'] ?? '',
            'subjectID' => $_POST['subjectID'] ?? '',
            'classID' => $_POST['classID'] ?? '',
            'term' => $_POST['term'] ?? '',
            'academicYear' => $_POST['academicYear'] ?? '',
            'examType' => $_POST['examType'] ?? '',
            'marks' => $_POST['marks'] ?? '',
            'maxMarks' => $_POST['maxMarks'] ?? 100,
            'remarks' => trim($_POST['remarks'] ?? '')
        ];
        
        // Validation
        if (empty($data['pupilID']) || empty($data['subjectID']) || empty($data['classID'])) {
            $error = 'Please select pupil, subject, and class';
        } elseif (empty($data['marks']) || !is_numeric($data['marks'])) {
            $error = 'Please enter valid marks';
        } elseif ($data['marks'] > $data['maxMarks']) {
            $error = 'Marks cannot exceed maximum marks';
        } elseif ($data['marks'] < 0) {
            $error = 'Marks cannot be negative';
        } else {
            try {
                if ($isEdit) {
                    $gradesModel->update($gradeID, $data);
                    Session::setFlash('success', 'Grade updated successfully');
                } else {
                    $gradesModel->saveGrade($data);
                    Session::setFlash('success', 'Grade recorded successfully');
                }
                CSRF::regenerateToken();
                header('Location: grades_list.php?class=' . $data['classID']);
                exit;
            } catch (Exception $e) {
                $error = 'Error: ' . $e->getMessage();
            }
        }
    }
}

// Get data for editing
if ($isEdit) {
    $grade = $gradesModel->find($gradeID);
    if (!$grade) {
        Session::setFlash('error', 'Grade not found');
        header('Location: grades_list.php');
        exit;
    }
} else {
    // Pre-fill with current settings
    $grade = [
        'term' => $settingsModel->getSetting('current_term', '1'),
        'academicYear' => $settingsModel->getSetting('current_academic_year', '2025-2026'),
        'maxMarks' => 100
    ];
}

// Get options
$pupils = $pupilModel->getAllWithParents();
$subjects = $subjectsModel->getAll();
$classes = $classModel->getAll();

$pageTitle = $isEdit ? 'Edit Grade' : 'Enter Grade';
$currentPage = 'grades';
require_once 'includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="bi bi-award"></i> <?= $pageTitle ?>
        </h5>
    </div>
    <div class="card-body">
        <?php if (isset($error)): ?>
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>
        
        <form method="POST" class="needs-validation" novalidate>
            <?= CSRF::field() ?>
            
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="pupilID" class="form-label">Student *</label>
                    <select name="pupilID" id="pupilID" class="form-select" required 
                            <?= $isEdit ? 'disabled' : '' ?>>
                        <option value="">-- Select Student --</option>
                        <?php foreach ($pupils as $pupil): ?>
                        <option value="<?= $pupil['pupilID'] ?>" 
                                <?= ($grade['pupilID'] ?? '') == $pupil['pupilID'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($pupil['admNo'] . ' - ' . $pupil['fName'] . ' ' . $pupil['lName']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($isEdit): ?>
                    <input type="hidden" name="pupilID" value="<?= $grade['pupilID'] ?>">
                    <?php endif; ?>
                </div>
                
                <div class="col-md-6">
                    <label for="classID" class="form-label">Class *</label>
                    <select name="classID" id="classID" class="form-select" required>
                        <option value="">-- Select Class --</option>
                        <?php foreach ($classes as $class): ?>
                        <option value="<?= $class['classID'] ?>" 
                                <?= ($grade['classID'] ?? '') == $class['classID'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($class['className']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label for="subjectID" class="form-label">Subject *</label>
                    <select name="subjectID" id="subjectID" class="form-select" required
                            <?= $isEdit ? 'disabled' : '' ?>>
                        <option value="">-- Select Subject --</option>
                        <?php foreach ($subjects as $subject): ?>
                        <option value="<?= $subject['subjectID'] ?>" 
                                <?= ($grade['subjectID'] ?? '') == $subject['subjectID'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($subject['subjectName']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($isEdit): ?>
                    <input type="hidden" name="subjectID" value="<?= $grade['subjectID'] ?>">
                    <?php endif; ?>
                </div>
                
                <div class="col-md-3">
                    <label for="term" class="form-label">Term *</label>
                    <select name="term" id="term" class="form-select" required>
                        <option value="1" <?= ($grade['term'] ?? '') == '1' ? 'selected' : '' ?>>Term 1</option>
                        <option value="2" <?= ($grade['term'] ?? '') == '2' ? 'selected' : '' ?>>Term 2</option>
                        <option value="3" <?= ($grade['term'] ?? '') == '3' ? 'selected' : '' ?>>Term 3</option>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="academicYear" class="form-label">Academic Year *</label>
                    <input type="text" class="form-control" id="academicYear" name="academicYear" 
                           value="<?= htmlspecialchars($grade['academicYear'] ?? '') ?>" 
                           placeholder="2025-2026" required>
                </div>
                
                <div class="col-md-4">
                    <label for="examType" class="form-label">Exam Type *</label>
                    <select name="examType" id="examType" class="form-select" required>
                        <option value="">-- Select Exam Type --</option>
                        <option value="CAT" <?= ($grade['examType'] ?? '') == 'CAT' ? 'selected' : '' ?>>CAT</option>
                        <option value="MidTerm" <?= ($grade['examType'] ?? '') == 'MidTerm' ? 'selected' : '' ?>>Mid Term</option>
                        <option value="EndTerm" <?= ($grade['examType'] ?? '') == 'EndTerm' ? 'selected' : '' ?>>End Term</option>
                        <option value="Mock" <?= ($grade['examType'] ?? '') == 'Mock' ? 'selected' : '' ?>>Mock</option>
                        <option value="Final" <?= ($grade['examType'] ?? '') == 'Final' ? 'selected' : '' ?>>Final</option>
                    </select>
                </div>
                
                <div class="col-md-4">
                    <label for="marks" class="form-label">Marks Obtained *</label>
                    <input type="number" class="form-control" id="marks" name="marks" 
                           value="<?= $grade['marks'] ?? '' ?>" 
                           min="0" max="<?= $grade['maxMarks'] ?? 100 ?>" step="0.01" required>
                </div>
                
                <div class="col-md-4">
                    <label for="maxMarks" class="form-label">Maximum Marks</label>
                    <input type="number" class="form-control" id="maxMarks" name="maxMarks" 
                           value="<?= $grade['maxMarks'] ?? 100 ?>" 
                           min="1" step="0.01" required onchange="updateMarksMax()">
                </div>
                
                <div class="col-12">
                    <label for="remarks" class="form-label">Remarks (Optional)</label>
                    <textarea class="form-control" id="remarks" name="remarks" 
                              rows="3"><?= htmlspecialchars($grade['remarks'] ?? '') ?></textarea>
                </div>
                
                <div class="col-12">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> Grade will be calculated automatically based on marks entered.
                    </div>
                </div>
            </div>
            
            <div class="mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle"></i> <?= $isEdit ? 'Update Grade' : 'Save Grade' ?>
                </button>
                <a href="grades_list.php<?= $isEdit ? '?class=' . $grade['classID'] : '' ?>" class="btn btn-secondary">
                    <i class="bi bi-x-circle"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<script>
// Update marks input max attribute when maxMarks changes
function updateMarksMax() {
    const maxMarks = document.getElementById('maxMarks').value;
    document.getElementById('marks').max = maxMarks;
}

// Form validation
(function() {
    'use strict';
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
})();
</script>

<?php require_once 'includes/footer.php'; ?>
