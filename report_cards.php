<?php
require_once 'includes/bootstrap.php';
require_once 'includes/Auth.php';

Auth::requireLogin();

require_once 'modules/roles/RolesModel.php';
$rolesModel = new RolesModel();
if (!$rolesModel->userHasPermission(Auth::id(), 'view_grades')) {
    Session::setFlash('error', 'You do not have permission to view grades.');
    header('Location: 403.php');
    exit;
}

require_once 'modules/grades/GradesModel.php';
require_once 'modules/pupils/PupilModel.php';
require_once 'modules/classes/ClassModel.php';
require_once 'modules/settings/SettingsModel.php';
require_once 'modules/attendance/AttendanceModel.php';

$gradesModel = new GradesModel();
$pupilModel = new PupilModel();
$classModel = new ClassModel();
$settingsModel = new SettingsModel();
$attendanceModel = new AttendanceModel();

// Get filters
$pupilID = $_GET['pupil'] ?? '';
$term = $_GET['term'] ?? $settingsModel->getSetting('current_term', '1');
$academicYear = $_GET['academic_year'] ?? $settingsModel->getSetting('current_academic_year', '2025-2026');

$pupil = null;
$grades = [];
$average = null;
$ranking = null;
$attendanceStats = null;

if ($pupilID) {
    $pupil = $pupilModel->getPupilWithParent($pupilID);
    if ($pupil) {
        // Get all grades for this pupil
        $grades = $gradesModel->getGradesByPupil($pupilID, $term, $academicYear);
        
        // Get averages
        $average = $gradesModel->getPupilAverage($pupilID, $term, $academicYear);
        
        // Get ranking in class
        if (!empty($pupil['classID'])) {
            $ranking = $gradesModel->getPupilRank($pupilID, $pupil['classID'], $term, $academicYear);
        }
        
        // Get attendance stats
        $attendanceStats = $attendanceModel->getPupilAttendanceStats($pupilID, $term, $academicYear);
    }
}

// Get all pupils for dropdown
$allPupils = $pupilModel->getAllWithParents();

