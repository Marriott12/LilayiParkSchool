<?php
require_once 'includes/bootstrap.php';
require_once 'includes/Auth.php';
require_once 'includes/CSRF.php';

Auth::requireLogin();
Auth::requireAnyRole(['admin']);

require_once 'modules/settings/HolidaysModel.php';
require_once 'modules/settings/SettingsModel.php';

$holidaysModel = new HolidaysModel();
$settingsModel = new SettingsModel();

$id = $_GET['id'] ?? null;
$holiday = $id ? $holidaysModel->getById($id) : null;
$currentYear = $settingsModel->getSetting('current_academic_year', date('Y') . '-' . (date('Y') + 1));

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::validate($_POST['csrf_token']);
    
    try {
        $data = [
            'holidayName' => $_POST['holidayName'],
            'holidayType' => $_POST['holidayType'],
            'startDate' => $_POST['startDate'],
            'endDate' => $_POST['endDate'],
            'academicYear' => $_POST['academicYear'],
            'description' => $_POST['description']
        ];
        
        // Validate dates
        if (strtotime($data['endDate']) < strtotime($data['startDate'])) {
            throw new Exception('End date must be after start date');
        }
        
        if ($id) {
            $holidaysModel->update($id, $data);
            Session::setFlash('success', 'Holiday updated successfully!');
        } else {
            $holidaysModel->create($data);
            Session::setFlash('success', 'Holiday created successfully!');
        }
        
        header('Location: holidays_list.php');
        exit;
    } catch (Exception $e) {
        Session::setFlash('error', $e->getMessage());
    }
}

$pageTitle = $id ? 'Edit Holiday' : 'Add Holiday';
$currentPage = 'settings';
require_once 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-12">
        <h2 class="mb-0">
            <i class="bi bi-calendar-event me-2"></i>
            <span style="color: #2d5016; font-weight: 600;"><?= $pageTitle ?></span>
        </h2>
        <p class="text-muted mt-1">Schedule school holidays and breaks</p>
    </div>
</div>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <form method="POST" action="">
                    <?= CSRF::field() ?>
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Holiday Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="holidayName" 
                               value="<?= htmlspecialchars($holiday['holidayName'] ?? '') ?>" 
                               placeholder="e.g., Term 1 Break, Easter Holiday" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Holiday Type <span class="text-danger">*</span></label>
                        <select class="form-select" name="holidayType" required>
                            <option value="">-- Select Type --</option>
                            <option value="term_break" <?= ($holiday['holidayType'] ?? '') == 'term_break' ? 'selected' : '' ?>>
                                Term Break
                            </option>
                            <option value="public_holiday" <?= ($holiday['holidayType'] ?? '') == 'public_holiday' ? 'selected' : '' ?>>
                                Public Holiday
                            </option>
                            <option value="school_event" <?= ($holiday['holidayType'] ?? '') == 'school_event' ? 'selected' : '' ?>>
                                School Event
                            </option>
                            <option value="other" <?= ($holiday['holidayType'] ?? '') == 'other' ? 'selected' : '' ?>>
                                Other
                            </option>
                        </select>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Start Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="startDate" 
                                   value="<?= htmlspecialchars($holiday['startDate'] ?? '') ?>" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">End Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="endDate" 
                                   value="<?= htmlspecialchars($holiday['endDate'] ?? '') ?>" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Academic Year <span class="text-danger">*</span></label>
                        <select class="form-select" name="academicYear" required>
                            <?php
                            $startYear = date('Y') - 1;
                            for ($i = 0; $i < 5; $i++) {
                                $yearOption = ($startYear + $i) . '-' . ($startYear + $i + 1);
                                $selected = $yearOption == ($holiday['academicYear'] ?? $currentYear) ? 'selected' : '';
                                echo "<option value='$yearOption' $selected>$yearOption</option>";
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Description</label>
                        <textarea class="form-control" name="description" rows="3" 
                                  placeholder="Optional description or notes"><?= htmlspecialchars($holiday['description'] ?? '') ?></textarea>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn shadow-sm" style="background-color: #2d5016; color: white;">
                            <i class="bi bi-check-circle me-1"></i> <?= $id ? 'Update' : 'Create' ?> Holiday
                        </button>
                        <a href="holidays_list.php" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle me-1"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
