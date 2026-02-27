<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../modules/classes/ClassModel.php';

$db = Database::getInstance()->getConnection();

// Find a pupil to test
$stmt = $db->query('SELECT pupilID, fName, lName FROM Pupil LIMIT 1');
$p = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$p) {
    echo "No pupils found in the database.\n";
    exit(1);
}
$pupilID = $p['pupilID'];
echo "Using pupil: {$pupilID} - {$p['fName']} {$p['lName']}\n";

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

showAssignments($db, $pupilID);

// If two args are provided, perform an assignment
if ($argc >= 3) {
    $newClass = $argv[1];
    echo "Assigning pupil {$pupilID} to class {$newClass}...\n";
    $cm = new ClassModel();
    $ok = $cm->assignPupil($newClass, $pupilID);
    echo $ok ? "assignPupil returned true\n" : "assignPupil failed\n";
    showAssignments($db, $pupilID);
} else {
    echo "To test assignment, run: php tools/test_assign_class.php <classID>\n";
}

return 0;
