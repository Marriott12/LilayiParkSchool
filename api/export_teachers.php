<?php
/**
 * Export Teachers Data
 * Supports CSV and Excel formats
 */

require_once '../includes/bootstrap.php';
require_once '../includes/Auth.php';
require_once '../includes/ExportHelper.php';

Auth::requireLogin();

require_once '../modules/roles/RolesModel.php';
$rolesModel = new RolesModel();
if (!$rolesModel->userHasPermission(Auth::id(), 'view_teachers')) {
    http_response_code(403);
    die('Access denied');
}

require_once '../modules/teachers/TeacherModel.php';

$teacherModel = new TeacherModel();
$format = $_GET['format'] ?? 'csv';

// Get all teachers
$teachers = $teacherModel->all();

if (empty($teachers)) {
    Session::setFlash('error', 'No teachers data to export.');
    header('Location: ../teachers_list.php');
    exit;
}

// Define columns for export
$columns = [
    'Teacher ID' => 'teacherID',
    'First Name' => 'fName',
    'Last Name' => 'lName',
    'Gender' => 'gender',
    'NRC' => 'NRC',
    'Email' => 'email',
    'Phone' => 'phone',
    'SSN' => 'SSN',
    'TPIN' => 'Tpin',
    'TCZ Number' => 'tczNo',
    'Created Date' => 'createdAt'
];

// Prepare data
$exportData = ExportHelper::prepareData($teachers, $columns, function($row, $original) {
    // Format dates
    if (!empty($row['Created Date'])) {
        $row['Created Date'] = date('Y-m-d H:i:s', strtotime($row['Created Date']));
    }
    // Format gender
    $row['Gender'] = $row['Gender'] === 'M' ? 'Male' : 'Female';
    return $row;
});

// Export based on format
if ($format === 'excel') {
    $filename = ExportHelper::generateFilename('teachers_export', 'xls');
    ExportHelper::toExcel($exportData, $filename, 'Teachers List');
} else {
    $filename = ExportHelper::generateFilename('teachers_export', 'csv');
    ExportHelper::toCSV($exportData, $filename);
}
