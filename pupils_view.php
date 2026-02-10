<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'includes/bootstrap.php';
require_once 'includes/Auth.php';

Auth::requireLogin();

require_once 'modules/roles/RolesModel.php';
$rolesModel = new RolesModel();
if (!$rolesModel->userHasPermission(Auth::id(), 'view_pupils')) {
    Session::setFlash('error', 'You do not have permission to view pupils.');
    header('Location: 403.php');
    exit;
}

require_once 'modules/pupils/PupilModel.php';
require_once 'modules/payments/PaymentModel.php';
require_once 'modules/fees/FeesModel.php';

$pupilID = $_GET['id'] ?? null;
if (empty($pupilID)) {
    header('Location: pupils_list.php');
    exit;
}

// Check if user can access this pupil
if (!Auth::canAccessPupil($pupilID)) {
    Session::setFlash('error', 'You do not have permission to view this pupil.');
    header('Location: /403.php');
    exit;
}

$pupilModel = new PupilModel();
$paymentModel = new PaymentModel();
$feesModel = new FeesModel();

$pupil = $pupilModel->getPupilWithParent($pupilID) ?: $pupilModel->getById($pupilID);
require_once 'modules/classes/ClassModel.php';
$classModel = new ClassModel();
$allClasses = $classModel->getAllWithDetails();
// Parent details are now stored on the pupil record (parent1, parent2, relationship, phone, parentEmail)
if (!$pupil || !is_array($pupil)) {
    require_once 'includes/header.php';
    echo '<div class="alert alert-danger mt-4">Pupil not found or record is unavailable.</div>';
    require_once 'includes/footer.php';
    exit;
}

$pageTitle = 'Pupil Details';
$currentPage = 'pupils';
require_once 'includes/header.php';
require_once 'includes/PermissionHelper.php';
?>

