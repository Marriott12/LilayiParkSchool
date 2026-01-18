<?php
/**
 * Export Pupils Data
 * Supports CSV and Excel formats
 */

require_once '../includes/bootstrap.php';
require_once '../includes/Auth.php';
require_once '../includes/ExportHelper.php';

Auth::requireLogin();

require_once '../modules/roles/RolesModel.php';
$rolesModel = new RolesModel();
if (!$rolesModel->userHasPermission(Auth::id(), 'view_pupils')) {
    http_response_code(403);
    die('Access denied');
}

require_once '../modules/pupils/PupilModel.php';

$pupilModel = new PupilModel();
$format = $_GET['format'] ?? 'csv';

// Get all pupils with class information
$pupils = $pupilModel->getAllWithClass();

if (empty($pupils)) {
    Session::setFlash('error', 'No pupils data to export.');
    header('Location: ../pupils_list.php');
    exit;
}

// Define columns for export
$columns = [
    'Pupil ID' => 'pupilID',
    'First Name' => 'fName',
    'Last Name' => 'lName',
    'Gender' => 'gender',
    'Date of Birth' => 'dob',
    'Admission Number' => 'admissionNumber',
    'Class' => 'className',
    'Parent Name' => 'parentName',
    'Parent Phone' => 'parentPhone',
    'Parent Email' => 'parentEmail',
    'Status' => 'status',
    'Admission Date' => 'admissionDate'
];

// Prepare data
$exportData = ExportHelper::prepareData($pupils, $columns, function($row, $original) {
    // Format dates
    if (!empty($row['Date of Birth'])) {
        $row['Date of Birth'] = date('Y-m-d', strtotime($row['Date of Birth']));
    }
    if (!empty($row['Admission Date'])) {
        $row['Admission Date'] = date('Y-m-d', strtotime($row['Admission Date']));
    }
    // Format gender
    $row['Gender'] = $row['Gender'] === 'M' ? 'Male' : 'Female';
    // Format parent name
    $row['Parent Name'] = $original['parentFName'] . ' ' . $original['parentLName'];
    return $row;
});

// Export based on format
if ($format === 'excel') {
    $filename = ExportHelper::generateFilename('pupils_export', 'xls');
    ExportHelper::toExcel($exportData, $filename, 'Pupils List');
} else {
    $filename = ExportHelper::generateFilename('pupils_export', 'csv');
    ExportHelper::toCSV($exportData, $filename);
}
