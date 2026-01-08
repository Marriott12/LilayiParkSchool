<?php
require_once 'includes/bootstrap.php';

RBAC::requireAuth();

// Check if editing or creating
$pupilID = $_GET['id'] ?? null;
$isEdit = !empty($pupilID);

// Require appropriate permissions
if ($isEdit) {
    RBAC::requirePermission('pupils', 'update');
} else {
    RBAC::requirePermission('pupils', 'create');
}

require_once 'modules/pupils/PupilModel.php';
require_once 'modules/parents/ParentModel.php';

$pupilModel = new PupilModel();
$parentModel = new ParentModel();

// Get all parents for dropdown
$parents = $parentModel->getAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token first
    if (!CSRF::requireToken()) {
        $error = $GLOBALS['csrf_error'] ?? 'Security validation failed. Please try again.';
    } else {
        $parentOption = $_POST['parentOption'] ?? 'existing';
        $parentID = null;
    
    // Handle parent creation/selection
    if ($parentOption === 'new' && !$isEdit) {
        // Validate and create new parent
        $parentData = [
            'fName' => trim($_POST['parent_fName'] ?? ''),
            'lName' => trim($_POST['parent_lName'] ?? ''),
            'relation' => trim($_POST['parent_relation'] ?? ''),
            'gender' => $_POST['parent_gender'] ?? '',
            'NRC' => trim($_POST['parent_NRC'] ?? ''),
            'phone' => trim($_POST['parent_phone'] ?? ''),
            'email1' => trim($_POST['parent_email1'] ?? ''),
            'email2' => trim($_POST['parent_email2'] ?? ''),
            'occupation' => trim($_POST['parent_occupation'] ?? ''),
            'workplace' => trim($_POST['parent_workplace'] ?? '')
        ];
        
        if (empty($parentData['fName'])) {
            $error = 'Parent first name is required';
        } elseif (empty($parentData['lName'])) {
            $error = 'Parent last name is required';
        } elseif (empty($parentData['relation'])) {
            $error = 'Parent relationship is required';
        } elseif (empty($parentData['gender'])) {
            $error = 'Parent gender is required';
        } elseif (empty($parentData['NRC'])) {
            $error = 'Parent NRC is required';
        } elseif (empty($parentData['phone'])) {
            $error = 'Parent phone is required';
        } elseif (empty($parentData['email1'])) {
            $error = 'Parent email is required';
        } elseif (!filter_var($parentData['email1'], FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid parent email format';
        }
    } else {
        $parentID = !empty($_POST['parentID']) ? $_POST['parentID'] : null;
        if (empty($parentID) && !$isEdit) {
            $error = 'Please select a parent or choose to add a new one';
        }
    }
    
    // Pupil data
    $data = [
        'fName' => trim($_POST['fName'] ?? ''),
        'sName' => trim($_POST['sName'] ?? ''),
        'gender' => $_POST['gender'] ?? '',
        'DoB' => $_POST['DoB'] ?? '',
        'homeAddress' => trim($_POST['homeAddress'] ?? ''),
        'homeArea' => trim($_POST['homeArea'] ?? ''),
        'medCondition' => trim($_POST['medCondition'] ?? ''),
        'medAllergy' => trim($_POST['medAllergy'] ?? ''),
        'restrictions' => trim($_POST['restrictions'] ?? ''),
        'prevSch' => trim($_POST['prevSch'] ?? ''),
        'reason' => trim($_POST['reason'] ?? ''),
        'enrollDate' => $_POST['enrollDate'] ?? date('Y-m-d'),
        'transport' => $_POST['transport'] ?? 'N',
        'lunch' => $_POST['lunch'] ?? 'N',
        'photo' => $_POST['photo'] ?? 'N',
        'passPhoto' => '' // Will be handled by file upload
    ];
    
    // Validation
    if (!isset($error)) {
        if (empty($data['fName'])) {
            $error = 'Pupil first name is required';
        } elseif (empty($data['sName'])) {
            $error = 'Pupil last name is required';
        } elseif (empty($data['gender'])) {
            $error = 'Gender is required';
        } elseif (empty($data['DoB'])) {
            $error = 'Date of birth is required';
        } elseif (empty($data['homeAddress'])) {
            $error = 'Home address is required';
        } elseif (empty($data['homeArea'])) {
            $error = 'Home area is required';
        }
    }
    
    if (!isset($error)) {
        try {
            // Create parent if new
            if ($parentOption === 'new' && !$isEdit) {
                $parentID = $parentModel->create($parentData);
            }
            
            $data['parentID'] = $parentID;
            
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
                $pupilModel->update($pupilID, $data);
                Session::setFlash('success', 'Pupil updated successfully');
            } else {
                $pupilModel->create($data);
                Session::setFlash('success', 'Pupil and ' . ($parentOption === 'new' ? 'parent' : 'parent link') . ' created successfully');
            }
            
            CSRF::regenerateToken();
            header('Location: pupils_list.php');
            exit;
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
        }
    }
}

