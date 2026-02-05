<?php
/**
 * Mobile API - Grades
 * Get grades data
 */

require_once '../../includes/bootstrap.php';
require_once '../../includes/MobileAPI.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Require authentication
$user = MobileAPI::requireAuth();
MobileAPI::requirePermission($user['userID'], 'view_grades');

require_once '../../modules/grades/GradesModel.php';
$gradesModel = new GradesModel();

// Get query parameters
$pupilID = $_GET['pupil'] ?? null;
$classID = $_GET['class'] ?? null;
$examID = $_GET['exam'] ?? null;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 20;

// Get grades
if ($pupilID) {
    $grades = $gradesModel->getGradesByPupil($pupilID);
} elseif ($classID) {
    $grades = $gradesModel->getGradesByClass($classID);
} else {
    // Get all grades - use database query
    $db = Database::getInstance()->getConnection();
    $stmt = $db->query("SELECT g.*, p.fName, p.lName, s.subjectName, c.className, e.examName 
                        FROM grades g 
                        JOIN pupil p ON g.pupilID = p.pupilID 
                        JOIN subjects s ON g.subjectID = s.subjectID
                        JOIN pupil_class pc ON p.pupilID = pc.pupilID
                        JOIN class c ON pc.classID = c.classID
                        JOIN examinations e ON g.examID = e.examID
                        ORDER BY g.createdAt DESC");
    $grades = $stmt->fetchAll();
}

// Paginate
$result = MobileAPI::paginate($grades, $page, $perPage);

MobileAPI::success($result);
