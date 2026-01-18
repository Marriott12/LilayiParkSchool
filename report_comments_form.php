<?php
require_once 'includes/bootstrap.php';
require_once 'includes/Auth.php';
require_once 'includes/PermissionHelper.php';
require_once 'includes/CSRF.php';

Auth::requireLogin();
Auth::requireAnyRole(['admin', 'teacher']);

require_once 'modules/reports/ReportCommentsModel.php';
require_once 'modules/pupils/PupilModel.php';
require_once 'modules/classes/ClassModel.php';
require_once 'modules/settings/SettingsModel.php';

$commentsModel = new ReportCommentsModel();
$pupilModel = new PupilModel();
$classModel = new ClassModel();
$settingsModel = new SettingsModel();

// Get filters
$classID = $_GET['class'] ?? '';
$term = $_GET['term'] ?? $settingsModel->getSetting('current_term', '1');
$academicYear = $_GET['academic_year'] ?? $settingsModel->getSetting('current_academic_year', '2025-2026');
$pupilID = $_GET['pupil'] ?? '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::validate($_POST['csrf_token']);
    
    if (isset($_POST['bulk_save'])) {
        // Bulk save comments for all pupils
        $commentsData = [];
        foreach ($_POST['pupils'] as $pID => $pupilData) {
            if (!empty($pupilData['teacherComment']) || !empty($pupilData['conductRating'])) {
                $commentsData[] = [
                    'pupilID' => $pID,
                    'classID' => $_POST['classID'],
                    'term' => $_POST['term'],
                    'academicYear' => $_POST['academicYear'],
                    'teacherComment' => $pupilData['teacherComment'] ?? '',
                    'headteacherComment' => $pupilData['headteacherComment'] ?? '',
                    'conductRating' => $pupilData['conductRating'] ?? null,
                    'attendance' => $pupilData['attendance'] ?? '',
                    'promotion' => $pupilData['promotion'] ?? 'Promoted',
                    'createdBy' => Auth::id()
                ];
            }
        }
        
        if (!empty($commentsData)) {
            $result = $commentsModel->bulkCreateOrUpdate($commentsData);
            Session::setFlash('success', "{$result['success']} comments saved successfully!");
            if (!empty($result['errors'])) {
                Session::setFlash('warning', implode('<br>', $result['errors']));
            }
        } else {
            Session::setFlash('warning', 'No comments to save.');
        }
    } else {
        // Single pupil comment
        $data = [
            'pupilID' => $_POST['pupilID'],
            'classID' => $_POST['classID'],
            'term' => $_POST['term'],
            'academicYear' => $_POST['academicYear'],
            'teacherComment' => $_POST['teacherComment'],
            'headteacherComment' => $_POST['headteacherComment'] ?? '',
            'conductRating' => $_POST['conductRating'],
            'attendance' => $_POST['attendance'],
            'promotion' => $_POST['promotion'],
            'createdBy' => Auth::id()
        ];
        
        $commentsModel->createOrUpdate($data);
        Session::setFlash('success', 'Comment saved successfully!');
    }
    
    header("Location: report_comments_form.php?class=$classID&term=$term&academic_year=$academicYear");
    exit;
}

// Get all classes
$classes = $classModel->all();

// Get pupils and existing comments if class selected
$pupils = [];
$existingComments = [];
if ($classID) {
    $pupils = $pupilModel->getPupilsByClass($classID);
    $existingComments = $commentsModel->getByClassTermYear($classID, $term, $academicYear);
    
    // Index comments by pupilID for easy access
    $commentsIndex = [];
    foreach ($existingComments as $comment) {
        $commentsIndex[$comment['pupilID']] = $comment;
    }
}

$pageTitle = 'Report Comments';
$currentPage = 'report_cards';
require_once 'includes/header.php';
?>

<!-- Page Header -->
<div class="row mb-4">
    <div class="col-md-12">
        <h2 class="mb-0">
            <i class="bi bi-chat-left-text me-2" style="color: #2d5016;"></i>
            <span style="color: #2d5016; font-weight: 600;">Report Comments & Conduct</span>
        </h2>
        <p class="text-muted mt-1">Add teacher comments, conduct ratings, and promotion decisions</p>
    </div>
</div>

<!-- Filters -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label class="form-label fw-semibold">Class <span class="text-danger">*</span></label>
                <select class="form-select" name="class" required onchange="this.form.submit()">
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
                <select class="form-select" name="term" onchange="this.form.submit()">
                    <option value="1" <?= $term == '1' ? 'selected' : '' ?>>Term 1</option>
                    <option value="2" <?= $term == '2' ? 'selected' : '' ?>>Term 2</option>
                    <option value="3" <?= $term == '3' ? 'selected' : '' ?>>Term 3</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold">Academic Year</label>
                <input type="text" class="form-control" name="academic_year" 
                       value="<?= htmlspecialchars($academicYear) ?>" onchange="this.form.submit()">
            </div>
            <div class="col-md-2 align-self-end">
                <button type="submit" class="btn w-100" style="background-color: #2d5016; color: white;">
                    <i class="bi bi-filter me-1"></i> Load
                </button>
            </div>
        </form>
    </div>
