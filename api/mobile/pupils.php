<?php
/**
 * Mobile API - Pupils
 * Get pupils data
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

// Check permission
MobileAPI::requirePermission($user['userID'], 'view_pupils');

require_once '../../modules/pupils/PupilModel.php';
$pupilModel = new PupilModel();

// Get query parameters
$search = $_GET['search'] ?? '';
$classID = $_GET['class'] ?? null;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 20;

// Limit per_page
$perPage = min($perPage, 100);

// Get pupils
if ($search) {
    $pupils = $pupilModel->search($search);
} elseif ($classID) {
    $pupils = $pupilModel->getPupilsByClass($classID);
} else {
    $pupils = $pupilModel->getAllWithParents();
}

// Paginate
$result = MobileAPI::paginate($pupils, $page, $perPage);

MobileAPI::success($result);