// Get pupil data if editing
$pupil = $isEdit ? $pupilModel->getById($pupilID) : null;

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
        <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle-fill"></i> <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <form method="POST" action="" enctype="multipart/form-data">
            <?= CSRF::field() ?>
            
            <!-- Parent Selection Section -->
            <div class="card mb-4" style="border-left: 4px solid #2d5016;">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="bi bi-person-hearts"></i> Parent/Guardian Information</h6>
                </div>
                <div class="card-body">
                    <?php if (!$isEdit): ?>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Parent/Guardian Option <span class="text-danger">*</span></label>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="parentOption" id="existingParent" value="existing" checked>
                            <label class="btn btn-outline-primary" for="existingParent">
                                <i class="bi bi-search"></i> Select Existing Parent
                            </label>
                            
                            <input type="radio" class="btn-check" name="parentOption" id="newParent" value="new">
                            <label class="btn btn-outline-success" for="newParent">
                                <i class="bi bi-person-plus"></i> Add New Parent
                            </label>
                        </div>
                    </div>
                    
                    <!-- Existing Parent Selection -->
                    <div id="existingParentSection">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Select Parent <span class="text-danger">*</span></label>
                            <select class="form-select" name="parentID" id="parentDropdown">
                                <option value="">-- Select Parent/Guardian --</option>
                                <?php foreach ($parents as $parent): ?>
                                <option value="<?= $parent['parentID'] ?>">
                                    <?= htmlspecialchars($parent['fName'] . ' ' . $parent['lName']) ?> 
                                    (<?= htmlspecialchars($parent['relation'] ?? 'N/A') ?>) - <?= htmlspecialchars($parent['phone']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Choose an existing parent/guardian from the list</div>
                        </div>
                    </div>
                    
                    <!-- New Parent Form -->
                    <div id="newParentSection" style="display: none;">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> Fill in the parent/guardian details below. They will be created along with the pupil.
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Forename <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="parent_fName" id="parent_fName">
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Last Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="parent_lName" id="parent_lName">
                            </div>
                        
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Relationship <span class="text-danger">*</span></label>
                                <select class="form-select" name="parent_relation" id="parent_relation">
                                    <option value="">Select Relationship</option>
                                    <option value="Father">Father</option>
                                    <option value="Mother">Mother</option>
                                    <option value="Guardian">Guardian</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Gender <span class="text-danger">*</span></label>
                                <div class="btn-group w-100" role="group">
                                    <input type="radio" class="btn-check" name="parent_gender" id="parent_gender_m" value="M">
                                    <label class="btn btn-outline-primary" for="parent_gender_m">
                                        <i class="bi bi-gender-male"></i> Male
                                    </label>
                                    
                                    <input type="radio" class="btn-check" name="parent_gender" id="parent_gender_f" value="F">
                                    <label class="btn btn-outline-primary" for="parent_gender_f">
                                        <i class="bi bi-gender-female"></i> Female
                                    </label>
                                </div>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">NRC <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="parent_NRC" id="parent_NRC" 
                                       placeholder="e.g., 123456/78/9">
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Phone <span class="text-danger">*</span></label>
                                <input type="tel" class="form-control" name="parent_phone" id="parent_phone" 
                                       placeholder="e.g., +260 97 1234567">
                            </div>
                        
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Email 1 <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" name="parent_email1" id="parent_email1">
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Email 2</label>
                                <input type="email" class="form-control" name="parent_email2" id="parent_email2" 
                                       placeholder="Secondary email (optional)">
                            </div>
                        
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Occupation</label>
                                <input type="text" class="form-control" name="parent_occupation" id="parent_occupation">
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Workplace</label>
                                <input type="text" class="form-control" name="parent_workplace" id="parent_workplace" 
                                       placeholder="Employer/Company name">
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> Parent cannot be changed when editing. Current parent: 
                        <strong><?= htmlspecialchars(($pupil['parentFName'] ?? '') . ' ' . ($pupil['parentLName'] ?? '')) ?></strong>
                    </div>
                    <input type="hidden" name="parentID" value="<?= htmlspecialchars($pupil['parentID'] ?? '') ?>">
                    <input type="hidden" name="parentOption" value="existing">
                    <?php endif; ?>
                </div>
            </div>
            
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
                                   value="<?= htmlspecialchars($pupil['fName'] ?? '') ?>" required>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Last Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="sName" 
                                   value="<?= htmlspecialchars($pupil['sName'] ?? '') ?>" required>
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
                            <input type="date" class="form-control" name="DoB" 
                                   value="<?= htmlspecialchars($pupil['DoB'] ?? '') ?>" required>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Home Address <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="homeAddress" 
                                   value="<?= htmlspecialchars($pupil['homeAddress'] ?? '') ?>" 
                                   placeholder="Street address" required>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Home Area <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="homeArea" 
                                   value="<?= htmlspecialchars($pupil['homeArea'] ?? '') ?>" 
                                   placeholder="Town/District" required>
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
                            <input type="date" class="form-control" name="enrollDate" 
                                   value="<?= htmlspecialchars($pupil['enrollDate'] ?? date('Y-m-d')) ?>">
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

<script>
// Toggle between existing and new parent sections
document.addEventListener('DOMContentLoaded', function() {
    const existingRadio = document.getElementById('existingParent');
    const newRadio = document.getElementById('newParent');
    const existingSection = document.getElementById('existingParentSection');
    const newSection = document.getElementById('newParentSection');
    const parentDropdown = document.getElementById('parentDropdown');
    const newParentFields = ['parent_fName', 'parent_lName', 'parent_relation', 'parent_gender', 
                             'parent_NRC', 'parent_phone', 'parent_email1'];
    
    function toggleSections() {
        if (existingRadio && existingRadio.checked) {
            existingSection.style.display = 'block';
            newSection.style.display = 'none';
            parentDropdown.required = true;
            
            // Remove required from new parent fields
            newParentFields.forEach(id => {
                const field = document.getElementById(id);
                if (field) field.required = false;
            });
        } else if (newRadio && newRadio.checked) {
            existingSection.style.display = 'none';
            newSection.style.display = 'block';
            parentDropdown.required = false;
            
            // Add required to new parent fields
            newParentFields.forEach(id => {
                const field = document.getElementById(id);
                if (field) field.required = true;
            });
        }
    }
    
    if (existingRadio) existingRadio.addEventListener('change', toggleSections);
    if (newRadio) newRadio.addEventListener('change', toggleSections);
    
    // Initialize on page load
    toggleSections();
});
</script>

<?php require_once 'includes/footer.php'; ?>
