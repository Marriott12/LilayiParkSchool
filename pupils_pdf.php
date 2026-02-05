<?php
require_once 'includes/bootstrap.php';
require_once 'includes/Auth.php';
Auth::requireLogin();

require_once 'modules/pupils/PupilModel.php';
require_once 'modules/payments/PaymentModel.php';
require_once 'modules/fees/FeesModel.php';
require_once 'modules/classes/ClassModel.php';

$pupilID = $_GET['id'] ?? null;
if (empty($pupilID)) {
    echo 'Pupil ID required';
    exit;
}

$pupilModel = new PupilModel();
$paymentModel = new PaymentModel();
$feesModel = new FeesModel();
$classModel = new ClassModel();

$pupil = $pupilModel->getPupilWithParent($pupilID) ?: $pupilModel->getById($pupilID);
if (!$pupil) {
    echo 'Pupil not found';
    exit;
}

// Get class and fee
// Safely attempt DB operations and show friendly message on failure
try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT pc.classID, c.className FROM Pupil_Class pc JOIN Class c ON pc.classID = c.classID WHERE pc.pupilID = ? LIMIT 1");
    $stmt->execute([$pupilID]);
    $pClass = $stmt->fetch();
    $classID = $pClass['classID'] ?? null;

    $totalFee = 0;
    $feeID = null;
    if ($classID) {
        $currentYear = date('Y');
        $stmt = $db->prepare("SELECT feeID, feeAmt, term FROM Fees WHERE classID = ? AND year = ? ORDER BY term DESC LIMIT 1");
        $stmt->execute([$classID, $currentYear]);
        $feeRow = $stmt->fetch();
        if ($feeRow) {
            $feeID = $feeRow['feeID'] ?? null;
            $totalFee = $feeRow['feeAmt'] ?? 0;
        }
    }
        // Prepare image URLs (use BASE_URL when available)
        $logoUrl = 'assets/images/logo.jpg';
        if (!defined('BASE_URL')) {
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            define('BASE_URL', $protocol . '://' . $host);
        }
        $logoUrl = rtrim(BASE_URL, '/') . '/assets/images/logo.jpg';
        $passPhotoUrl = '';
        $passPhotoUrl = '';
        if (!empty($pupil['passPhoto'])) {
            $pp = $pupil['passPhoto'];
            if (strpos($pp, 'http') === 0) {
                $passPhotoUrl = $pp;
            } elseif (defined('APP_ROOT') && strpos($pp, APP_ROOT) === 0) {
                // convert filesystem path to URL path
                $rel = str_replace('\\', '/', substr($pp, strlen(APP_ROOT)));
                $passPhotoUrl = rtrim(BASE_URL, '/') . $rel;
            } elseif (strpos($pp, '/') === 0) {
                $passPhotoUrl = rtrim(BASE_URL, '/') . $pp;
            } else {
                $passPhotoUrl = rtrim(BASE_URL, '/') . '/' . ltrim($pp, '/');
            }
        }


    // Fetch payments filtered by pupilID and feeID (when available)
    if (!empty($feeID)) {
        $stmt = $db->prepare("SELECT payID, pupilID, feeID, classID, pmtAmt, balance, paymentDate, paymentMode, remark, createdAt, updatedAt, term, academicYear FROM Payment WHERE pupilID = ? AND feeID = ? ORDER BY paymentDate DESC");
        $stmt->execute([$pupilID, $feeID]);
    } else {
        // Fallback: show payments for the pupil if fee not found
        $stmt = $db->prepare("SELECT payID, pupilID, feeID, classID, pmtAmt, balance, paymentDate, paymentMode, remark, createdAt, updatedAt, term, academicYear FROM Payment WHERE pupilID = ? ORDER BY paymentDate DESC");
        $stmt->execute([$pupilID]);
    }
    $payments = $stmt->fetchAll();

    $totalPaid = 0;
    foreach ($payments as $p) {
        // ensure payment belongs to this pupil
        if (($p['pupilID'] ?? null) !== $pupilID) continue;
        $totalPaid += floatval($p['pmtAmt'] ?? 0);
    }
    $outstanding = $totalFee - $totalPaid;

} catch (Exception $e) {
    // Log and show a friendly message instead of HTTP 500
    error_log('pupils_pdf.php error: ' . $e->getMessage());
    echo '<h3>Unable to load pupil payments</h3><p>Please check the database connection or contact the administrator.</p>';
    exit;
}

// Support server-side PDF generation when requested
$isDownload = !empty($_GET['download']);

