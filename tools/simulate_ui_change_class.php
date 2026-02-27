<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../modules/classes/ClassModel.php';
require_once __DIR__ . '/../modules/pupils/PupilModel.php';

if ($argc < 3) {
    echo "Usage: php tools/simulate_ui_change_class.php <pupilID> <newClassID>\n";
    exit(1);
}

$pupilID = $argv[1];
$newClassID = $argv[2];

$db = Database::getInstance()->getConnection();
$cm = new ClassModel();
$pm = new PupilModel();

$pupil = $pm->getById($pupilID);
if (!$pupil) {
    echo "Pupil {$pupilID} not found\n";
    exit(1);
}

echo "Simulating class change for pupil {$pupilID} - {$pupil['fName']} {$pupil['lName']}\n\n";

function showAssignments($db, $pupilID) {
    $stmt = $db->prepare('SELECT * FROM Pupil_Class WHERE pupilID = ?');
    $stmt->execute([$pupilID]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($rows)) {
        echo "No Pupil_Class rows for pupil {$pupilID}\n";
    } else {
        echo "Pupil_Class rows for pupil {$pupilID}:\n";
        foreach ($rows as $r) {
            echo " - classID={$r['classID']} enrollmentDate={$r['enrollmentDate']}\n";
        }
    }
}

echo "Before:\n";
showAssignments($db, $pupilID);

echo "\nAssigning to class {$newClassID}...\n";
$ok = $cm->assignPupil($newClassID, $pupilID);
if ($ok) {
    echo "assignPupil returned true\n";
} else {
    echo "assignPupil failed\n";
}

echo "\nAfter:\n";
showAssignments($db, $pupilID);

return 0;
