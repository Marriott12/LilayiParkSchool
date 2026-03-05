<?php
require_once 'includes/bootstrap.php';
require_once 'includes/Auth.php';
Auth::requireLogin();

require_once 'modules/roles/RolesModel.php';
$rolesModel = new RolesModel();
if (!$rolesModel->userHasPermission(Auth::id(), 'manage_pupils')) {
    Session::setFlash('error', 'You do not have permission to manage pupil transfers.');
    header('Location: 403.php');
    exit;
}

// Handle revert POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['revert']) && isset($_POST['transferID'])) {
    if (!CSRF::requireToken()) {
        Session::setFlash('error', 'Security validation failed.');
        header('Location: pupil_transfers_admin.php');
        exit;
    }

    $transferID = (int)$_POST['transferID'];
    $db = Database::getInstance()->getConnection();
    try {
        // Fetch transfer row
        $stmt = $db->prepare('SELECT * FROM Pupil_Transfer WHERE transferID = ? LIMIT 1');
        $stmt->execute([$transferID]);
        $t = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$t) {
            Session::setFlash('error', 'Transfer not found.');
            header('Location: pupil_transfers_admin.php'); exit;
        }
        $pupilID = $t['pupilID'];

        // If pupil exists, unset transferred flag; otherwise, recreate basic pupil record
        $stmtP = $db->prepare('SELECT * FROM Pupil WHERE pupilID = ? LIMIT 1');
        $stmtP->execute([$pupilID]);
        $p = $stmtP->fetch(PDO::FETCH_ASSOC);

        if ($p) {
            $upd = $db->prepare('UPDATE Pupil SET transferred = 0, transferredAt = NULL WHERE pupilID = ?');
            $upd->execute([$pupilID]);
        } else {
            // Recreate minimal pupil row using snapshot columns if present
            $fields = ['pupilID' => $pupilID];
            $cols = [];
            try {
                $colStmt = $db->query('SHOW COLUMNS FROM Pupil');
                $existing = $colStmt->fetchAll(PDO::FETCH_COLUMN, 0);
            } catch (Exception $e) {
                $existing = [];
            }
            $snapKeys = ['fName','lName','DoB','gender','parentEmail','phone','enrollDate'];
            foreach ($snapKeys as $k) {
                if (in_array($k, $existing, true) && isset($t[$k])) {
                    $fields[$k] = $t[$k];
                }
            }
            // Build insert
            $cols = array_keys($fields);
            $place = implode(',', array_fill(0, count($cols), '?'));
            $sql = 'INSERT INTO Pupil (' . implode(',', $cols) . ') VALUES (' . $place . ')';
            $ins = $db->prepare($sql);
            $ins->execute(array_values($fields));
        }

        // Remove transfer record (or you may prefer to mark it reverted instead)
        $del = $db->prepare('DELETE FROM Pupil_Transfer WHERE transferID = ?');
        $del->execute([$transferID]);

        Session::setFlash('success', 'Transfer reverted and pupil unarchived.');
    } catch (Exception $e) {
        Session::setFlash('error', 'Failed to revert transfer: ' . $e->getMessage());
    }

    header('Location: pupil_transfers_admin.php');
    exit;
}

$pageTitle = 'Manage Pupil Transfers';
$currentPage = 'pupil_transfers';
require_once 'includes/header.php';

$db = Database::getInstance()->getConnection();
try {
    $stmt = $db->prepare('SELECT pt.* FROM Pupil_Transfer pt ORDER BY pt.transferDate DESC');
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $rows = [];
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-gear"></i> Manage Pupil Transfers</h2>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Transfer ID</th>
                        <th>Pupil ID</th>
                        <th>Name</th>
                        <th>From Class</th>
                        <th>To School</th>
                        <th>Transfer Date</th>
                        <th>Outstanding</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                    <tr><td colspan="8" class="text-center text-muted">No transfers recorded</td></tr>
                    <?php else: foreach ($rows as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars($r['transferID']) ?></td>
                        <td><?= htmlspecialchars($r['pupilID']) ?></td>
                        <td><?= htmlspecialchars((($r['fName'] ?? $r['pupilFirstName'] ?? '') . ' ' . ($r['lName'] ?? $r['pupilLastName'] ?? '')) ) ?></td>
                        <td><?= htmlspecialchars($r['fromClassID'] ?? '') ?></td>
                        <td><?= htmlspecialchars($r['toSchool'] ?? '') ?></td>
                        <td><?= htmlspecialchars(!empty($r['transferDate']) ? date('d-m-Y', strtotime($r['transferDate'])) : '') ?></td>
                        <td><?= number_format((float)($r['outstanding'] ?? 0), 2) ?></td>
                        <td>
                            <form method="POST" style="display:inline">
                                <?= CSRF::field() ?>
                                <input type="hidden" name="transferID" value="<?= (int)$r['transferID'] ?>">
                                <button name="revert" value="1" class="btn btn-sm btn-warning" onclick="return confirm('Revert this transfer and unarchive the pupil?');">Revert</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
