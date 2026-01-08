<?php
require_once __DIR__ . '/includes/bootstrap.php';

// Require authentication
RBAC::requireAuth();

// Set page variables
$pageTitle = 'Dashboard';
$currentPage = 'dashboard';

// Get dashboard statistics
require_once __DIR__ . '/modules/reports/ReportsModel.php';
require_once __DIR__ . '/modules/announcements/AnnouncementsModel.php';
require_once __DIR__ . '/modules/settings/SettingsModel.php';

$reportsModel = new ReportsModel();
$stats = $reportsModel->getDashboardStats();

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
        <a href="classes_list.php" class="text-decoration-none">
            <div class="stat-card card-purple">
                <h2><?php echo $stats['totalClasses'] ?? 0; ?></h2>
                <p><i class="bi bi-building me-1"></i>Total Classes</p>
            </div>
        </a>
    </div>
    
    <div class="col-md-3">
        <?php if (RBAC::hasPermission(Session::getUserRole(), 'subjects', 'read')): ?>
        <a href="subjects_list.php" class="text-decoration-none">
            <div class="stat-card card-yellow">
                <h2><?php echo $totalSubjects; ?></h2>
                <p><i class="bi bi-book me-1"></i>Total Subjects</p>
            </div>
        </a>
        <?php else: ?>
        <div class="stat-card card-yellow">
            <h2><?php echo $stats['recentEnrollments'] ?? 0; ?></h2>
            <p><i class="bi bi-calendar-plus me-1"></i>Recent Enrollments</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Finance Cards -->
<?php if (RBAC::hasPermission(Session::getUserRole(), 'payments', 'read')): ?>
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

<!-- Quick Actions -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-lightning me-2"></i>Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="quick-actions">
                    <?php if (RBAC::hasPermission(Session::getUserRole(), 'pupils', 'create')): ?>
                        <a href="pupils_form.php" class="btn btn-primary">
                            <i class="bi bi-plus-circle me-1"></i>Add New Pupil
                        </a>
                    <?php endif; ?>
                    
                    <?php if (RBAC::hasPermission(Session::getUserRole(), 'teachers', 'create')): ?>
                        <a href="teachers_form.php" class="btn btn-success">
                            <i class="bi bi-plus-circle me-1"></i>Add New Teacher
                        </a>
                    <?php endif; ?>
                    
                    <?php if (RBAC::hasPermission(Session::getUserRole(), 'classes', 'create')): ?>
                        <a href="classes_form.php" class="btn btn-info">
                            <i class="bi bi-plus-circle me-1"></i>Create Class
                        </a>
                    <?php endif; ?>
                    
                    <?php if (RBAC::hasPermission(Session::getUserRole(), 'payments', 'create')): ?>
                        <a href="payments_form.php" class="btn btn-warning">
                            <i class="bi bi-cash me-1"></i>Record Payment
                        </a>
                    <?php endif; ?>
                    
                    <?php if (RBAC::hasPermission(Session::getUserRole(), 'attendance', 'create')): ?>
                        <a href="attendance_form.php" class="btn btn-secondary">
                            <i class="bi bi-calendar-check me-1"></i>Mark Attendance
                        </a>
                    <?php endif; ?>
                    
                    <?php if (RBAC::hasPermission(Session::getUserRole(), 'announcements', 'create')): ?>
                        <a href="announcements_form.php" class="btn" style="background-color: #f0ad4e; color: white;">
                            <i class="bi bi-megaphone me-1"></i>New Announcement
                        </a>
                    <?php endif; ?>
                    
                    <?php if (RBAC::hasPermission(Session::getUserRole(), 'reports', 'read')): ?>
                        <a href="reports.php" class="btn btn-dark">
                            <i class="bi bi-file-earmark-text me-1"></i>View Reports
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

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
                        <td><?php echo Session::get('user_name'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Role:</strong></td>
                        <td><span class="badge bg-info"><?php echo ucfirst(Session::getUserRole()); ?></span></td>
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
                <?php if (RBAC::hasPermission(Session::getUserRole(), 'settings', 'update')): ?>
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
