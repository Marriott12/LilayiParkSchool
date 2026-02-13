<?php
require_once 'includes/bootstrap.php';
require_once 'includes/Auth.php';

Auth::requireLogin();

// Check if editing or creating
$pupilID = $_GET['id'] ?? null;
$isEdit = !empty($pupilID);

// Require appropriate permissions
require_once 'modules/roles/RolesModel.php';
$rolesModel = new RolesModel();
if (!$rolesModel->userHasPermission(Auth::id(), 'manage_pupils')) {
    Session::setFlash('error', 'You do not have permission to manage pupils.');
    header('Location: 403.php');
    exit;
}

require_once 'modules/pupils/PupilModel.php';
require_once 'modules/users/UsersModel.php';
require_once 'modules/classes/ClassModel.php';

$pupilModel = new PupilModel();
$usersModel = new UsersModel();
// Ensure class model is available for assignment within request
$classModel = new ClassModel();


// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        error_log('=== POST REQUEST RECEIVED ===');
        error_log('POST data keys: ' . implode(', ', array_keys($_POST)));
        // Validate CSRF token first
        if (!CSRF::requireToken()) {
            $error = $GLOBALS['csrf_error'] ?? 'Security validation failed. Please try again.';
            error_log('CSRF validation failed: ' . $error);
        } else {
            error_log('CSRF validation passed');
            // Pupil data
            $enroll_day = $_POST['enroll_day'] ?? '';
            $enroll_month = $_POST['enroll_month'] ?? '';
            $enroll_year = $_POST['enroll_year'] ?? '';
            error_log('Enrollment date parts: day=' . $enroll_day . ', month=' . $enroll_month . ', year=' . $enroll_year);
            $enrollDate = '';
            if ($enroll_year && $enroll_month && $enroll_day) {
                $enrollDate = sprintf('%04d-%02d-%02d', (int)$enroll_year, (int)$enroll_month, (int)$enroll_day);
            }
            error_log('Enrollment date formatted: ' . $enrollDate);

            $data = [
                'fName' => trim($_POST['fName'] ?? ''),
                'lName' => trim($_POST['lName'] ?? ''),
                'gender' => $_POST['gender'] ?? '',
                'dob_day' => $_POST['dob_day'] ?? '',
                'dob_month' => $_POST['dob_month'] ?? '',
                'dob_year' => $_POST['dob_year'] ?? '',
                'DoB' => ($_POST['dob_year'] ?? '') . '-' . str_pad($_POST['dob_month'] ?? '', 2, '0', STR_PAD_LEFT) . '-' . str_pad($_POST['dob_day'] ?? '', 2, '0', STR_PAD_LEFT),
                'nationality' => trim($_POST['nationality'] ?? ''),
                'homeAddress' => trim($_POST['homeAddress'] ?? ''),
                'homeArea' => trim($_POST['homeArea'] ?? ''),
                // Parent details stored on the pupil record (consistent column names)
                'parent1' => trim($_POST['parent1'] ?? ''),
                'parent2' => trim($_POST['parent2'] ?? ''),
                'relationship' => trim($_POST['relationship'] ?? ''),
                'phone' => trim($_POST['phone'] ?? ''),
                'parentEmail' => trim($_POST['parentEmail'] ?? ''),
                'medCondition' => trim($_POST['medCondition'] ?? ''),
                'medAllergy' => trim($_POST['medAllergy'] ?? ''),
                'restrictions' => trim($_POST['restrictions'] ?? ''),
                'prevSch' => trim($_POST['prevSch'] ?? ''),
                'reason' => trim($_POST['reason'] ?? ''),
                'enrollDate' => $enrollDate,
                'transport' => $_POST['transport'] ?? 'N',
                'lunch' => $_POST['lunch'] ?? 'N',
                'photo' => $_POST['photo'] ?? 'N',
                'passPhoto' => '' // Will be handled by file upload
            ];
            // Keep a copy of raw form data so we can repopulate the form on error
            $formData = $data;
            // Validation
            if (!isset($error)) {
                error_log('Starting validation...');
                if (empty($data['fName'])) {
                    $error = 'Pupil first name is required';
                    error_log('Validation error: ' . $error);
                } elseif (empty($data['lName'])) {
                    $error = 'Pupil last name is required';
                    error_log('Validation error: ' . $error);
                } elseif (empty($data['gender'])) {
                    $error = 'Gender is required';
                    error_log('Validation error: ' . $error);
                } elseif (empty($data['dob_day']) || empty($data['dob_month']) || empty($data['dob_year'])) {
                    $error = 'Date of birth is required';
                    error_log('Validation error: ' . $error);
                } elseif (empty($data['homeArea'])) {
                    $error = 'Home area is required';
                    error_log('Validation error: ' . $error);
                } elseif (empty($data['parent1'])) {
                    $error = 'Parent/guardian name is required';
                    error_log('Validation error: ' . $error);
                } elseif (empty($data['phone'])) {
                    $error = 'Parent/guardian phone is required';
                    error_log('Validation error: ' . $error);
                } elseif (empty($enroll_day) || empty($enroll_month) || empty($enroll_year)) {
                    $error = 'Enrollment date is required';
                    error_log('Validation error: ' . $error . ' (day=' . var_export($enroll_day, true) . ', month=' . var_export($enroll_month, true) . ', year=' . var_export($enroll_year, true) . ')');
                } elseif (empty($_POST['classID'] ?? '')) {
                    $error = 'Assigning a class is required';
                    error_log('Validation error: ' . $error);
                } else {
                    error_log('All validations passed');
                }
            }
            if (!isset($error)) {
                error_log('Proceeding to duplicate check and DB operations...');
                try {
                    // Check for duplicate pupil (same parent identifier, first name, last name, and date of birth)
                    $parentIdentifier = $data['phone'] ?: $data['parent1'];
                    $existingPupil = $pupilModel->findByParentAndDetails($parentIdentifier, $data['fName'], $data['lName'], $data['DoB']);
                    if (!$isEdit && $existingPupil) {
                        $error = 'A pupil with the same parent, name, and date of birth already exists.';
                    } else {
                        // Handle file upload for passport photo
                        if (isset($_FILES['passPhotoFile']) && $_FILES['passPhotoFile']['error'] === UPLOAD_ERR_OK) {
                            $uploadDir = 'uploads/pupils/';
                            if (!is_dir($uploadDir)) {
                                mkdir($uploadDir, 0777, true);
                            }
                            $fileExtension = pathinfo($_FILES['passPhotoFile']['name'], PATHINFO_EXTENSION);
                            $fileName = uniqid('pupil_') . '.' . $fileExtension;
                            $uploadPath = $uploadDir . $fileName;
                            if (move_uploaded_file($_FILES['passPhotoFile']['tmp_name'], $uploadPath)) {
                                $data['passPhoto'] = $uploadPath;
                            }
                        }
                        if ($isEdit) {
                            // Use a filtered copy for DB update (remove form-only fields)
                            $updateData = $data;
                            $removeKeys = ['dob_day','dob_month','dob_year','enroll_day','enroll_month','enroll_year'];
                            foreach ($removeKeys as $rk) { if (isset($updateData[$rk])) { unset($updateData[$rk]); } }
                            $pupilModel->update($pupilID, $updateData);
                            // Assign to class if provided
                            $selectedClass = $_POST['classID'] ?? '';
                            if (!empty($selectedClass)) {
                                $classModel->assignPupil($selectedClass, $pupilID);
                            }
                            Session::setFlash('success', 'Pupil updated successfully');
                            header('Location: pupils_list.php');
                            exit;
                        } else {
                            // Create pupil and assign to class atomically using a transaction
                            $db = Database::getInstance()->getConnection();
                            $selectedClass = $_POST['classID'] ?? '';
                            try {
                                $db->beginTransaction();

                                // Prepare insert data (remove form-only fields before DB insert)
                                $insertData = $data;
                                $removeKeys = ['dob_day','dob_month','dob_year','enroll_day','enroll_month','enroll_year'];
                                foreach ($removeKeys as $rk) { if (isset($insertData[$rk])) { unset($insertData[$rk]); } }

                                // Create pupil - create() returns lastInsertId or the primary key
                                $newPupilID = $pupilModel->create($insertData);
                                error_log('Pupil created with ID: ' . ($newPupilID ?: 'NULL/0'));

                                if (!$newPupilID) {
                                    throw new Exception('Failed to create pupil record - no ID returned');
                                }

                                // If a class was selected, assign within the same transaction
                                if (!empty($selectedClass)) {
                                    error_log('Assigning pupil ' . $newPupilID . ' to class ' . $selectedClass);
                                    $ok = $classModel->assignPupil($selectedClass, $newPupilID);
                                    if (!$ok) {
                                        throw new Exception('Failed to assign pupil to class');
                                    }
                                }

                                $db->commit();

                                Session::setFlash('success', 'Pupil created successfully.');
                                header('Location: pupils_list.php');
                                exit;
                            } catch (Exception $ex) {
                                // Rollback and rethrow to outer catch for user display
                                if ($db && $db->inTransaction()) {
                                    $db->rollBack();
                                }
                                error_log('Pupil creation error: ' . $ex->getMessage() . ' | File: ' . $ex->getFile() . ' | Line: ' . $ex->getLine());
                                throw $ex;
                            }
                        }
                        CSRF::regenerateToken();
                    }
                } catch (Exception $e) {
                    error_log('Pupil form error: ' . $e->getMessage() . ' | File: ' . $e->getFile() . ' | Line: ' . $e->getLine());
                    $error = 'Database error: ' . $e->getMessage();
                }
            }
        }
}

