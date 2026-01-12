<?php
/**
 * API endpoint for live pupil search
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
if (!$rolesModel->userHasPermission(Auth::id(), 'view_pupils')) {
    http_response_code(403);
    echo json_encode(['error' => 'Permission denied']);
    exit;
}

require_once '../modules/pupils/PupilModel.php';

$searchTerm = $_GET['term'] ?? '';
$page = $_GET['page'] ?? 1;
$perPage = 20;

$pupilModel = new PupilModel();

// Get accessible pupil IDs based on user role
$accessiblePupilIDs = Auth::getAccessiblePupilIDs();

try {
    if ($searchTerm) {
        $filters = [];
        if (isset($_GET['classID'])) $filters['classID'] = $_GET['classID'];
        if (isset($_GET['gender'])) $filters['gender'] = $_GET['gender'];
        
        $allPupils = $pupilModel->search($searchTerm, $filters);
        
        // Filter by accessible pupils for teachers/parents
        if ($accessiblePupilIDs !== null) {
            $allPupils = array_filter($allPupils, function($pupil) use ($accessiblePupilIDs) {
                return in_array($pupil['pupilID'], $accessiblePupilIDs);
            });
        }
        
        $totalRecords = count($allPupils);
        $pagination = new Pagination($totalRecords, $perPage, $page);
        $pupils = array_slice($allPupils, $pagination->getOffset(), $pagination->getLimit());
    } else {
        if ($accessiblePupilIDs === null) {
            $totalRecords = $pupilModel->count();
            $pagination = new Pagination($totalRecords, $perPage, $page);
            $pupils = $pupilModel->getAllWithParents($pagination->getLimit(), $pagination->getOffset());
        } else {
            $totalRecords = count($accessiblePupilIDs);
            $pagination = new Pagination($totalRecords, $perPage, $page);
            $pupils = $pupilModel->getByIDs($accessiblePupilIDs, $pagination->getLimit(), $pagination->getOffset());
        }
    }
    
    echo json_encode([
        'success' => true,
        'data' => $pupils,
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
