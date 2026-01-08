<?php
require_once 'includes/bootstrap.php';

RBAC::requireAuth();
RBAC::requirePermission('announcements', 'read');

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

<div class="mb-4">
    <a href="announcements_list.php" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back to Announcements
    </a>
</div>

<div class="card">
    <?php if ($announcement['isPinned']): ?>
    <div class="card-header bg-warning text-dark">
        <i class="bi bi-pin-fill"></i> Pinned Announcement
    </div>
    <?php endif; ?>
    <div class="card-body">
        <h3 class="card-title" style="color: #2d5016;"><?= htmlspecialchars($announcement['title']) ?></h3>
        
        <div class="mb-3 d-flex gap-2 align-items-center flex-wrap">
            <span class="badge" style="background-color: #f0ad4e;">
                <i class="bi bi-people"></i> <?= ucfirst($announcement['targetAudience']) ?>
            </span>
            <span class="badge <?= $announcement['status'] == 'published' ? 'bg-success' : 'bg-secondary' ?>">
                <?= ucfirst($announcement['status']) ?>
            </span>
            <small class="text-muted">
                <i class="bi bi-person"></i> By <?= htmlspecialchars($announcement['authorName'] ?? 'Unknown') ?>
            </small>
            <small class="text-muted">
                <i class="bi bi-clock"></i> <?= date('M d, Y H:i', strtotime($announcement['createdAt'])) ?>
            </small>
            <?php if ($announcement['expiryDate']): ?>
            <small class="text-danger">
                <i class="bi bi-calendar-x"></i> Expires: <?= date('M d, Y', strtotime($announcement['expiryDate'])) ?>
            </small>
            <?php endif; ?>
        </div>
        
        <hr>
        
        <div class="announcement-content">
            <?= nl2br(htmlspecialchars($announcement['content'])) ?>
        </div>
        
        <?php if (RBAC::hasPermission(Session::getUserRole(), 'announcements', 'update')): ?>
        <hr>
        <div class="mt-4">
            <a href="announcements_form.php?id=<?= $announcement['announcementID'] ?>" class="btn btn-warning">
                <i class="bi bi-pencil"></i> Edit
            </a>
            <?php if (RBAC::hasPermission(Session::getUserRole(), 'announcements', 'delete')): ?>
            <a href="delete.php?module=announcements&id=<?= $announcement['announcementID'] ?>" 
               class="btn btn-danger" onclick="return confirm('Are you sure?')">
                <i class="bi bi-trash"></i> Delete
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
