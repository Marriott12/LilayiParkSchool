<?php
require_once 'includes/bootstrap.php';
require_once 'includes/Auth.php';
require_once 'includes/PermissionHelper.php';

Auth::requireLogin();

require_once 'modules/roles/RolesModel.php';
$rolesModel = new RolesModel();
if (!$rolesModel->userHasPermission(Auth::id(), 'view_reports')) {
    Session::setFlash('error', 'You do not have permission to view transfer reports.');
    header('Location: 403.php');
    exit;
}

require_once 'modules/pupils/PupilModel.php';
$pupilModel = new PupilModel();

$pageTitle = 'Pupil Transfer Report';
$currentPage = 'pupil_transfers';
require_once 'includes/header.php';

$db = Database::getInstance()->getConnection();
$selectName = '';
try {
    $colStmt = $db->query('SHOW COLUMNS FROM Pupil_Transfer');
    $cols = $colStmt->fetchAll(PDO::FETCH_COLUMN, 0);
    $hasFName = in_array('fName', $cols, true);
    $hasLName = in_array('lName', $cols, true);
} catch (Exception $e) {
    $hasFName = false;
    $hasLName = false;
}

if ($hasFName && $hasLName) {
    $selectName = 'COALESCE(pt.fName, p.fName) AS pupilFirstName, COALESCE(pt.lName, p.lName) AS pupilLastName,';
} else {
    $selectName = 'p.fName AS pupilFirstName, p.lName AS pupilLastName,';
}

$sql = 'SELECT pt.*, ' . $selectName . " c.className AS fromClassName
    FROM Pupil_Transfer pt
    LEFT JOIN Pupil p ON pt.pupilID = p.pupilID
    LEFT JOIN `Class` c ON pt.fromClassID = c.classID
    ORDER BY pt.transferDate DESC";

$stmt = $db->prepare($sql);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-arrow-right-circle"></i> Pupil Transfer Report</h2>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Transfer ID</th>
                        <th>Pupil</th>
                        <th>From Class</th>
                        <th>To School</th>
                        <th>Transfer Date</th>
                        <th>Outstanding (K)</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                    <tr><td colspan="7" class="text-center text-muted">No transfers recorded</td></tr>
                    <?php else: foreach ($rows as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars($r['transferID']) ?></td>
                        <td><?= htmlspecialchars(trim(($r['pupilFirstName'] ?? '') . ' ' . ($r['pupilLastName'] ?? ''))) ?></td>
                        <td><?= htmlspecialchars($r['fromClassName'] ?? ($r['fromClassID'] ?? 'N/A')) ?></td>
                        <td><?= htmlspecialchars($r['toSchool'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars(!empty($r['transferDate']) ? date('d-m-Y', strtotime($r['transferDate'])) : '') ?></td>
                        <td><?= number_format((float)($r['outstanding'] ?? 0), 2) ?></td>
                        <td><?= htmlspecialchars(substr($r['notes'] ?? $r['reason'] ?? '', 0, 100)) ?></td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
