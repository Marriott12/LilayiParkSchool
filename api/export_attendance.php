<?php
/**
 * Export Attendance Data
 * Supports CSV and Excel formats
 */

require_once '../includes/bootstrap.php';
require_once '../includes/Auth.php';
require_once '../includes/ExportHelper.php';

Auth::requireLogin();

require_once '../modules/roles/RolesModel.php';
$rolesModel = new RolesModel();
if (!$rolesModel->userHasPermission(Auth::id(), 'view_attendance')) {
    http_response_code(403);
    die('Access denied');
}

require_once '../modules/attendance/AttendanceModel.php';

$attendanceModel = new AttendanceModel();
$format = $_GET['format'] ?? 'csv';

// Get filters
$classID = $_GET['class'] ?? null;
$date = $_GET['date'] ?? null;
$startDate = $_GET['start_date'] ?? null;
$endDate = $_GET['end_date'] ?? null;

// Build query
$db = Database::getInstance()->getConnection();
$sql = "SELECT a.attendanceID, a.attendanceDate, a.status, a.remarks,
               p.pupilID, p.fName, p.lName, p.admissionNumber,
               c.className,
               u.username as markedBy
        FROM attendance a
        JOIN pupil p ON a.pupilID = p.pupilID
        JOIN pupil_class pc ON p.pupilID = pc.pupilID
        JOIN class c ON pc.classID = c.classID
        LEFT JOIN users u ON a.markedBy = u.userID
        WHERE 1=1";

$params = [];

if ($classID) {
    $sql .= " AND c.classID = ?";
    $params[] = $classID;
}

if ($date) {
    $sql .= " AND a.attendanceDate = ?";
    $params[] = $date;
} elseif ($startDate && $endDate) {
    $sql .= " AND a.attendanceDate BETWEEN ? AND ?";
    $params[] = $startDate;
    $params[] = $endDate;
}

$sql .= " ORDER BY a.attendanceDate DESC, c.className, p.lName";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$attendance = $stmt->fetchAll();

if (empty($attendance)) {
    Session::setFlash('error', 'No attendance data to export.');
    header('Location: ../attendance_list.php');
    exit;
}

// Define columns for export
$columns = [
    'Date' => 'attendanceDate',
    'Pupil ID' => 'pupilID',
    'Admission Number' => 'admissionNumber',
    'First Name' => 'fName',
    'Last Name' => 'lName',
    'Class' => 'className',
    'Status' => 'status',
    'Remarks' => 'remarks',
    'Marked By' => 'markedBy'
];

// Prepare data
$exportData = ExportHelper::prepareData($attendance, $columns, function($row, $original) {
    // Format date
    if (!empty($row['Date'])) {
        $row['Date'] = date('Y-m-d', strtotime($row['Date']));
    }
    // Capitalize status
    $row['Status'] = ucfirst($row['Status']);
    return $row;
});

// Export based on format
if ($format === 'excel') {
    $filename = ExportHelper::generateFilename('attendance_export', 'xls');
    ExportHelper::toExcel($exportData, $filename, 'Attendance Report');
} else {
    $filename = ExportHelper::generateFilename('attendance_export', 'csv');
    ExportHelper::toCSV($exportData, $filename);
}
