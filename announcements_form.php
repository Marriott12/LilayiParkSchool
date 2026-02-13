<?php
require_once 'includes/bootstrap.php';
require_once 'includes/Auth.php';

Auth::requireLogin();
Auth::requireAnyRole(['admin']);

$announcementID = $_GET['id'] ?? null;
$isEdit = !empty($announcementID);

require_once 'modules/announcements/AnnouncementsModel.php';

$announcementsModel = new AnnouncementsModel();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token first
    if (!CSRF::requireToken()) {
        $error = $GLOBALS['csrf_error'] ?? 'Security validation failed. Please try again.';
    } else {
        $data = [
            'title' => trim($_POST['title'] ?? ''),
            'content' => trim($_POST['content'] ?? ''),
            'targetAudience' => $_POST['targetAudience'] ?? 'all',
            'isPinned' => isset($_POST['isPinned']) ? 1 : 0,
            'status' => $_POST['status'] ?? 'draft',
            'expiryDate' => !empty($_POST['expiryDate']) ? $_POST['expiryDate'] : null,
            'createdBy' => Session::get('user_id')
        ];
        
        // Validation
        if (empty($data['title'])) {
            $error = 'Title is required';
        } elseif (empty($data['content'])) {
            $error = 'Content is required';
        }
        
        if (!isset($error)) {
            try {
                if ($isEdit) {
                    $announcementsModel->update($announcementID, $data);
                    Session::setFlash('success', 'Announcement updated successfully');
                } else {
                    $announcementsModel->create($data);
                    Session::setFlash('success', 'Announcement created successfully');
                }
                
                CSRF::regenerateToken();
                header('Location: announcements_list.php');
                exit;
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
    }
}

// Get announcement data if editing
$announcement = $isEdit ? $announcementsModel->getById($announcementID) : null;

$pageTitle = $isEdit ? 'Edit Announcement' : 'New Announcement';
$currentPage = 'announcements';
require_once 'includes/header.php';
?>

<!-- Page Header -->
<div class="row mb-4">
    <div class="col-md-8">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="announcements_list.php" class="text-decoration-none">Announcements</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?= $isEdit ? 'Edit' : 'New' ?></li>
            </ol>
        </nav>
        <h2 class="mb-0">
            <i class="bi bi-megaphone-fill me-2" style="color: #2d5016;"></i>
            <span style="color: #2d5016; font-weight: 600;"><?= $pageTitle ?></span>
        </h2>
    </div>
    <div class="col-md-4 text-end align-self-end">
        <a href="announcements_list.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Announcements
        </a>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom">
        <h5 class="mb-0">
            <i class="bi bi-megaphone-fill me-2" style="color: #2d5016;"></i><?= $pageTitle ?>
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
            <?= CSRF::field() ?>
            
            <div class="card mb-4 border-primary" style="border-width: 2px;">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0">
                        <i class="bi bi-file-text me-2"></i>Announcement Content
                    </h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-lg" name="title" 
                               placeholder="Enter announcement title..."
                               value="<?= htmlspecialchars($announcement['title'] ?? '') ?>" required>
                        <small class="text-muted">Keep it clear and concise</small>
                    </div>
                    
                    <div class="mb-0">
                        <label class="form-label fw-semibold">Content <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="content" rows="10" 
                                  placeholder="Write your announcement here..."
                                  required><?= htmlspecialchars($announcement['content'] ?? '') ?></textarea>
                        <small class="text-muted">Provide detailed information about the announcement</small>
                    </div>
                </div>
            </div>
            
            <div class="card mb-4 border-info" style="border-width: 2px;">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0">
                        <i class="bi bi-gear me-2"></i>Settings & Options
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">Target Audience <span class="text-danger">*</span></label>
                            <select class="form-select" name="targetAudience" required>
                                <option value="all" <?= ($announcement['targetAudience'] ?? 'all') == 'all' ? 'selected' : '' ?>>
                                    <i class="bi bi-people-fill"></i> All Users
                                </option>
                                <option value="teacher" <?= ($announcement['targetAudience'] ?? '') == 'teacher' ? 'selected' : '' ?>>
                                    Teachers Only
                                </option>
                                <option value="parent" <?= ($announcement['targetAudience'] ?? '') == 'parent' ? 'selected' : '' ?>>
                                    Parents Only
                                </option>
                                <option value="admin" <?= ($announcement['targetAudience'] ?? '') == 'admin' ? 'selected' : '' ?>>
                                    Admins Only
                                </option>
                            </select>
                            <small class="text-muted">Who should see this?</small>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                            <select class="form-select" name="status" required>
                                <option value="draft" <?= ($announcement['status'] ?? 'draft') == 'draft' ? 'selected' : '' ?>>
                                    Draft (Not Visible)
                                </option>
                                <option value="published" <?= ($announcement['status'] ?? '') == 'published' ? 'selected' : '' ?>>
                                    Published (Visible)
                                </option>
                            </select>
                            <small class="text-muted">Publish or save as draft</small>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">Expiry Date (Optional)</label>
                            <input type="date" class="form-control" name="expiryDate" 
                                   min="<?= date('Y-m-d') ?>"
                                   value="<?= htmlspecialchars($announcement['expiryDate'] ?? '') ?>">
                            <small class="text-muted">Auto-hide after this date</small>
                        </div>
                    </div>
                    
                    <div class="alert alert-warning mb-0">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="isPinned" id="isPinned" 
                                   <?= ($announcement['isPinned'] ?? 0) == 1 ? 'checked' : '' ?>>
                            <label class="form-check-label fw-semibold" for="isPinned">
                                <i class="bi bi-pin-angle-fill"></i> Pin to Top
                            </label>
                            <br>
                            <small class="text-muted">Pinned announcements appear at the top of the list</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <hr class="my-4">
            
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
