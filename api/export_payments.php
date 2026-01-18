<?php
/**
 * Export Payments Data
 * Supports CSV and Excel formats
 */

require_once '../includes/bootstrap.php';
require_once '../includes/Auth.php';
require_once '../includes/ExportHelper.php';

Auth::requireLogin();

require_once '../modules/roles/RolesModel.php';
$rolesModel = new RolesModel();
if (!$rolesModel->userHasPermission(Auth::id(), 'view_payments')) {
    http_response_code(403);
    die('Access denied');
}

require_once '../modules/payments/PaymentModel.php';

$paymentModel = new PaymentModel();
$format = $_GET['format'] ?? 'csv';

// Get filters
$classID = $_GET['class'] ?? null;
$term = $_GET['term'] ?? null;
$year = $_GET['year'] ?? null;

// Get all payments
$payments = $paymentModel->getAll($classID, $term, $year);

if (empty($payments)) {
    Session::setFlash('error', 'No payments data to export.');
    header('Location: ../payments_list.php');
    exit;
}

// Define columns for export
$columns = [
    'Payment ID' => 'payID',
    'Pupil ID' => 'pupilID',
    'Pupil Name' => 'pupilName',
    'Class' => 'className',
    'Amount' => 'amount',
    'Payment Date' => 'paymentDate',
    'Payment Method' => 'paymentMethod',
    'Receipt Number' => 'receiptNumber',
    'Term' => 'term',
    'Academic Year' => 'academicYear',
    'Recorded By' => 'recordedBy'
];

// Prepare data
$exportData = ExportHelper::prepareData($payments, $columns, function($row, $original) {
    // Format amount
    $row['Amount'] = number_format($row['Amount'], 2);
    // Format date
    if (!empty($row['Payment Date'])) {
        $row['Payment Date'] = date('Y-m-d', strtotime($row['Payment Date']));
    }
    return $row;
});

// Export based on format
if ($format === 'excel') {
    $filename = ExportHelper::generateFilename('payments_export', 'xls');
    ExportHelper::toExcel($exportData, $filename, 'Payments Report');
} else {
    $filename = ExportHelper::generateFilename('payments_export', 'csv');
    ExportHelper::toCSV($exportData, $filename);
}
