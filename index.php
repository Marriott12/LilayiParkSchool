<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/Auth.php';
require_once __DIR__ . '/includes/PermissionHelper.php';

// Require authentication
Auth::requireLogin();

// Check if user must change password
require_once __DIR__ . '/modules/users/UsersModel.php';
$usersModel = new UsersModel();
$currentUser = $usersModel->find(Auth::id());
if (isset($currentUser['mustChangePassword']) && $currentUser['mustChangePassword'] === 'Y') {
    header('Location: ' . BASE_URL . '/change_password.php');
    exit;
}

// Set page variables
$pageTitle = 'Dashboard';
$currentPage = 'dashboard';

// Get dashboard statistics
require_once __DIR__ . '/modules/reports/ReportsModel.php';
require_once __DIR__ . '/modules/announcements/AnnouncementsModel.php';
require_once __DIR__ . '/modules/settings/SettingsModel.php';
require_once __DIR__ . '/modules/roles/RolesModel.php';

$rolesModel = new RolesModel();
$reportsModel = new ReportsModel();
$stats = $reportsModel->getDashboardStats();
// Add totalParents to stats if not present
if (!isset($stats['totalParents'])) {
    $parentCount = $db->query('SELECT COUNT(*) FROM Parent')->fetchColumn();
    $stats['totalParents'] = $parentCount;
}

// Get gender statistics
$db = Database::getInstance()->getConnection();
$genderStmt = $db->query("SELECT gender, COUNT(*) as count FROM Pupil GROUP BY gender");
$genderStats = [];
while ($row = $genderStmt->fetch()) {
    $genderStats[$row['gender']] = $row['count'];
}
$maleCount = $genderStats['M'] ?? 0;
$femaleCount = $genderStats['F'] ?? 0;

