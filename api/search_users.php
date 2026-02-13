<?php
/**
 * API endpoint for live user search
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
Auth::requireAnyRole(['admin']);

require_once '../modules/users/UsersModel.php';

$searchTerm = $_GET['term'] ?? '';
$page = $_GET['page'] ?? 1;
$perPage = 20;

$usersModel = new UsersModel();

try {
    if ($searchTerm) {
        $filters = [];
        if (isset($_GET['roleID'])) $filters['roleID'] = $_GET['roleID'];
        if (isset($_GET['isActive'])) $filters['isActive'] = $_GET['isActive'];
        
        $allUsers = $usersModel->search($searchTerm, $filters);
        $totalRecords = count($allUsers);
        $pagination = new Pagination($totalRecords, $perPage, $page);
        $users = array_slice($allUsers, $pagination->getOffset(), $pagination->getLimit());
    } else {
        $totalRecords = $usersModel->count();
        $pagination = new Pagination($totalRecords, $perPage, $page);
        $users = $usersModel->getAllWithRBAC();
        $users = array_slice($users, $pagination->getOffset(), $pagination->getLimit());
    }
    
    echo json_encode([
        'success' => true,
        'data' => $users,
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
