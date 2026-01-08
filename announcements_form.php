<?php
require_once 'includes/bootstrap.php';

RBAC::requireAuth();

$announcementID = $_GET['id'] ?? null;
$isEdit = !empty($announcementID);

if ($isEdit) {
    RBAC::requirePermission('announcements', 'update');
} else {
    RBAC::requirePermission('announcements', 'create');
}

require_once 'modules/announcements/AnnouncementsModel.php';

$announcementsModel = new AnnouncementsModel();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'title' => trim($_POST['title'] ?? ''),
        'content' => trim($_POST['content'] ?? ''),
        'targetAudience' => $_POST['targetAudience'] ?? 'all',
        'isPinned' => isset($_POST['isPinned']) ? 1 : 0,
        'status' => $_POST['status'] ?? 'draft',
        'expiryDate' => !empty($_POST['expiryDate']) ? $_POST['expiryDate'] : null,
        'createdBy' => Session::get('user_id')
    ];
    
    try {
        if ($isEdit) {
            $announcementsModel->update($announcementID, $data);
            Session::setFlash('success', 'Announcement updated successfully');
        } else {
            $announcementsModel->create($data);
            Session::setFlash('success', 'Announcement created successfully');
        }
        header('Location: announcements_list.php');
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get announcement data if editing
$announcement = $isEdit ? $announcementsModel->getById($announcementID) : null;

$pageTitle = $isEdit ? 'Edit Announcement' : 'New Announcement';
$currentPage = 'announcements';
require_once 'includes/header.php';
?>

<div class="mb-4">
    <a href="announcements_list.php" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back to Announcements
    </a>
</div>

<div class="card">
    <div class="card-header" style="background: linear-gradient(135deg, #2d5016 0%, #5cb85c 100%); color: white;">
        <h5 class="mb-0">
            <i class="bi bi-megaphone-fill"></i> <?= $pageTitle ?>
        </h5>
    </div>
    <div class="card-body">
        <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle-fill"></i> <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label">Title <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="title" 
                       value="<?= htmlspecialchars($announcement['title'] ?? '') ?>" required>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Content <span class="text-danger">*</span></label>
                <textarea class="form-control" name="content" rows="8" required><?= htmlspecialchars($announcement['content'] ?? '') ?></textarea>
            </div>
            
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Target Audience</label>
                    <select class="form-select" name="targetAudience">
                        <option value="all" <?= ($announcement['targetAudience'] ?? 'all') == 'all' ? 'selected' : '' ?>>All Users</option>
                        <option value="teachers" <?= ($announcement['targetAudience'] ?? '') == 'teachers' ? 'selected' : '' ?>>Teachers Only</option>
                        <option value="parents" <?= ($announcement['targetAudience'] ?? '') == 'parents' ? 'selected' : '' ?>>Parents Only</option>
                        <option value="admin" <?= ($announcement['targetAudience'] ?? '') == 'admin' ? 'selected' : '' ?>>Admins Only</option>
                    </select>
                </div>
                
                <div class="col-md-4 mb-3">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="draft" <?= ($announcement['status'] ?? 'draft') == 'draft' ? 'selected' : '' ?>>Draft</option>
                        <option value="published" <?= ($announcement['status'] ?? '') == 'published' ? 'selected' : '' ?>>Published</option>
                    </select>
                </div>
                
                <div class="col-md-4 mb-3">
                    <label class="form-label">Expiry Date (Optional)</label>
                    <input type="date" class="form-control" name="expiryDate" 
                           value="<?= htmlspecialchars($announcement['expiryDate'] ?? '') ?>">
                </div>
            </div>
            
            <div class="mb-3">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="isPinned" id="isPinned" 
                           <?= ($announcement['isPinned'] ?? 0) == 1 ? 'checked' : '' ?>>
                    <label class="form-check-label" for="isPinned">
                        <i class="bi bi-pin-fill"></i> Pin to top
                    </label>
                </div>
            </div>
            
            <div class="d-flex gap-2">
                <button type="submit" class="btn" style="background-color: #2d5016; color: white;">
                    <i class="bi bi-save"></i> <?= $isEdit ? 'Update' : 'Create' ?> Announcement
                </button>
                <a href="announcements_list.php" class="btn btn-secondary">
                    <i class="bi bi-x-circle"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
