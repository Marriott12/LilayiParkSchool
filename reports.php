<?php
require_once 'includes/bootstrap.php';
require_once 'includes/Auth.php';

Auth::requireLogin();

require_once 'modules/roles/RolesModel.php';
$rolesModel = new RolesModel();
if (!$rolesModel->userHasPermission(Auth::id(), 'view_reports')) {
    Session::setFlash('error', 'You do not have permission to view reports.');
    header('Location: /LilayiParkSchool/403.php');
    exit;
}

require_once 'modules/reports/ReportsModel.php';
require_once 'modules/classes/ClassModel.php';

$reportsModel = new ReportsModel();
$classModel = new ClassModel();

// Get filter parameters
$reportType = $_GET['type'] ?? 'dashboard';
$term = $_GET['term'] ?? null;
$year = $_GET['year'] ?? date('Y');
$classID = $_GET['class_id'] ?? null;

// Fetch data based on report type
$reportData = [];
switch ($reportType) {
    case 'dashboard':
        $reportData = $reportsModel->getDashboardStats();
        break;
    case 'fees':
        $reportData = $reportsModel->getFeeCollectionReport($term, $year);
        break;
    case 'attendance':
        $reportData = $reportsModel->getAttendanceReport($term, $year);
        break;
    case 'enrollment':
        $reportData = $reportsModel->getClassEnrollmentReport();
        break;
}

// Get all classes for filters
$classes = $classModel->all();

$pageTitle = 'Reports & Analytics';
$currentPage = 'reports';
require_once 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-graph-up"></i> Reports & Analytics</h2>
    <button onclick="window.print()" class="btn btn-sm" style="background-color: #2d5016; color: white;">
        <i class="bi bi-printer"></i> Print Report
    </button>
</div>

<!-- Report Type Selector -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <label class="form-label">Report Type</label>
                <select class="form-select" id="reportType" onchange="changeReportType(this.value)">
                    <option value="dashboard" <?= $reportType === 'dashboard' ? 'selected' : '' ?>>Dashboard Overview</option>
                    <option value="fees" <?= $reportType === 'fees' ? 'selected' : '' ?>>Fee Collection</option>
                    <option value="attendance" <?= $reportType === 'attendance' ? 'selected' : '' ?>>Attendance Report</option>
                    <option value="enrollment" <?= $reportType === 'enrollment' ? 'selected' : '' ?>>Class Enrollment</option>
                </select>
            </div>
            
            <?php if ($reportType !== 'dashboard' && $reportType !== 'enrollment'): ?>
            <div class="col-md-3">
                <label class="form-label">Term</label>
                <select class="form-select" name="term" id="term" onchange="applyFilters()">
                    <option value="">All Terms</option>
                    <option value="1" <?= $term == '1' ? 'selected' : '' ?>>Term 1</option>
                    <option value="2" <?= $term == '2' ? 'selected' : '' ?>>Term 2</option>
                    <option value="3" <?= $term == '3' ? 'selected' : '' ?>>Term 3</option>
                </select>
            </div>
            
            <div class="col-md-3">
                <label class="form-label">Year</label>
                <select class="form-select" name="year" id="year" onchange="applyFilters()">
                    <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                    <option value="<?= $y ?>" <?= $year == $y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            
            <div class="col-md-3">
                <label class="form-label">&nbsp;</label>
                <button onclick="applyFilters()" class="btn w-100" style="background-color: #5cb85c; color: white;">
                    <i class="bi bi-filter"></i> Apply Filters
                </button>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Dashboard Report -->
