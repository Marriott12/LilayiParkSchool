<?php
/**
 * Create deployment package with all fixed files
 */

$rootDir = __DIR__;
$zipFile = $rootDir . '/production_deploy_' . date('Y-m-d_His') . '.zip';

// Files to include in deployment
$files = [
    'attendance_form.php',
    'attendance_list.php',
    'attendance_view.php',
    'classes_form.php',
    'classes_list.php',
    'classes_view.php',
    'delete.php',
    'examinations_delete.php',
    'examinations_form.php',
    'examinations_list.php',
    'examinations_schedule.php',
    'fees_form.php',
    'fees_list.php',
    'fees_view.php',
    'grades_bulk.php',
    'grades_form.php',
    'grades_list.php',
    'library_list.php',
    'library_view.php',
    'parents_form.php',
    'parents_list.php',
    'parents_view.php',
    'payments_form.php',
    'payments_list.php',
    'payments_view.php',
    'pupils_form.php',
    'pupils_view.php',
    'reports.php',
    'report_cards.php',
    'subjects_form.php',
    'subjects_list.php',
    'subjects_view.php',
    'teachers_form.php',
    'teachers_list.php',
    'teachers_view.php',
    'timetable_list.php',
    'timetable_view.php',
    'users_list.php',
    'users_password.php',
    'users_view.php',
    'includes/Auth.php',
    'includes/PermissionHelper.php',
    'modules/payments/PaymentModel.php',
];

if (!class_exists('ZipArchive')) {
    die("Error: ZipArchive extension not available. Please upload files manually.\n");
}

$zip = new ZipArchive();
if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    die("Error: Could not create zip file.\n");
}

$count = 0;
foreach ($files as $file) {
    $filePath = $rootDir . '/' . $file;
    if (file_exists($filePath)) {
        $zip->addFile($filePath, $file);
        $count++;
        echo "Added: $file\n";
    } else {
        echo "Warning: File not found: $file\n";
    }
}

$zip->close();

echo "\n=== Deployment Package Created ===\n";
echo "File: " . basename($zipFile) . "\n";
echo "Files included: $count\n";
echo "\nTo deploy:\n";
echo "1. Download this zip file\n";
echo "2. Extract on your production server at /home/envithcy/lps.envisagezm.com\n";
echo "3. Make sure to preserve directory structure\n";
echo "\nOr use FTP/SFTP to upload individual files listed in DEPLOYMENT_FILES.txt\n";
?>
