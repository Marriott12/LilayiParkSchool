<?php
/**
 * Mobile API - Timetable
 * Get timetable data
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
MobileAPI::requirePermission($user['userID'], 'view_classes');

require_once '../../modules/timetable/TimetableModel.php';
require_once '../../modules/settings/SettingsModel.php';

$timetableModel = new TimetableModel();
$settingsModel = new SettingsModel();

// Get query parameters
$classID = $_GET['class'] ?? null;
$teacherID = $_GET['teacher'] ?? null;
$term = $_GET['term'] ?? $settingsModel->getSetting('current_term');
$year = $_GET['year'] ?? $settingsModel->getSetting('current_academic_year');

if (!$classID && !$teacherID) {
    MobileAPI::error('Either class or teacher parameter is required');
}

// Get timetable
if ($classID) {
    $timetable = $timetableModel->getByClass($classID, $term, $year);
} else {
    $timetable = $timetableModel->getByTeacher($teacherID, $term, $year);
}

// Organize by day
$schedule = [
    'Monday' => [],
    'Tuesday' => [],
    'Wednesday' => [],
    'Thursday' => [],
    'Friday' => []
];

foreach ($timetable as $entry) {
    $schedule[$entry['dayOfWeek']][] = $entry;
}

// Sort each day by start time
foreach ($schedule as $day => $entries) {
    usort($schedule[$day], function($a, $b) {
        return strcmp($a['startTime'], $b['startTime']);
    });
}

MobileAPI::success([
    'term' => $term,
    'academicYear' => $year,
    'classID' => $classID,
    'teacherID' => $teacherID,
    'schedule' => $schedule
]);