<?php if ($reportType === 'dashboard'): ?>
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-white" style="background: linear-gradient(135deg, #2d5016 0%, #5cb85c 100%);">
            <div class="card-body">
                <h5 class="card-title"><i class="bi bi-people-fill"></i> Total Pupils</h5>
                <h2 class="mb-0"><?= number_format($reportData['totalPupils'] ?? 0) ?></h2>
                <small>Enrolled Students</small>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card text-white" style="background: linear-gradient(135deg, #5cb85c 0%, #f0ad4e 100%);">
            <div class="card-body">
                <h5 class="card-title"><i class="bi bi-person-workspace"></i> Teachers</h5>
                <h2 class="mb-0"><?= number_format($reportData['totalTeachers'] ?? 0) ?></h2>
                <small>Active Staff</small>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card text-white" style="background: linear-gradient(135deg, #f0ad4e 0%, #2d5016 100%);">
            <div class="card-body">
                <h5 class="card-title"><i class="bi bi-building"></i> Classes</h5>
                <h2 class="mb-0"><?= number_format($reportData['totalClasses'] ?? 0) ?></h2>
                <small>Active Classes</small>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card text-white bg-success">
            <div class="card-body">
                <h5 class="card-title"><i class="bi bi-person-plus"></i> New Enrollments</h5>
                <h2 class="mb-0"><?= number_format($reportData['recentEnrollments'] ?? 0) ?></h2>
                <small>Last 30 Days</small>
            </div>
        </div>
    </div>
</div>

<!-- Financial Summary -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h5 style="color: #2d5016;"><i class="bi bi-cash-stack"></i> Total Fees</h5>
                <h3 class="mb-0">K <?= number_format($reportData['totalFees'] ?? 0, 2) ?></h3>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h5 style="color: #5cb85c;"><i class="bi bi-credit-card"></i> Collected</h5>
                <h3 class="mb-0">K <?= number_format($reportData['totalPayments'] ?? 0, 2) ?></h3>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h5 style="color: #f0ad4e;"><i class="bi bi-exclamation-triangle"></i> Outstanding</h5>
                <h3 class="mb-0">K <?= number_format($reportData['outstandingBalance'] ?? 0, 2) ?></h3>
            </div>
        </div>
    </div>
</div>

<!-- Recent Pupils -->
<?php if (!empty($reportData['recentPupils'])): ?>
<div class="card">
    <div class="card-header" style="background-color: #f8f9fa;">
        <h5 class="mb-0"><i class="bi bi-clock-history"></i> Recent Enrollments</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Pupil ID</th>
                        <th>Name</th>
                        <th>Enrollment Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reportData['recentPupils'] as $pupil): ?>
                    <tr>
                        <td><?= htmlspecialchars($pupil['pupilID']) ?></td>
                        <td><?= htmlspecialchars($pupil['fName'] . ' ' . $pupil['lName']) ?></td>
                        <td><?= $pupil['enrollDate'] ? date('M d, Y', strtotime($pupil['enrollDate'])) : 'N/A' ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>
<?php endif; ?>

<!-- Fee Collection Report -->
<?php if ($reportType === 'fees'): ?>
<div class="card">
    <div class="card-header" style="background: linear-gradient(135deg, #2d5016 0%, #5cb85c 100%); color: white;">
        <h5 class="mb-0"><i class="bi bi-cash-coin"></i> Fee Collection Report <?= $term ? "- Term $term" : '' ?> <?= $year ?></h5>
    </div>
    <div class="card-body">
        <?php if (empty($reportData)): ?>
        <div class="text-center py-5">
            <i class="bi bi-inbox" style="font-size: 3rem; opacity: 0.3;"></i>
            <p class="text-muted mt-2">No fee data available for the selected period</p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead style="background-color: #f8f9fa;">
                    <tr>
                        <th>Class</th>
                        <th>Term</th>
                        <th>Year</th>
                        <th>Total Fee (ZMW)</th>
                        <th>Collected (ZMW)</th>
                        <th>Outstanding (ZMW)</th>
                        <th>Collection %</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $totalFees = 0;
                    $totalCollected = 0;
                    $totalOutstanding = 0;
                    foreach ($reportData as $row): 
                        $totalFees += $row['feeAmt'];
                        $totalCollected += $row['totalCollected'];
                        $totalOutstanding += $row['outstanding'];
                        $collectionRate = $row['feeAmt'] > 0 ? ($row['totalCollected'] / $row['feeAmt']) * 100 : 0;
                    ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($row['className']) ?></strong></td>
                        <td>Term <?= $row['term'] ?></td>
                        <td><?= $row['year'] ?></td>
                        <td>K <?= number_format($row['feeAmt'], 2) ?></td>
                        <td style="color: #5cb85c;"><strong>K <?= number_format($row['totalCollected'], 2) ?></strong></td>
                        <td style="color: #f0ad4e;"><strong>K <?= number_format($row['outstanding'], 2) ?></strong></td>
                        <td>
                            <div class="progress" style="height: 20px;">
                                <div class="progress-bar" style="background-color: #5cb85c; width: <?= $collectionRate ?>%">
                                    <?= number_format($collectionRate, 1) ?>%
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot style="background-color: #f8f9fa; font-weight: bold;">
                    <tr>
                        <td colspan="3">TOTAL</td>
                        <td>K <?= number_format($totalFees, 2) ?></td>
                        <td style="color: #5cb85c;">K <?= number_format($totalCollected, 2) ?></td>
                        <td style="color: #f0ad4e;">K <?= number_format($totalOutstanding, 2) ?></td>
                        <td><?= $totalFees > 0 ? number_format(($totalCollected / $totalFees) * 100, 1) : 0 ?>%</td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- Attendance Report -->
<?php if ($reportType === 'attendance'): ?>
<div class="card">
    <div class="card-header" style="background: linear-gradient(135deg, #2d5016 0%, #5cb85c 100%); color: white;">
        <h5 class="mb-0"><i class="bi bi-calendar-check"></i> Attendance Report <?= $term ? "- Term $term" : '' ?> <?= $year ?></h5>
    </div>
    <div class="card-body">
        <?php if (empty($reportData)): ?>
        <div class="text-center py-5">
            <i class="bi bi-inbox" style="font-size: 3rem; opacity: 0.3;"></i>
            <p class="text-muted mt-2">No attendance data available for the selected period</p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead style="background-color: #f8f9fa;">
                    <tr>
                        <th>Pupil ID</th>
                        <th>Name</th>
                        <th>Term</th>
                        <th>Year</th>
                        <th>Days Present</th>
                        <th>Days Absent</th>
                        <th>Total Days</th>
                        <th>Attendance Rate</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reportData as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['pupilID']) ?></td>
                        <td><strong><?= htmlspecialchars($row['fName'] . ' ' . $row['sName']) ?></strong></td>
                        <td>Term <?= $row['term'] ?></td>
                        <td><?= $row['year'] ?></td>
                        <td style="color: #5cb85c;"><strong><?= $row['daysPresent'] ?></strong></td>
                        <td style="color: #f0ad4e;"><strong><?= $row['daysAbsent'] ?></strong></td>
                        <td><?= $row['totalDays'] ?></td>
                        <td>
                            <?php 
                            $rate = $row['attendanceRate'];
                            $badgeClass = $rate >= 90 ? 'bg-success' : ($rate >= 75 ? 'bg-warning' : 'bg-danger');
                            ?>
                            <span class="badge <?= $badgeClass ?>"><?= $rate ?>%</span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- Class Enrollment Report -->
<?php if ($reportType === 'enrollment'): ?>
<div class="card">
    <div class="card-header" style="background: linear-gradient(135deg, #2d5016 0%, #5cb85c 100%); color: white;">
        <h5 class="mb-0"><i class="bi bi-building"></i> Class Enrollment Report</h5>
    </div>
    <div class="card-body">
        <?php if (empty($reportData)): ?>
        <div class="text-center py-5">
            <i class="bi bi-inbox" style="font-size: 3rem; opacity: 0.3;"></i>
            <p class="text-muted mt-2">No class enrollment data available</p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead style="background-color: #f8f9fa;">
                    <tr>
                        <th>Class Name</th>
                        <th>Class Teacher</th>
                        <th>Total Pupils</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $totalPupils = 0;
                    foreach ($reportData as $row): 
                        $totalPupils += $row['totalPupils'];
                        $capacity = $row['totalPupils'];
                        $statusBadge = $capacity == 0 ? 'bg-secondary' : ($capacity < 20 ? 'bg-warning' : 'bg-success');
                        $statusText = $capacity == 0 ? 'Empty' : ($capacity < 20 ? 'Low' : 'Good');
                    ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($row['className']) ?></strong></td>
                        <td><?= htmlspecialchars(($row['teacherFirstName'] ?? '') . ' ' . ($row['teacherLastName'] ?? 'Not Assigned')) ?></td>
                        <td>
                            <span class="badge" style="background-color: #2d5016; font-size: 1rem;">
                                <?= $row['totalPupils'] ?> pupils
                            </span>
                        </td>
                        <td><span class="badge <?= $statusBadge ?>"><?= $statusText ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot style="background-color: #f8f9fa; font-weight: bold;">
                    <tr>
                        <td colspan="2">TOTAL ENROLLMENT</td>
                        <td colspan="2"><?= $totalPupils ?> pupils across <?= count($reportData) ?> classes</td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<script>
function changeReportType(type) {
    window.location.href = 'reports.php?type=' + type;
}

function applyFilters() {
    const reportType = document.getElementById('reportType').value;
    const term = document.getElementById('term')?.value || '';
    const year = document.getElementById('year')?.value || '';
    
    let url = 'reports.php?type=' + reportType;
    if (term) url += '&term=' + term;
    if (year) url += '&year=' + year;
    
    window.location.href = url;
}
</script>

<style>
@media print {
    .btn, .form-select, .card-header, nav, .sidebar { display: none !important; }
    .card { border: none; }
}
</style>

<?php require_once 'includes/footer.php'; ?>
