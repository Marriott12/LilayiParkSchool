<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Dashboard'; ?> - Lilayi Park School</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
</head>
<body>
    <div class="d-flex" id="wrapper">
        <!-- Sidebar -->
        <div class="bg-primary text-white" id="sidebar-wrapper">
            <div class="sidebar-heading p-3 border-bottom border-light">
                <h5 class="mb-0">Lilayi Park School</h5>
            </div>
            <div class="list-group list-group-flush">
                <a href="<?php echo BASE_URL; ?>/index.php" class="list-group-item list-group-item-action bg-primary text-white border-0 <?php echo ($currentPage ?? '') === 'dashboard' ? 'active' : ''; ?>">
                    <i class="bi bi-speedometer2 me-2"></i> Dashboard
                </a>
                
                <?php if (RBAC::hasPermission(Session::getUserRole(), 'pupils', 'read')): ?>
                <a href="<?php echo BASE_URL; ?>/modules/pupils/index.php" class="list-group-item list-group-item-action bg-primary text-white border-0 <?php echo ($currentPage ?? '') === 'pupils' ? 'active' : ''; ?>">
                    <i class="bi bi-person-video3 me-2"></i> Pupils
                </a>
                <?php endif; ?>
                
                <?php if (RBAC::hasPermission(Session::getUserRole(), 'teachers', 'read')): ?>
                <a href="<?php echo BASE_URL; ?>/modules/teachers/index.php" class="list-group-item list-group-item-action bg-primary text-white border-0 <?php echo ($currentPage ?? '') === 'teachers' ? 'active' : ''; ?>">
                    <i class="bi bi-person-workspace me-2"></i> Teachers
                </a>
                <?php endif; ?>
                
                <?php if (RBAC::hasPermission(Session::getUserRole(), 'parents', 'read')): ?>
                <a href="<?php echo BASE_URL; ?>/modules/parents/index.php" class="list-group-item list-group-item-action bg-primary text-white border-0 <?php echo ($currentPage ?? '') === 'parents' ? 'active' : ''; ?>">
                    <i class="bi bi-people me-2"></i> Parents
                </a>
                <?php endif; ?>
                
                <?php if (RBAC::hasPermission(Session::getUserRole(), 'classes', 'read')): ?>
                <a href="<?php echo BASE_URL; ?>/modules/classes/index.php" class="list-group-item list-group-item-action bg-primary text-white border-0 <?php echo ($currentPage ?? '') === 'classes' ? 'active' : ''; ?>">
                    <i class="bi bi-building me-2"></i> Classes
                </a>
                <?php endif; ?>
                
                <?php if (RBAC::hasPermission(Session::getUserRole(), 'fees', 'read')): ?>
                <a href="<?php echo BASE_URL; ?>/modules/fees/index.php" class="list-group-item list-group-item-action bg-primary text-white border-0 <?php echo ($currentPage ?? '') === 'fees' ? 'active' : ''; ?>">
                    <i class="bi bi-cash-coin me-2"></i> Fees
                </a>
                <?php endif; ?>
                
                <?php if (RBAC::hasPermission(Session::getUserRole(), 'payments', 'read')): ?>
                <a href="<?php echo BASE_URL; ?>/modules/payments/index.php" class="list-group-item list-group-item-action bg-primary text-white border-0 <?php echo ($currentPage ?? '') === 'payments' ? 'active' : ''; ?>">
                    <i class="bi bi-credit-card me-2"></i> Payments
                </a>
                <?php endif; ?>
                
                <?php if (RBAC::hasPermission(Session::getUserRole(), 'attendance', 'read')): ?>
                <a href="<?php echo BASE_URL; ?>/modules/attendance/index.php" class="list-group-item list-group-item-action bg-primary text-white border-0 <?php echo ($currentPage ?? '') === 'attendance' ? 'active' : ''; ?>">
                    <i class="bi bi-calendar-check me-2"></i> Attendance
                </a>
                <?php endif; ?>
                
                <?php if (RBAC::hasPermission(Session::getUserRole(), 'reports', 'read')): ?>
                <a href="<?php echo BASE_URL; ?>/modules/reports/index.php" class="list-group-item list-group-item-action bg-primary text-white border-0 <?php echo ($currentPage ?? '') === 'reports' ? 'active' : ''; ?>">
                    <i class="bi bi-bar-chart me-2"></i> Reports
                </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Page Content -->
        <div id="page-content-wrapper" class="flex-grow-1">
            <!-- Top Navigation -->
            <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
                <div class="container-fluid">
                    <button class="btn btn-primary" id="sidebarToggle">
                        <i class="bi bi-list"></i>
                    </button>
                    
                    <h4 class="mb-0 ms-3">School Management Portal</h4>
                    
                    <div class="ms-auto d-flex align-items-center">
                        <span class="me-3">
                            <i class="bi bi-person-circle me-1"></i>
                            <?php echo Session::get('user_name', 'User'); ?>
                        </span>
                        <span class="badge bg-info me-3"><?php echo ucfirst(Session::getUserRole()); ?></span>
                        <a href="<?php echo BASE_URL; ?>/logout.php" class="btn btn-danger btn-sm">
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
                
                <!-- Page Content Goes Here -->
                <?php echo $content ?? ''; ?>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="<?php echo BASE_URL; ?>/assets/js/main.js"></script>
</body>
</html>
