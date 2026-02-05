<?php
require_once 'includes/bootstrap.php';
require_once 'includes/Auth.php';
require_once 'includes/CSRF.php';

Auth::requireLogin();
Auth::requireAnyRole(['admin']);

require_once 'modules/settings/SettingsModel.php';

$settingsModel = new SettingsModel();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::validate($_POST['csrf_token']);
    
    try {
        $errors = [];
        $savedCount = 0;
        
        // Validate required fields
        if (empty($_POST['school_name'])) {
            $errors[] = 'School name is required';
        }
        if (empty($_POST['current_term']) || !in_array($_POST['current_term'], ['1', '2', '3'])) {
            $errors[] = 'Valid current term (1, 2, or 3) is required';
        }
        if (empty($_POST['current_academic_year'])) {
            $errors[] = 'Current academic year is required';
        }
        
        // Validate numeric fields
        $numericFields = [
            'attendance_threshold' => 'Attendance threshold must be a number between 0 and 100',
            'grade_a_min' => 'Grade A minimum must be a number between 0 and 100',
            'grade_b_min' => 'Grade B minimum must be a number between 0 and 100',
            'grade_c_min' => 'Grade C minimum must be a number between 0 and 100',
            'grade_d_min' => 'Grade D minimum must be a number between 0 and 100',
            'passing_grade' => 'Passing grade must be a number between 0 and 100',
            'late_fee_penalty' => 'Late fee penalty must be a number',
            'library_fine_per_day' => 'Library fine per day must be a number',
            'library_max_books' => 'Library max books must be a positive integer',
            'library_loan_period' => 'Library loan period must be a positive integer',
            'smtp_port' => 'SMTP port must be a valid port number',
            'session_timeout' => 'Session timeout must be a positive integer',
        ];
        
        foreach ($numericFields as $field => $message) {
            if (isset($_POST[$field]) && !empty($_POST[$field]) && !is_numeric($_POST[$field])) {
                $errors[] = $message;
            }
        }
        
        // Validate email fields
        if (!empty($_POST['school_email']) && !filter_var($_POST['school_email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'School email must be a valid email address';
        }
        if (!empty($_POST['smtp_from_email']) && !filter_var($_POST['smtp_from_email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'SMTP from email must be a valid email address';
        }
        
        // If validation fails, show errors
        if (!empty($errors)) {
            Session::setFlash('error', implode('<br>', $errors));
        } else {
            // Save all settings
            $settingsToSave = [
                // School Information
                ['key' => 'school_name', 'value' => $_POST['school_name'], 'category' => 'school'],
                ['key' => 'school_address', 'value' => $_POST['school_address'], 'category' => 'school'],
                ['key' => 'school_phone', 'value' => $_POST['school_phone'], 'category' => 'school'],
                ['key' => 'school_email', 'value' => $_POST['school_email'], 'category' => 'school'],
                ['key' => 'school_motto', 'value' => $_POST['school_motto'] ?? '', 'category' => 'school'],
                
                // Academic Settings
                ['key' => 'current_term', 'value' => $_POST['current_term'], 'category' => 'academic'],
                ['key' => 'current_academic_year', 'value' => $_POST['current_academic_year'], 'category' => 'academic'],
                ['key' => 'term1_start', 'value' => $_POST['term1_start'] ?? '', 'category' => 'academic'],
                ['key' => 'term1_end', 'value' => $_POST['term1_end'] ?? '', 'category' => 'academic'],
                ['key' => 'term2_start', 'value' => $_POST['term2_start'] ?? '', 'category' => 'academic'],
                ['key' => 'term2_end', 'value' => $_POST['term2_end'] ?? '', 'category' => 'academic'],
                ['key' => 'term3_start', 'value' => $_POST['term3_start'] ?? '', 'category' => 'academic'],
                ['key' => 'term3_end', 'value' => $_POST['term3_end'] ?? '', 'category' => 'academic'],
                ['key' => 'attendance_threshold', 'value' => $_POST['attendance_threshold'], 'category' => 'academic'],
                
                // Grading Scale
                ['key' => 'grade_a_min', 'value' => $_POST['grade_a_min'] ?? '80', 'category' => 'grading'],
                ['key' => 'grade_b_min', 'value' => $_POST['grade_b_min'] ?? '70', 'category' => 'grading'],
                ['key' => 'grade_c_min', 'value' => $_POST['grade_c_min'] ?? '60', 'category' => 'grading'],
                ['key' => 'grade_d_min', 'value' => $_POST['grade_d_min'] ?? '50', 'category' => 'grading'],
                ['key' => 'passing_grade', 'value' => $_POST['passing_grade'] ?? '50', 'category' => 'grading'],
                
                // Financial Settings
                ['key' => 'currency', 'value' => $_POST['currency'], 'category' => 'financial'],
                ['key' => 'late_fee_penalty', 'value' => $_POST['late_fee_penalty'], 'category' => 'financial'],
                
                // Library Settings
                ['key' => 'library_fine_per_day', 'value' => $_POST['library_fine_per_day'] ?? '0.50', 'category' => 'library'],
                ['key' => 'library_max_books', 'value' => $_POST['library_max_books'] ?? '3', 'category' => 'library'],
                ['key' => 'library_loan_period', 'value' => $_POST['library_loan_period'] ?? '14', 'category' => 'library'],
                
                // Notification Settings
                ['key' => 'notifications_enabled', 'value' => $_POST['notifications_enabled'] ?? '0', 'category' => 'notifications'],
                ['key' => 'email_notifications', 'value' => $_POST['email_notifications'] ?? '0', 'category' => 'notifications'],
                ['key' => 'sms_notifications', 'value' => $_POST['sms_notifications'] ?? '0', 'category' => 'notifications'],
                ['key' => 'sms_api_key', 'value' => $_POST['sms_api_key'] ?? '', 'category' => 'notifications'],
                
                // Email/SMTP Configuration
                ['key' => 'smtp_host', 'value' => $_POST['smtp_host'] ?? '', 'category' => 'email'],
                ['key' => 'smtp_port', 'value' => $_POST['smtp_port'] ?? '587', 'category' => 'email'],
                ['key' => 'smtp_username', 'value' => $_POST['smtp_username'] ?? '', 'category' => 'email'],
                ['key' => 'smtp_password', 'value' => $_POST['smtp_password'] ?? '', 'category' => 'email'],
                ['key' => 'smtp_encryption', 'value' => $_POST['smtp_encryption'] ?? 'tls', 'category' => 'email'],
                ['key' => 'smtp_from_email', 'value' => $_POST['smtp_from_email'] ?? '', 'category' => 'email'],
                ['key' => 'smtp_from_name', 'value' => $_POST['smtp_from_name'] ?? 'Lilayi Park School', 'category' => 'email'],
                ['key' => 'send_account_emails', 'value' => $_POST['send_account_emails'] ?? '0', 'category' => 'email'],
                
                // Report Card Settings
                ['key' => 'report_show_position', 'value' => $_POST['report_show_position'] ?? '1', 'category' => 'reports'],
                ['key' => 'report_show_average', 'value' => $_POST['report_show_average'] ?? '1', 'category' => 'reports'],
                ['key' => 'report_show_attendance', 'value' => $_POST['report_show_attendance'] ?? '1', 'category' => 'reports'],
                ['key' => 'report_head_signature', 'value' => $_POST['report_head_signature'] ?? '', 'category' => 'reports'],
                
                // System Maintenance
                ['key' => 'maintenance_mode', 'value' => $_POST['maintenance_mode'] ?? '0', 'category' => 'system'],
                ['key' => 'session_timeout', 'value' => $_POST['session_timeout'] ?? '30', 'category' => 'system'],
            ];
            
            // Save each setting
            foreach ($settingsToSave as $setting) {
                if ($settingsModel->setSetting($setting['key'], $setting['value'], $setting['category'])) {
                    $savedCount++;
                }
            }
            
            if ($savedCount === count($settingsToSave)) {
                Session::setFlash('success', "All settings saved successfully! ({$savedCount} settings updated)");
            } else {
                Session::setFlash('warning', "Partially saved: {$savedCount} of " . count($settingsToSave) . " settings updated.");
            }
            
            header('Location: settings.php');
            exit;
        }
    } catch (Exception $e) {
        Session::setFlash('error', 'Error saving settings: ' . $e->getMessage());
    }
}

// Get all settings
$settings = $settingsModel->getAllSettings();

$pageTitle = 'System Settings';
$currentPage = 'settings';
require_once 'includes/header.php';
?>

<h2 class="mb-4"><i class="bi bi-gear-fill me-2"></i> System Settings</h2>

<form method="POST" action="">
    <?= CSRF::field() ?>
    
    <div class="row">
        <!-- School Information -->
        <div class="col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header" style="background-color: #2d5016; color: white;">
                    <h5 class="mb-0"><i class="bi bi-building me-2"></i>School Information</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">School Name</label>
                        <input type="text" class="form-control" name="school_name" 
                               value="<?= htmlspecialchars($settings['school_name'] ?? 'Lilayi Park School') ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">School Motto</label>
                        <input type="text" class="form-control" name="school_motto" 
                               value="<?= htmlspecialchars($settings['school_motto'] ?? '') ?>" 
                               placeholder="e.g., Excellence in Education">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">School Address</label>
                        <textarea class="form-control" name="school_address" rows="2" required><?= htmlspecialchars($settings['school_address'] ?? '') ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">School Phone</label>
                        <input type="text" class="form-control" name="school_phone" 
                               value="<?= htmlspecialchars($settings['school_phone'] ?? '') ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">School Email</label>
                        <input type="email" class="form-control" name="school_email" 
                               value="<?= htmlspecialchars($settings['school_email'] ?? '') ?>" required>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Academic Settings -->
        <div class="col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header" style="background-color: #2d5016; color: white;">
                    <h5 class="mb-0"><i class="bi bi-calendar-range me-2"></i>Academic Settings</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Current Term</label>
                            <select class="form-select" name="current_term" required>
                                <option value="1" <?= ($settings['current_term'] ?? '1') == '1' ? 'selected' : '' ?>>Term 1</option>
                                <option value="2" <?= ($settings['current_term'] ?? '1') == '2' ? 'selected' : '' ?>>Term 2</option>
                                <option value="3" <?= ($settings['current_term'] ?? '1') == '3' ? 'selected' : '' ?>>Term 3</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Academic Year</label>
                            <input type="text" class="form-control" name="current_academic_year" 
                                   value="<?= htmlspecialchars($settings['current_academic_year'] ?? '2025-2026') ?>" 
                                   placeholder="2025-2026" required>
                        </div>
                    </div>
                    
                    <h6 class="mt-3 mb-2 fw-bold">Term Dates</h6>
                    <div class="row">
                        <div class="col-6 mb-2">
                            <label class="form-label small">Term 1 Start</label>
                            <input type="date" class="form-control form-control-sm" name="term1_start" 
                                   value="<?= htmlspecialchars($settings['term1_start'] ?? '') ?>">
                        </div>
                        <div class="col-6 mb-2">
                            <label class="form-label small">Term 1 End</label>
                            <input type="date" class="form-control form-control-sm" name="term1_end" 
                                   value="<?= htmlspecialchars($settings['term1_end'] ?? '') ?>">
                        </div>
                        <div class="col-6 mb-2">
                            <label class="form-label small">Term 2 Start</label>
                            <input type="date" class="form-control form-control-sm" name="term2_start" 
                                   value="<?= htmlspecialchars($settings['term2_start'] ?? '') ?>">
                        </div>
                        <div class="col-6 mb-2">
                            <label class="form-label small">Term 2 End</label>
                            <input type="date" class="form-control form-control-sm" name="term2_end" 
                                   value="<?= htmlspecialchars($settings['term2_end'] ?? '') ?>">
                        </div>
                        <div class="col-6 mb-2">
                            <label class="form-label small">Term 3 Start</label>
                            <input type="date" class="form-control form-control-sm" name="term3_start" 
                                   value="<?= htmlspecialchars($settings['term3_start'] ?? '') ?>">
                        </div>
                        <div class="col-6 mb-2">
                            <label class="form-label small">Term 3 End</label>
                            <input type="date" class="form-control form-control-sm" name="term3_end" 
                                   value="<?= htmlspecialchars($settings['term3_end'] ?? '') ?>">
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <label class="form-label fw-semibold">Attendance Threshold (%)</label>
                        <input type="number" class="form-control" name="attendance_threshold" min="0" max="100"
                               value="<?= htmlspecialchars($settings['attendance_threshold'] ?? '75') ?>">
                        <small class="text-muted">Minimum attendance required</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Grading Scale -->
        <div class="col-md-6 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header" style="background-color: #2d5016; color: white;">
                    <h5 class="mb-0"><i class="bi bi-bar-chart-steps me-2"></i>Grading Scale</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label fw-semibold">Grade A (min %)</label>
                            <input type="number" class="form-control" name="grade_a_min" min="0" max="100"
                                   value="<?= htmlspecialchars($settings['grade_a_min'] ?? '80') ?>">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label fw-semibold">Grade B (min %)</label>
                            <input type="number" class="form-control" name="grade_b_min" min="0" max="100"
                                   value="<?= htmlspecialchars($settings['grade_b_min'] ?? '70') ?>">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label fw-semibold">Grade C (min %)</label>
                            <input type="number" class="form-control" name="grade_c_min" min="0" max="100"
                                   value="<?= htmlspecialchars($settings['grade_c_min'] ?? '60') ?>">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label fw-semibold">Grade D (min %)</label>
                            <input type="number" class="form-control" name="grade_d_min" min="0" max="100"
                                   value="<?= htmlspecialchars($settings['grade_d_min'] ?? '50') ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Passing Grade (%)</label>
                            <input type="number" class="form-control" name="passing_grade" min="0" max="100"
                                   value="<?= htmlspecialchars($settings['passing_grade'] ?? '50') ?>">
                            <small class="text-muted">Minimum score to pass</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Financial Settings -->
        <div class="col-md-6 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header" style="background-color: #2d5016; color: white;">
                    <h5 class="mb-0"><i class="bi bi-cash-stack me-2"></i>Financial Settings</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Currency</label>
                        <select class="form-select" name="currency">
                            <option value="ZMW" <?= ($settings['currency'] ?? 'ZMW') == 'ZMW' ? 'selected' : '' ?>>ZMW (Zambian Kwacha)</option>
                            <option value="USD" <?= ($settings['currency'] ?? 'ZMW') == 'USD' ? 'selected' : '' ?>>USD (US Dollar)</option>
                            <option value="EUR" <?= ($settings['currency'] ?? 'ZMW') == 'EUR' ? 'selected' : '' ?>>EUR (Euro)</option>
                            <option value="GBP" <?= ($settings['currency'] ?? 'ZMW') == 'GBP' ? 'selected' : '' ?>>GBP (British Pound)</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Late Fee Penalty (%)</label>
                        <input type="number" class="form-control" name="late_fee_penalty" min="0" max="100" step="0.1"
                               value="<?= htmlspecialchars($settings['late_fee_penalty'] ?? '0') ?>">
                        <small class="text-muted">Penalty for late payment</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Library Settings -->
        <div class="col-md-6 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header" style="background-color: #2d5016; color: white;">
                    <h5 class="mb-0"><i class="bi bi-book me-2"></i>Library Settings</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Fine Per Day (K)</label>
                        <input type="number" class="form-control" name="library_fine_per_day" min="0" step="0.01"
                               value="<?= htmlspecialchars($settings['library_fine_per_day'] ?? '0.50') ?>">
                        <small class="text-muted">Fine charged per day for overdue books</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Maximum Books Per Pupil</label>
                        <input type="number" class="form-control" name="library_max_books" min="1" max="10"
                               value="<?= htmlspecialchars($settings['library_max_books'] ?? '3') ?>">
                        <small class="text-muted">Maximum books a pupil can borrow at once</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Loan Period (Days)</label>
                        <input type="number" class="form-control" name="library_loan_period" min="1" max="90"
                               value="<?= htmlspecialchars($settings['library_loan_period'] ?? '14') ?>">
                        <small class="text-muted">Number of days books can be borrowed</small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Notification Settings -->
        <div class="col-md-6 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header" style="background-color: #2d5016; color: white;">
                    <h5 class="mb-0"><i class="bi bi-bell me-2"></i>Notification Settings</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3 form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="notifications_enabled" value="1"
                               id="notificationsEnabled" <?= ($settings['notifications_enabled'] ?? '0') == '1' ? 'checked' : '' ?>>
                        <label class="form-check-label fw-semibold" for="notificationsEnabled">
                            Enable Notifications
                        </label>
                    </div>
                    
                    <div class="mb-3 form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="email_notifications" value="1"
                               id="emailNotifications" <?= ($settings['email_notifications'] ?? '0') == '1' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="emailNotifications">
                            Email Notifications
                        </label>
                        <small class="d-block text-muted">Send notifications via email</small>
                    </div>
                    
                    <div class="mb-3 form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="sms_notifications" value="1"
                               id="smsNotifications" <?= ($settings['sms_notifications'] ?? '0') == '1' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="smsNotifications">
                            SMS Notifications
                        </label>
                        <small class="d-block text-muted">Send notifications via SMS</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">SMS API Key</label>
                        <input type="text" class="form-control" name="sms_api_key" 
                               value="<?= htmlspecialchars($settings['sms_api_key'] ?? '') ?>">
                        <small class="text-muted">API key for SMS service provider</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Email/SMTP Configuration -->
        <div class="col-md-12 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header" style="background-color: #2d5016; color: white;">
                    <h5 class="mb-0"><i class="bi bi-envelope-at me-2"></i>Email/SMTP Configuration</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info mb-3">
                        <i class="bi bi-info-circle me-2"></i>
                        <small><strong>Configure SMTP settings to send email notifications and account credentials.</strong> 
                        Use your email provider's SMTP settings (Gmail, Outlook, etc.)</small>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">SMTP Host</label>
                            <input type="text" class="form-control" name="smtp_host" 
                                   value="<?= htmlspecialchars($settings['smtp_host'] ?? '') ?>"
                                   placeholder="e.g., smtp.gmail.com">
                            <small class="text-muted">SMTP server address</small>
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-semibold">SMTP Port</label>
                            <input type="number" class="form-control" name="smtp_port" min="1" max="65535"
                                   value="<?= htmlspecialchars($settings['smtp_port'] ?? '587') ?>">
                            <small class="text-muted">Usually 587 or 465</small>
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-semibold">Encryption</label>
                            <select class="form-select" name="smtp_encryption">
                                <option value="tls" <?= ($settings['smtp_encryption'] ?? 'tls') == 'tls' ? 'selected' : '' ?>>TLS</option>
                                <option value="ssl" <?= ($settings['smtp_encryption'] ?? 'tls') == 'ssl' ? 'selected' : '' ?>>SSL</option>
                                <option value="none" <?= ($settings['smtp_encryption'] ?? 'tls') == 'none' ? 'selected' : '' ?>>None</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">SMTP Username</label>
                            <input type="text" class="form-control" name="smtp_username" 
                                   value="<?= htmlspecialchars($settings['smtp_username'] ?? '') ?>"
                                   placeholder="your-email@domain.com">
                            <small class="text-muted">Usually your email address</small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">SMTP Password</label>
                            <input type="password" class="form-control" name="smtp_password" 
                                   value="<?= htmlspecialchars($settings['smtp_password'] ?? '') ?>"
                                   placeholder="Enter SMTP password">
                            <small class="text-muted">App-specific password recommended</small>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">From Email Address</label>
                            <input type="email" class="form-control" name="smtp_from_email" 
                                   value="<?= htmlspecialchars($settings['smtp_from_email'] ?? '') ?>"
                                   placeholder="noreply@school.com">
                            <small class="text-muted">Email address shown as sender</small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">From Name</label>
                            <input type="text" class="form-control" name="smtp_from_name" 
                                   value="<?= htmlspecialchars($settings['smtp_from_name'] ?? 'Lilayi Park School') ?>">
                            <small class="text-muted">Name shown as sender</small>
                        </div>
                    </div>
                    
                    <div class="mb-3 form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="send_account_emails" value="1"
                               id="sendAccountEmails" <?= ($settings['send_account_emails'] ?? '0') == '1' ? 'checked' : '' ?>>
                        <label class="form-check-label fw-semibold" for="sendAccountEmails">
                            Send Account Credentials via Email
                        </label>
                        <small class="d-block text-muted">Automatically email login credentials when creating teacher/parent accounts</small>
                    </div>
                    
                    <button type="button" class="btn btn-outline-primary btn-sm" id="testEmailBtn">
                        <i class="bi bi-send me-1"></i> Send Test Email
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Report Card Settings -->
        <div class="col-md-6 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header" style="background-color: #2d5016; color: white;">
                    <h5 class="mb-0"><i class="bi bi-file-earmark-text me-2"></i>Report Card Settings</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3 form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="report_show_position" value="1"
                               id="reportPosition" <?= ($settings['report_show_position'] ?? '1') == '1' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="reportPosition">
                            Show Student Position/Rank
                        </label>
                    </div>
                    
                    <div class="mb-3 form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="report_show_average" value="1"
                               id="reportAverage" <?= ($settings['report_show_average'] ?? '1') == '1' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="reportAverage">
                            Show Class Average
                        </label>
                    </div>
                    
                    <div class="mb-3 form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="report_show_attendance" value="1"
                               id="reportAttendance" <?= ($settings['report_show_attendance'] ?? '1') == '1' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="reportAttendance">
                            Show Attendance Summary
                        </label>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Head Teacher Signature Name</label>
                        <input type="text" class="form-control" name="report_head_signature" 
                               value="<?= htmlspecialchars($settings['report_head_signature'] ?? '') ?>">
                        <small class="text-muted">Name to display on report cards</small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- System Settings -->
        <div class="col-md-6 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header" style="background-color: #2d5016; color: white;">
                    <h5 class="mb-0"><i class="bi bi-tools me-2"></i>System Settings</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3 form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="maintenance_mode" value="1"
                               id="maintenanceMode" <?= ($settings['maintenance_mode'] ?? '0') == '1' ? 'checked' : '' ?>>
                        <label class="form-check-label fw-semibold text-danger" for="maintenanceMode">
                            Maintenance Mode
                        </label>
                        <small class="d-block text-muted">Only admins can access the system</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Session Timeout (Minutes)</label>
                        <input type="number" class="form-control" name="session_timeout" min="5" max="1440"
                               value="<?= htmlspecialchars($settings['session_timeout'] ?? '30') ?>">
                        <small class="text-muted">Auto-logout after inactivity</small>
                    </div>
                    
                    <div class="alert alert-warning mb-0">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <small><strong>Note:</strong> Enabling maintenance mode will restrict access to administrators only.</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Links -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header" style="background-color: #2d5016; color: white;">
                    <h5 class="mb-0"><i class="bi bi-link-45deg me-2"></i>Quick Links</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <a href="holidays_list.php" class="text-decoration-none">
                                <div class="d-flex align-items-center p-3 border rounded hover-shadow">
                                    <i class="bi bi-calendar-event me-3" style="font-size: 2rem; color: #2d5016;"></i>
                                    <div>
                                        <h6 class="mb-0">Manage Holidays</h6>
                                        <small class="text-muted">School holidays & breaks</small>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-4">
                            <a href="users_list.php" class="text-decoration-none">
                                <div class="d-flex align-items-center p-3 border rounded hover-shadow">
                                    <i class="bi bi-person-gear me-3" style="font-size: 2rem; color: #2d5016;"></i>
                                    <div>
                                        <h6 class="mb-0">User Management</h6>
                                        <small class="text-muted">Manage system users</small>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-4">
                            <a href="clear_cache.php" class="text-decoration-none">
                                <div class="d-flex align-items-center p-3 border rounded hover-shadow">
                                    <i class="bi bi-arrow-clockwise me-3" style="font-size: 2rem; color: #2d5016;"></i>
                                    <div>
                                        <h6 class="mb-0">Clear Cache</h6>
                                        <small class="text-muted">Clear system cache</small>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-lg shadow-sm" style="background-color: #2d5016; color: white;">
            <i class="bi bi-check-circle me-1"></i> Save Settings
        </button>
        <a href="index.php" class="btn btn-lg btn-outline-secondary">
            <i class="bi bi-x-circle me-1"></i> Cancel
        </a>
    </div>
</form>

<script>
document.getElementById('testEmailBtn').addEventListener('click', function() {
    const btn = this;
    const originalHTML = btn.innerHTML;
    
    // Prompt for email address
    const email = prompt('Enter email address to send test email:', '<?= $user['email'] ?? '' ?>');
    
    if (!email) {
        return;
    }
    
    // Disable button and show loading
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Sending...';
    
    // Send test email
    fetch('api/email_test.php?action=test&email=' + encodeURIComponent(email))
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✓ Test email sent successfully to ' + email + '!\n\nPlease check your inbox (and spam folder).');
            } else {
                alert('✗ Failed to send test email:\n\n' + data.message + '\n\nPlease check your SMTP configuration.');
            }
        })
        .catch(error => {
            alert('Error sending test email: ' + error.message);
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = originalHTML;
        });
});
</script>

<?php require_once 'includes/footer.php'; ?>