<!-- Page Header with Actions -->
<div class="row mb-4">
    <div class="col-md-8">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="pupils_list.php" class="text-decoration-none">Pupils</a></li>
                <li class="breadcrumb-item active" aria-current="page">View Details</li>
            </ol>
        </nav>
        <h2 class="mb-0">
            <i class="bi bi-mortarboard me-2" style="color: #2d5016;"></i>
            <span style="color: #2d5016; font-weight: 600;">Pupil Profile</span>
        </h2>
    </div>
    <div class="col-md-4 text-end align-self-end">
        <div class="btn-group" role="group">
            <a href="pupils_list.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
            <a href="pupils_pdf.php?id=<?= urlencode($pupilID) ?>&print=1" target="_blank" class="btn btn-outline-dark">
                <i class="bi bi-printer me-1"></i> Print
            </a>
            <?php if (Auth::hasRole('admin')): ?>
                <a href="payments_form.php?pupil=<?= $pupilID ?>" class="btn btn-sm btn-success">
                <i class="bi bi-cash-coin me-1"></i> Add Payment
            </a>
            <?php endif; ?>
            <?php if ($rolesModel->userHasPermission(Auth::id(), 'manage_classes')): ?>
                <a href="#" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#assignClassModal">
                <i class="bi bi-arrow-right-square me-1"></i> Class
            </a>
            <?php endif; ?>
            <?php if (PermissionHelper::canManage('pupils')): ?>
                <a href="pupils_form.php?id=<?= $pupilID ?>" class="btn btn-sm btn-warning">
                <i class="bi bi-pencil me-1"></i> Edit
            </a>
            <a href="pupils_delete.php?id=<?= $pupilID ?>" 
               class="btn btn-danger"
               onclick="return confirm('Are you sure you want to delete this pupil? This action cannot be undone.');">
                    <i class="bi bi-trash me-1"></i> Delete
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Pupil Profile -->
<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <div class="mb-3">
                    <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center" 
                         style="width: 120px; height: 120px;">
                        <i class="bi bi-person-circle" style="font-size: 6rem; color: #2d5016;"></i>
                    </div>
                </div>
                <h4 class="mb-1"><?= htmlspecialchars($pupil['fName'] ?? '') . ' ' . htmlspecialchars($pupil['lName'] ?? '') ?></h4>
                <p class="text-muted mb-3">Student</p>
                <div class="mb-3">
                    <span class="badge bg-success px-3 py-2">
                        <i class="bi bi-check-circle me-1"></i>Active
                    </span>
                </div>
                <hr>
                <div class="text-start">
                    <small class="text-muted d-block mb-2">Quick Info</small>
                    <div class="d-flex align-items-center mb-2">
                        <i class="bi bi-hash text-muted me-2"></i>
                        <!-- Removed pupil ID from quick info -->
                    </div>
                    <div class="d-flex align-items-center mb-2">
                        <i class="bi bi-calendar text-muted me-2"></i>
                        <span class="small"><?= htmlspecialchars($pupil['DoB'] ?? 'N/A') ?></span>
                    </div>
                    <div class="d-flex align-items-center">
                        <i class="bi bi-gender-ambiguous text-muted me-2"></i>
                        <span class="small"><?php 
                            $gender = $pupil['gender'] ?? '';
                            echo $gender === 'M' ? 'Male' : ($gender === 'F' ? 'Female' : 'N/A');
                        ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <!-- Personal Information -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">
                    <i class="bi bi-person-lines-fill me-2" style="color: #2d5016;"></i>Personal Information
                </h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="text-muted small mb-1">First Name</label>
                        <p class="mb-0 fw-semibold"><?= htmlspecialchars($pupil['fName'] ?? 'N/A') ?></p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small mb-1">Last Name</label>
                        <p class="mb-0 fw-semibold"><?= htmlspecialchars($pupil['lName'] ?? 'N/A') ?></p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small mb-1">Pupil ID</label>
                        <!-- Removed pupil ID from personal information -->
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small mb-1">Date of Birth</label>
                        <p class="mb-0 fw-semibold"><?= htmlspecialchars($pupil['DoB'] ?? 'N/A') ?></p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small mb-1">Gender</label>
                        <p class="mb-0 fw-semibold">
                            <?php 
                            $gender = $pupil['gender'] ?? '';
                            echo $gender === 'M' ? 'Male' : ($gender === 'F' ? 'Female' : 'N/A');
                            ?>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small mb-1">Class</label>
                        <p class="mb-0 fw-semibold">
                            <?= htmlspecialchars($pupil['className'] ?? 'Not Assigned') ?>
                            <?php if (!empty($pupil['teacherName'])): ?>
                                <br><small class="text-muted">Teacher: <?= htmlspecialchars($pupil['teacherName']) ?></small>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
            <!-- Payments Summary and History -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">
                        <i class="bi bi-wallet2 me-2" style="color: #2d5016;"></i>Payments & Balance
                    </h5>
                </div>
                <div class="card-body">
                    <?php
                    // Determine pupil's current class to find applicable fees
                    $db = Database::getInstance()->getConnection();
                    $stmt = $db->prepare("SELECT pc.classID, c.className FROM Pupil_Class pc JOIN Class c ON pc.classID = c.classID WHERE pc.pupilID = ? LIMIT 1");
                    $stmt->execute([$pupilID]);
                    $pClass = $stmt->fetch();
                    $classID = $pClass['classID'] ?? null;

                    // Default values
                    $totalFee = 0;
                    $feeID = null;

                    // If pupil has a class, load the applicable Fees row (by class and current year)
                    if ($classID) {
                        $currentYear = date('Y');
                        $stmt = $db->prepare("SELECT feeID, feeAmt, term, year FROM Fees WHERE classID = ? AND year = ? ORDER BY term DESC LIMIT 1");
                        $stmt->execute([$classID, $currentYear]);
                        $feeRow = $stmt->fetch();
                        if ($feeRow) {
                            $feeID = $feeRow['feeID'] ?? null;
                            $totalFee = $feeRow['feeAmt'] ?? 0;
                        }
                    }

                    // Get payments for this pupil. If we found a feeID, filter payments to that feeID so
                    // the relation between pupil and payment is determined by class -> Fees(feeID).
                    if ($feeID) {
                        $stmt = $db->prepare(
                            "SELECT payID, pupilID, feeID, classID, pmtAmt, balance, paymentDate, paymentMode, remark, createdAt, updatedAt, term, academicYear
                             FROM Payment WHERE pupilID = ? AND feeID = ? ORDER BY paymentDate DESC"
                        );
                        $stmt->execute([$pupilID, $feeID]);
                    } else {
                        // Fallback: if no fee row exists for the pupil's class/year, show all payments for pupil
                        $stmt = $db->prepare(
                            "SELECT payID, pupilID, feeID, classID, pmtAmt, balance, paymentDate, paymentMode, remark, createdAt, updatedAt, term, academicYear
                             FROM Payment WHERE pupilID = ? ORDER BY paymentDate DESC"
                        );
                        $stmt->execute([$pupilID]);
                    }
                    $payments = $stmt->fetchAll();
                    // Calculate totals and running balance (starting from total fee)
                    $totalPaid = 0;
                    foreach ($payments as $pay) {
                        $totalPaid += floatval($pay['pmtAmt'] ?? 0);
                    }
                    $outstanding = $totalFee - $totalPaid;
                    ?>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <small class="text-muted">Total Fee (current term)</small>
                            <h5 class="mb-0">K <?= number_format($totalFee, 2) ?></h5>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted">Total Paid</small>
                            <h5 class="mb-0 text-success">K <?= number_format($totalPaid, 2) ?></h5>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted">Outstanding Balance</small>
                            <h5 class="mb-0 text-danger">K <?= number_format(max(0, $outstanding), 2) ?></h5>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Mode</th>
                                    <th>Amount (K)</th>
                                    <th>Balance After (K)</th>
                                    <th>Remark</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($payments)): ?>
                                    <tr><td colspan="5" class="text-center text-muted">No payments recorded for this pupil</td></tr>
                                <?php else:
                                    // Payments returned DESC; compute running balance after each payment
                                    $runningBalance = $totalFee;
                                    // To show from last to first (most recent first), iterate as-is
                                    foreach ($payments as $pay):
                                        $amt = floatval($pay['pmtAmt'] ?? 0);
                                        // Assuming balance in Payment record is balance after payment; if not, compute
                                        $balanceAfter = isset($pay['balance']) ? floatval($pay['balance']) : ($runningBalance - $amt);
                                        // Update running balance to reflect previous state when moving to next (older) record
                                        $runningBalance = $balanceAfter;
                                ?>
                                    <tr>
                                        <td><?= htmlspecialchars(date('M d, Y', strtotime($pay['paymentDate'] ?? '')) ) ?></td>
                                        <td><?= htmlspecialchars($pay['paymentMode'] ?? 'Cash') ?></td>
                                        <td><?= number_format($amt, 2) ?></td>
                                        <td><?= number_format($balanceAfter, 2) ?></td>
                                        <td><?= htmlspecialchars($pay['remark'] ?? '') ?></td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <!-- Parent/Guardian Information -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">
                    <i class="bi bi-people-fill me-2" style="color: #2d5016;"></i>Parent / Guardian Information
                </h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="text-muted small mb-1">Parent / Guardian</label>
                        <p class="mb-0 fw-semibold"><?= htmlspecialchars($pupil['parent1'] ?? 'N/A') ?><?php if (!empty($pupil['parent2'])): ?> &amp; <?= htmlspecialchars($pupil['parent2']) ?><?php endif; ?></p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small mb-1">Relationship</label>
                        <p class="mb-0 fw-semibold"><?= htmlspecialchars($pupil['relationship'] ?? 'N/A') ?></p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small mb-1">Phone</label>
                        <p class="mb-0 fw-semibold">
                            <?php if (!empty($pupil['phone'])): ?>
                            <a href="tel:<?= htmlspecialchars($pupil['phone']) ?>" class="text-decoration-none">
                                <i class="bi bi-telephone me-1"></i><?= htmlspecialchars($pupil['phone']) ?>
                            </a>
                            <?php else: ?>
                                N/A
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small mb-1">Email</label>
                        <p class="mb-0 fw-semibold">
                            <?php if (!empty($pupil['parentEmail'])): ?>
                            <a href="mailto:<?= htmlspecialchars($pupil['parentEmail']) ?>" class="text-decoration-none">
                                <i class="bi bi-envelope me-1"></i><?= htmlspecialchars($pupil['parentEmail']) ?>
                            </a>
                            <?php else: ?>
                                N/A
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small mb-1">Enrollment Date</label>
                        <p class="mb-0 fw-semibold">
                            <?php
                            if (!empty($pupil['enrollDate'])) {
                                echo date('d-m-Y', strtotime($pupil['enrollDate']));
                            } else {
                                echo 'N/A';
                            }
                            ?>
                        </p>
                    </div>
                    <div class="col-md-12">
                        <label class="text-muted small mb-1">Home Address</label>
                        <p class="mb-0"><?= nl2br(htmlspecialchars($pupil['homeAddress'] ?? 'Not provided')) ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Medical Information -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">
                    <i class="bi bi-heart-pulse-fill me-2" style="color: #2d5016;"></i>Medical Information
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <label class="text-muted small mb-1">Medical Conditions</label>
                        <p class="mb-0"><?= nl2br(htmlspecialchars($pupil['medCondition'] ?? 'None')) ?></p>
                    </div>
                    <div class="col-md-4">
                        <label class="text-muted small mb-1">Medical Allergies</label>
                        <p class="mb-0"><?= nl2br(htmlspecialchars($pupil['medAllergy'] ?? 'None')) ?></p>
                    </div>
                    <div class="col-md-4">
                        <label class="text-muted small mb-1">Restrictions</label>
                        <p class="mb-0"><?= nl2br(htmlspecialchars($pupil['restrictions'] ?? 'None')) ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .card {
        transition: all 0.3s ease;
    }
    .card:hover {
        transform: translateY(-2px);
    }
    .btn {
        transition: all 0.2s ease;
    }
    .btn:hover {
        transform: translateY(-1px);
    }
