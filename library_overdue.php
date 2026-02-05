<?php
require_once 'includes/bootstrap.php';
require_once 'includes/Auth.php';
require_once 'includes/PermissionHelper.php';

Auth::requireLogin();
Auth::requireAnyRole(['admin', 'teacher']);

require_once 'modules/library/LibraryModel.php';
require_once 'modules/settings/SettingsModel.php';

$libraryModel = new LibraryModel();
$settingsModel = new SettingsModel();

$overdueBooks = $libraryModel->getOverdueBooks();

$pageTitle = 'Overdue Books';
$currentPage = 'library';
require_once 'includes/header.php';
?>

<!-- Page Header -->
<div class="row mb-4">
    <div class="col-md-12">
        <h2 class="mb-0">
            <i class="bi bi-exclamation-triangle me-2 text-danger"></i>
            <span style="color: #2d5016; font-weight: 600;">Overdue Books</span>
        </h2>
        <p class="text-muted mt-1">Manage overdue library books and fines</p>
    </div>
</div>

<!-- Statistics -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <i class="bi bi-book-fill text-danger" style="font-size: 2.5rem;"></i>
                <h3 class="mt-2 mb-0"><?= count($overdueBooks) ?></h3>
                <p class="text-muted mb-0">Overdue Books</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <i class="bi bi-cash-coin text-warning" style="font-size: 2.5rem;"></i>
                <h3 class="mt-2 mb-0">
                    K<?php 
                    $totalFines = 0;
                    $finePerDay = $settingsModel->getSetting('library_fine_per_day', 0.5);
                    foreach ($overdueBooks as $book) {
                        $totalFines += $book['daysOverdue'] * $finePerDay;
                    }
                    echo number_format($totalFines, 2);
                    ?>
                </h3>
                <p class="text-muted mb-0">Total Potential Fines</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <i class="bi bi-currency-exchange text-success" style="font-size: 2.5rem;"></i>
                <h3 class="mt-2 mb-0">K<?= number_format($finePerDay, 2) ?></h3>
                <p class="text-muted mb-0">Fine Per Day</p>
            </div>
        </div>
    </div>
</div>

<!-- Overdue Books Table -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom">
        <h5 class="mb-0">
            <i class="bi bi-list-ul me-2"></i>Overdue Books List
        </h5>
    </div>
    <div class="card-body p-0">
        <?php if (empty($overdueBooks)): ?>
        <div class="text-center py-5">
            <i class="bi bi-check-circle text-success" style="font-size: 4rem;"></i>
            <p class="text-muted mt-3">No overdue books! All books are returned on time.</p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>Book Title</th>
                        <th>ISBN</th>
                        <th>Pupil</th>
                        <th>Pupil ID</th>
                        <th>Borrow Date</th>
                        <th>Due Date</th>
                        <th>Days Overdue</th>
                        <th>Fine Amount</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($overdueBooks as $book): ?>
                    <?php
                    $fine = $book['daysOverdue'] * $finePerDay;
                    ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($book['title']) ?></strong>
                            <br><small class="text-muted"><?= htmlspecialchars($book['author']) ?></small>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark"><?= htmlspecialchars($book['ISBN']) ?></span>
                        </td>
                        <td>
                            <a href="pupils_view.php?id=<?= $book['pupilID'] ?>">
                                <?= htmlspecialchars($book['pupilFirstName'] . ' ' . $book['pupilLastName']) ?>
                            </a>
                        </td>
                        <td><?= htmlspecialchars($book['pupilID']) ?></td>
                        <td><?= date('d M Y', strtotime($book['borrowDate'])) ?></td>
                        <td><?= date('d M Y', strtotime($book['dueDate'])) ?></td>
                        <td>
                            <span class="badge bg-danger">
                                <?= $book['daysOverdue'] ?> days
                            </span>
                        </td>
                        <td>
                            <strong class="text-danger">K<?= number_format($fine, 2) ?></strong>
                        </td>
                        <td>
                            <a href="library_borrow.php?pupil=<?= $book['pupilID'] ?>" 
                               class="btn btn-sm btn-outline-primary" title="Process Return">
                                <i class="bi bi-arrow-left-circle"></i> Return
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="bg-light">
                    <tr>
                        <th colspan="7" class="text-end">Total Fines:</th>
                        <th colspan="2">
                            <strong class="text-danger">K<?= number_format($totalFines, 2) ?></strong>
                        </th>
                    </tr>
                </tfoot>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="text-center mt-4">
    <a href="library_list.php" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Back to Library
    </a>
</div>

<?php require_once 'includes/footer.php'; ?>
