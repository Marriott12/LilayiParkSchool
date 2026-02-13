<?php
/**
 * API endpoint for live subjects search
 */
require_once '../includes/bootstrap.php';
require_once '../includes/Auth.php';

header('Content-Type: application/json');

// Require authentication
if (!Auth::check()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once '../modules/subjects/SubjectsModel.php';

$searchTerm = $_GET['term'] ?? '';
$page = $_GET['page'] ?? 1;
$perPage = 20;

$subjectsModel = new SubjectsModel();

try {
    if ($searchTerm) {
        $allSubjects = $subjectsModel->search($searchTerm);
        $totalRecords = count($allSubjects);
        $pagination = new Pagination($totalRecords, $perPage, $page);
        $subjects = array_slice($allSubjects, $pagination->getOffset(), $pagination->getLimit());
    } else {
        $totalRecords = $subjectsModel->count();
        $pagination = new Pagination($totalRecords, $perPage, $page);
        $subjects = $subjectsModel->getAllWithTeachers($pagination->getLimit(), $pagination->getOffset());
    }
    
    echo json_encode([
        'success' => true,
        'data' => $subjects,
        'pagination' => [
            'total' => $totalRecords,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => $pagination->getTotalPages()
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
