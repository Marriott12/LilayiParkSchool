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
    
    // The pupil rows already contain the actual DB fields (pupilID, fName, lName, gender, DoB,
    // homeAddress, homeArea, medCondition, medAllergy, restrictions, prevSch, reason, parentID,
    // enrollDate, transport, lunch, photo, passPhoto, parent1, parent2, relationship, phone,
    // parentEmail, createdAt, updatedAt). Add backward-compatible fields so older front-end
    // code continues to work (parentName, parentPhone, parentEmail, parent object).
    foreach ($pupils as &$p) {
        // parentName: combine parent1 & parent2 if present
        $p['parentName'] = trim((string)($p['parent1'] ?? '') . (isset($p['parent2']) && $p['parent2'] !== '' ? ' & ' . $p['parent2'] : ''));
        if ($p['parentName'] === '') $p['parentName'] = null;
        $p['parentPhone'] = $p['phone'] ?? null;
        $p['parentEmail'] = $p['parentEmail'] ?? null;
        // Minimal parent object to satisfy legacy consumers expecting pupil.parent.fName etc.
        $p['parent'] = [
            'fName' => $p['parent1'] ?? '',
            'lName' => $p['parent2'] ?? '',
            'phone' => $p['phone'] ?? '',
            'email' => $p['parentEmail'] ?? ''
        ];
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
    // Return JSON with success=false to allow client to show friendly error
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
