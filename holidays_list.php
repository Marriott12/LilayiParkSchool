<?php
require_once 'includes/bootstrap.php';
require_once 'includes/Auth.php';
require_once 'includes/PermissionHelper.php';

Auth::requireLogin();
Auth::requireAnyRole(['admin']);

require_once 'modules/settings/HolidaysModel.php';
require_once 'modules/settings/SettingsModel.php';

$holidaysModel = new HolidaysModel();
$settingsModel = new SettingsModel();

$currentYear = $settingsModel->getSetting('current_academic_year', date('Y') . '-' . (date('Y') + 1));
$year = $_GET['year'] ?? $currentYear;

$holidays = $holidaysModel->getAllHolidays($year);

$pageTitle = 'School Holidays';
$currentPage = 'settings';
require_once 'includes/header.php';
?>

<!-- Page Header -->
<div class="row mb-4">
    <div class="col-md-8">
        <h2 class="mb-0">
            <i class="bi bi-calendar-event me-2"></i>
            <span style="color: #2d5016; font-weight: 600;">School Holidays</span>
        </h2>
        <p class="text-muted mt-1">Manage school holidays and breaks</p>
    </div>
    <div class="col-md-4 text-end">
        <a href="holidays_form.php" class="btn shadow-sm" style="background-color: #2d5016; color: white;">
            <i class="bi bi-plus-circle me-1"></i> Add Holiday
        </a>
    </div>
</div>

<!-- Filter -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Academic Year</label>
                <select name="year" class="form-select" onchange="this.form.submit()">
                    <?php
                    $startYear = date('Y') - 2;
                    for ($i = 0; $i < 5; $i++) {
                        $yearOption = ($startYear + $i) . '-' . ($startYear + $i + 1);
                        $selected = $yearOption == $year ? 'selected' : '';
                        echo "<option value='$yearOption' $selected>$yearOption</option>";
                    }
                    ?>
                </select>
            </div>
        </form>
    </div>
</div>

<!-- Statistics -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <i class="bi bi-calendar-check" style="font-size: 2.5rem; color: #2d5016;"></i>
                <h3 class="mt-2 mb-0"><?= count($holidays) ?></h3>
                <p class="text-muted mb-0">Total Holidays</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <i class="bi bi-briefcase text-warning" style="font-size: 2.5rem;"></i>
                <h3 class="mt-2 mb-0">
                    <?= count(array_filter($holidays, fn($h) => $h['holidayType'] == 'term_break')) ?>
                </h3>
                <p class="text-muted mb-0">Term Breaks</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <i class="bi bi-flag text-success" style="font-size: 2.5rem;"></i>
                <h3 class="mt-2 mb-0">
                    <?= count(array_filter($holidays, fn($h) => $h['holidayType'] == 'public_holiday')) ?>
                </h3>
                <p class="text-muted mb-0">Public Holidays</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <i class="bi bi-star text-info" style="font-size: 2.5rem;"></i>
                <h3 class="mt-2 mb-0">
                    <?= count(array_filter($holidays, fn($h) => $h['holidayType'] == 'school_event')) ?>
                </h3>
                <p class="text-muted mb-0">School Events</p>
            </div>
        </div>
    </div>
</div>

<!-- Holidays Calendar View -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom">
        <h5 class="mb-0">
            <i class="bi bi-list-ul me-2"></i>Holidays & Events
        </h5>
    </div>
    <div class="card-body p-0">
        <?php if (empty($holidays)): ?>
        <div class="text-center py-5">
            <i class="bi bi-calendar-x text-muted" style="font-size: 4rem;"></i>
            <p class="text-muted mt-3">No holidays scheduled for <?= htmlspecialchars($year) ?>.</p>
            <a href="holidays_form.php" class="btn btn-outline-primary">
                <i class="bi bi-plus-circle me-1"></i> Add First Holiday
            </a>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>Holiday Name</th>
                        <th>Type</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Duration</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($holidays as $holiday): ?>
                    <?php
                    $start = new DateTime($holiday['startDate']);
                    $end = new DateTime($holiday['endDate']);
                    $duration = $start->diff($end)->days + 1;
                    $today = new DateTime();
                    
                    if ($today < $start) {
                        $status = '<span class="badge bg-info">Upcoming</span>';
                    } elseif ($today > $end) {
                        $status = '<span class="badge bg-secondary">Past</span>';
                    } else {
                        $status = '<span class="badge bg-success">Ongoing</span>';
                    }
                    
                    $typeLabels = [
                        'term_break' => '<span class="badge bg-warning">Term Break</span>',
                        'public_holiday' => '<span class="badge bg-success">Public Holiday</span>',
                        'school_event' => '<span class="badge bg-info">School Event</span>',
                        'other' => '<span class="badge bg-secondary">Other</span>'
                    ];
                    ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($holiday['holidayName']) ?></strong>
                            <?php if ($holiday['description']): ?>
                            <br><small class="text-muted"><?= htmlspecialchars($holiday['description']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?= $typeLabels[$holiday['holidayType']] ?? $holiday['holidayType'] ?></td>
                        <td><?= date('d M Y', strtotime($holiday['startDate'])) ?></td>
                        <td><?= date('d M Y', strtotime($holiday['endDate'])) ?></td>
                        <td>
                            <span class="badge bg-light text-dark">
                                <?= $duration ?> day<?= $duration > 1 ? 's' : '' ?>
                            </span>
                        </td>
                        <td><?= $status ?></td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="holidays_form.php?id=<?= $holiday['holidayID'] ?>" 
                                   class="btn btn-outline-primary" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="delete.php?table=holidays&id=<?= $holiday['holidayID'] ?>&return=holidays_list.php" 
                                   class="btn btn-outline-danger" 
                                   onclick="return confirm('Delete this holiday?')" title="Delete">
                                    <i class="bi bi-trash"></i>
                                </a>
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

<div class="text-center mt-4">
    <a href="settings.php" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Back to Settings
    </a>
</div>

<?php require_once 'includes/footer.php'; ?>
