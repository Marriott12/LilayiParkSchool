<?php
/**
 * API endpoint for live parent search
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
if (!$rolesModel->userHasPermission(Auth::id(), 'view_parents')) {
    http_response_code(403);
    echo json_encode(['error' => 'Permission denied']);
    exit;
}

require_once '../modules/parents/ParentModel.php';

$searchTerm = $_GET['term'] ?? '';
$page = $_GET['page'] ?? 1;
$perPage = 20;

$parentModel = new ParentModel();

try {
    if ($searchTerm) {
        $filters = [];
        if (isset($_GET['hasAccount'])) $filters['hasAccount'] = $_GET['hasAccount'];
        if (isset($_GET['isActive'])) $filters['isActive'] = $_GET['isActive'];
        
        $allParents = $parentModel->search($searchTerm, $filters);
        $totalRecords = count($allParents);
        $pagination = new Pagination($totalRecords, $perPage, $page);
        $parents = array_slice($allParents, $pagination->getOffset(), $pagination->getLimit());
    } else {
        $totalRecords = $parentModel->count();
        $pagination = new Pagination($totalRecords, $perPage, $page);
        $parents = $parentModel->getAllWithChildrenCount($pagination->getLimit(), $pagination->getOffset());
    }
    
    echo json_encode([
        'success' => true,
        'data' => $parents,
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
