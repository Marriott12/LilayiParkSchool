<?php
require_once 'includes/bootstrap.php';
require_once 'includes/Auth.php';
require_once 'includes/PermissionHelper.php';

Auth::requireLogin();
// All users can view announcements, but they see only those targeted to their role

require_once 'modules/announcements/AnnouncementsModel.php';

$announcementsModel = new AnnouncementsModel();

// Handle filters
$statusFilter = $_GET['status'] ?? '';
$audienceFilter = $_GET['audience'] ?? '';
$searchTerm = $_GET['search'] ?? '';

// Pagination
$page = $_GET['page'] ?? 1;
$perPage = 20;

// Get announcements based on user role with filters
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

// Apply filters
if ($statusFilter || $audienceFilter || $searchTerm) {
    $announcements = array_filter($announcements, function($ann) use ($statusFilter, $audienceFilter, $searchTerm) {
        $matchStatus = !$statusFilter || $ann['status'] === $statusFilter;
        $matchAudience = !$audienceFilter || $ann['targetAudience'] === $audienceFilter;
        $matchSearch = !$searchTerm || 
            stripos($ann['title'], $searchTerm) !== false || 
            stripos($ann['content'], $searchTerm) !== false;
        return $matchStatus && $matchAudience && $matchSearch;
    });
}

// Get stats (filtered by user role)
$db = Database::getInstance()->getConnection();
if ($userRole === 'admin') {
    $stats = [
        'total' => $totalRecords,
        'published' => $db->query("SELECT COUNT(*) as count FROM Announcements WHERE status='published'")->fetch()['count'] ?? 0,
        'pinned' => $db->query("SELECT COUNT(*) as count FROM Announcements WHERE isPinned=1")->fetch()['count'] ?? 0,
        'expiring' => $db->query("SELECT COUNT(*) as count FROM Announcements WHERE expiryDate IS NOT NULL AND expiryDate > NOW() AND expiryDate <= DATE_ADD(NOW(), INTERVAL 7 DAY)")->fetch()['count'] ?? 0
    ];
} else {
    // For non-admins, count only announcements they can see
    $audienceFilter = "(targetAudience = 'all' OR targetAudience = '$userRole')";
    $stats = [
        'total' => $totalRecords,
        'published' => $db->query("SELECT COUNT(*) as count FROM Announcements WHERE status='published' AND $audienceFilter")->fetch()['count'] ?? 0,
        'pinned' => $db->query("SELECT COUNT(*) as count FROM Announcements WHERE isPinned=1 AND $audienceFilter")->fetch()['count'] ?? 0,
        'expiring' => $db->query("SELECT COUNT(*) as count FROM Announcements WHERE expiryDate IS NOT NULL AND expiryDate > NOW() AND expiryDate <= DATE_ADD(NOW(), INTERVAL 7 DAY) AND $audienceFilter")->fetch()['count'] ?? 0
    ];
}

$pageTitle = 'Announcements';
$currentPage = 'announcements';
require_once 'includes/header.php';
?>

