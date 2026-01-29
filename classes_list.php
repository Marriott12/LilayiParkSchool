<?php
require_once 'includes/bootstrap.php';
require_once 'includes/Auth.php';
require_once 'includes/PermissionHelper.php';

Auth::requireLogin();

require_once 'modules/roles/RolesModel.php';
$rolesModel = new RolesModel();
if (!$rolesModel->userHasPermission(Auth::id(), 'view_classes')) {
    Session::setFlash('error', 'You do not have permission to view classes.');
    header('Location: /LilayiParkSchool/403.php');
    exit;
}

require_once 'modules/classes/ClassModel.php';

$classModel = new ClassModel();

// Pagination
$page = $_GET['page'] ?? 1;
$perPage = 20;
$totalRecords = $classModel->count();
$pagination = new Pagination($totalRecords, $perPage, $page);
$classes = $classModel->getAllWithDetails($pagination->getLimit(), $pagination->getOffset());

$pageTitle = 'Classes Management';
$currentPage = 'classes';
require_once 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-building"></i> Classes</h2>
    <?php if (PermissionHelper::canManage('classes')): ?>
    <!--<a href="classes_form.php" class="btn btn-sm" style="background-color: #2d5016; color: white;">
        <i class="bi bi-plus-circle"></i> Add New Class
    </a>-->
    <?php endif; ?>
</div>

<?php if (!empty($_GET['added'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle-fill"></i> Pupil(s) successfully added to the class.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Classes Grid -->
<div class="row">
    <?php if (empty($classes)): ?>
    <div class="col-12">
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-inbox" style="font-size: 3rem; opacity: 0.3;"></i>
                <p class="text-muted mt-2">No classes found</p>
            </div>
        </div>
    </div>
    <?php else: ?>
    <?php foreach ($classes as $class): ?>
    <div class="col-md-6 col-lg-4 mb-4">
        <div class="card h-100 shadow-sm">
            <div class="card-body">
                <h5 class="card-title" style="color: #2d5016;">
                    <i class="bi bi-building me-2"></i><?= htmlspecialchars($class['className']) ?>
                </h5>
                <hr>
                <p class="card-text">
                    <button type="button" class="btn btn-outline-success btn-sm" onclick="openAddPupilModal('<?= htmlspecialchars($class['classID']) ?>')">
                        <i class="bi bi-plus-circle"></i> Add Pupil
                    </button>
                </p>
            </div>
            <div class="card-footer bg-white">
                <div class="btn-group btn-group-sm w-100" role="group">
                    <a href="classes_view.php?id=<?= $class['classID'] ?>" class="btn btn-outline-info btn-sm">
                        <i class="bi bi-eye"></i> View
                    </a>
                    <?php if (PermissionHelper::canManage('classes')): ?>
                    <a href="classes_form.php?id=<?= $class['classID'] ?>" class="btn btn-outline-warning btn-sm">
                        <i class="bi bi-pencil"></i> Edit
                    </a>
                    <a href="delete.php?module=classes&id=<?= $class['classID'] ?>" 
                       class="btn btn-outline-danger btn-sm" 
                       onclick="return confirm('Are you sure you want to delete this class?');">
                        <i class="bi bi-trash"></i> Delete
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php if ($pagination->hasPages()): ?>
<div class="card">
    <div class="card-footer">
        <?= $pagination->render() ?>
    </div>
</div>
<?php endif; ?>

<!-- Class View Modal (loads classes_view.php in an iframe) -->
<div class="modal fade" id="classViewModal" tabindex="-1" aria-labelledby="classViewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" style="min-width: 80%;">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #2d5016; color: white;">
                <h5 class="modal-title" id="classViewModalLabel"><i class="bi bi-building me-2"></i>Class View</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0" style="height: 80vh;">
                <iframe id="classViewIframe" src="" style="width: 100%; height: 100%; border: 0;" title="Class View"></iframe>
            </div>
        </div>
    </div>
</div>

<!-- Add Pupils Modal (copied from classes_view.php) -->
<div class="modal fade" id="addPupilModal" tabindex="-1" aria-labelledby="addPupilModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #2d5016; color: white;">
                <h5 class="modal-title" id="addPupilModalLabel">
                    <i class="bi bi-person-plus me-2"></i>Add Pupils to Class
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-8">
                        <label class="form-label">Search Pupils</label>
                        <input type="text" class="form-control" id="pupilSearchInput" placeholder="Search by name or student number...">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="selectAllPupils()">
                                <i class="bi bi-check-all"></i> Select All
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="clearAllPupils()">
                                <i class="bi bi-x"></i> Clear
                            </button>
                        </div>
                    </div>
                </div>
                <div id="availablePupilsList" style="max-height: 400px; overflow-y: auto;">
                    <div class="text-center py-4">
                        <i class="bi bi-inbox" style="font-size: 2rem; opacity: 0.3;"></i>
                        <p class="text-muted mt-2">No available pupils to add</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <span id="selectedCount" class="me-auto text-muted">0 selected</span>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="bulkAddBtn" onclick="bulkAddPupils()" disabled>
                    <i class="bi bi-plus-circle me-1"></i>Add Selected
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Add Pupils modal logic for classes_list page
let availablePupils = [];
let selectedPupils = new Set();
let currentAddClassID = null;

function openAddPupilModal(classID) {
    currentAddClassID = classID;
    selectedPupils.clear();
    document.getElementById('pupilSearchInput').value = '';
    document.getElementById('selectedCount').textContent = '0 selected';
    document.getElementById('bulkAddBtn').disabled = true;
    loadAvailablePupils();
    const modal = new bootstrap.Modal(document.getElementById('addPupilModal'));
    modal.show();
}

document.getElementById('pupilSearchInput').addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    const filtered = availablePupils.filter(p =>
        (p.fName + ' ' + p.lName).toLowerCase().includes(searchTerm) ||
        (p.pupilID || '').toLowerCase().includes(searchTerm)
    );
    displayAvailablePupils(filtered);
});

function loadAvailablePupils() {
    const container = document.getElementById('availablePupilsList');
    container.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="text-muted mt-2">Loading available pupils...</p>
        </div>`;

    fetch(`classes_manage_pupils.php?action=getAvailable&classID=${encodeURIComponent(currentAddClassID)}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                availablePupils = data.pupils || [];
                displayAvailablePupils(availablePupils);
            } else {
                container.innerHTML = `<div class="alert alert-danger">Error: ${data.error}</div>`;
            }
        })
        .catch(err => {
            console.error(err);
            container.innerHTML = '<div class="alert alert-danger">Failed to load pupils</div>';
        });
}