</div>

<?php if ($classID && !empty($pupils)): ?>
<!-- Comments Form -->
<form method="POST">
    <?= CSRF::field(); ?>
    <input type="hidden" name="classID" value="<?= $classID ?>">
    <input type="hidden" name="term" value="<?= $term ?>">
    <input type="hidden" name="academicYear" value="<?= $academicYear ?>">
    
    <div class="card border-0 shadow-sm">
        <div class="card-header" style="background-color: #2d5016; color: white;">
            <h5 class="mb-0">
                <i class="bi bi-people me-2"></i>Class Comments (<?= count($pupils) ?> pupils)
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th width="15%">Pupil</th>
                            <th width="25%">Teacher Comment</th>
                            <th width="25%">Head Teacher Comment</th>
                            <th width="12%">Conduct</th>
                            <th width="10%">Attendance</th>
                            <th width="13%">Promotion</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pupils as $pupil): ?>
                        <?php
                        $existingComment = $commentsIndex[$pupil['pupilID']] ?? null;
                        ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($pupil['fName'] . ' ' . $pupil['lName']) ?></strong>
                                <br><small class="text-muted"><?= htmlspecialchars($pupil['pupilID']) ?></small>
                            </td>
                            <td>
                                <textarea class="form-control form-control-sm" 
                                          name="pupils[<?= $pupil['pupilID'] ?>][teacherComment]" 
                                          rows="2" placeholder="Teacher's comment..."><?= htmlspecialchars($existingComment['teacherComment'] ?? '') ?></textarea>
                            </td>
                            <td>
                                <?php if (Auth::hasRole('admin')): ?>
                                <textarea class="form-control form-control-sm" 
                                          name="pupils[<?= $pupil['pupilID'] ?>][headteacherComment]" 
                                          rows="2" placeholder="Head teacher's comment..."><?= htmlspecialchars($existingComment['headteacherComment'] ?? '') ?></textarea>
                                <?php else: ?>
                                <small class="text-muted"><?= htmlspecialchars($existingComment['headteacherComment'] ?? 'N/A') ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <select class="form-select form-select-sm" 
                                        name="pupils[<?= $pupil['pupilID'] ?>][conductRating]">
                                    <option value="">-</option>
                                    <?php
                                    $ratings = ['Excellent', 'Very Good', 'Good', 'Fair', 'Poor'];
                                    foreach ($ratings as $rating):
                                    ?>
                                    <option value="<?= $rating ?>" 
                                            <?= ($existingComment && $existingComment['conductRating'] == $rating) ? 'selected' : '' ?>>
                                        <?= $rating ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <input type="text" class="form-control form-control-sm" 
                                       name="pupils[<?= $pupil['pupilID'] ?>][attendance]" 
                                       value="<?= htmlspecialchars($existingComment['attendance'] ?? '') ?>"
                                       placeholder="e.g., 95%">
                            </td>
                            <td>
                                <select class="form-select form-select-sm" 
                                        name="pupils[<?= $pupil['pupilID'] ?>][promotion]">
                                    <option value="Promoted" 
                                            <?= (!$existingComment || $existingComment['promotion'] == 'Promoted') ? 'selected' : '' ?>>
                                        Promoted
                                    </option>
                                    <option value="Retained" 
                                            <?= ($existingComment && $existingComment['promotion'] == 'Retained') ? 'selected' : '' ?>>
                                        Retained
                                    </option>
                                    <option value="Conditional" 
                                            <?= ($existingComment && $existingComment['promotion'] == 'Conditional') ? 'selected' : '' ?>>
                                        Conditional
                                    </option>
                                </select>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white">
            <button type="submit" name="bulk_save" value="1" class="btn btn-lg shadow-sm" 
                    style="background-color: #2d5016; color: white;">
                <i class="bi bi-save me-1"></i> Save All Comments
            </button>
            <a href="report_cards.php?class=<?= $classID ?>&term=<?= $term ?>&academic_year=<?= $academicYear ?>" 
               class="btn btn-lg btn-outline-secondary">
                <i class="bi bi-file-earmark-text me-1"></i> View Report Cards
            </a>
        </div>
    </div>
</form>
<?php elseif ($classID): ?>
<div class="alert alert-info">
    <i class="bi bi-info-circle me-2"></i>No pupils found in this class.
</div>
<?php else: ?>
<div class="alert alert-secondary">
    <i class="bi bi-funnel me-2"></i>Please select a class to manage report comments.
</div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
