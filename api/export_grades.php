<?php
/**
 * Export Grades Data
 * Supports CSV and Excel formats
 */

require_once '../includes/bootstrap.php';
require_once '../includes/Auth.php';
require_once '../includes/ExportHelper.php';

Auth::requireLogin();

require_once '../modules/roles/RolesModel.php';
$rolesModel = new RolesModel();
if (!$rolesModel->userHasPermission(Auth::id(), 'view_grades')) {
    http_response_code(403);
    die('Access denied');
}

require_once '../modules/grades/GradesModel.php';

$gradesModel = new GradesModel();
$format = $_GET['format'] ?? 'csv';

// Get filters
$classID = $_GET['class'] ?? null;
$examID = $_GET['exam'] ?? null;
$subjectID = $_GET['subject'] ?? null;

// Get all grades
$db = Database::getInstance()->getConnection();
$sql = "SELECT g.gradeID, g.score, g.grade, g.remarks,
               p.pupilID, p.fName, p.lName, p.admissionNumber,
               c.className,
               s.subjectName,
               e.examName, e.term, e.academicYear,
               u.username as recordedBy
        FROM grades g
        JOIN pupil p ON g.pupilID = p.pupilID
        JOIN pupil_class pc ON p.pupilID = pc.pupilID
        JOIN class c ON pc.classID = c.classID
        JOIN subjects s ON g.subjectID = s.subjectID
        JOIN examinations e ON g.examID = e.examID
        LEFT JOIN users u ON g.recordedBy = u.userID
        WHERE 1=1";

$params = [];

if ($classID) {
    $sql .= " AND c.classID = ?";
    $params[] = $classID;
}

if ($examID) {
    $sql .= " AND g.examID = ?";
    $params[] = $examID;
}

if ($subjectID) {
    $sql .= " AND g.subjectID = ?";
    $params[] = $subjectID;
}

$sql .= " ORDER BY e.academicYear DESC, e.term DESC, c.className, p.lName, s.subjectName";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$grades = $stmt->fetchAll();

if (empty($grades)) {
    Session::setFlash('error', 'No grades data to export.');
    header('Location: ../grades_list.php');
    exit;
}

// Define columns for export
$columns = [
    'Pupil ID' => 'pupilID',
    'Admission Number' => 'admissionNumber',
    'First Name' => 'fName',
    'Last Name' => 'lName',
    'Class' => 'className',
    'Subject' => 'subjectName',
    'Examination' => 'examName',
    'Term' => 'term',
    'Academic Year' => 'academicYear',
    'Score' => 'score',
    'Grade' => 'grade',
    'Remarks' => 'remarks',
    'Recorded By' => 'recordedBy'
];

// Prepare data
$exportData = ExportHelper::prepareData($grades, $columns, function($row, $original) {
    // Format score
    $row['Score'] = number_format($row['Score'], 1);
    return $row;
});

// Export based on format
if ($format === 'excel') {
    $filename = ExportHelper::generateFilename('grades_export', 'xls');
    ExportHelper::toExcel($exportData, $filename, 'Grades Report');
} else {
    $filename = ExportHelper::generateFilename('grades_export', 'csv');
    ExportHelper::toCSV($exportData, $filename);
}
