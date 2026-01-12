<?php
/**
 * Mobile API - Attendance
 * Get and mark attendance
 */

require_once '../../includes/bootstrap.php';
require_once '../../includes/MobileAPI.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Require authentication
$user = MobileAPI::requireAuth();

require_once '../../modules/attendance/AttendanceModel.php';
$attendanceModel = new AttendanceModel();

// GET - Fetch attendance
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    MobileAPI::requirePermission($user['userID'], 'view_attendance');
    
    $date = $_GET['date'] ?? date('Y-m-d');
    $classID = $_GET['class'] ?? null;
    
    if (!$classID) {
        MobileAPI::error('Class ID is required');
    }
    
    $attendance = $attendanceModel->getByClassAndDate($classID, $date);
    
    MobileAPI::success([
        'date' => $date,
        'classID' => $classID,
        'attendance' => $attendance
    ]);
}

// POST - Mark attendance
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    MobileAPI::requirePermission($user['userID'], 'mark_attendance');
    
    $data = MobileAPI::getRequestBody();
    
    // Validate
    $errors = MobileAPI::validateRequired($data, ['pupilID', 'status', 'date']);
    if (!empty($errors)) {
        MobileAPI::error('Validation failed', 400, $errors);
    }
    
    // Mark attendance
    $result = $attendanceModel->mark(
        $data['pupilID'],
        $data['date'],
        $data['status'],
        $data['remarks'] ?? null,
        $user['userID']
    );
    
    if ($result) {
        MobileAPI::success(['attendanceID' => $result], 'Attendance marked successfully');
    } else {
        MobileAPI::error('Failed to mark attendance', 500);
    }
}
