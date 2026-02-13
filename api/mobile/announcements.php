<?php
/**
 * Mobile API - Announcements
 * Get announcements
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

require_once '../../modules/announcements/AnnouncementsModel.php';
$announcementsModel = new AnnouncementsModel();

// Get query parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
$perPage = min($perPage, 50);

// Get role
$stmt = Database::getInstance()->getConnection()->prepare("
    SELECT r.roleName
    FROM userroles ur
    JOIN roles r ON ur.roleID = r.roleID
    WHERE ur.userID = ?
");
$stmt->execute([$user['userID']]);
$role = $stmt->fetchColumn();

// Get announcements for user's role
if ($role) {
    $announcements = $announcementsModel->getByAudience($role);
} else {
    $announcements = $announcementsModel->getActiveAnnouncements();
}

// Paginate
$result = MobileAPI::paginate($announcements, $page, $perPage);

MobileAPI::success($result);
