<?php
require_once 'includes/bootstrap.php';
require_once 'includes/Auth.php';
require_once 'includes/PermissionHelper.php';

Auth::requireLogin();

require_once 'modules/roles/RolesModel.php';
$rolesModel = new RolesModel();
if (!$rolesModel->userHasPermission(Auth::id(), 'view_announcements')) {
    Session::setFlash('error', 'You do not have permission to view announcements.');
    header('Location: /LilayiParkSchool/403.php');
    exit;
}

require_once 'modules/announcements/AnnouncementsModel.php';

$announcementID = $_GET['id'] ?? null;
if (empty($announcementID)) {
    header('Location: announcements_list.php');
    exit;
}

$announcementsModel = new AnnouncementsModel();
$announcement = $announcementsModel->getAnnouncementWithAuthor($announcementID) ?: $announcementsModel->getById($announcementID);

$pageTitle = 'Announcement Details';
$currentPage = 'announcements';
require_once 'includes/header.php';
?>

<!-- Page Header with Actions -->
<div class="row mb-4">
    <div class="col-md-8">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="announcements_list.php" class="text-decoration-none">Announcements</a></li>
                <li class="breadcrumb-item active" aria-current="page">View Details</li>
            </ol>
        </nav>
        <h2 class="mb-0">
            <i class="bi bi-megaphone me-2" style="color: #2d5016;"></i>
            <span style="color: #2d5016; font-weight: 600;">Announcement Details</span>
        </h2>
    </div>
    <div class="col-md-4 text-end align-self-end">
        <div class="btn-group" role="group">
            <a href="announcements_list.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
            <?php if (PermissionHelper::canManage('announcements')): ?>
            <a href="announcements_form.php?id=<?= $announcement['announcementID'] ?>" class="btn btn-warning">
                <i class="bi bi-pencil me-1"></i> Edit
            </a>
            <a href="delete.php?module=announcements&id=<?= $announcement['announcementID'] ?>" 
               class="btn btn-danger"
               onclick="return confirm('Are you sure you want to delete this announcement? This action cannot be undone.');">
                <i class="bi bi-trash me-1"></i> Delete
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Announcement Details -->
<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <div class="mb-3">
                    <?php if ($announcement['isPinned']): ?>
                    <div class="alert alert-warning py-2 px-3 mb-3">
                        <i class="bi bi-pin-angle-fill"></i> Pinned Announcement
                    </div>
                    <?php endif; ?>
                    <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center" 
                         style="width: 120px; height: 120px;">
                        <i class="bi bi-megaphone-fill" style="font-size: 5rem; color: #2d5016;"></i>
                    </div>
                </div>
                <h5 class="mb-1"><?= htmlspecialchars($announcement['title']) ?></h5>
                <p class="text-muted mb-3">Announcement</p>
                <div class="mb-3">
                    <?php 
                    $statusClass = $announcement['status'] == 'published' ? 'bg-success' : 'bg-secondary';
                    ?>
                    <span class="badge <?= $statusClass ?> px-3 py-2">
                        <i class="bi bi-<?= $announcement['status'] == 'published' ? 'check-circle' : 'clock' ?> me-1"></i>
                        <?= ucfirst($announcement['status']) ?>
                    </span>
                </div>
                <hr>
                <div class="text-start">
                    <small class="text-muted d-block mb-2">Quick Info</small>
                    <div class="d-flex align-items-center mb-2">
                        <i class="bi bi-person text-muted me-2"></i>
                        <span class="small"><?= htmlspecialchars($announcement['authorName'] ?? 'Unknown') ?></span>
                    </div>
                    <div class="d-flex align-items-center mb-2">
                        <i class="bi bi-people text-muted me-2"></i>
                        <span class="small"><?= ucfirst($announcement['targetAudience']) ?></span>
                    </div>
                    <div class="d-flex align-items-center">
                        <i class="bi bi-calendar text-muted me-2"></i>
                        <span class="small"><?= date('M d, Y', strtotime($announcement['createdAt'])) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <!-- Announcement Information -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">
                    <i class="bi bi-info-circle-fill me-2" style="color: #2d5016;"></i>Announcement Information
                </h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="text-muted small mb-1">Status</label>
                        <p class="mb-0">
                            <?php 
                            $statusClass = $announcement['status'] == 'published' ? 'bg-success' : 'bg-secondary';
                            ?>
                            <span class="badge <?= $statusClass ?> px-3 py-2">
                                <?= ucfirst($announcement['status']) ?>
                            </span>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small mb-1">Target Audience</label>
                        <p class="mb-0">
                            <span class="badge px-3 py-2" style="background-color: #f0ad4e;">
                                <i class="bi bi-people me-1"></i><?= ucfirst($announcement['targetAudience']) ?>
                            </span>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small mb-1">Created By</label>
                        <p class="mb-0 fw-semibold">
                            <i class="bi bi-person-fill me-1"></i><?= htmlspecialchars($announcement['authorName'] ?? 'Unknown') ?>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small mb-1">Created On</label>
                        <p class="mb-0 fw-semibold">
                            <i class="bi bi-calendar-event me-1"></i>
                            <?= date('M d, Y H:i', strtotime($announcement['createdAt'])) ?>
                        </p>
                    </div>
                    <?php if ($announcement['expiryDate']): ?>
                    <div class="col-md-12">
                        <label class="text-muted small mb-1">Expiry Date</label>
                        <p class="mb-0">
                            <span class="badge bg-danger px-3 py-2">
                                <i class="bi bi-calendar-x me-1"></i>
                                Expires: <?= date('M d, Y', strtotime($announcement['expiryDate'])) ?>
                            </span>
                        </p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Announcement Content -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">
                    <i class="bi bi-file-text me-2" style="color: #2d5016;"></i>Content
                </h5>
            </div>
            <div class="card-body">
                <div class="announcement-content" style="line-height: 1.8;">
                    <?= nl2br(htmlspecialchars($announcement['content'])) ?>
                </div>
            </div>
        </div>
    </div>
</div>

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
    .announcement-content {
        font-size: 1rem;
        color: #333;
    }
</style>

<?php require_once 'includes/footer.php'; ?>
