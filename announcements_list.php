<?php
require_once 'includes/bootstrap.php';
require_once 'includes/Auth.php';
require_once 'includes/PermissionHelper.php';

Auth::requireLogin();
Auth::requireAnyRole(['admin']);

require_once 'modules/announcements/AnnouncementsModel.php';

$announcementsModel = new AnnouncementsModel();

// Pagination
$page = $_GET['page'] ?? 1;
$perPage = 20;

// Get announcements based on user role
$userRole = Session::getUserRole();
if ($userRole === 'admin' || PermissionHelper::canManage('announcements')) {
    $totalRecords = $announcementsModel->count();
    $pagination = new Pagination($totalRecords, $perPage, $page);
    $announcements = $announcementsModel->getAllWithAuthors($pagination->getLimit(), $pagination->getOffset());
} else {
    $allAnnouncements = $announcementsModel->getByAudience($userRole);
    $totalRecords = count($allAnnouncements);
    $pagination = new Pagination($totalRecords, $perPage, $page);
    $announcements = array_slice($allAnnouncements, $pagination->getOffset(), $pagination->getLimit());
}

$pageTitle = 'Announcements';
$currentPage = 'announcements';
require_once 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-megaphone-fill"></i> Announcements</h2>
    <?php if (PermissionHelper::canManage('announcements')): ?>
    <a href="announcements_form.php" class="btn btn-sm" style="background-color: #2d5016; color: white;">
        <i class="bi bi-plus-circle"></i> New Announcement
    </a>
    <?php endif; ?>
</div>

<!-- Announcements List -->
<?php if (empty($announcements)): ?>
<div class="card">
    <div class="card-body text-center py-5">
        <i class="bi bi-inbox" style="font-size: 3rem; opacity: 0.3;"></i>
        <p class="text-muted mt-2">No announcements found</p>
    </div>
</div>
<?php else: ?>
<?php foreach ($announcements as $announcement): ?>
<div class="card mb-3 <?= $announcement['isPinned'] ? 'border-warning' : '' ?>">
    <?php if ($announcement['isPinned']): ?>
    <div class="card-header bg-warning text-dark">
        <i class="bi bi-pin-fill"></i> Pinned Announcement
    </div>
    <?php endif; ?>
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start mb-2">
            <h5 class="card-title mb-0" style="color: #2d5016;">
                <?= htmlspecialchars($announcement['title']) ?>
            </h5>
            <div class="btn-group btn-group-sm" role="group">
                <a href="announcements_view.php?id=<?= $announcement['announcementID'] ?>" class="btn btn-outline-info btn-sm">
                    <i class="bi bi-eye"></i> View
                </a>
                <?php if (PermissionHelper::canManage('announcements')): ?>
                <a href="announcements_form.php?id=<?= $announcement['announcementID'] ?>" class="btn btn-outline-warning btn-sm">
                    <i class="bi bi-pencil"></i> Edit
                </a>
                <a href="delete.php?module=announcements&id=<?= $announcement['announcementID'] ?>" 
                   class="btn btn-outline-danger btn-sm" 
                   onclick="return confirm('Are you sure you want to delete this announcement?');">
                    <i class="bi bi-trash"></i> Delete
                </a>
                <?php endif; ?>
            </div>
        </div>
        
        <p class="card-text text-muted mb-2">
            <?= htmlspecialchars(substr($announcement['content'], 0, 200)) ?><?= strlen($announcement['content']) > 200 ? '...' : '' ?>
        </p>
        
        <div class="d-flex gap-2 align-items-center text-sm">
            <span class="badge" style="background-color: #f0ad4e;">
                <?= ucfirst($announcement['targetAudience']) ?>
            </span>
            <span class="badge <?= $announcement['status'] == 'published' ? 'bg-success' : 'bg-secondary' ?>">
                <?= ucfirst($announcement['status']) ?>
            </span>
            <small class="text-muted">
                <i class="bi bi-person"></i> <?= htmlspecialchars($announcement['authorName'] ?? 'Unknown') ?>
                • <i class="bi bi-clock"></i> <?= date('M d, Y H:i', strtotime($announcement['createdAt'])) ?>
            </small>
            <?php if ($announcement['expiryDate']): ?>
            <small class="text-danger">
                <i class="bi bi-calendar-x"></i> Expires: <?= date('M d, Y', strtotime($announcement['expiryDate'])) ?>
            </small>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<?php if ($pagination->hasPages()): ?>
<div class="card">
    <div class="card-footer">
        <?= $pagination->render() ?>
    </div>
</div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
