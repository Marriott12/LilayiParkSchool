<?php
require_once 'includes/bootstrap.php';

RBAC::requireAuth();
RBAC::requirePermission('examinations', $_GET['examID'] ?? null ? 'update' : 'create');

require_once 'modules/examinations/ExaminationsModel.php';

$examinationsModel = new ExaminationsModel();

$isEdit = isset($_GET['examID']);
$examID = $_GET['examID'] ?? null;
$error = null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'examName' => trim($_POST['examName'] ?? ''),
        'examType' => $_POST['examType'] ?? '',
        'term' => $_POST['term'] ?? '',
        'academicYear' => $_POST['academicYear'] ?? '',
        'startDate' => $_POST['startDate'] ?? '',
        'endDate' => $_POST['endDate'] ?? '',
        'totalMarks' => $_POST['totalMarks'] ?? 100,
        'passingMarks' => $_POST['passingMarks'] ?? 40,
        'instructions' => trim($_POST['instructions'] ?? ''),
        'status' => $_POST['status'] ?? 'Scheduled'
    ];
    
    // Validation
    if (empty($data['examName'])) {
        $error = 'Exam name is required';
    } elseif (empty($data['examType'])) {
        $error = 'Exam type is required';
    } elseif (empty($data['term']) || $data['term'] < 1 || $data['term'] > 3) {
        $error = 'Valid term (1-3) is required';
    } elseif (empty($data['academicYear'])) {
        $error = 'Academic year is required';
    } elseif (empty($data['startDate'])) {
        $error = 'Start date is required';
    } elseif (empty($data['endDate'])) {
        $error = 'End date is required';
    } elseif (strtotime($data['endDate']) < strtotime($data['startDate'])) {
        $error = 'End date must be after start date';
    } elseif ($data['passingMarks'] > $data['totalMarks']) {
        $error = 'Passing marks cannot exceed total marks';
    }
    
    if (!isset($error)) {
        CSRF::requireToken();
        
        try {
            if ($isEdit) {
                $examinationsModel->update($examID, $data);
                Session::setFlash('success', 'Examination updated successfully');
            } else {
                $newExamID = $examinationsModel->create($data);
                Session::setFlash('success', 'Examination created successfully');
                // Redirect to schedule page for new exam
                CSRF::regenerateToken();
                header('Location: examinations_schedule.php?examID=' . $newExamID);
                exit;
            }
            
            CSRF::regenerateToken();
            header('Location: examinations_list.php');
            exit;
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

// Get exam data if editing
$exam = $isEdit ? $examinationsModel->getById($examID) : null;

$pageTitle = $isEdit ? 'Edit Examination' : 'Schedule New Examination';
$currentPage = 'examinations';
require_once 'includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="bi bi-clipboard-check me-2"></i><?= $pageTitle ?></h2>
                <a href="examinations_list.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Back to List
                </a>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <form method="POST" id="examForm">
                        <?= CSRF::field() ?>

                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label class="form-label required">Examination Name</label>
                                <input type="text" name="examName" class="form-control" 
                                       value="<?= htmlspecialchars($exam['examName'] ?? '') ?>" 
                                       placeholder="e.g., Term 1 End-Term Examination 2026"
                                       required autofocus>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label required">Exam Type</label>
                                <select name="examType" class="form-select" required>
                                    <option value="">Select Type</option>
                                    <option value="CAT" <?= ($exam['examType'] ?? '') == 'CAT' ? 'selected' : '' ?>>CAT</option>
                                    <option value="MidTerm" <?= ($exam['examType'] ?? '') == 'MidTerm' ? 'selected' : '' ?>>Mid-Term</option>
                                    <option value="EndTerm" <?= ($exam['examType'] ?? '') == 'EndTerm' ? 'selected' : '' ?>>End-Term</option>
                                    <option value="Mock" <?= ($exam['examType'] ?? '') == 'Mock' ? 'selected' : '' ?>>Mock</option>
                                    <option value="Final" <?= ($exam['examType'] ?? '') == 'Final' ? 'selected' : '' ?>>Final</option>
                                    <option value="Practice" <?= ($exam['examType'] ?? '') == 'Practice' ? 'selected' : '' ?>>Practice</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label required">Term</label>
                                <select name="term" class="form-select" required>
                                    <option value="">Select Term</option>
                                    <option value="1" <?= ($exam['term'] ?? '') == '1' ? 'selected' : '' ?>>Term 1</option>
                                    <option value="2" <?= ($exam['term'] ?? '') == '2' ? 'selected' : '' ?>>Term 2</option>
                                    <option value="3" <?= ($exam['term'] ?? '') == '3' ? 'selected' : '' ?>>Term 3</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label required">Academic Year</label>
                                <select name="academicYear" class="form-select" required>
                                    <option value="">Select Year</option>
                                    <option value="2025/2026" <?= ($exam['academicYear'] ?? '') == '2025/2026' ? 'selected' : '' ?>>2025/2026</option>
                                    <option value="2024/2025" <?= ($exam['academicYear'] ?? '') == '2024/2025' ? 'selected' : '' ?>>2024/2025</option>
                                    <option value="2026/2027" <?= ($exam['academicYear'] ?? '') == '2026/2027' ? 'selected' : '' ?>>2026/2027</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label required">Start Date</label>
                                <input type="date" name="startDate" class="form-control" 
                                       value="<?= $exam['startDate'] ?? '' ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label required">End Date</label>
                                <input type="date" name="endDate" class="form-control" 
                                       value="<?= $exam['endDate'] ?? '' ?>" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label required">Total Marks</label>
                                <input type="number" name="totalMarks" class="form-control" 
                                       value="<?= $exam['totalMarks'] ?? 100 ?>" min="1" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label required">Passing Marks</label>
                                <input type="number" name="passingMarks" class="form-control" 
                                       value="<?= $exam['passingMarks'] ?? 40 ?>" min="1" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label required">Status</label>
                                <select name="status" class="form-select" required>
                                    <option value="Scheduled" <?= ($exam['status'] ?? 'Scheduled') == 'Scheduled' ? 'selected' : '' ?>>Scheduled</option>
                                    <option value="Ongoing" <?= ($exam['status'] ?? '') == 'Ongoing' ? 'selected' : '' ?>>Ongoing</option>
                                    <option value="Completed" <?= ($exam['status'] ?? '') == 'Completed' ? 'selected' : '' ?>>Completed</option>
                                    <option value="Cancelled" <?= ($exam['status'] ?? '') == 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Instructions</label>
                            <textarea name="instructions" class="form-control" rows="4" 
                                      placeholder="General instructions for students and teachers..."><?= htmlspecialchars($exam['instructions'] ?? '') ?></textarea>
                            <small class="text-muted">Optional instructions that will appear on the exam schedule</small>
                        </div>

                        <div class="border-top pt-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle me-1"></i>
                                <?= $isEdit ? 'Update Examination' : 'Create & Schedule Classes' ?>
                            </button>
                            <a href="examinations_list.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>

            <?php if ($isEdit && $exam): ?>
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <a href="examinations_schedule.php?examID=<?= $examID ?>" class="btn btn-outline-primary">
                        <i class="bi bi-calendar3 me-1"></i> Manage Class Schedule
                    </a>
                    <a href="grades_list.php?examType=<?= urlencode($exam['examType']) ?>&term=<?= $exam['term'] ?>" 
                       class="btn btn-outline-secondary">
                        <i class="bi bi-journal-text me-1"></i> View Grades
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.required::after {
    content: " *";
    color: #dc3545;
}
</style>

<?php require_once 'includes/footer.php'; ?>