// Get pupil data if editing
$pupil = $isEdit ? $pupilModel->getById($pupilID) : null;

// If there is an error from POST, repopulate $pupil with submitted data so the form is filled
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($error)) {
    // Restore submitted form values so the user doesn't lose input on error
    $pupil = isset($formData) ? $formData : (isset($data) ? $data : null);
}
$classModel = new ClassModel();
$allClasses = $classModel->getAllWithDetails();

$pageTitle = $isEdit ? 'Edit Pupil' : 'Add New Pupil';
$currentPage = 'pupils';
require_once 'includes/header.php';
?>

<div class="mb-4">
    <a href="pupils_list.php" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back to Pupils
    </a>
</div>

<div class="card">
    <div class="card-header" style="background: linear-gradient(135deg, #2d5016 0%, #5cb85c 100%); color: white;">
        <h5 class="mb-0">
            <i class="bi bi-person-plus-fill"></i> <?= $pageTitle ?>
        </h5>
    </div>
    <div class="card-body">

                <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="bi bi-exclamation-triangle-fill"></i> <?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

        <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle-fill"></i> Pupil created successfully.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <form id="pupilForm" method="POST" action="" enctype="multipart/form-data">
            <?= CSRF::field() ?>
            
            <!-- Pupil Information Section -->
            <div class="card mb-4" style="border-left: 4px solid #5cb85c;">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="bi bi-person-fill"></i> Pupil Information</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Forename <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="fName" 
                                   value="<?= htmlspecialchars($pupil['fName'] ?? '') ?>" required style="text-transform: capitalize;">
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Last Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="lName" 
                                value="<?= isset($pupil['lName']) ? htmlspecialchars($pupil['lName']) : '' ?>" required style="text-transform: capitalize;">
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Gender <span class="text-danger">*</span></label>
                            <div class="btn-group w-100" role="group">
                                <input type="radio" class="btn-check" name="gender" id="gender_m" value="M" 
                                       <?= ($pupil['gender'] ?? '') === 'M' ? 'checked' : '' ?> required>
                                <label class="btn btn-outline-primary" for="gender_m">
                                    <i class="bi bi-gender-male"></i> Male
                                </label>
                                
                                <input type="radio" class="btn-check" name="gender" id="gender_f" value="F" 
                                       <?= ($pupil['gender'] ?? '') === 'F' ? 'checked' : '' ?> required>
                                <label class="btn btn-outline-primary" for="gender_f">
                                    <i class="bi bi-gender-female"></i> Female
                                </label>
                            </div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Date of Birth <span class="text-danger">*</span></label>
                            <div class="row g-1">
                                <div class="col">
                                    <select class="form-select" name="dob_day" required>
                                        <option value="">Day</option>
                                        <?php for ($d = 1; $d <= 31; $d++): ?>
                                            <option value="<?= $d ?>" <?= (isset($pupil['dob_day']) && $pupil['dob_day'] == $d) ? 'selected' : ((isset($pupil['DoB']) && intval(date('d', strtotime($pupil['DoB']))) == $d) ? 'selected' : '') ?>><?= $d ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="col">
                                    <select class="form-select" name="dob_month" required>
                                        <option value="">Month</option>
                                                <?php for ($m = 1; $m <= 12; $m++):
                                                    $monthName = DateTime::createFromFormat('!m', $m)->format('F'); ?>
                                                    <option value="<?= $m ?>" <?= (isset($pupil['dob_month']) && $pupil['dob_month'] == $m) ? 'selected' : ((isset($pupil['DoB']) && intval(date('m', strtotime($pupil['DoB']))) == $m) ? 'selected' : '') ?>><?= $monthName ?></option>
                                                <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="col">
                                    <select class="form-select" name="dob_year" required>
                                        <option value="">Year</option>
                                        <?php $currentYear = date('Y');
                                            for ($y = $currentYear; $y >= $currentYear - 25; $y--): ?>
                                            <option value="<?= $y ?>" <?= (isset($pupil['dob_year']) && $pupil['dob_year'] == $y) ? 'selected' : ((isset($pupil['DoB']) && intval(date('Y', strtotime($pupil['DoB']))) == $y) ? 'selected' : '') ?>><?= $y ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Nationality</label>
                            <select class="form-select" name="nationality">
                                <option value="">-- Select Nationality --</option>
                                <?php
                                $nationalities = [
                                    'Zambia',
                                    'Angola',
                                    'Botswana',
                                    'Congo',
                                    'Kenya',
                                    'Malawi',
                                    'Mozambique',
                                    'Namibia',
                                    'South Africa',
                                    'Tanzania',
                                    'Uganda',
                                    'Zimbabwe',
                                    'Other'
                                ];
                                $selectedNationality = $pupil['nationality'] ?? '';
                                foreach ($nationalities as $nationality) {
                                    $selected = ($selectedNationality === $nationality) ? 'selected' : '';
                                    echo "<option value=\"$nationality\" $selected>$nationality</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Home Address</label>
                            <input type="text" class="form-control" name="homeAddress" 
                                   value="<?= htmlspecialchars($pupil['homeAddress'] ?? '') ?>" 
                                   placeholder="Street address">
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Home Area <span class="text-danger">*</span></label>
                            <select class="form-select" name="homeArea" required>
                                <option value="">-- Select Home Area --</option>
                                <?php
                                $areas = [
                                    'Bonaventure',
                                    'Chalala',
                                    'Chawama',
                                    'Chilanga',
                                    'Jack',
                                    'Kamwala South',
                                    'Libala South',
                                    'Lilayi',
                                    'Lilayi Estates',
                                    'Lilayi Police',
                                    'Mahopo',
                                    'Mimosa',
                                    'Shantumbu'
                                ];
                                sort($areas);
                                $selectedArea = $pupil['homeArea'] ?? '';
                                foreach ($areas as $area) {
                                    $selected = ($selectedArea === $area) ? 'selected' : '';
                                    echo "<option value=\"$area\" $selected>$area</option>";
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Assign Class <span class="text-danger">*</span></label>
                            <select name="classID" class="form-select" required>
                                <option value="">-- Select Class --</option>
                                <?php
                                $currentClass = null;
                                if ($isEdit && !empty($pupil['pupilID'])) {
                                    $db = Database::getInstance()->getConnection();
                                    $stmt = $db->prepare('SELECT classID FROM Pupil_Class WHERE pupilID = ? LIMIT 1');
                                    $stmt->execute([$pupil['pupilID']]);
                                    $rowC = $stmt->fetch();
                                    $currentClass = $rowC['classID'] ?? null;
                                }
                                foreach ($allClasses as $c) {
                                    $sel = ($currentClass && $currentClass === ($c['classID'] ?? '')) || (isset($_POST['classID']) && $_POST['classID'] === ($c['classID'] ?? '')) ? 'selected' : '';
                                    echo '<option value="' . htmlspecialchars($c['classID']) . '" ' . $sel . '>' . htmlspecialchars(($c['className'] ?? '') . ' - ' . trim(($c['teacherFirstName'] ?? '') . ' ' . ($c['teacherLastName'] ?? ''))) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Parent/Guardian Information Section (now stored on pupil record) -->
            <div class="card mb-4" style="border-left: 4px solid #2d5016;">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="bi bi-person-hearts"></i> Parent/Guardian Information</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Parent 1 (Primary) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="parent1" placeholder="e.g., John Banda" value="<?= htmlspecialchars($pupil['parent1'] ?? '') ?>" required style="text-transform: capitalize;">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Parent 2 (Optional)</label>
                            <input type="text" class="form-control" name="parent2" placeholder="Optional" value="<?= htmlspecialchars($pupil['parent2'] ?? '') ?>" style="text-transform: capitalize;">
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Relationship</label>
                            <select class="form-select" name="relationship">
                                <?php
                                $relOptions = ['Mother', 'Father', 'Guardian', 'Aunt/Uncle', 'Sibling', 'Grandparent'];
                                $selRel = $pupil['relationship'] ?? '';
                                echo '<option value="">-- Select Relationship --</option>';
                                foreach ($relOptions as $opt) {
                                    $sel = ($selRel === $opt) ? 'selected' : '';
                                    echo "<option value=\"$opt\" $sel>$opt</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Phone <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="phone" placeholder="e.g., +260971234567" value="<?= htmlspecialchars($pupil['phone'] ?? '') ?>" required>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Parent Email</label>
                            <input type="email" class="form-control" name="parentEmail" placeholder="parent@example.com" value="<?= htmlspecialchars($pupil['parentEmail'] ?? '') ?>">
                        </div>

                        
                    </div>
                </div>
            </div>

            
            <!-- Medical Information Section -->
            <div class="card mb-4" style="border-left: 4px solid #d9534f;">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="bi bi-heart-pulse-fill"></i> Medical Information</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Medical Conditions</label>
                        <textarea class="form-control" name="medCondition" rows="2" 
                                  placeholder="Any existing medical conditions (e.g., asthma, diabetes)"><?= htmlspecialchars($pupil['medCondition'] ?? '') ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Medical Allergies</label>
                        <textarea class="form-control" name="medAllergy" rows="2" 
                                  placeholder="Any known allergies (e.g., peanuts, penicillin)"><?= htmlspecialchars($pupil['medAllergy'] ?? '') ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Restrictions</label>
                        <textarea class="form-control" name="restrictions" rows="2" 
                                  placeholder="Any dietary or activity restrictions"><?= htmlspecialchars($pupil['restrictions'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>
            
            <!-- Previous School Information -->
            <div class="card mb-4" style="border-left: 4px solid #f0ad4e;">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="bi bi-building"></i> Previous School Information</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Previous School</label>
                        <input type="text" class="form-control" name="prevSch" 
                               value="<?= htmlspecialchars($pupil['prevSch'] ?? '') ?>" 
                               placeholder="Name of previous school">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Reason for Leaving</label>
                        <textarea class="form-control" name="reason" rows="2" 
                                  placeholder="Reason for leaving previous school"><?= htmlspecialchars($pupil['reason'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>
            
            <!-- Enrollment & Services -->
            <div class="card mb-4" style="border-left: 4px solid #5bc0de;">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="bi bi-calendar-check"></i> Enrollment & Services</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Enrollment Date</label>
                            <div class="row g-1">
                                <div class="col">
                                    <select class="form-select" name="enroll_day" required>
                                        <option value="">Day</option>
                                        <?php for ($d = 1; $d <= 31; $d++): ?>
                                            <option value="<?= $d ?>" <?= (isset($pupil['enroll_day']) && $pupil['enroll_day'] == $d) ? 'selected' : ((isset($pupil['enrollDate']) && intval(date('d', strtotime($pupil['enrollDate']))) == $d) ? 'selected' : '') ?>><?= $d ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="col">
                                    <select class="form-select" name="enroll_month" required>
                                        <option value="">Month</option>
                                        <?php for ($m = 1; $m <= 12; $m++):
                                            $monthName = DateTime::createFromFormat('!m', $m)->format('F'); ?>
                                            <option value="<?= $m ?>" <?= (isset($pupil['enroll_month']) && $pupil['enroll_month'] == $m) ? 'selected' : ((isset($pupil['enrollDate']) && intval(date('m', strtotime($pupil['enrollDate']))) == $m) ? 'selected' : '') ?>><?= $monthName ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="col">
                                    <select class="form-select" name="enroll_year" required>
                                        <option value="">Year</option>
                                        <?php $currentYear = date('Y');
                                            for ($y = $currentYear; $y >= 2024; $y--): ?>
                                            <option value="<?= $y ?>" <?= (isset($pupil['enroll_year']) && $pupil['enroll_year'] == $y) ? 'selected' : ((isset($pupil['enrollDate']) && intval(date('Y', strtotime($pupil['enrollDate']))) == $y) ? 'selected' : '') ?>><?= $y ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Transport <span class="text-danger">*</span></label>
                            <div class="btn-group w-100" role="group">
                                <input type="radio" class="btn-check" name="transport" id="transportYes" value="Y" 
                                       <?= ($pupil['transport'] ?? 'N') === 'Y' ? 'checked' : '' ?>>
                                <label class="btn btn-outline-success" for="transportYes">
                                    <i class="bi bi-bus-front"></i> Yes
                                </label>
                                
                                <input type="radio" class="btn-check" name="transport" id="transportNo" value="N" 
                                       <?= ($pupil['transport'] ?? 'N') === 'N' ? 'checked' : '' ?>>
                                <label class="btn btn-outline-secondary" for="transportNo">
                                    <i class="bi bi-x-circle"></i> No
                                </label>
                            </div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Lunch <span class="text-danger">*</span></label>
                            <div class="btn-group w-100" role="group">
                                <input type="radio" class="btn-check" name="lunch" id="lunchYes" value="Y" 
                                       <?= ($pupil['lunch'] ?? 'N') === 'Y' ? 'checked' : '' ?>>
                                <label class="btn btn-outline-success" for="lunchYes">
                                    <i class="bi bi-egg-fried"></i> Yes
                                </label>
                                
                                <input type="radio" class="btn-check" name="lunch" id="lunchNo" value="N" 
                                       <?= ($pupil['lunch'] ?? 'N') === 'N' ? 'checked' : '' ?>>
                                <label class="btn btn-outline-secondary" for="lunchNo">
                                    <i class="bi bi-x-circle"></i> No
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Photo Consent <span class="text-danger">*</span></label>
                            <div class="btn-group w-100" role="group">
                                <input type="radio" class="btn-check" name="photo" id="photoYes" value="Y" 
                                       <?= ($pupil['photo'] ?? 'N') === 'Y' ? 'checked' : '' ?>>
                                <label class="btn btn-outline-success" for="photoYes">
                                    <i class="bi bi-camera"></i> Yes
                                </label>
                                
                                <input type="radio" class="btn-check" name="photo" id="photoNo" value="N" 
                                       <?= ($pupil['photo'] ?? 'N') === 'N' ? 'checked' : '' ?>>
                                <label class="btn btn-outline-secondary" for="photoNo">
                                    <i class="bi bi-x-circle"></i> No
                                </label>
                            </div>
                            <div class="form-text">Can use photo on school platforms/adverts</div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Passport Photo</label>
                            <input type="file" class="form-control" name="passPhotoFile" accept="image/*">
                            <div class="form-text">JPG/PNG - max 2MB</div>
                            <?php if (!empty($pupil['passPhoto'])): ?>
                            <div class="mt-2">
                                <img src="<?= htmlspecialchars($pupil['passPhoto']) ?>" alt="Current Photo" 
                                     class="img-thumbnail" style="max-width: 100px;">
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn" style="background-color: #2d5016; color: white;">
                    <i class="bi bi-save"></i> <?= $isEdit ? 'Update' : 'Create' ?> Pupil
                </button>
                <a href="pupils_list.php" class="btn btn-secondary">
                    <i class="bi bi-x-circle"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Removed parent selection JS -->

<!-- Confirm Add Modal + handler -->
<div class="modal fade" id="confirmAddModal" tabindex="-1" aria-labelledby="confirmAddModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmAddModalLabel">Confirm Add</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="confirmAddMessage"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmAddYes">Yes, Add</button>
            </div>
        </div>
    </div>
</div>

<script>
// Bootstrap modal confirmation for adding pupil to class
(function(){
        var isEdit = <?= $isEdit ? 'true' : 'false' ?>;
        var form = document.getElementById('pupilForm');
        var modalEl = document.getElementById('confirmAddModal');
        var msgEl = document.getElementById('confirmAddMessage');
        var yesBtn = document.getElementById('confirmAddYes');
        if (!form || !modalEl || !msgEl || !yesBtn) return;
        var bsModal = new bootstrap.Modal(modalEl);

        form.addEventListener('submit', function(e){
                if (isEdit) return; // no confirmation on edit
                var sel = document.querySelector('select[name="classID"]');
                if (!sel || !sel.value) return; // let validation handle missing class

                e.preventDefault();
                var f = (document.querySelector('input[name="fName"]') || {}).value || '';
                var l = (document.querySelector('input[name="lName"]') || {}).value || '';
                var full = (f + ' ' + l).trim() || 'this pupil';
                var className = sel.options[sel.selectedIndex] ? sel.options[sel.selectedIndex].text : 'the selected class';
                msgEl.textContent = 'Are you sure you want to add ' + full + ' to ' + className + '?';
                bsModal.show();
        });

        yesBtn.addEventListener('click', function(){
                bsModal.hide();
                // submit without triggering submit handlers
                form.submit();
        });
})();
</script>

<?php require_once 'includes/footer.php'; ?>

<?php if ($isEdit && !empty($pupil['pupilID']) && !empty($allClasses)): ?>
<!-- Assign Class Modal -->
<div class="modal fade" id="assignClassModal" tabindex="-1" aria-labelledby="assignClassModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #2d5016; color: white;">
                <h5 class="modal-title" id="assignClassModalLabel"><i class="bi bi-arrow-right-square me-2"></i>Assign to Class</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Select Class</label>
                    <select id="assignClassSelect" class="form-select">
                        <option value="">-- Select a class --</option>
                        <?php foreach ($allClasses as $c): ?>
                            <option value="<?= htmlspecialchars($c['classID']) ?>"><?= htmlspecialchars($c['className'] . ' - ' . trim(($c['teacherFirstName'] ?? '') . ' ' . ($c['teacherLastName'] ?? ''))) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div id="assignClassAlert"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="assignClassBtn" onclick="assignClassToPupil()">Assign</button>
            </div>
        </div>
    </div>
</div>

<script>
async function assignClassToPupil() {
    const select = document.getElementById('assignClassSelect');
    const classID = select.value;
    const alertBox = document.getElementById('assignClassAlert');
    const btn = document.getElementById('assignClassBtn');
    const pupilID = '<?= htmlspecialchars($pupil['pupilID']) ?>';

    alertBox.innerHTML = '';
    if (!classID) {
        alertBox.innerHTML = '<div class="alert alert-warning">Please select a class.</div>';
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Assigning...';

    try {
        const formData = new FormData();
        formData.append('action', 'add');
        formData.append('classID', classID);
        formData.append('pupilID', pupilID);

        const resp = await fetch('classes_manage_pupils.php', { method: 'POST', body: formData });
        const data = await resp.json();
        if (data.success) {
            location.reload();
        } else {
            alertBox.innerHTML = '<div class="alert alert-danger">Error: ' + (data.error || 'Failed to assign') + '</div>';
            btn.disabled = false;
            btn.innerHTML = 'Assign';
        }
    } catch (err) {
        console.error(err);
        alertBox.innerHTML = '<div class="alert alert-danger">An unexpected error occurred.</div>';
        btn.disabled = false;
        btn.innerHTML = 'Assign';
    }
}
</script>
<?php endif; ?>
