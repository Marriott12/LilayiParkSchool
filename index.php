<?php
require_once __DIR__ . '/includes/bootstrap.php';

// Require authentication
RBAC::requireAuth();

// Set page variables
$pageTitle = 'Dashboard';
$currentPage = 'dashboard';

// Get dashboard statistics
require_once __DIR__ . '/modules/reports/ReportsModel.php';
$reportsModel = new ReportsModel();
$stats = $reportsModel->getDashboardStats();

// Start output buffering for content
ob_start();
?>

<h1 class="mb-4">
    <i class="bi bi-speedometer2 me-2"></i>Dashboard
</h1>

<!-- Statistics Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <a href="<?php echo BASE_URL; ?>/modules/pupils/index.php" class="text-decoration-none">
            <div class="stat-card card-blue">
                <h2><?php echo $stats['totalPupils']; ?></h2>
                <p><i class="bi bi-person-video3 me-1"></i>Total Pupils</p>
            </div>
        </a>
    </div>
    
    <div class="col-md-3">
        <a href="<?php echo BASE_URL; ?>/modules/teachers/index.php" class="text-decoration-none">
            <div class="stat-card card-green">
                <h2><?php echo $stats['totalTeachers']; ?></h2>
                <p><i class="bi bi-person-workspace me-1"></i>Total Teachers</p>
            </div>
        </a>
    </div>
    
    <div class="col-md-3">
        <a href="<?php echo BASE_URL; ?>/modules/classes/index.php" class="text-decoration-none">
            <div class="stat-card card-purple">
                <h2><?php echo $stats['totalClasses']; ?></h2>
                <p><i class="bi bi-building me-1"></i>Total Classes</p>
            </div>
        </a>
    </div>
    
    <div class="col-md-3">
        <div class="stat-card card-yellow">
            <h2><?php echo $stats['recentEnrollments']; ?></h2>
            <p><i class="bi bi-calendar-plus me-1"></i>Recent Enrollments</p>
        </div>
    </div>
</div>

<!-- Finance Cards -->
<?php if (RBAC::hasPermission(Session::getUserRole(), 'payments', 'read')): ?>
<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="stat-card card-indigo">
            <h2><?php echo Utils::formatCurrency($stats['totalFees']); ?></h2>
            <p><i class="bi bi-cash-coin me-1"></i>Total Fees</p>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="stat-card card-teal">
            <h2><?php echo Utils::formatCurrency($stats['totalPayments']); ?></h2>
            <p><i class="bi bi-credit-card me-1"></i>Total Payments</p>
        </div>
    </div>
    
    <div class="col-md-4">
        <a href="<?php echo BASE_URL; ?>/modules/payments/index.php" class="text-decoration-none">
            <div class="stat-card card-red">
                <h2><?php echo Utils::formatCurrency($stats['outstandingBalance']); ?></h2>
                <p><i class="bi bi-exclamation-triangle me-1"></i>Outstanding Balance</p>
            </div>
        </a>
    </div>
</div>
<?php endif; ?>

<!-- Quick Actions -->
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-lightning me-2"></i>Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="quick-actions">
                    <?php if (RBAC::hasPermission(Session::getUserRole(), 'pupils', 'create')): ?>
                        <a href="<?php echo BASE_URL; ?>/modules/pupils/create.php" class="btn btn-primary">
                            <i class="bi bi-plus-circle me-1"></i>Add New Pupil
                        </a>
                    <?php endif; ?>
                    
                    <?php if (RBAC::hasPermission(Session::getUserRole(), 'teachers', 'create')): ?>
                        <a href="<?php echo BASE_URL; ?>/modules/teachers/create.php" class="btn btn-success">
                            <i class="bi bi-plus-circle me-1"></i>Add New Teacher
                        </a>
                    <?php endif; ?>
                    
                    <?php if (RBAC::hasPermission(Session::getUserRole(), 'classes', 'create')): ?>
                        <a href="<?php echo BASE_URL; ?>/modules/classes/create.php" class="btn btn-info">
                            <i class="bi bi-plus-circle me-1"></i>Create Class
                        </a>
                    <?php endif; ?>
                    
                    <?php if (RBAC::hasPermission(Session::getUserRole(), 'payments', 'create')): ?>
                        <a href="<?php echo BASE_URL; ?>/modules/payments/create.php" class="btn btn-warning">
                            <i class="bi bi-cash me-1"></i>Record Payment
                        </a>
                    <?php endif; ?>
                    
                    <?php if (RBAC::hasPermission(Session::getUserRole(), 'attendance', 'create')): ?>
                        <a href="<?php echo BASE_URL; ?>/modules/attendance/create.php" class="btn btn-secondary">
                            <i class="bi bi-calendar-check me-1"></i>Mark Attendance
                        </a>
                    <?php endif; ?>
                    
                    <?php if (RBAC::hasPermission(Session::getUserRole(), 'reports', 'read')): ?>
                        <a href="<?php echo BASE_URL; ?>/modules/reports/index.php" class="btn btn-dark">
                            <i class="bi bi-file-earmark-text me-1"></i>View Reports
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity (optional - can be added later) -->
<div class="row mt-4">
    <div class="col-md-6">
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
    
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>System Information</h6>
            </div>
            <div class="card-body">
                <p><strong>Logged in as:</strong> <?php echo Session::get('user_name'); ?></p>
                <p><strong>Role:</strong> <span class="badge bg-info"><?php echo ucfirst(Session::getUserRole()); ?></span></p>
                <p><strong>Today's Date:</strong> <?php echo date('d M Y'); ?></p>
                <p class="mb-0"><strong>Academic Year:</strong> 2025/2026</p>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
?>
