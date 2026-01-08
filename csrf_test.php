<?php
require_once 'includes/bootstrap.php';

RBAC::requireAuth();

$testResult = '';
$sessionToken = '';
$postToken = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sessionToken = $_SESSION['csrf_token'] ?? 'NOT SET';
    $postToken = $_POST['csrf_token'] ?? 'NOT SET';
    
    try {
        CSRF::requireToken();
        $testResult = '✅ SUCCESS! CSRF validation passed!';
    } catch (Exception $e) {
        $testResult = '❌ FAILED: ' . $e->getMessage();
    }
}

$pageTitle = 'CSRF Test';
require_once 'includes/header.php';
?>

<div class="container mt-4">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5>CSRF Token Test Page</h5>
        </div>
        <div class="card-body">
            <?php if ($testResult): ?>
            <div class="alert alert-<?= strpos($testResult, 'SUCCESS') !== false ? 'success' : 'danger' ?>">
                <?= $testResult ?>
                <hr>
                <small>Session Token: <?= htmlspecialchars($sessionToken) ?></small><br>
                <small>POST Token: <?= htmlspecialchars($postToken) ?></small>
            </div>
            <?php endif; ?>
            
            <div class="mb-3">
                <strong>Current Session Token:</strong><br>
                <code><?= htmlspecialchars($_SESSION['csrf_token'] ?? 'NOT SET') ?></code>
            </div>
            
            <form method="POST">
                <?= CSRF::field() ?>
                <button type="submit" class="btn btn-primary">Test CSRF Token</button>
            </form>
            
            <hr>
            
            <h6>Debug Info:</h6>
            <ul>
                <li>Session ID: <?= session_id() ?></li>
                <li>Session Created: <?= $_SESSION['created'] ?? 'NOT SET' ?></li>
                <li>IP Address: <?= $_SESSION['ip_address'] ?? 'NOT SET' ?></li>
                <li>User ID: <?= Session::getUserId() ?? 'NOT SET' ?></li>
            </ul>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
