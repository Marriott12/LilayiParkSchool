<?php
require_once 'includes/bootstrap.php';
require_once 'includes/Auth.php';

Auth::requireLogin();

require_once 'modules/roles/RolesModel.php';
$rolesModel = new RolesModel();
if (!$rolesModel->userHasPermission(Auth::id(), 'view_classes')) {
    Session::setFlash('error', 'You do not have permission to view classes.');
    header('Location: /LilayiParkSchool/403.php');
    exit;
}

require_once 'modules/classes/ClassModel.php';

$classID = $_GET['id'] ?? null;
if (empty($classID)) {
    header('Location: classes_list.php');
    exit;
}

$classModel = new ClassModel();
$class = $classModel->getClassWithTeacher($classID) ?: $classModel->getById($classID);
$roster = method_exists($classModel, 'getClassRoster') ? $classModel->getClassRoster($classID) : [];

$pageTitle = 'Class Details';
$currentPage = 'classes';
require_once 'includes/header.php';
require_once 'includes/PermissionHelper.php';
?>

<!-- Page Header with Actions -->
<div class="row mb-4">
    <div class="col-md-8">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="classes_list.php" class="text-decoration-none">Classes</a></li>
                <li class="breadcrumb-item active" aria-current="page">View Details</li>
            </ol>
        </nav>
        <h2 class="mb-0">
            <i class="bi bi-building me-2" style="color: #2d5016;"></i>
            <span style="color: #2d5016; font-weight: 600;">Class Details</span>
        </h2>
    </div>
    <div class="col-md-4 text-end align-self-end">
        <div class="btn-group" role="group">
            <a href="classes_list.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
            <?php if (PermissionHelper::canManage('classes')): ?>
            <a href="classes_form.php?id=<?= $classID ?>" class="btn btn-warning">
                <i class="bi bi-pencil me-1"></i> Edit
            </a>
            <a href="delete.php?module=classes&id=<?= $classID ?>" 
               class="btn btn-danger"
               onclick="return confirm('Are you sure you want to delete this class? This action cannot be undone.');">
                <i class="bi bi-trash me-1"></i> Delete
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Class Profile -->
<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <div class="mb-3">
                    <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center" 
                         style="width: 120px; height: 120px;">
                        <i class="bi bi-building" style="font-size: 5rem; color: #2d5016;"></i>
                    </div>
                </div>
                <h4 class="mb-1"><?= htmlspecialchars($class['className'] ?? '') ?></h4>
                <p class="text-muted mb-3">Class</p>
                <div class="mb-3">
                    <span class="badge bg-success px-3 py-2">
                        <i class="bi bi-check-circle me-1"></i>Active
                    </span>
                </div>
                <hr>
                <div class="text-start">
                    <small class="text-muted d-block mb-2">Quick Info</small>
                    <div class="d-flex align-items-center mb-2">
                        <i class="bi bi-hash text-muted me-2"></i>
                        <span class="small">Class ID: <?= htmlspecialchars($class['classID'] ?? 'N/A') ?></span>
                    </div>
                    <div class="d-flex align-items-center mb-2">
                        <i class="bi bi-person-badge text-muted me-2"></i>
                        <span class="small"><?= htmlspecialchars(($class['teacherFirstName'] ?? $class['fName'] ?? '') . ' ' . ($class['teacherLastName'] ?? $class['lName'] ?? 'No Teacher')) ?></span>
                    </div>
                    <div class="d-flex align-items-center">
                        <i class="bi bi-people text-muted me-2"></i>
                        <span class="small"><?= count($roster) ?> Student<?= count($roster) !== 1 ? 's' : '' ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <!-- Class Information -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">
                    <i class="bi bi-info-circle-fill me-2" style="color: #2d5016;"></i>Class Information
                </h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="text-muted small mb-1">Class Name</label>
                        <p class="mb-0 fw-semibold"><?= htmlspecialchars($class['className'] ?? 'N/A') ?></p>
                    </div>
                    <div class="col-md-12">
                        <label class="text-muted small mb-1">Assigned Teacher</label>
                        <p class="mb-0 fw-semibold">
                            <?= htmlspecialchars(($class['teacherFirstName'] ?? $class['fName'] ?? '') . ' ' . ($class['teacherLastName'] ?? $class['lName'] ?? 'Not Assigned')) ?>
                        </p>
                    </div>
                    <div class="col-md-12">
                        <label class="text-muted small mb-1">Description</label>
                        <p class="mb-0"><?= nl2br(htmlspecialchars($class['description'] ?? 'No description available')) ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Class Roster -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-people-fill me-2" style="color: #2d5016;"></i>Class Roster
                        <span class="badge bg-primary ms-2"><?= count($roster) ?></span>
                    </h5>
                    <?php if (PermissionHelper::canManage('classes')): ?>
                    <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addPupilModal">
                        <i class="bi bi-plus-circle me-1"></i>Add Pupil
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body">
                <?php if (!empty($roster)): ?>
                <div class="row g-3">
                    <?php foreach ($roster as $p): ?>
                    <div class="col-md-6">
                        <div class="card bg-light border-0">
                            <div class="card-body py-2">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center">
                                        <div class="rounded-circle bg-white d-flex align-items-center justify-content-center me-2" 
                                             style="width: 40px; height: 40px;">
                                            <i class="bi bi-person-fill" style="color: #2d5016;"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0">
                                                <a href="pupils_view.php?id=<?= $p['pupilID'] ?>" class="text-decoration-none">
                                                    <?= htmlspecialchars(($p['fName'] ?? $p['firstName'] ?? '') . ' ' . ($p['lName'] ?? $p['lastName'] ?? '')) ?>
                                                </a>
                                            </h6>
                                            <small class="text-muted">
                                                <i class="bi bi-hash"></i><?= htmlspecialchars($p['studentNumber'] ?? $p['pupilID'] ?? '') ?>
                                            </small>
                                        </div>
                                    </div>
                                    <?php if (PermissionHelper::canManage('classes')): ?>
                                    <button type="button" 
                                            class="btn btn-sm btn-outline-danger remove-pupil-btn" 
                                            data-pupil-id="<?= $p['pupilID'] ?>"
                                            data-pupil-name="<?= htmlspecialchars(($p['fName'] ?? $p['firstName'] ?? '') . ' ' . ($p['lName'] ?? $p['lastName'] ?? '')) ?>"
                                            title="Remove from class">
                                        <i class="bi bi-x-circle"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="text-center py-4">
                    <i class="bi bi-people" style="font-size: 3rem; color: #dee2e6;"></i>
                    <p class="text-muted mt-2 mb-0">No pupils assigned to this class</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Add Pupil Modal -->
