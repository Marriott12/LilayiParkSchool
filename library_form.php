<?php
require_once 'includes/bootstrap.php';
require_once 'includes/Auth.php';
require_once 'includes/PermissionHelper.php';
require_once 'includes/CSRF.php';

Auth::requireLogin();
Auth::requireAnyRole(['admin']);

require_once 'modules/library/LibraryModel.php';

$libraryModel = new LibraryModel();

$bookID = $_GET['id'] ?? null;
$book = $bookID ? $libraryModel->find($bookID) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::validate($_POST['csrf_token']);
    
    $data = [
        'ISBN' => $_POST['ISBN'],
        'title' => $_POST['title'],
        'author' => $_POST['author'],
        'publisher' => $_POST['publisher'],
        'category' => $_POST['category'],
        'totalCopies' => (int)$_POST['totalCopies'],
        'availableCopies' => $bookID ? $book['availableCopies'] : (int)$_POST['totalCopies'],
        'shelfLocation' => $_POST['shelfLocation']
    ];
    
    if ($bookID) {
        // If totalCopies changed, adjust availableCopies
        if ($book['totalCopies'] != $data['totalCopies']) {
            $difference = $data['totalCopies'] - $book['totalCopies'];
            $data['availableCopies'] = max(0, $book['availableCopies'] + $difference);
        }
        
        $libraryModel->update($bookID, $data);
        Session::setFlash('success', 'Book updated successfully!');
    } else {
        $libraryModel->create($data);
        Session::setFlash('success', 'Book added successfully!');
    }
    header('Location: library_list.php');
    exit;
}

$pageTitle = $bookID ? 'Edit Book' : 'Add Book';
$currentPage = 'library';
require_once 'includes/header.php';
?>

<!-- Page Header -->
<div class="row mb-4">
    <div class="col-md-12">
        <h2 class="mb-0">
            <i class="bi bi-book me-2" style="color: #2d5016;"></i>
            <span style="color: #2d5016; font-weight: 600;"><?= $pageTitle ?></span>
        </h2>
        <p class="text-muted mt-1">Add or edit book information</p>
    </div>
</div>

<!-- Form Card -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <form method="POST">
            <?= CSRF::field(); ?>
            
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">
                        <i class="bi bi-upc-scan me-1"></i>ISBN <span class="text-danger">*</span>
                    </label>
                    <input type="text" class="form-control" name="ISBN" 
                           value="<?= htmlspecialchars($book['ISBN'] ?? '') ?>" 
                           placeholder="978-3-16-148410-0" required>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label fw-semibold">
                        <i class="bi bi-journal-text me-1"></i>Title <span class="text-danger">*</span>
                    </label>
                    <input type="text" class="form-control" name="title" 
                           value="<?= htmlspecialchars($book['title'] ?? '') ?>" 
                           placeholder="Book Title" required>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label fw-semibold">
                        <i class="bi bi-person me-1"></i>Author <span class="text-danger">*</span>
                    </label>
                    <input type="text" class="form-control" name="author" 
                           value="<?= htmlspecialchars($book['author'] ?? '') ?>" 
                           placeholder="Author Name" required>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label fw-semibold">
                        <i class="bi bi-building me-1"></i>Publisher <span class="text-danger">*</span>
                    </label>
                    <input type="text" class="form-control" name="publisher" 
                           value="<?= htmlspecialchars($book['publisher'] ?? '') ?>" 
                           placeholder="Publisher Name" required>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label fw-semibold">
                        <i class="bi bi-tag me-1"></i>Category <span class="text-danger">*</span>
                    </label>
                    <select class="form-select" name="category" required>
                        <option value="">Select Category</option>
                        <?php
                        $categories = ['Fiction', 'Non-Fiction', 'Science', 'Mathematics', 'History', 
                                     'Geography', 'Literature', 'Reference', 'Children', 'Educational'];
                        foreach ($categories as $cat):
                        ?>
                        <option value="<?= $cat ?>" <?= ($book && $book['category'] == $cat) ? 'selected' : '' ?>>
                            <?= $cat ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label fw-semibold">
                        <i class="bi bi-hash me-1"></i>Total Copies <span class="text-danger">*</span>
                    </label>
                    <input type="number" class="form-control" name="totalCopies" min="1"
                           value="<?= $book['totalCopies'] ?? '1' ?>" required>
                    <?php if ($bookID): ?>
                    <small class="text-muted">
                        Currently available: <?= $book['availableCopies'] ?> copies
                    </small>
                    <?php endif; ?>
                </div>
                
                <div class="col-md-12">
                    <label class="form-label fw-semibold">
                        <i class="bi bi-geo-alt me-1"></i>Shelf Location <span class="text-danger">*</span>
                    </label>
                    <input type="text" class="form-control" name="shelfLocation" 
                           value="<?= htmlspecialchars($book['shelfLocation'] ?? '') ?>" 
                           placeholder="e.g., Shelf A-3, Row 2" required>
                </div>
            </div>
            
            <div class="mt-4">
                <button type="submit" class="btn btn-lg shadow-sm" style="background-color: #2d5016; color: white;">
                    <i class="bi bi-check-circle me-1"></i>
                    <?= $bookID ? 'Update' : 'Add' ?> Book
                </button>
                <a href="library_list.php" class="btn btn-lg btn-outline-secondary">
                    <i class="bi bi-x-circle me-1"></i>Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
