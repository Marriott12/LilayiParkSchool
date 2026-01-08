<?php
require_once 'includes/bootstrap.php';

RBAC::requireAuth();
RBAC::requirePermission('examinations', 'delete');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Session::setFlash('error', 'Invalid request method');
    header('Location: examinations_list.php');
    exit;
}

CSRF::requireToken();

require_once 'modules/examinations/ExaminationsModel.php';

$examinationsModel = new ExaminationsModel();
$examID = $_POST['examID'] ?? null;

if (!$examID) {
    Session::setFlash('error', 'Exam ID is required');
    header('Location: examinations_list.php');
    exit;
}

try {
    // Check if exam has schedules
    $statistics = $examinationsModel->getStatistics($examID);
    if ($statistics['totalSchedules'] > 0) {
        Session::setFlash('error', 'Cannot delete examination with existing schedules. Delete all schedules first.');
    } else {
        $examinationsModel->delete($examID);
        Session::setFlash('success', 'Examination deleted successfully');
    }
} catch (Exception $e) {
    Session::setFlash('error', $e->getMessage());
}

CSRF::regenerateToken();
header('Location: examinations_list.php');
exit;