</style>

<?php require_once 'includes/footer.php'; ?>
<?php if (!empty($allClasses) && $rolesModel->userHasPermission(Auth::id(), 'manage_classes')): ?>
<!-- Assign / Change Class Modal (moved out of card to avoid flicker from transformed ancestor) -->
<div class="modal fade" id="assignClassModal" tabindex="-1" aria-labelledby="assignClassModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #2d5016; color: white;">
                <h5 class="modal-title" id="assignClassModalLabel">
                    <i class="bi bi-arrow-right-square me-2"></i>Assign / Change Class
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Select Class</label>
                    <select id="assignClassSelect" class="form-select">
                        <option value="">-- Select a class --</option>
                        <?php foreach ($allClasses as $c): ?>
                            <option value="<?= htmlspecialchars($c['classID']) ?>"><?= htmlspecialchars($c['className'] . ' - ' . trim(($c['teacherFirstName'] ?? '') . ' ' . ($c['teacherLastName'] ?? ''))) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php $currentClass = $pupil['classID'] ?? null; ?>
                <?php if (!empty($currentClass)): ?>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" value="1" id="replaceClassCheck" checked>
                    <label class="form-check-label" for="replaceClassCheck">
                        Replace existing class assignment (current: <?= htmlspecialchars($currentClass) ?>)
                    </label>
                </div>
                <?php endif; ?>
                <div id="assignClassAlert" class="mt-2"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="assignClassBtn" onclick="assignClass()">
                    <i class="bi bi-check2-circle me-1"></i>Assign
                </button>
            </div>
        </div>
    </div>