ob_start();
?><!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Pupil Profile - <?= htmlspecialchars($pupil['fName'] . ' ' . $pupil['lName']) ?></title>
<style>
    @page { size: A4; margin: 20mm; }
    html, body { width: 210mm; margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial; color:#222; }
    .page { box-sizing: border-box; width: 170mm; margin: 0 auto; padding: 10mm 20mm; }
    .header { display:flex; align-items:center; gap:16px; margin-bottom:12px; }
    .logo { width:84px; height:auto; }
    .school-title { font-size:18px; font-weight:700; color:#2d5016; }
    h1 { font-size:16px; margin:8px 0 12px; }
    h2 { font-size:14px; margin:8px 0; }
    .row { display:flex; gap:16px; }
    .col { flex:1; }
    .field { margin-bottom:6px; }
    .label { font-size:11px; color:#666; }
    .value { font-size:12px; font-weight:600; }
    table { width:100%; border-collapse: collapse; margin-top:8px; }
    table th, table td { border:1px solid #ddd; padding:6px 8px; font-size:12px; }
    /* hide-only-when-printing: toolbar should be visible on-screen but hidden when printing */
    @media print { .no-print { display:none; } }
    .section { margin-bottom:14px; }
    /* Avoid breaking sections across pages */
    .section, .row { page-break-inside: avoid; }
    .page-break { page-break-after: always; }
    /* Print toolbar */
    .print-toolbar { text-align:right; margin-bottom:10px; }
    .btn-print { background:#2d5016; color:white; border:none; padding:8px 12px; border-radius:4px; cursor:pointer; }
    .btn-print:active { transform:translateY(1px); }
</style>
</head>
<body>
<div class="no-print print-toolbar">
    <button class="btn-print" onclick="window.print();">Print / Save as PDF</button>
</div>
<div class="page">
    <div class="header" style="justify-content:center; text-align:center;">
        <div>
            <img src="<?= htmlspecialchars($logoUrl) ?>" alt="School Logo" class="logo">
        </div>
    </div>

    <h1 style="display:flex; align-items:center; justify-content:space-between; gap:12px;">
        <span><?= htmlspecialchars($pupil['fName'] . ' ' . $pupil['lName']) ?></span>
        <?php if (!empty($passPhotoUrl)): ?>
            <img src="<?= htmlspecialchars($passPhotoUrl) ?>" alt="Passport Photo" style="width:100px;height:120px;object-fit:cover;border-radius:6px;border:1px solid #ccc;">
        <?php else: ?>
            <img src="<?= htmlspecialchars(rtrim(BASE_URL, '/') . '/assets/images/passport-placeholder.svg') ?>" alt="Passport Placeholder" style="width:100px;height:120px;object-fit:cover;border-radius:6px;border:1px solid #ccc;">
        <?php endif; ?>
    </h1>

    <div class="section">
        <div class="row">
            <div class="col">
                <div class="field"><div class="label">Pupil ID</div><div class="value"><?= htmlspecialchars($pupil['pupilID'] ?? '') ?></div></div>
                <div class="field"><div class="label">Date of Birth</div><div class="value"><?= htmlspecialchars($pupil['DoB'] ?? 'N/A') ?></div></div>
                <div class="field"><div class="label">Gender</div><div class="value"><?php $g = $pupil['gender'] ?? ''; echo $g === 'M' ? 'Male' : ($g === 'F' ? 'Female' : 'N/A'); ?></div></div>
            </div>
            <div class="col">
                <div class="field"><div class="label">Class</div><div class="value"><?= htmlspecialchars($pClass['className'] ?? 'Not Assigned') ?></div></div>
                <div class="field"><div class="label">Enrollment Date</div><div class="value"><?php if (!empty($pupil['enrollDate'])) { echo date('d-m-Y', strtotime($pupil['enrollDate'])); } else { echo 'N/A'; } ?></div></div>
            </div>
        </div>
    </div>

    <div class="section">
        <h2>Payments</h2>
        <div><strong>Total Fee:</strong> K <?= number_format($totalFee,2) ?> &nbsp; <strong>Total Paid:</strong> K <?= number_format($totalPaid,2) ?> &nbsp; <strong>Outstanding:</strong> K <?= number_format(max(0,$outstanding),2) ?></div>

        <table>
            <thead>
                <tr><th style="width:120px">Date</th><th>Mode</th><th style="width:110px">Amount (K)</th><th style="width:130px">Balance After (K)</th><th>Remark</th></tr>
            </thead>
            <tbody>
            <?php if (empty($payments)): ?>
                <tr><td colspan="5" style="text-align:center;padding:12px;color:#666">No payments recorded</td></tr>
            <?php else:
                foreach ($payments as $pay):
                    $amt = floatval($pay['pmtAmt'] ?? 0);
                    $balanceAfter = isset($pay['balance']) ? floatval($pay['balance']) : '';
            ?>
                <tr>
                    <td><?= htmlspecialchars(date('M d, Y', strtotime($pay['paymentDate'] ?? '')) ) ?></td>
                    <td><?= htmlspecialchars($pay['paymentMode'] ?? 'Cash') ?></td>
                    <td style="text-align:right"><?= number_format($amt,2) ?></td>
                    <td style="text-align:right"><?= $balanceAfter !== '' ? number_format($balanceAfter,2) : '-' ?></td>
                    <td><?= htmlspecialchars($pay['remark'] ?? '') ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <div class="section">
        <h2>Parent / Guardian</h2>
        <div class="row">
            <div class="col">
                <div class="field"><div class="label">Name</div><div class="value"><?= htmlspecialchars($pupil['parent1'] ?? 'N/A') ?><?php if (!empty($pupil['parent2'])): ?> &amp; <?= htmlspecialchars($pupil['parent2']) ?><?php endif; ?></div></div>
                <div class="field"><div class="label">Relationship</div><div class="value"><?= htmlspecialchars($pupil['relationship'] ?? 'N/A') ?></div></div>
            </div>
            <div class="col">
                <div class="field"><div class="label">Phone</div><div class="value"><?php if (!empty($pupil['phone'])): ?><a href="tel:<?= htmlspecialchars($pupil['phone']) ?>"><?= htmlspecialchars($pupil['phone']) ?></a><?php else: ?>N/A<?php endif; ?></div></div>
                <div class="field"><div class="label">Email</div><div class="value"><?php if (!empty($pupil['parentEmail'])): ?><a href="mailto:<?= htmlspecialchars($pupil['parentEmail']) ?>"><?= htmlspecialchars($pupil['parentEmail']) ?></a><?php else: ?>N/A<?php endif; ?></div></div>
            </div>
        </div>
    </div>

    <div class="section">
        <h2>Medical Information</h2>
        <div class="row">
            <div class="col"><div class="label">Medical Conditions</div><div class="value"><?= nl2br(htmlspecialchars($pupil['medCondition'] ?? 'None')) ?></div></div>
            <div class="col"><div class="label">Medical Allergies</div><div class="value"><?= nl2br(htmlspecialchars($pupil['medAllergy'] ?? 'None')) ?></div></div>
            <div class="col"><div class="label">Restrictions</div><div class="value"><?= nl2br(htmlspecialchars($pupil['restrictions'] ?? 'None')) ?></div></div>
        </div>
    </div>

    <div style="margin-top:20px;font-size:11px;color:#666">Generated: <?= date('d-m-Y H:i') ?></div>
</div>
</body>
</html>
<?php
$html = ob_get_clean();

if ($isDownload) {
    // Ensure Dompdf is available (try Composer autoload)
    if (!class_exists('Dompdf\\Dompdf')) {
        if (file_exists(__DIR__ . '/vendor/autoload.php')) {
            require_once __DIR__ . '/vendor/autoload.php';
        } elseif (file_exists(__DIR__ . '/../vendor/autoload.php')) {
            require_once __DIR__ . '/../vendor/autoload.php';
        }
    }

    if (!class_exists('Dompdf\\Dompdf')) {
        echo '<h3>PDF generator not installed</h3><p>Run <code>composer install</code> to install dependencies (dompdf).</p>';
        exit;
    }

    try {
        $dompdf = new \Dompdf\Dompdf();
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->loadHtml($html);
        $dompdf->render();
        $filename = 'pupil_' . preg_replace('/[^A-Za-z0-9_-]/', '_', ($pupil['pupilID'] ?? $pupilID)) . '.pdf';
        $dompdf->stream($filename, ['Attachment' => 1]);
        exit;
    } catch (Exception $e) {
        error_log('Dompdf error: ' . $e->getMessage());
        echo '<h3>Unable to generate PDF</h3><p>See server logs for details.</p>';
        exit;
    }

} else {
    // Normal HTML output; auto-print handled by ?print=1 param
    echo $html;
    if (!empty($_GET['print'])): ?>
    <script>
    // Auto-print after load to allow images to render
    window.addEventListener('load', function(){
        setTimeout(function(){ window.print(); }, 400);
    });
    </script>
    <?php
    endif;
}
