<?php
require_once 'includes/bootstrap.php';
require_once 'includes/Auth.php';
require_once 'includes/PermissionHelper.php';

Auth::requireLogin();

require_once 'modules/announcements/AnnouncementsModel.php';

$announcementID = $_GET['id'] ?? null;
if (empty($announcementID)) {
    header('Location: announcements_list.php');
    exit;
}

$announcementsModel = new AnnouncementsModel();
$announcement = $announcementsModel->getAnnouncementWithAuthor($announcementID) ?: $announcementsModel->getById($announcementID);

if (!$announcement) {
    Session::setFlash('error', 'Announcement not found');
    header('Location: announcements_list.php');
    exit;
}

// Check if user has permission to view this announcement
$userRole = Session::getUserRole();
if ($userRole !== 'admin') {
    // Non-admins can only view announcements targeted to their role or 'all'
    if ($announcement['targetAudience'] !== 'all' && $announcement['targetAudience'] !== $userRole) {
        Session::setFlash('error', 'You do not have permission to view this announcement.');
        header('Location: announcements_list.php');
        exit;
    }
}

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
            <?php if (Auth::hasRole('admin')): ?>
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
        <div class="card border-0 shadow-sm sticky-top" style="top: 20px;">
            <div class="card-body text-center">
                <div class="mb-4">
                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center shadow" 
                         style="width: 120px; height: 120px; background: linear-gradient(135deg, #2d5016 0%, #5cb85c 100%);">
                        <i class="bi bi-megaphone-fill" style="font-size: 4rem; color: white;"></i>
                    </div>
                </div>
                
                <div class="mb-3">
                    <?php if ($announcement['isPinned']): ?>
                    <span class="badge bg-warning text-dark mb-2 me-1">
                        <i class="bi bi-pin-angle-fill"></i> Pinned
                    </span>
                    <?php endif; ?>
                    <span class="badge <?= $announcement['status'] == 'published' ? 'bg-success' : 'bg-secondary' ?> mb-2">
                        <i class="bi bi-<?= $announcement['status'] == 'published' ? 'check-circle' : 'file-earmark' ?>"></i>
                        <?= ucfirst($announcement['status']) ?>
                    </span>
                </div>
                
                <hr>
                
                <div class="text-start">
                    <div class="mb-3">
                        <small class="text-muted d-block mb-1"><strong>Author</strong></small>
                        <div class="d-flex align-items-center">
                            <i class="bi bi-person-circle me-2" style="font-size: 1.5rem; color: #2d5016;"></i>
                            <span><?= htmlspecialchars($announcement['authorName']) ?></span>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <small class="text-muted d-block mb-1"><strong>Target Audience</strong></small>
                        <div class="d-flex align-items-center">
                            <i class="bi bi-people-fill me-2" style="font-size: 1.5rem; color: #f0ad4e;"></i>
                            <span class="badge" style="background-color: #f0ad4e;">
                                <?= ucfirst($announcement['targetAudience']) ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <small class="text-muted d-block mb-1"><strong>Posted Date</strong></small>
                        <div class="d-flex align-items-center">
                            <i class="bi bi-calendar-event me-2" style="font-size: 1.5rem; color: #5cb85c;"></i>
                            <span><?= date('F d, Y', strtotime($announcement['createdAt'])) ?></span>
                        </div>
                        <small class="text-muted ms-4"><?= date('h:i A', strtotime($announcement['createdAt'])) ?></small>
                    </div>
                    
                    <?php if ($announcement['expiryDate']): ?>
                    <div class="mb-0">
                        <small class="text-muted d-block mb-1"><strong>Expires On</strong></small>
                        <div class="d-flex align-items-center">
                            <i class="bi bi-calendar-x-fill me-2" style="font-size: 1.5rem; color: #d9534f;"></i>
                            <span class="text-danger"><?= date('F d, Y', strtotime($announcement['expiryDate'])) ?></span>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <!-- Announcement Title and Content -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header" style="background: linear-gradient(135deg, #2d5016 0%, #5cb85c 100%); color: white;">
                <h5 class="mb-0">
                    <i class="bi bi-megaphone-fill me-2"></i><?= htmlspecialchars($announcement['title']) ?>
                </h5>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="text-muted small mb-1"><strong>Status</strong></label>
                        <p class="mb-0">
                            <?php 
                            $statusClass = $announcement['status'] == 'published' ? 'bg-success' : 'bg-secondary';
                            ?>
                            <span class="badge <?= $statusClass ?> px-3 py-2">
                                <i class="bi bi-<?= $announcement['status'] == 'published' ? 'check-circle' : 'clock' ?> me-1"></i>
                                <?= ucfirst($announcement['status']) ?>
                            </span>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small mb-1"><strong>Target Audience</strong></label>
                        <p class="mb-0">
                            <span class="badge px-3 py-2" style="background-color: #f0ad4e;">
                                <i class="bi bi-people me-1"></i><?= ucfirst($announcement['targetAudience']) ?>
                            </span>
                        </p>
                    </div>
                </div>
                
                <hr>
                
                <div class="announcement-content" style="line-height: 1.8; font-size: 1.05rem;">
                    <?= nl2br(htmlspecialchars($announcement['content'])) ?>
                </div>
                
                <?php if ($announcement['expiryDate']): ?>
                <div class="alert alert-danger mt-4 mb-0">
                    <i class="bi bi-calendar-x-fill me-2"></i>
                    <strong>Notice:</strong> This announcement will expire on <?= date('F d, Y', strtotime($announcement['expiryDate'])) ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.sticky-top {
    position: -webkit-sticky;
    position: sticky;
}
.announcement-content {
    color: #333;
    word-wrap: break-word;
}
.announcement-content p {
    margin-bottom: 1rem;
}
.card {
    transition: all 0.3s ease;
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
