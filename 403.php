<?php
/**
 * 403 Forbidden Page
 */
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6 text-center">
            <h1 class="display-1 text-danger">403</h1>
            <h2 class="mb-4">Access Forbidden</h2>
            <p class="lead">You do not have permission to access this page.</p>
            <p class="text-muted">
                If you believe this is an error, please contact your administrator.
            </p>
            <div class="mt-4">
                <a href="/dashboard.php" class="btn btn-primary">Go to Dashboard</a>
                <a href="javascript:history.back()" class="btn btn-outline-secondary">Go Back</a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