function displayAvailablePupils(pupils) {
    const container = document.getElementById('availablePupilsList');
    if (!pupils || pupils.length === 0) {
        container.innerHTML = `
            <div class="text-center py-4">
                <i class="bi bi-inbox" style="font-size: 2rem; opacity: 0.3;"></i>
                <p class="text-muted mt-2">No available pupils to add</p>
            </div>`;
        return;
    }
    container.innerHTML = '<div class="list-group">' + pupils.map(p => `
        <label class="list-group-item list-group-item-action d-flex align-items-center" style="cursor: pointer;">
            <input type="checkbox" class="form-check-input me-3 available-pupil-checkbox" value="${p.pupilID}" ${selectedPupils.has(p.pupilID) ? 'checked' : ''} onchange="updateSelection()">
            <div class="flex-grow-1">
                <h6 class="mb-0">${escapeHtml(p.fName + ' ' + p.lName)}</h6>
                <small class="text-muted"><i class="bi bi-hash"></i>${escapeHtml(p.pupilID)}</small>
            </div>
        </label>`).join('') + '</div>';
}

function selectAllPupils() {
    document.querySelectorAll('.available-pupil-checkbox').forEach(cb => {
        cb.checked = true;
        selectedPupils.add(cb.value);
    });
    updateSelection();
}

function clearAllPupils() {
    document.querySelectorAll('.available-pupil-checkbox').forEach(cb => cb.checked = false);
    selectedPupils.clear();
    updateSelection();
}

function updateSelection() {
    selectedPupils.clear();
    document.querySelectorAll('.available-pupil-checkbox:checked').forEach(cb => selectedPupils.add(cb.value));
    const count = selectedPupils.size;
    document.getElementById('selectedCount').textContent = `${count} selected`;
    document.getElementById('bulkAddBtn').disabled = count === 0;
}

function bulkAddPupils() {
    const count = selectedPupils.size;
    if (count === 0) return;
    if (!confirm(`Add ${count} pupil(s) to this class?`)) return;

    const btn = document.getElementById('bulkAddBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Adding...';

    const formData = new FormData();
    formData.append('action', 'bulkAdd');
    formData.append('classID', currentAddClassID);
    selectedPupils.forEach(pupilID => formData.append('pupilIDs[]', pupilID));

    fetch('classes_manage_pupils.php', { method: 'POST', body: formData })
        .then(resp => resp.json())
        .then(data => {
                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('addPupilModal')).hide();
                    // Redirect with flag so page shows a success message
                    const url = new URL(window.location.href);
                    url.searchParams.set('added', '1');
                    window.location.href = url.toString();
                } else {
                alert('Error: ' + data.error);
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-plus-circle me-1"></i>Add Selected';
            }
        })
        .catch(err => {
            console.error(err);
            alert('An error occurred');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-plus-circle me-1"></i>Add Selected';
        });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>

<?php require_once 'includes/footer.php'; ?>

<script>
function openClassModal(classID) {
    const iframe = document.getElementById('classViewIframe');
    // Load the class view page into the iframe
    iframe.src = `classes_view.php?id=${encodeURIComponent(classID)}`;
    const modalEl = document.getElementById('classViewModal');
    const modal = new bootstrap.Modal(modalEl);
    modal.show();

    // Clear iframe when modal hidden to stop activity
    modalEl.addEventListener('hidden.bs.modal', function onHidden() {
        iframe.src = '';
        modalEl.removeEventListener('hidden.bs.modal', onHidden);
    });
}
</script>
