<?php
require_once 'includes/bootstrap.php';

RBAC::requireAuth();
RBAC::requirePermission('settings', 'update');

require_once 'modules/settings/SettingsModel.php';

$settingsModel = new SettingsModel();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::requireToken();
    
    try {
        // School Information
        $settingsModel->setSetting('school_name', $_POST['school_name'], 'school');
        $settingsModel->setSetting('school_address', $_POST['school_address'], 'school');
        $settingsModel->setSetting('school_phone', $_POST['school_phone'], 'school');
        $settingsModel->setSetting('school_email', $_POST['school_email'], 'school');
        
        // Academic Settings
        $settingsModel->setSetting('current_term', $_POST['current_term'], 'academic');
        $settingsModel->setSetting('current_year', $_POST['current_year'], 'academic');
        $settingsModel->setSetting('attendance_threshold', $_POST['attendance_threshold'], 'academic');
        
        // Financial Settings
        $settingsModel->setSetting('currency', $_POST['currency'], 'financial');
        $settingsModel->setSetting('late_fee_penalty', $_POST['late_fee_penalty'], 'financial');
        
        Session::setFlash('success', 'Settings updated successfully');
        header('Location: settings.php');
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get all settings
$settings = $settingsModel->getAllSettings();

$pageTitle = 'System Settings';
$currentPage = 'settings';
require_once 'includes/header.php';
?>

<h2 class="mb-4"><i class="bi bi-gear-fill"></i> System Settings</h2>

<?php if (isset($error)): ?>
<div class="alert alert-danger alert-dismissible fade show">
    <i class="bi bi-exclamation-triangle-fill"></i> <?= htmlspecialchars($error) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<form method="POST" action="">
    <?= CSRF::field() ?>
    <!-- School Information -->
    <div class="card mb-4">
        <div class="card-header" style="background-color: #f8f9fa;">
            <h5 class="mb-0"><i class="bi bi-building"></i> School Information</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">School Name</label>
                    <input type="text" class="form-control" name="school_name" 
                           value="<?= htmlspecialchars($settings['school_name'] ?? 'Lilayi Park School') ?>">
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">School Email</label>
                    <input type="email" class="form-control" name="school_email" 
                           value="<?= htmlspecialchars($settings['school_email'] ?? '') ?>">
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">School Address</label>
                    <textarea class="form-control" name="school_address" rows="2"><?= htmlspecialchars($settings['school_address'] ?? '') ?></textarea>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">School Phone</label>
                    <input type="text" class="form-control" name="school_phone" 
                           value="<?= htmlspecialchars($settings['school_phone'] ?? '') ?>">
                </div>
            </div>
        </div>
    </div>
    
    <!-- Academic Settings -->
    <div class="card mb-4">
        <div class="card-header" style="background-color: #f8f9fa;">
            <h5 class="mb-0"><i class="bi bi-calendar-range"></i> Academic Settings</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Current Term</label>
                    <select class="form-select" name="current_term">
                        <option value="1" <?= ($settings['current_term'] ?? '1') == '1' ? 'selected' : '' ?>>Term 1</option>
                        <option value="2" <?= ($settings['current_term'] ?? '1') == '2' ? 'selected' : '' ?>>Term 2</option>
                        <option value="3" <?= ($settings['current_term'] ?? '1') == '3' ? 'selected' : '' ?>>Term 3</option>
                    </select>
                </div>
                
                <div class="col-md-4 mb-3">
                    <label class="form-label">Current Year</label>
                    <input type="number" class="form-control" name="current_year" min="2020" max="2099"
                           value="<?= htmlspecialchars($settings['current_year'] ?? date('Y')) ?>">
                </div>
                
                <div class="col-md-4 mb-3">
                    <label class="form-label">Attendance Threshold (%)</label>
                    <input type="number" class="form-control" name="attendance_threshold" min="0" max="100"
                           value="<?= htmlspecialchars($settings['attendance_threshold'] ?? '75') ?>">
                    <small class="text-muted">Minimum attendance required</small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Financial Settings -->
    <div class="card mb-4">
        <div class="card-header" style="background-color: #f8f9fa;">
            <h5 class="mb-0"><i class="bi bi-cash-stack"></i> Financial Settings</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Currency</label>
                    <select class="form-select" name="currency">
                        <option value="ZMW" <?= ($settings['currency'] ?? 'ZMW') == 'ZMW' ? 'selected' : '' ?>>ZMW (Zambian Kwacha)</option>
                        <option value="USD" <?= ($settings['currency'] ?? 'ZMW') == 'USD' ? 'selected' : '' ?>>USD (US Dollar)</option>
                        <option value="EUR" <?= ($settings['currency'] ?? 'ZMW') == 'EUR' ? 'selected' : '' ?>>EUR (Euro)</option>
                    </select>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Late Fee Penalty (%)</label>
                    <input type="number" class="form-control" name="late_fee_penalty" min="0" max="100" step="0.1"
                           value="<?= htmlspecialchars($settings['late_fee_penalty'] ?? '0') ?>">
                    <small class="text-muted">Penalty for late payment</small>
                </div>
            </div>
        </div>
    </div>
    
    <div class="d-flex gap-2">
        <button type="submit" class="btn" style="background-color: #2d5016; color: white;">
            <i class="bi bi-save"></i> Save Settings
        </button>
        <a href="index.php" class="btn btn-secondary">
            <i class="bi bi-x-circle"></i> Cancel
        </a>
    </div>
</form>

<?php require_once 'includes/footer.php'; ?>