$pageTitle = 'Report Cards';
$currentPage = 'report_cards';
require_once 'includes/header.php';
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="bi bi-file-earmark-text"></i> Student Report Card
        </h5>
        <?php if ($pupil): ?>
        <div>
            <button onclick="window.print()" class="btn btn-primary btn-sm">
                <i class="bi bi-printer"></i> Print
            </button>
            <a href="report_card_pdf.php?pupil=<?= $pupilID ?>&term=<?= $term ?>&academic_year=<?= $academicYear ?>" 
               class="btn btn-danger btn-sm" target="_blank">
                <i class="bi bi-file-pdf"></i> Export PDF
            </a>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="card-body">
        <!-- Filter Form -->
        <form method="GET" class="row g-3 mb-4 p-3 bg-light rounded no-print">
            <div class="col-md-6">
                <label class="form-label">Student *</label>
                <select name="pupil" class="form-select" required onchange="this.form.submit()">
                    <option value="">-- Select Student --</option>
                    <?php foreach ($allPupils as $p): ?>
                    <option value="<?= $p['pupilID'] ?>" <?= $pupilID == $p['pupilID'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($p['admNo'] . ' - ' . $p['fName'] . ' ' . $p['lName']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-3">
                <label class="form-label">Term</label>
                <select name="term" class="form-select" onchange="this.form.submit()">
                    <option value="1" <?= $term == '1' ? 'selected' : '' ?>>Term 1</option>
                    <option value="2" <?= $term == '2' ? 'selected' : '' ?>>Term 2</option>
                    <option value="3" <?= $term == '3' ? 'selected' : '' ?>>Term 3</option>
                </select>
            </div>
            
            <div class="col-md-3">
                <label class="form-label">Academic Year</label>
                <input type="text" name="academic_year" class="form-control" 
                       value="<?= htmlspecialchars($academicYear) ?>" 
                       placeholder="2025-2026" onchange="this.form.submit()">
            </div>
        </form>
        
        <?php if (!$pupilID): ?>
        <div class="alert alert-warning">
            <i class="bi bi-info-circle"></i> Please select a student to view their report card.
        </div>
        <?php elseif (!$pupil): ?>
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle"></i> Student not found.
        </div>
        <?php elseif (empty($grades)): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> No grades recorded for this student in Term <?= $term ?>, <?= $academicYear ?>.
        </div>
        <?php else: ?>
        
        <!-- Report Card -->
        <div id="reportCard" class="p-4 border rounded bg-white">
            <!-- School Header -->
            <div class="text-center mb-4 pb-3 border-bottom">
                <h2 class="fw-bold text-primary">LILAYI PARK SCHOOL</h2>
                <p class="mb-0">Excellence in Education</p>
                <h4 class="mt-3">STUDENT REPORT CARD</h4>
                <p class="mb-0">Academic Year: <?= htmlspecialchars($academicYear) ?> | Term <?= $term ?></p>
            </div>
            
            <!-- Student Details -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <table class="table table-sm table-borderless">
                        <tr>
                            <th width="40%">Student Name:</th>
                            <td><strong><?= htmlspecialchars($pupil['fName'] . ' ' . $pupil['lName']) ?></strong></td>
                        </tr>
                        <tr>
                            <th>Admission No:</th>
                            <td><?= htmlspecialchars($pupil['admNo']) ?></td>
                        </tr>
                        <tr>
                            <th>Class:</th>
                            <td><?= htmlspecialchars($pupil['className'] ?? 'N/A') ?></td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-sm table-borderless">
                        <tr>
                            <th width="40%">Date of Birth:</th>
                            <td><?= date('M d, Y', strtotime($pupil['dob'])) ?></td>
                        </tr>
                        <tr>
                            <th>Gender:</th>
                            <td><?= ucfirst($pupil['gender']) ?></td>
                        </tr>
                        <?php if ($attendanceStats): ?>
                        <tr>
                            <th>Attendance:</th>
                            <td><?= $attendanceStats['present'] ?? 0 ?> / <?= $attendanceStats['total'] ?? 0 ?> days</td>
                        </tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
            
            <!-- Grades Table -->
            <h5 class="mb-3">Academic Performance</h5>
            <div class="table-responsive mb-4">
                <table class="table table-bordered">
                    <thead class="table-primary">
                        <tr>
                            <th>Subject</th>
                            <th>CAT</th>
                            <th>Mid Term</th>
                            <th>End Term</th>
                            <th>Total</th>
                            <th>Grade</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $subjects = [];
                        foreach ($grades as $grade) {
                            $subjects[$grade['subjectID']]['name'] = $grade['subjectName'];
                            $subjects[$grade['subjectID']][$grade['examType']] = $grade;
                        }
                        
                        foreach ($subjects as $subjectID => $subject): 
                            $cat = $subject['CAT'] ?? null;
                            $midTerm = $subject['MidTerm'] ?? null;
                            $endTerm = $subject['EndTerm'] ?? null;
                            
                            $totalMarks = 0;
                            $count = 0;
                            if ($cat) { $totalMarks += $cat['marks']; $count++; }
                            if ($midTerm) { $totalMarks += $midTerm['marks']; $count++; }
                            if ($endTerm) { $totalMarks += $endTerm['marks']; $count++; }
                            
                            $average = $count > 0 ? $totalMarks / $count : 0;
                            $gradeInfo = $gradesModel->calculateGrade($average, 100);
                        ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($subject['name']) ?></strong></td>
                            <td><?= $cat ? $cat['marks'] : '-' ?></td>
                            <td><?= $midTerm ? $midTerm['marks'] : '-' ?></td>
                            <td><?= $endTerm ? $endTerm['marks'] : '-' ?></td>
                            <td><strong><?= $count > 0 ? number_format($average, 1) : '-' ?></strong></td>
                            <td>
                                <span class="badge bg-<?= match($gradeInfo['grade']) {
                                    'A' => 'success', 'B' => 'primary', 'C' => 'info',
                                    'D' => 'warning', 'E' => 'secondary', default => 'danger'
                                } ?>">
                                    <?= $gradeInfo['grade'] ?>
                                </span>
                            </td>
                            <td><small><?= $endTerm['remarks'] ?? '' ?></small></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <th colspan="4">OVERALL AVERAGE</th>
                            <th><?= number_format($average['average'] ?? 0, 2) ?>%</th>
                            <th>GPA: <?= number_format($average['GPA'] ?? 0, 2) ?></th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
            
            <!-- Performance Summary -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h6 class="card-title">Performance Summary</h6>
                            <table class="table table-sm table-borderless mb-0">
                                <tr>
                                    <td>Overall Average:</td>
                                    <td><strong><?= number_format($average['average'] ?? 0, 2) ?>%</strong></td>
                                </tr>
                                <tr>
                                    <td>Grade Point Average:</td>
                                    <td><strong><?= number_format($average['GPA'] ?? 0, 2) ?></strong></td>
                                </tr>
                                <tr>
                                    <td>Subjects Taken:</td>
                                    <td><strong><?= $average['subjectsTaken'] ?? 0 ?></strong></td>
                                </tr>
                                <?php if ($ranking): ?>
                                <tr>
                                    <td>Class Position:</td>
                                    <td><strong><?= $ranking['rank'] ?> / <?= $ranking['totalStudents'] ?></strong></td>
                                </tr>
                                <?php endif; ?>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h6 class="card-title">Grading Scale</h6>
                            <table class="table table-sm mb-0">
                                <tr><td>80-100:</td><td><span class="badge bg-success">A - Excellent</span></td></tr>
                                <tr><td>70-79:</td><td><span class="badge bg-primary">B - Very Good</span></td></tr>
                                <tr><td>60-69:</td><td><span class="badge bg-info">C - Good</span></td></tr>
                                <tr><td>50-59:</td><td><span class="badge bg-warning">D - Fair</span></td></tr>
                                <tr><td>40-49:</td><td><span class="badge bg-secondary">E - Pass</span></td></tr>
                                <tr><td>0-39:</td><td><span class="badge bg-danger">F - Fail</span></td></tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Comments Section -->
            <div class="mb-4">
                <h6>Class Teacher's Comment:</h6>
                <div class="border rounded p-3 mb-3" style="min-height: 80px;">
                    <em class="text-muted">
                        <?php
                        $avgPercent = $average['average'] ?? 0;
                        if ($avgPercent >= 80) {
                            echo "Excellent performance! Keep up the outstanding work.";
                        } elseif ($avgPercent >= 70) {
                            echo "Very good performance. Continue working hard.";
                        } elseif ($avgPercent >= 60) {
                            echo "Good effort. Keep striving for excellence.";
                        } elseif ($avgPercent >= 50) {
                            echo "Fair performance. More effort is needed.";
                        } elseif ($avgPercent >= 40) {
                            echo "Satisfactory. Please put in more effort.";
                        } else {
                            echo "Needs significant improvement. Extra attention required.";
                        }
                        ?>
                    </em>
                </div>
                
                <h6>Head Teacher's Comment:</h6>
                <div class="border rounded p-3 mb-3" style="min-height: 80px;">
                    <em class="text-muted">
                        <?php if ($ranking && $ranking['rank'] <= 3): ?>
                        Congratulations on your excellent performance. Well done!
                        <?php elseif ($avgPercent >= 50): ?>
                        Good effort. Continue to work hard and aim higher.
                        <?php else: ?>
                        Please work harder next term. You can do better.
                        <?php endif; ?>
                    </em>
                </div>
            </div>
            
            <!-- Signatures -->
            <div class="row mt-5 pt-4">
                <div class="col-md-4">
                    <div class="border-top pt-2 text-center">
                        <small>Class Teacher's Signature</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border-top pt-2 text-center">
                        <small>Head Teacher's Signature</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border-top pt-2 text-center">
                        <small>Parent's Signature</small>
                    </div>
                </div>
            </div>
            
            <div class="text-center mt-4 text-muted">
                <small>Generated on <?= date('F d, Y') ?></small>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
@media print {
    .no-print {
        display: none !important;
    }
    .card {
        border: none !important;
        box-shadow: none !important;
    }
    #reportCard {
        padding: 0 !important;
    }
}
</style>

<?php require_once 'includes/footer.php'; ?>