<div class="modal fade" id="addPupilModal" tabindex="-1" aria-labelledby="addPupilModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #2d5016; color: white;">
                <h5 class="modal-title" id="addPupilModalLabel">
                    <i class="bi bi-person-plus me-2"></i>Add Pupil to Class
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Search Pupils</label>
                    <input type="text" class="form-control" id="pupilSearchInput" placeholder="Search by name or student number...">
                </div>
                <div id="availablePupilsList" style="max-height: 400px; overflow-y: auto;">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="text-muted mt-2">Loading available pupils...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const classID = '<?= $classID ?>';
let availablePupils = [];

// Load available pupils when modal is opened
document.getElementById('addPupilModal').addEventListener('show.bs.modal', function() {
    loadAvailablePupils();
});

// Search functionality
document.getElementById('pupilSearchInput').addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    const filtered = availablePupils.filter(p => 
        (p.fName + ' ' + p.lName).toLowerCase().includes(searchTerm) ||
        p.studentNumber.toLowerCase().includes(searchTerm)
    );
    displayAvailablePupils(filtered);
});

function loadAvailablePupils() {
    fetch(`classes_manage_pupils.php?action=getAvailable&classID=${classID}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                availablePupils = data.pupils;
                displayAvailablePupils(availablePupils);
            } else {
                document.getElementById('availablePupilsList').innerHTML = 
                    `<div class="alert alert-danger">Error: ${data.error}</div>`;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('availablePupilsList').innerHTML = 
                '<div class="alert alert-danger">Failed to load pupils</div>';
        });
}

function displayAvailablePupils(pupils) {
    const container = document.getElementById('availablePupilsList');
    
    if (pupils.length === 0) {
        container.innerHTML = `
            <div class="text-center py-4">
                <i class="bi bi-inbox" style="font-size: 3rem; opacity: 0.3;"></i>
                <p class="text-muted mt-2">No available pupils to add</p>
            </div>`;
        return;
    }
    
    container.innerHTML = '<div class="list-group">' + 
        pupils.map(p => `
            <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="mb-0">${escapeHtml(p.fName + ' ' + p.lName)}</h6>
                    <small class="text-muted"><i class="bi bi-hash"></i>${escapeHtml(p.studentNumber)}</small>
                </div>
                <button type="button" 
                        class="btn btn-sm btn-success add-pupil-btn" 
                        data-pupil-id="${p.pupilID}"
                        data-pupil-name="${escapeHtml(p.fName + ' ' + p.lName)}">
                    <i class="bi bi-plus-circle me-1"></i>Add
                </button>
            </div>
        `).join('') + 
    '</div>';
    
    // Attach event listeners to add buttons
    document.querySelectorAll('.add-pupil-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            addPupilToClass(this.dataset.pupilId, this.dataset.pupilName, this);
        });
    });
}

function addPupilToClass(pupilID, pupilName, button) {
    if (!confirm(`Add ${pupilName} to this class?`)) {
        return;
    }
    
    button.disabled = true;
    button.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
    
    const formData = new FormData();
    formData.append('action', 'add');
    formData.append('classID', classID);
    formData.append('pupilID', pupilID);
    
    fetch('classes_manage_pupils.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Close modal and reload page
            bootstrap.Modal.getInstance(document.getElementById('addPupilModal')).hide();
            location.reload();
        } else {
            alert('Error: ' + data.error);
            button.disabled = false;
            button.innerHTML = '<i class="bi bi-plus-circle me-1"></i>Add';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred');
        button.disabled = false;
        button.innerHTML = '<i class="bi bi-plus-circle me-1"></i>Add';
    });
}

// Remove pupil from class
document.querySelectorAll('.remove-pupil-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const pupilID = this.dataset.pupilId;
        const pupilName = this.dataset.pupilName;
        
        if (!confirm(`Remove ${pupilName} from this class?`)) {
            return;
        }
        
        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
        
        const formData = new FormData();
        formData.append('action', 'remove');
        formData.append('classID', classID);
        formData.append('pupilID', pupilID);
        
        fetch('classes_manage_pupils.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + data.error);
                this.disabled = false;
                this.innerHTML = '<i class="bi bi-x-circle"></i>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred');
            this.disabled = false;
            this.innerHTML = '<i class="bi bi-x-circle"></i>';
        });
    });
});

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>

<style>
    .card {
        transition: all 0.3s ease;
    }
    .card:hover {
        transform: translateY(-2px);
    }
    .btn {
        transition: all 0.2s ease;
    }
    .btn:hover {
        transform: translateY(-1px);
    }
</style>

<?php require_once 'includes/footer.php'; ?>