// Get class distribution by gender
$classDistStmt = $db->query("
    SELECT 
        c.classID,
        c.className,
        COUNT(CASE WHEN p.gender = 'M' THEN 1 END) as maleCount,
        COUNT(CASE WHEN p.gender = 'F' THEN 1 END) as femaleCount,
        COUNT(p.pupilID) as totalCount
    FROM Class c
    LEFT JOIN Pupil_Class pc ON c.classID = pc.classID
    LEFT JOIN Pupil p ON pc.pupilID = p.pupilID
    GROUP BY c.classID, c.className
    ORDER BY c.className ASC
");
$classDistribution = $classDistStmt->fetchAll();

// Get upcoming birthdays (next 7 days)
$upcomingBirthdays = $db->query("
    SELECT pupilID, fName, lName, DoB,
           DATE_FORMAT(DoB, '%M %d') as birthDate,
           DATEDIFF(
               DATE_ADD(DoB, INTERVAL (YEAR(CURDATE()) - YEAR(DoB) + IF(DAYOFYEAR(DoB) < DAYOFYEAR(CURDATE()), 1, 0)) YEAR),
               CURDATE()
           ) as daysUntil
    FROM Pupil
    WHERE DATEDIFF(
        DATE_ADD(DoB, INTERVAL (YEAR(CURDATE()) - YEAR(DoB) + IF(DAYOFYEAR(DoB) < DAYOFYEAR(CURDATE()), 1, 0)) YEAR),
        CURDATE()
    ) BETWEEN 0 AND 7
    ORDER BY daysUntil ASC
")->fetchAll();

// Get additional stats
require_once __DIR__ . '/modules/subjects/SubjectsModel.php';
$subjectsModel = new SubjectsModel();
$totalSubjects = count($subjectsModel->getAll());

$announcementsModel = new AnnouncementsModel();
$userRole = Session::getUserRole();
$announcements = $announcementsModel->getByAudience($userRole, 3);

$settingsModel = new SettingsModel();
$currentTerm = $settingsModel->getSetting('current_term', 'term1');
$currentYear = $settingsModel->getSetting('academic_year', date('Y') . '/' . (date('Y') + 1));
$attendanceThreshold = $settingsModel->getSetting('attendance_threshold', '75');
$schoolName = $settingsModel->getSetting('school_name', 'Lilayi Park School');

// Check for alerts
$alerts = [];
if (isset($stats['outstandingBalance']) && $stats['outstandingBalance'] > 10000) {
    $alerts[] = ['type' => 'warning', 'icon' => 'exclamation-triangle-fill', 'message' => 'High outstanding fees: ' . Utils::formatCurrency($stats['outstandingBalance'])];
}

require_once 'includes/header.php';
?>

<!-- Alerts -->
<?php if (!empty($alerts)): ?>
<div class="row mb-4">
    <div class="col-12">
        <?php foreach ($alerts as $alert): ?>
        <div class="alert alert-<?= $alert['type'] ?> alert-dismissible fade show">
            <i class="bi bi-<?= $alert['icon'] ?>"></i> <?= $alert['message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<h1 class="mb-4">
    <i class="bi bi-speedometer2 me-2"></i>Dashboard
</h1>

<!-- Quick Actions -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-lightning me-2"></i>Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="quick-actions">
                    <!--<?php if (PermissionHelper::canManage('parents')): ?>
                        <a href="parents_form.php" class="btn btn-secondary">
                            <i class="bi bi-plus-circle me-1"></i>Add New Parent
                        </a>
                    <?php endif; ?>-->

                    <?php if (PermissionHelper::canManage('pupils')): ?>
                        <a href="pupils_form.php" class="btn btn-primary">
                            <i class="bi bi-plus-circle me-1"></i>Add New Pupil
                        </a>
                    <?php endif; ?>
                    
                    <?php if (PermissionHelper::canManage('teachers')): ?>
                        <a href="teachers_form.php" class="btn btn-success">
                            <i class="bi bi-plus-circle me-1"></i>Add New Teacher
                        </a>
                    <?php endif; ?>
                    
                    <?php if (Auth::hasRole('admin')): ?>
                        <a href="payments_form.php" class="btn btn-warning">
                            <i class="bi bi-cash me-1"></i>Add Payment
                        </a>
                    <?php endif; ?>
                    
                    <?php if (PermissionHelper::canManage('attendance')): ?>
                        <a href="attendance_form.php" class="btn btn-secondary">
                            <i class="bi bi-calendar-check me-1"></i>Mark Attendance
                        </a>
                    <?php endif; ?>
                    
                    <?php if (PermissionHelper::canManage('announcements')): ?>
                        <a href="announcements_form.php" class="btn" style="background-color: #f0ad4e; color: white;">
                            <i class="bi bi-megaphone me-1"></i>New Announcement
                        </a>
                    <?php endif; ?>
                    
                    <?php if ($rolesModel->userHasPermission(Auth::id(), 'view_reports')): ?>
                        <a href="reports.php" class="btn btn-dark">
                            <i class="bi bi-file-earmark-text me-1"></i>View Reports
                        </a>
                    <?php endif; ?>
                    
                    <?php if (PermissionHelper::canManage('classes')): ?>
                        <!--<a href="classes_form.php" class="btn btn-info">
                            <i class="bi bi-plus-circle me-1"></i>Create Class
                        </a>-->
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <a href="pupils_list.php" class="text-decoration-none">
            <div class="stat-card card-blue">
                <h2><?php echo $stats['totalPupils'] ?? 0; ?></h2>
                <p><i class="bi bi-person-video3 me-1"></i>Total Pupils</p>
            </div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="teachers_list.php" class="text-decoration-none">
            <div class="stat-card card-green">
                <h2><?php echo $stats['totalTeachers'] ?? 0; ?></h2>
                <p><i class="bi bi-person-workspace me-1"></i>Total Teachers</p>
            </div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="parents_list.php" class="text-decoration-none">
            <div class="stat-card card-orange">
                <h2><?php echo $stats['totalParents'] ?? 0; ?></h2>
                <p><i class="bi bi-people me-1"></i>Total Parents</p>
            </div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="classes_list.php" class="text-decoration-none">
            <div class="stat-card card-purple">
                <h2><?php echo $stats['totalClasses'] ?? 0; ?></h2>
                <p><i class="bi bi-building me-1"></i>Total Classes</p>
            </div>
        </a>
    </div>
</div>

<!-- Gender Summary Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card shadow-sm" style="border-left: 4px solid #0d6efd;">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Male Pupils</h6>
                        <h2 class="mb-0"><?= $maleCount ?></h2>
                        <small class="text-muted"><?= $stats['totalPupils'] > 0 ? number_format(($maleCount / $stats['totalPupils']) * 100, 1) : 0 ?>%</small>
                    </div>
                    <div class="text-primary" style="font-size: 3rem; opacity: 0.2;">
                        <i class="bi bi-gender-male"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card shadow-sm" style="border-left: 4px solid #d63384;">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Female Pupils</h6>
                        <h2 class="mb-0"><?= $femaleCount ?></h2>
                        <small class="text-muted"><?= $stats['totalPupils'] > 0 ? number_format(($femaleCount / $stats['totalPupils']) * 100, 1) : 0 ?>%</small>
                    </div>
                    <div style="color: #d63384; font-size: 3rem; opacity: 0.2;">
                        <i class="bi bi-gender-female"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Class Distribution by Gender -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header" style="background: linear-gradient(135deg, #2d5016 0%, #5cb85c 100%); color: white;">
                <h6 class="mb-0"><i class="bi bi-bar-chart-fill me-2"></i>Class Distribution by Gender</h6>
            </div>
            <div class="card-body">
                <?php if (empty($classDistribution)): ?>
                    <p class="text-muted mb-0">No classes found.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>Class</th>
                                    <th class="text-center"><i class="bi bi-gender-male text-primary"></i> Male</th>
                                    <th class="text-center"><i class="bi bi-gender-female" style="color: #d63384;"></i> Female</th>
                                    <th class="text-center">Total</th>
                                    <th>Distribution</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($classDistribution as $class): 
                                    $total = $class['totalCount'] ?? 0;
                                    $male = $class['maleCount'] ?? 0;
                                    $female = $class['femaleCount'] ?? 0;
                                    $malePercent = $total > 0 ? ($male / $total) * 100 : 0;
                                    $femalePercent = $total > 0 ? ($female / $total) * 100 : 0;
                                ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($class['className']) ?></strong></td>
                                    <td class="text-center">
                                        <span class="badge bg-primary"><?= $male ?></span>
                                        <small class="text-muted">(<?= number_format($malePercent, 1) ?>%)</small>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge" style="background-color: #d63384;"><?= $female ?></span>
                                        <small class="text-muted">(<?= number_format($femalePercent, 1) ?>%)</small>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary"><?= $total ?></span>
                                    </td>
                                    <td>
                                        <div class="progress" style="height: 25px;">
                                            <?php if ($male > 0): ?>
                                            <div class="progress-bar bg-primary" 
                                                 role="progressbar" 
                                                 style="width: <?= $malePercent ?>%" 
                                                 aria-valuenow="<?= $malePercent ?>" 
                                                 aria-valuemin="0" 
                                                 aria-valuemax="100">
                                                <?php if ($malePercent > 15): ?><?= $male ?> M<?php endif; ?>
                                            </div>
                                            <?php endif; ?>
                                            <?php if ($female > 0): ?>
                                            <div class="progress-bar" 
                                                 style="background-color: #d63384; width: <?= $femalePercent ?>%" 
                                                 role="progressbar" 
                                                 aria-valuenow="<?= $femalePercent ?>" 
                                                 aria-valuemin="0" 
                                                 aria-valuemax="100">
                                                <?php if ($femalePercent > 15): ?><?= $female ?> F<?php endif; ?>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Finance Cards -->
<?php if ($rolesModel->userHasPermission(Auth::id(), 'view_payments')): ?>
<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="stat-card card-indigo">
            <h2><?php echo Utils::formatCurrency($stats['totalFees'] ?? 0); ?></h2>
            <p><i class="bi bi-cash-coin me-1"></i>Total Fees</p>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="stat-card card-teal">
            <h2><?php echo Utils::formatCurrency($stats['totalPayments'] ?? 0); ?></h2>
            <p><i class="bi bi-credit-card me-1"></i>Total Payments</p>
        </div>
    </div>
    
    <div class="col-md-4">
        <a href="payments_list.php" class="text-decoration-none">
            <div class="stat-card card-red">
                <h2><?php echo Utils::formatCurrency($stats['outstandingBalance'] ?? 0); ?></h2>
                <p><i class="bi bi-exclamation-triangle me-1"></i>Outstanding Balance</p>
            </div>
        </a>
    </div>
</div>
<?php endif; ?>

<!-- Upcoming Birthdays -->
<?php if (!empty($upcomingBirthdays)): ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header" style="background: linear-gradient(135deg, #ff6b6b 0%, #feca57 100%); color: white;">
                <h6 class="mb-0"><i class="bi bi-cake2-fill me-2"></i>Upcoming Birthdays (Next 7 Days)</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <?php foreach ($upcomingBirthdays as $birthday): ?>
                    <div class="col-md-4">
                        <div class="card border-0" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="me-3" style="font-size: 2.5rem;">
                                        🎂
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0"><?= htmlspecialchars($birthday['fName'] . ' ' . $birthday['lName']) ?></h6>
                                        <small class="text-muted"><?= htmlspecialchars($birthday['birthDate']) ?></small>
                                        <br>
                                        <?php if ($birthday['daysUntil'] == 0): ?>
                                            <span class="badge bg-danger"><i class="bi bi-gift-fill"></i> Today!</span>
                                        <?php elseif ($birthday['daysUntil'] == 1): ?>
                                            <span class="badge bg-warning">Tomorrow</span>
                                        <?php else: ?>
                                            <span class="badge bg-info">In <?= $birthday['daysUntil'] ?> days</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Row with Recent Activity & Announcements -->
<div class="row">
    <!-- Recent Announcements -->
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header" style="background: linear-gradient(135deg, #f0ad4e 0%, #ffc107 100%); color: white;">
                <h6 class="mb-0"><i class="bi bi-megaphone-fill me-2"></i>Recent Announcements</h6>
            </div>
            <div class="card-body">
                <?php if (!empty($announcements)): ?>
                    <?php foreach ($announcements as $announcement): ?>
                    <div class="mb-3 pb-3 border-bottom">
                        <h6 class="mb-1" style="color: #2d5016;">
                            <?php if ($announcement['isPinned']): ?><i class="bi bi-pin-fill text-warning"></i> <?php endif; ?>
                            <?= htmlspecialchars($announcement['title']) ?>
                        </h6>
                        <p class="text-muted small mb-1">
                            <?= htmlspecialchars(substr($announcement['content'], 0, 100)) ?><?= strlen($announcement['content']) > 100 ? '...' : '' ?>
                        </p>
                        <small class="text-muted">
                            <i class="bi bi-clock"></i> <?= Utils::formatDate($announcement['createdAt']) ?>
                        </small>
                        <a href="announcements_view.php?id=<?= $announcement['announcementID'] ?>" class="btn btn-sm btn-outline-secondary float-end">
                            View
                        </a>
                    </div>
                    <?php endforeach; ?>
                    <a href="announcements_list.php" class="btn btn-sm btn-outline-primary mt-2">
                        View All Announcements
                    </a>
                <?php else: ?>
                    <p class="text-muted mb-0">No announcements available</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- System Information -->
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header" style="background: linear-gradient(135deg, #2d5016 0%, #5cb85c 100%); color: white;">
                <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>System Information</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td><strong>Logged in as:</strong></td>
                        <td><?php echo $_SESSION['user_name'] ?? Auth::username(); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Role:</strong></td>
                        <td><span class="badge bg-info"><?php echo ucfirst($_SESSION['user_role'] ?? 'user'); ?></span></td>
                    </tr>
                    <tr>
                        <td><strong>Today's Date:</strong></td>
                        <td><?php echo date('d M Y'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Current Term:</strong></td>
                        <td><span class="badge" style="background-color: #2d5016;"><?= ucfirst($currentTerm) ?></span></td>
                    </tr>
                    <tr>
                        <td><strong>Academic Year:</strong></td>
                        <td><?= $currentYear ?></td>
                    </tr>
                </table>
                <?php if (PermissionHelper::canManage('settings')): ?>
                <hr>
                <a href="settings.php" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-gear"></i> Settings
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Recent Enrollments -->
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="bi bi-clock-history me-2"></i>Recent Enrollments</h6>
            </div>
            <div class="card-body">
                <?php if (!empty($stats['recentPupils'])): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach (array_slice($stats['recentPupils'], 0, 5) as $pupil): ?>
                            <div class="list-group-item px-0">
                                <i class="bi bi-person-fill me-2 text-primary"></i>
                                <strong><?php echo htmlspecialchars($pupil['fName'] . ' ' . $pupil['sName']); ?></strong>
                                <small class="text-muted float-end">
                                    <?php echo Utils::formatDate($pupil['enrollDate']); ?>
                                </small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted mb-0">No recent enrollments</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>
