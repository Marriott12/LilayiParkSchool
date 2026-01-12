<?php
require_once 'includes/bootstrap.php';
require_once 'includes/Auth.php';

Auth::requireLogin();
Auth::requireAnyRole(['admin']);

require_once 'modules/teachers/TeacherModel.php';
require_once 'modules/users/UsersModel.php';
require_once 'modules/roles/RolesModel.php';
require_once 'includes/EmailService.php';

$teacherModel = new TeacherModel();
$usersModel = new UsersModel();
$rolesModel = new RolesModel();
$emailService = new EmailService();

// Get teacher ID
$teacherID = $_GET['id'] ?? null;

if (!$teacherID) {
    Session::setFlash('error', 'Teacher ID is required.');
    header('Location: teachers_list.php');
    exit;
}

$teacher = $teacherModel->find($teacherID);

if (!$teacher) {
    Session::setFlash('error', 'Teacher not found.');
    header('Location: teachers_list.php');
    exit;
}

if (!empty($teacher['userID'])) {
    Session::setFlash('error', 'This teacher already has a user account.');
    header('Location: teachers_list.php');
    exit;
}

try {
    // Generate username
    $username = strtolower($teacher['fName'] . '.' . $teacher['lName']);
    $baseUsername = $username;
    $counter = 1;
    while ($usersModel->usernameExists($username)) {
        $username = $baseUsername . $counter;
        $counter++;
    }
    
    // Generate password
    $generatedPassword = Auth::generatePassword(12);
    
    // Generate email and ensure uniqueness
    $email = $teacher['email'] ?? $username . '@lilayipark.edu.zm';
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
        'firstName' => $teacher['fName'],
        'lastName' => $teacher['lName'],
        'isActive' => 'Y',
        'mustChangePassword' => 'Y',
        'createdAt' => date('Y-m-d H:i:s')
    ];
    
    $newUserID = $usersModel->create($userData);
    
    if ($newUserID) {
        // Assign teacher role
        $teacherRole = $rolesModel->getRoleByName('teacher');
        if ($teacherRole) {
            $rolesModel->assignRole($newUserID, $teacherRole['roleID'], Auth::id());
        }
        
        // Link user to teacher
        $teacherModel->update($teacherID, ['userID' => $newUserID]);
        
        // Send email with credentials if enabled
        $emailSent = false;
        $emailMessage = '';
        if ($emailService->isAccountEmailsEnabled() && !empty($email)) {
            $emailResult = $emailService->sendAccountCredentials($email, $username, $generatedPassword, 'Teacher');
            $emailSent = $emailResult['success'];
            $emailMessage = $emailResult['message'];
        }
        
        // Store credentials for modal display
        echo json_encode([
            'success' => true,
            'name' => $teacher['fName'] . ' ' . $teacher['lName'],
            'username' => $username,
            'password' => $generatedPassword,
            'email' => $email,
            'emailSent' => $emailSent,
            'emailMessage' => $emailMessage
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to create user account']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
