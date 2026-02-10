<?php
require_once 'includes/bootstrap.php';
require_once 'includes/Auth.php';
require_once 'includes/PermissionHelper.php';

Auth::requireLogin();

require_once 'modules/roles/RolesModel.php';
$rolesModel = new RolesModel();
if (!$rolesModel->userHasPermission(Auth::id(), 'view_library')) {
    Session::setFlash('error', 'You do not have permission to view library.');
    header('Location: 403.php');
    exit;
}

require_once 'modules/library/LibraryModel.php';

$libraryModel = new LibraryModel();

$bookID = $_GET['id'] ?? null;
if (!$bookID) {
    Session::setFlash('error', 'Book ID is required.');
    header('Location: library_list.php');
    exit;
}

$book = $libraryModel->find($bookID);
if (!$book) {
    Session::setFlash('error', 'Book not found.');
    header('Location: library_list.php');
    exit;
}

// Get borrow history
$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("
    SELECT br.*, 
           p.firstName as pupilFirstName, 
           p.lastName as pupilLastName,
           p.admissionNumber,
           u1.username as issuedByUsername,
           u2.username as returnedToUsername
    FROM borrowrecords br
    LEFT JOIN Pupil p ON br.pupilID = p.pupilID
    LEFT JOIN Users u1 ON br.issuedBy = u1.userID
    LEFT JOIN Users u2 ON br.returnedTo = u2.userID
    WHERE br.bookID = ?
    ORDER BY br.borrowDate DESC
    LIMIT 20
");
$stmt->execute([$bookID]);
$borrowHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Book Details';
$currentPage = 'library';
require_once 'includes/header.php';
?>

<!-- Page Header -->
<div class="row mb-4">
    <div class="col-md-8">
        <h2 class="mb-0">
            <i class="bi bi-book me-2" style="color: #2d5016;"></i>
            <span style="color: #2d5016; font-weight: 600;">Book Details</span>
        </h2>
    </div>
    <div class="col-md-4 text-end">
        <?php if (Auth::hasRole('admin')): ?>
        <a href="library_form.php?id=<?= $bookID ?>" class="btn" style="background-color: #2d5016; color: white;">
            <i class="bi bi-pencil me-1"></i> Edit
        </a>
        <?php endif; ?>
        <a href="library_list.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>
</div>

<!-- Book Information -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header" style="background-color: #2d5016; color: white;">
        <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Book Information</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <table class="table table-borderless">
                    <tr>
                        <th width="40%">ISBN:</th>
                        <td><span class="badge bg-secondary"><?= htmlspecialchars($book['ISBN']) ?></span></td>
                    </tr>
                    <tr>
                        <th>Title:</th>
                        <td><strong><?= htmlspecialchars($book['title']) ?></strong></td>
                    </tr>
                    <tr>
                        <th>Author:</th>
                        <td><?= htmlspecialchars($book['author']) ?></td>
                    </tr>
                    <tr>
                        <th>Publisher:</th>
                        <td><?= htmlspecialchars($book['publisher']) ?></td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <table class="table table-borderless">
                    <tr>
                        <th width="40%">Category:</th>
                        <td><span class="badge" style="background-color: #e8f5e9; color: #2d5016;"><?= htmlspecialchars($book['category']) ?></span></td>
                    </tr>
                    <tr>
                        <th>Total Copies:</th>
                        <td><span class="badge bg-secondary"><?= $book['totalCopies'] ?></span></td>
                    </tr>
                    <tr>
                        <th>Available:</th>
                        <td>
                            <?php 
                            $available = $book['availableCopies'];
                            $badgeClass = $available > 0 ? 'bg-success' : 'bg-danger';
                            ?>
                            <span class="badge <?= $badgeClass ?>"><?= $available ?> copies</span>
                        </td>
                    </tr>
                    <tr>
                        <th>Location:</th>
                        <td><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($book['shelfLocation']) ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Borrow History -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom">
        <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Borrow History</h5>
    </div>
    <div class="card-body p-0">
        <?php if (empty($borrowHistory)): ?>
        <div class="text-center py-5">
            <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
            <p class="text-muted mt-3">No borrow history for this book.</p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>Pupil</th>
                        <th>Admission No.</th>
                        <th>Borrow Date</th>
                        <th>Due Date</th>
                        <th>Return Date</th>
                        <th>Status</th>
                        <th>Fine</th>
                        <th>Issued By</th>
                        <th>Returned To</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($borrowHistory as $record): ?>
                    <tr>
                        <td>
                            <a href="pupils_view.php?id=<?= $record['pupilID'] ?>">
                                <?= htmlspecialchars($record['pupilFirstName'] . ' ' . $record['pupilLastName']) ?>
                            </a>
                        </td>
                        <td><?= htmlspecialchars($record['admissionNumber']) ?></td>
                        <td><?= date('d M Y', strtotime($record['borrowDate'])) ?></td>
                        <td><?= date('d M Y', strtotime($record['dueDate'])) ?></td>
                        <td>
                            <?= $record['returnDate'] ? date('d M Y', strtotime($record['returnDate'])) : '-' ?>
                        </td>
                        <td>
                            <?php
                            $statusBadge = [
                                'borrowed' => 'bg-warning',
                                'returned' => 'bg-success',
                                'lost' => 'bg-danger'
                            ];
                            $badgeClass = $statusBadge[$record['status']] ?? 'bg-secondary';
                            ?>
                            <span class="badge <?= $badgeClass ?>">
                                <?= ucfirst($record['status']) ?>
                            </span>
                        </td>
                        <td>
                            <?= $record['fine'] > 0 ? 'K' . number_format($record['fine'], 2) : '-' ?>
                        </td>
                        <td><small class="text-muted"><?= htmlspecialchars($record['issuedByUsername']) ?></small></td>
                        <td><small class="text-muted"><?= $record['returnedToUsername'] ?? '-' ?></small></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
