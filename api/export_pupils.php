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

// Get all pupils (no limit)
$pupils = $pupilModel->getAllWithParents();

if (empty($pupils)) {
    Session::setFlash('error', 'No pupils data to export.');
    header('Location: ../pupils_list.php');
    exit;
}

// Map of display column => data key in pupil record
$columnMap = [
    'Pupil ID' => 'pupilID',
    'First Name' => 'fName',
    'Last Name' => 'lName',
    'Gender' => 'gender',
    'Date of Birth' => 'DoB',
    'Home Address' => 'homeAddress',
    'Home Area' => 'homeArea',
    'Medical Conditions' => 'medCondition',
    'Medical Allergies' => 'medAllergy',
    'Restrictions' => 'restrictions',
    'Previous School' => 'prevSch',
    'Reason' => 'reason',
    'Parent ID' => 'parentID',
    'Enrollment Date' => 'enrollDate',
    'Transport' => 'transport',
    'Lunch' => 'lunch',
    'Photo' => 'photo',
    'Passport Photo' => 'passPhoto',
    'Parent 1' => 'parent1',
    'Parent 2' => 'parent2',
    'Relationship' => 'relationship',
    'Phone' => 'phone',
    'Parent Email' => 'parentEmail'
];

// Optional formatter to normalize gender and date formats
$formatter = function($rowDisplay, $originalRow) {
    // Normalize gender to M/F or empty
    if (!empty($rowDisplay['Gender'])) {
        $g = strtoupper(substr($rowDisplay['Gender'], 0, 1));
        $rowDisplay['Gender'] = ($g === 'M' || $g === 'F') ? $g : $rowDisplay['Gender'];
    }
    // Format dates as YYYY-MM-DD for consistency
    if (!empty($rowDisplay['Date of Birth'])) {
        $d = date('Y-m-d', strtotime($rowDisplay['Date of Birth']));
        $rowDisplay['Date of Birth'] = $d;
    }
    if (!empty($rowDisplay['Enrollment Date'])) {
        $d = date('Y-m-d', strtotime($rowDisplay['Enrollment Date']));
        $rowDisplay['Enrollment Date'] = $d;
    }
    return $rowDisplay;
};

// Prepare export rows using ExportHelper to ensure header/value alignment
$exportRows = ExportHelper::prepareData($pupils, $columnMap, $formatter);

if (empty($exportRows)) {
    Session::setFlash('error', 'No pupils data to export.');
    header('Location: ../pupils_list.php');
    exit;
}

// Export based on format
if ($format === 'excel') {
    $filename = ExportHelper::generateFilename('pupils_export', 'xls');
    ExportHelper::toExcel($exportRows, $filename, 'Pupils List');
} else {
    $filename = ExportHelper::generateFilename('pupils_export', 'csv');
    ExportHelper::toCSV($exportRows, $filename);
}
