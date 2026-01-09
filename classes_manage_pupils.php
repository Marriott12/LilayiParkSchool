<?php
require_once 'includes/bootstrap.php';
require_once 'includes/Auth.php';

Auth::requireLogin();

require_once 'modules/roles/RolesModel.php';
$rolesModel = new RolesModel();
if (!$rolesModel->userHasPermission(Auth::id(), 'manage_classes')) {
    echo json_encode(['success' => false, 'error' => 'Permission denied']);
    exit;
}

require_once 'modules/classes/ClassModel.php';

$classModel = new ClassModel();
$action = $_POST['action'] ?? $_GET['action'] ?? null;
$classID = $_POST['classID'] ?? $_GET['classID'] ?? null;

if (!$classID) {
    echo json_encode(['success' => false, 'error' => 'Class ID is required']);
    exit;
}

// Verify class exists
$class = $classModel->find($classID);
if (!$class) {
    echo json_encode(['success' => false, 'error' => 'Class not found']);
    exit;
}

try {
    switch ($action) {
        case 'add':
            $pupilID = $_POST['pupilID'] ?? null;
            if (!$pupilID) {
                echo json_encode(['success' => false, 'error' => 'Pupil ID is required']);
                exit;
            }
            
            if ($classModel->assignPupil($classID, $pupilID)) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Pupil added to class successfully'
                ]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to add pupil to class']);
            }
            break;
            
        case 'remove':
            $pupilID = $_POST['pupilID'] ?? null;
            if (!$pupilID) {
                echo json_encode(['success' => false, 'error' => 'Pupil ID is required']);
                exit;
            }
            
            if ($classModel->removePupil($classID, $pupilID)) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Pupil removed from class successfully'
                ]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to remove pupil from class']);
            }
            break;
            
        case 'getAvailable':
            // Get pupils not in this class
            $sql = "SELECT p.pupilID, p.fName, p.lName, p.studentNumber
                    FROM Pupil p
                    WHERE p.pupilID NOT IN (
                        SELECT pupilID FROM Pupil_Class WHERE classID = ?
                    )
                    ORDER BY p.fName, p.lName";
            $stmt = $classModel->db->prepare($sql);
            $stmt->execute([$classID]);
            $availablePupils = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'pupils' => $availablePupils
            ]);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
