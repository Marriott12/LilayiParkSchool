<?php
require_once 'includes/bootstrap.php';
require_once 'includes/Auth.php';

Auth::requireLogin();
Auth::requireAnyRole(['admin']);

require_once 'modules/parents/ParentModel.php';
require_once 'modules/users/UsersModel.php';
require_once 'modules/roles/RolesModel.php';

$parentModel = new ParentModel();
$usersModel = new UsersModel();
$rolesModel = new RolesModel();

// Get parent ID
$parentID = $_GET['id'] ?? null;

if (!$parentID) {
    Session::setFlash('error', 'Parent ID is required.');
    header('Location: parents_list.php');
    exit;
}

$parent = $parentModel->find($parentID);

if (!$parent) {
    Session::setFlash('error', 'Parent not found.');
    header('Location: parents_list.php');
    exit;
}

if (!empty($parent['userID'])) {
    Session::setFlash('error', 'This parent already has a user account.');
    header('Location: parents_list.php');
    exit;
}

try {
    // Generate username
    $username = strtolower($parent['fName'] . '.' . $parent['lName']);
    $baseUsername = $username;
    $counter = 1;
    while ($usersModel->usernameExists($username)) {
        $username = $baseUsername . $counter;
        $counter++;
    }
    
    // Generate password
    $generatedPassword = Auth::generatePassword(12);
    
    // Generate email and ensure uniqueness
    $email = $parent['email1'] ?? $username . '@lilayipark.edu.zm';
    $baseEmail = $email;
    $emailCounter = 1;
    while ($usersModel->emailExists($email)) {
        $emailParts = explode('@', $baseEmail);
        $email = $emailParts[0] . $emailCounter . '@' . $emailParts[1];
        $emailCounter++;
    }
    
    // Create user account
    $userData = [
        'username' => $username,
        'password' => password_hash($generatedPassword, PASSWORD_BCRYPT),
        'email' => $email,
        'firstName' => $parent['fName'],
        'lastName' => $parent['lName'],
        'isActive' => 'Y',
        'mustChangePassword' => 'Y',
        'createdAt' => date('Y-m-d H:i:s')
    ];
    
    $newUserID = $usersModel->create($userData);
    
    if ($newUserID) {
        // Assign parent role
        $parentRole = $rolesModel->getRoleByName('parent');
        if ($parentRole) {
            $rolesModel->assignRole($newUserID, $parentRole['roleID'], Auth::id());
        }
        
        // Link user to parent
        $parentModel->update($parentID, ['userID' => $newUserID]);
        
        // Store credentials for modal display
        echo json_encode([
            'success' => true,
            'name' => $parent['fName'] . ' ' . $parent['lName'],
            'username' => $username,
            'password' => $generatedPassword
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to create user account']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
