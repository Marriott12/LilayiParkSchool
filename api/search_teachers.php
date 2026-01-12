<?php
/**
 * API endpoint for live teacher search
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

// Check permission
require_once '../modules/roles/RolesModel.php';
$rolesModel = new RolesModel();
if (!$rolesModel->userHasPermission(Auth::id(), 'view_teachers')) {
    http_response_code(403);
    echo json_encode(['error' => 'Permission denied']);
    exit;
}

require_once '../modules/teachers/TeacherModel.php';

$searchTerm = $_GET['term'] ?? '';
$page = $_GET['page'] ?? 1;
$perPage = 20;

$teacherModel = new TeacherModel();

try {
    if ($searchTerm) {
        $filters = [];
        if (isset($_GET['employmentStatus'])) $filters['employmentStatus'] = $_GET['employmentStatus'];
        if (isset($_GET['isActive'])) $filters['isActive'] = $_GET['isActive'];
        
        $allTeachers = $teacherModel->search($searchTerm, $filters);
        $totalRecords = count($allTeachers);
        $pagination = new Pagination($totalRecords, $perPage, $page);
        $teachers = array_slice($allTeachers, $pagination->getOffset(), $pagination->getLimit());
    } else {
        $totalRecords = $teacherModel->count();
        $pagination = new Pagination($totalRecords, $perPage, $page);
        $teachers = $teacherModel->all(null, $pagination->getLimit(), $pagination->getOffset());
    }
    
    echo json_encode([
        'success' => true,
        'data' => $teachers,
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