</div>
<script>
async function postForm(formData) {
    const resp = await fetch('classes_manage_pupils.php', {
        method: 'POST',
        body: formData
    });
    return resp.json();
}

async function assignClass() {
    const select = document.getElementById('assignClassSelect');
    const classID = select.value;
    const alertBox = document.getElementById('assignClassAlert');
    const btn = document.getElementById('assignClassBtn');
    const replace = document.getElementById('replaceClassCheck') ? document.getElementById('replaceClassCheck').checked : false;
    const pupilID = '<?= htmlspecialchars($pupilID) ?>';
    const currentClass = '<?= htmlspecialchars($pupil['classID'] ?? '') ?>';

    alertBox.innerHTML = '';
    if (!classID) {
        alertBox.innerHTML = '<div class="alert alert-warning">Please select a class to assign.</div>';
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Assigning...';

    try {
        if (replace && currentClass) {
            const removeData = new FormData();
            removeData.append('action', 'remove');
            removeData.append('classID', currentClass);
            removeData.append('pupilID', pupilID);
            const removeResp = await postForm(removeData);
            if (!removeResp.success) {
                alertBox.innerHTML = '<div class="alert alert-danger">Failed to remove existing class: ' + (removeResp.error || 'Unknown') + '</div>';
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-check2-circle me-1"></i>Assign';
                return;
            }
        }

        const addData = new FormData();
        addData.append('action', 'add');
        addData.append('classID', classID);
        addData.append('pupilID', pupilID);
        const addResp = await postForm(addData);
        if (addResp.success) {
            // close modal and reload
            const modalEl = document.getElementById('assignClassModal');
            const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
            modal.hide();
            location.reload();
        } else {
            alertBox.innerHTML = '<div class="alert alert-danger">Error: ' + (addResp.error || 'Failed to assign') + '</div>';
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check2-circle me-1"></i>Assign';
        }
    } catch (err) {
        console.error(err);
        alertBox.innerHTML = '<div class="alert alert-danger">An unexpected error occurred.</div>';
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check2-circle me-1"></i>Assign';
    }
}
</script>
<?php endif; ?>
