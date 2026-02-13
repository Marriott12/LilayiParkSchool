<?php
/**
 * API endpoint for live library books search
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
if (!$rolesModel->userHasPermission(Auth::id(), 'view_library')) {
    http_response_code(403);
    echo json_encode(['error' => 'Permission denied']);
    exit;
}

require_once '../modules/library/LibraryModel.php';

$searchTerm = $_GET['term'] ?? '';
$page = $_GET['page'] ?? 1;
$perPage = 15;

$libraryModel = new LibraryModel();

try {
    if ($searchTerm) {
        $allBooks = $libraryModel->search($searchTerm);
        $totalRecords = count($allBooks);
        $pagination = new Pagination($totalRecords, $perPage, $page);
        $books = array_slice($allBooks, $pagination->getOffset(), $pagination->getLimit());
    } else {
        $totalRecords = $libraryModel->count();
        $pagination = new Pagination($totalRecords, $perPage, $page);
        $books = $libraryModel->getAllWithAvailability($pagination->getLimit(), $pagination->getOffset());
    }
    
    echo json_encode([
        'success' => true,
        'data' => $books,
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