<!-- Page Header -->
<div class="row mb-4">
    <div class="col-md-6">
        <h2 class="mb-0">
            <i class="bi bi-megaphone-fill me-2" style="color: #2d5016;"></i>
            <span style="color: #2d5016; font-weight: 600;">Announcements</span>
        </h2>
        <p class="text-muted mt-1">Manage school announcements and notifications</p>
    </div>
    <div class="col-md-6 text-end align-self-center">
        <?php if (Auth::hasRole('admin')): ?>
        <a href="announcements_form.php" class="btn btn-lg shadow-sm" style="background-color: #2d5016; color: white;">
            <i class="bi bi-plus-circle me-1"></i> Add Announcement
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- Stats Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1 small">Total Announcements</p>
                        <h3 class="mb-0" style="color: #2d5016;"><?= $stats['total'] ?></h3>
                    </div>
                    <div class="bg-light rounded-circle p-3">
                        <i class="bi bi-megaphone-fill" style="font-size: 1.5rem; color: #2d5016;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1 small">Published</p>
                        <h3 class="mb-0 text-success"><?= $stats['published'] ?></h3>
                    </div>
                    <div class="bg-light rounded-circle p-3">
                        <i class="bi bi-check-circle" style="font-size: 1.5rem; color: #5cb85c;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1 small">Pinned</p>
                        <h3 class="mb-0 text-warning"><?= $stats['pinned'] ?></h3>
                    </div>
                    <div class="bg-light rounded-circle p-3">
                        <i class="bi bi-pin-angle-fill" style="font-size: 1.5rem; color: #f0ad4e;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1 small">Expiring Soon</p>
                        <h3 class="mb-0 text-danger"><?= $stats['expiring'] ?></h3>
                    </div>
                    <div class="bg-light rounded-circle p-3">
                        <i class="bi bi-calendar-x" style="font-size: 1.5rem; color: #d9534f;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Search and Filter Bar -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-center">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" class="form-control border-start-0 ps-0" name="search" 
                           placeholder="Search announcements..." 
                           value="<?= htmlspecialchars($searchTerm) ?>">
                </div>
            </div>
            <div class="col-md-2">
                <select class="form-select" name="status">
                    <option value="">All Status</option>
                    <option value="published" <?= $statusFilter === 'published' ? 'selected' : '' ?>>Published</option>
                    <option value="draft" <?= $statusFilter === 'draft' ? 'selected' : '' ?>>Draft</option>
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-select" name="audience">
                    <option value="">All Audiences</option>
                    <option value="all" <?= $audienceFilter === 'all' ? 'selected' : '' ?>>Everyone</option>
                    <option value="teacher" <?= $audienceFilter === 'teacher' ? 'selected' : '' ?>>Teachers</option>
                    <option value="parent" <?= $audienceFilter === 'parent' ? 'selected' : '' ?>>Parents</option>
                    <option value="admin" <?= $audienceFilter === 'admin' ? 'selected' : '' ?>>Admins</option>
                </select>
            </div>
            <div class="col-md-3">
                <div class="d-grid gap-2">
                    <button type="submit" class="btn" style="background-color: #2d5016; color: white;">
                        <i class="bi bi-funnel me-1"></i> Filter
                    </button>
                    <?php if ($statusFilter || $audienceFilter || $searchTerm): ?>
                    <a href="announcements_list.php" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle me-1"></i> Clear
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Announcements List -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom">
        <h5 class="mb-0">
            <i class="bi bi-list-ul me-2"></i>Announcements
            <?php if ($searchTerm || $statusFilter || $audienceFilter): ?>
                <span class="badge bg-secondary ms-2"><?= count($announcements) ?> results</span>
            <?php endif; ?>
        </h5>
    </div>
    <div class="card-body p-0">
        <?php if (empty($announcements)): ?>
        <div class="text-center py-5">
            <i class="bi bi-megaphone" style="font-size: 4rem; color: #dee2e6;"></i>
            <h5 class="text-muted mt-3">No Announcements Found</h5>
            <p class="text-muted mb-3">
                <?php if ($searchTerm || $statusFilter || $audienceFilter): ?>
                    No announcements match your filter criteria. Try adjusting your filters.
                <?php else: ?>
                    Start by creating your first announcement.
                <?php endif; ?>
            </p>
            <?php if (PermissionHelper::canManage('announcements') && !$searchTerm && !$statusFilter && !$audienceFilter): ?>
            <a href="announcements_form.php" class="btn btn-sm" style="background-color: #2d5016; color: white;">
                <i class="bi bi-plus-circle me-1"></i> Create First Announcement
            </a>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="list-group list-group-flush">
            <?php foreach ($announcements as $announcement): ?>
            <div class="list-group-item <?= $announcement['isPinned'] ? 'border-start border-warning border-4' : '' ?> hover-shadow">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <div class="d-flex align-items-start">
                            <?php if ($announcement['isPinned']): ?>
                            <div class="me-2">
                                <i class="bi bi-pin-angle-fill text-warning" style="font-size: 1.5rem;"></i>
                            </div>
                            <?php endif; ?>
                            <div class="flex-grow-1">
                                <h5 class="mb-1">
                                    <a href="announcements_view.php?id=<?= $announcement['announcementID'] ?>" 
                                       class="text-decoration-none" style="color: #2d5016;">
                                        <?= htmlspecialchars($announcement['title']) ?>
                                    </a>
                                </h5>
                                <p class="text-muted mb-2 small">
                                    <?= htmlspecialchars(substr($announcement['content'], 0, 150)) ?><?= strlen($announcement['content']) > 150 ? '...' : '' ?>
                                </p>
                                <div class="d-flex gap-2 flex-wrap align-items-center">
                                    <span class="badge <?= $announcement['status'] == 'published' ? 'bg-success' : 'bg-secondary' ?>">
                                        <?= ucfirst($announcement['status']) ?>
                                    </span>
                                    <span class="badge" style="background-color: #f0ad4e;">
                                        <i class="bi bi-people me-1"></i><?= ucfirst($announcement['targetAudience']) ?>
                                    </span>
                                    <small class="text-muted">
                                        <i class="bi bi-person"></i> <?= htmlspecialchars($announcement['authorName'] ?? 'Unknown') ?>
                                    </small>
                                    <small class="text-muted">
                                        <i class="bi bi-calendar"></i> <?= date('M d, Y', strtotime($announcement['createdAt'])) ?>
                                    </small>
                                    <?php if ($announcement['expiryDate']): ?>
                                    <small class="text-danger">
                                        <i class="bi bi-calendar-x"></i> Expires: <?= date('M d, Y', strtotime($announcement['expiryDate'])) ?>
                                    </small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 text-end">
                        <div class="btn-group btn-group-sm" role="group">
                            <a href="announcements_view.php?id=<?= $announcement['announcementID'] ?>" 
                               class="btn btn-outline-info" title="View Details">
                                <i class="bi bi-eye"></i>
                            </a>
                            <?php if (Auth::hasRole('admin')): ?>
                            <a href="announcements_form.php?id=<?= $announcement['announcementID'] ?>" 
                               class="btn btn-outline-warning" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <a href="delete.php?module=announcements&id=<?= $announcement['announcementID'] ?>" 
                               class="btn btn-outline-danger" 
                               title="Delete"
                               onclick="return confirm('Are you sure you want to delete this announcement?');">
                                <i class="bi bi-trash"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($pagination->hasPages()): ?>
<div class="card border-0 shadow-sm mt-4">
    <div class="card-footer bg-white">
        <?= $pagination->render() ?>
    </div>
</div>
<?php endif; ?>

<style>
.hover-shadow {
    transition: all 0.3s ease;
}
.hover-shadow:hover {
    background-color: #f8f9fa;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
.list-group-item {
    border-left-width: 1px !important;
}
.list-group-item.border-start {
    border-left-width: 4px !important;
}
</style>

<?php require_once 'includes/footer.php'; ?>
