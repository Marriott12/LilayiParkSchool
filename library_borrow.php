<?php
require_once 'includes/bootstrap.php';
require_once 'includes/Auth.php';
require_once 'includes/PermissionHelper.php';
require_once 'includes/CSRF.php';

Auth::requireLogin();
Auth::requireAnyRole(['admin', 'teacher']);

require_once 'modules/library/LibraryModel.php';
require_once 'modules/pupils/PupilModel.php';
require_once 'modules/settings/SettingsModel.php';

$libraryModel = new LibraryModel();
$pupilModel = new PupilModel();
$settingsModel = new SettingsModel();

$action = $_GET['action'] ?? 'borrow';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::validate($_POST['csrf_token']);
    
    if ($_POST['action'] === 'borrow') {
        $bookID = $_POST['bookID'];
        $pupilID = $_POST['pupilID'];
        $borrowDays = (int)$settingsModel->getSetting('library_borrow_days', 14);
        $dueDate = date('Y-m-d', strtotime("+$borrowDays days"));
        
        $result = $libraryModel->borrowBook($bookID, $pupilID, $dueDate, Auth::id());
        
        if ($result === true) {
            Session::setFlash('success', 'Book issued successfully!');
        } else {
            Session::setFlash('error', $result);
        }
    } elseif ($_POST['action'] === 'return') {
        $borrowID = $_POST['borrowID'];
        $fine = $_POST['fine'] ?? 0;
        
        $result = $libraryModel->returnBook($borrowID, Auth::id(), $fine);
        
        if ($result === true) {
            $msg = 'Book returned successfully!';
            if ($fine > 0) {
                $msg .= ' Fine charged: K' . number_format($fine, 2);
            }
            Session::setFlash('success', $msg);
        } else {
            Session::setFlash('error', $result);
        }
    }
    
    header('Location: library_borrow.php');
    exit;
}

// Get pupil's borrowed books if viewing for specific pupil
$pupilID = $_GET['pupil'] ?? null;
$borrowedBooks = [];
if ($pupilID) {
    $borrowedBooks = $libraryModel->getBorrowedByPupil($pupilID);
}

// Get all pupils for selection
$pupils = $pupilModel->all();

// Get all available books
$availableBooks = $libraryModel->getAllWithAvailability();

$pageTitle = 'Issue/Return Books';
$currentPage = 'library';
require_once 'includes/header.php';
?>

<!-- Page Header -->
<div class="row mb-4">
    <div class="col-md-12">
        <h2 class="mb-0">
            <i class="bi bi-arrow-left-right me-2" style="color: #2d5016;"></i>
            <span style="color: #2d5016; font-weight: 600;">Issue / Return Books</span>
        </h2>
        <p class="text-muted mt-1">Process book borrowing and returns</p>
    </div>
</div>

<div class="row">
    <!-- Issue Book -->
    <div class="col-md-6 mb-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header" style="background-color: #2d5016; color: white;">
                <h5 class="mb-0"><i class="bi bi-arrow-right-circle me-2"></i>Issue Book</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <?= CSRF::field(); ?>
                    <input type="hidden" name="action" value="borrow">
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-person me-1"></i>Select Pupil <span class="text-danger">*</span>
                        </label>
                        <select class="form-select" name="pupilID" id="pupilSelect" required>
                            <option value="">Choose a pupil...</option>
                            <?php foreach ($pupils as $pupil): ?>
                            <option value="<?= $pupil['pupilID'] ?>">
                                <?= htmlspecialchars($pupil['firstName'] . ' ' . $pupil['lastName']) ?> 
                                (<?= htmlspecialchars($pupil['admissionNumber']) ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-book me-1"></i>Select Book <span class="text-danger">*</span>
                        </label>
                        <select class="form-select" name="bookID" required>
                            <option value="">Choose a book...</option>
                            <?php foreach ($availableBooks as $book): ?>
                            <?php if ($book['availableCopies'] > 0): ?>
                            <option value="<?= $book['bookID'] ?>">
                                <?= htmlspecialchars($book['title']) ?> by <?= htmlspecialchars($book['author']) ?>
                                (<?= $book['availableCopies'] ?> available)
                            </option>
                            <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        <small>
                            Due date: <?= date('d M Y', strtotime('+' . $settingsModel->getSetting('library_borrow_days', 14) . ' days')) ?>
                            <br>Max books per pupil: <?= $settingsModel->getSetting('library_max_books', 3) ?>
                        </small>
                    </div>
                    
                    <button type="submit" class="btn w-100" style="background-color: #2d5016; color: white;">
                        <i class="bi bi-check-circle me-1"></i>Issue Book
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Return Book -->
    <div class="col-md-6 mb-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bi bi-arrow-left-circle me-2"></i>Return Book</h5>
            </div>
            <div class="card-body">
                <form method="GET" class="mb-3">
                    <div class="input-group">
                        <select class="form-select" name="pupil" id="pupilSelectReturn">
                            <option value="">Select pupil to view borrowed books...</option>
                            <?php foreach ($pupils as $pupil): ?>
                            <option value="<?= $pupil['pupilID'] ?>" <?= $pupilID == $pupil['pupilID'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($pupil['firstName'] . ' ' . $pupil['lastName']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-outline-secondary">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </form>
                
                <?php if ($pupilID && !empty($borrowedBooks)): ?>
                <div class="list-group">
                    <?php foreach ($borrowedBooks as $borrow): ?>
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <strong class="d-block"><?= htmlspecialchars($borrow['title']) ?></strong>
                                <small class="text-muted">
                                    Borrowed: <?= date('d M Y', strtotime($borrow['borrowDate'])) ?><br>
                                    Due: <?= date('d M Y', strtotime($borrow['dueDate'])) ?>
                                </small>
                                <?php if ($borrow['daysOverdue'] > 0): ?>
                                <br><span class="badge bg-danger">
                                    Overdue by <?= $borrow['daysOverdue'] ?> days
                                </span>
                                <?php endif; ?>
                            </div>
                            <form method="POST" class="d-inline">
                                <?= CSRF::field(); ?>
                                <input type="hidden" name="action" value="return">
                                <input type="hidden" name="borrowID" value="<?= $borrow['borrowID'] ?>">
                                <?php if ($borrow['daysOverdue'] > 0): ?>
                                <input type="hidden" name="fine" value="<?= $borrow['daysOverdue'] * $settingsModel->getSetting('library_fine_per_day', 0.5) ?>">
                                <?php endif; ?>
                                <button type="submit" class="btn btn-sm btn-success">
                                    <i class="bi bi-check"></i> Return
                                </button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php elseif ($pupilID): ?>
                <div class="alert alert-info mb-0">
                    <i class="bi bi-info-circle me-2"></i>No borrowed books found for this pupil.
                </div>
                <?php else: ?>
                <div class="alert alert-secondary mb-0">
                    <i class="bi bi-arrow-up me-2"></i>Select a pupil to view their borrowed books.
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="text-center mt-3">
    <a href="library_list.php" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Back to Library
    </a>
</div>

<?php require_once 'includes/footer.php'; ?>
