<?php
if (!class_exists('Auth')) {
    require_once __DIR__ . '/Auth.php';
}
if (!class_exists('RolesModel')) {
    require_once __DIR__ . '/../modules/roles/RolesModel.php';
}
if (!isset($rolesModel)) {
    $rolesModel = new RolesModel();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Dashboard'; ?> - Lilayi Park School</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo BASE_URL; ?>/assets/images/favicon.ico">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/enhancements.css">
</head>
<body>
    <div class="d-flex" id="wrapper">
        <!-- Sidebar -->
        <div class="text-white" id="sidebar-wrapper" style="background: linear-gradient(180deg, #2d5016 0%, #1f3810 100%);">
            <div class="sidebar-heading p-3 border-bottom border-light text-center">
                <img src="<?php echo BASE_URL; ?>/assets/images/logo.jpg" alt="Lilayi Park School" style="max-width: 80px; height: auto; border-radius: 50%; margin-bottom: 10px;">
                <h6 class="mb-0 fw-bold">Lilayi Park School</h6>
                <small class="text-light opacity-75">Management Portal</small>
            </div>
            <div class="list-group list-group-flush">
                <a href="<?php echo BASE_URL; ?>/index.php" class="list-group-item list-group-item-action text-white border-0 <?php echo ($currentPage ?? '') === 'dashboard' ? 'active' : ''; ?>" style="background: transparent;">
                    <i class="bi bi-speedometer2 me-2"></i> Dashboard
                </a>
                
                <?php if ($rolesModel->userHasPermission(Auth::id(), 'view_pupils')): ?>
                <a href="<?php echo BASE_URL; ?>/pupils_list.php" class="list-group-item list-group-item-action text-white border-0 <?php echo ($currentPage ?? '') === 'pupils' ? 'active' : ''; ?>" style="background: transparent;">
                    <i class="bi bi-mortarboard me-2"></i> Pupils
                </a>
                <?php endif; ?>
                
                <?php if ($rolesModel->userHasPermission(Auth::id(), 'view_teachers')): ?>
                <a href="<?php echo BASE_URL; ?>/teachers_list.php" class="list-group-item list-group-item-action text-white border-0 <?php echo ($currentPage ?? '') === 'teachers' ? 'active' : ''; ?>" style="background: transparent;">
                    <i class="bi bi-person-workspace me-2"></i> Teachers
                </a>
                <?php if (Auth::hasRole('admin')): ?>
                <a href="<?php echo BASE_URL; ?>/teachers_bulk_accounts.php" class="list-group-item list-group-item-action text-white border-0 ps-5 small <?php echo ($currentPage ?? '') === 'teachers_bulk' ? 'active' : ''; ?>" style="background: transparent;">
                    <i class="bi bi-person-plus me-2"></i> Bulk Accounts
                </a>
                <?php endif; ?>
                <?php endif; ?>
                
                <?php if ($rolesModel->userHasPermission(Auth::id(), 'view_parents')): ?>
                <a href="<?php echo BASE_URL; ?>/parents_list.php" class="list-group-item list-group-item-action text-white border-0 <?php echo ($currentPage ?? '') === 'parents' ? 'active' : ''; ?>" style="background: transparent;">
                    <i class="bi bi-people me-2"></i> Parents
                </a>
                <?php endif; ?>
                
                <?php if ($rolesModel->userHasPermission(Auth::id(), 'view_classes')): ?>
                <a href="<?php echo BASE_URL; ?>/classes_list.php" class="list-group-item list-group-item-action text-white border-0 <?php echo ($currentPage ?? '') === 'classes' ? 'active' : ''; ?>" style="background: transparent;">
                    <i class="bi bi-building me-2"></i> Classes
                </a>
                <?php endif; ?>
                
                <?php if ($rolesModel->userHasPermission(Auth::id(), 'view_fees')): ?>
                <a href="<?php echo BASE_URL; ?>/fees_list.php" class="list-group-item list-group-item-action text-white border-0 <?php echo ($currentPage ?? '') === 'fees' ? 'active' : ''; ?>" style="background: transparent;">
                    <i class="bi bi-cash-coin me-2"></i> Fees
                </a>
                <?php endif; ?>
                
                <?php if ($rolesModel->userHasPermission(Auth::id(), 'view_payments')): ?>
                <a href="<?php echo BASE_URL; ?>/payments_list.php" class="list-group-item list-group-item-action text-white border-0 <?php echo ($currentPage ?? '') === 'payments' ? 'active' : ''; ?>" style="background: transparent;">
                    <i class="bi bi-credit-card me-2"></i> Payments
                </a>
                <?php endif; ?>
                
                <?php if ($rolesModel->userHasPermission(Auth::id(), 'view_attendance')): ?>
                <a href="<?php echo BASE_URL; ?>/attendance_list.php" class="list-group-item list-group-item-action text-white border-0 <?php echo ($currentPage ?? '') === 'attendance' ? 'active' : ''; ?>" style="background: transparent;">
                    <i class="bi bi-calendar-check me-2"></i> Attendance
                </a>
                <?php endif; ?>
                
                <!-- Academic Section -->
                <?php if ($rolesModel->userHasPermission(Auth::id(), 'view_grades')): ?>
                <hr class="my-2 border-light opacity-25">
                <div class="mt-3 mb-2 px-3">
                    <small class="text-white-50 text-uppercase fw-bold">Academic</small>
                </div>
                <a href="<?php echo BASE_URL; ?>/grades_list.php" class="list-group-item list-group-item-action text-white border-0 <?php echo ($currentPage ?? '') === 'grades' ? 'active' : ''; ?>" style="background: transparent;">
                    <i class="bi bi-award me-2"></i> Grades & Marks
                </a>
                <a href="<?php echo BASE_URL; ?>/report_cards.php" class="list-group-item list-group-item-action text-white border-0 <?php echo ($currentPage ?? '') === 'report_cards' ? 'active' : ''; ?>" style="background: transparent;">
                    <i class="bi bi-file-earmark-text me-2"></i> Report Cards
                </a>
                <?php endif; ?>
                
                <a href="<?php echo BASE_URL; ?>/subjects_list.php" class="list-group-item list-group-item-action text-white border-0 <?php echo ($currentPage ?? '') === 'subjects' ? 'active' : ''; ?>" style="background: transparent;">
                    <i class="bi bi-book me-2"></i> Subjects
                </a>
                
                <?php if ($rolesModel->userHasPermission(Auth::id(), 'view_reports')): ?>
                <a href="<?php echo BASE_URL; ?>/reports.php" class="list-group-item list-group-item-action text-white border-0 <?php echo ($currentPage ?? '') === 'reports' ? 'active' : ''; ?>" style="background: transparent;">
                    <i class="bi bi-bar-chart me-2"></i> Reports
                </a>
                <?php endif; ?>
                
                <!-- System & Management -->
                <?php if (Auth::hasRole('admin')): ?>
                <hr class="my-2 border-light opacity-25">
                <div class="mt-3 mb-2 px-3">
                    <small class="text-white-50 text-uppercase fw-bold">System</small>
                </div>
                
                <a href="<?php echo BASE_URL; ?>/users_list.php" class="list-group-item list-group-item-action text-white border-0 <?php echo ($currentPage ?? '') === 'users' ? 'active' : ''; ?>" style="background: transparent;">
                    <i class="bi bi-person-gear me-2"></i> User Management
                </a>
                
                <a href="<?php echo BASE_URL; ?>/announcements_list.php" class="list-group-item list-group-item-action text-white border-0 <?php echo ($currentPage ?? '') === 'announcements' ? 'active' : ''; ?>" style="background: transparent;">
                    <i class="bi bi-megaphone me-2"></i> Announcements
                </a>
                
                <a href="<?php echo BASE_URL; ?>/settings.php" class="list-group-item list-group-item-action text-white border-0 <?php echo ($currentPage ?? '') === 'settings' ? 'active' : ''; ?>" style="background: transparent;">
                    <i class="bi bi-gear me-2"></i> Settings
                </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Page Content -->
        <div id="page-content-wrapper" class="flex-grow-1">
            <!-- Top Navigation -->
            <nav class="navbar navbar-expand-lg navbar-light shadow-sm" style="background: linear-gradient(90deg, #f8f9fa 0%, #e9ecef 100%);">
                <div class="container-fluid">
                    <button class="btn btn-sm" id="sidebarToggle" style="background-color: #2d5016; color: white; border: none;">
                        <i class="bi bi-list"></i>
                    </button>
                    
                    <h4 class="mb-0 ms-3" style="color: #2d5016; font-weight: 600;">School Management Portal</h4>
                    
                    <div class="ms-auto d-flex align-items-center">
                        <span class="me-3" style="color: #2d5016;">
                            <i class="bi bi-person-circle me-1"></i>
                            <?php echo $_SESSION['user_name'] ?? Session::get('user_name', Auth::username()); ?>
                        </span>
                        <span class="badge me-3" style="background-color: #f0ad4e; color: #fff;"><?php echo ucfirst($_SESSION['user_role'] ?? 'user'); ?></span>
                        <a href="<?php echo BASE_URL; ?>/logout.php" class="btn btn-sm" style="background-color: #dc3545; color: white;">
                            <i class="bi bi-box-arrow-right me-1"></i> Logout
                        </a>
                    </div>
                </div>
            </nav>

            <!-- Main Content -->
            <div class="container-fluid p-4">
                <?php
                // Display flash messages
                if ($error = Session::getFlash('error')): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif;
                
                if ($success = Session::getFlash('success')): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($success); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Page Content Starts Here -->
