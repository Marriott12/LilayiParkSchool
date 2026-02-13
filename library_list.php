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
require_once 'includes/Pagination.php';

$libraryModel = new LibraryModel();

// Get search and pagination
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 15;
$offset = ($page - 1) * $limit;

// Get books
if ($search) {
    $books = $libraryModel->search($search);
    $totalRecords = count($books);
    $books = array_slice($books, $offset, $limit);
} else {
    $books = $libraryModel->getAllWithAvailability($limit, $offset);
    $totalRecords = $libraryModel->count();
}

$pagination = new Pagination($totalRecords, $limit, $page);

$pageTitle = 'Library Management';
$currentPage = 'library';
require_once 'includes/header.php';
?>

<!-- Page Header -->
<div class="row mb-4">
    <div class="col-md-8">
        <h2 class="mb-0">
            <i class="bi bi-book me-2" style="color: #2d5016;"></i>
            <span style="color: #2d5016; font-weight: 600;">Library Management</span>
        </h2>
        <p class="text-muted mt-1">Manage library books and borrowing</p>
    </div>
    <div class="col-md-4 text-end align-self-center">
        <?php if (Auth::hasRole('admin')): ?>
        <a href="library_form.php" class="btn btn-lg shadow-sm" style="background-color: #2d5016; color: white;">
            <i class="bi bi-plus-circle me-1"></i> Add Book
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- Search and Filter -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-12">
                <div class="input-group input-group-lg">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" class="form-control border-start-0 ps-0" id="liveSearchInput" 
                           value="<?= htmlspecialchars($search) ?>" 
                           placeholder="Start typing to search by title, author, ISBN, or category..."
                           autocomplete="off">
                </div>
                <small class="text-muted">Results update as you type</small>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<?php if (Auth::hasRole('admin') || Auth::hasRole('teacher')): ?>
<div class="row mb-4">
    <div class="col-md-6">
        <a href="library_borrow.php" class="card border-0 shadow-sm text-decoration-none">
            <div class="card-body text-center">
                <i class="bi bi-arrow-right-circle" style="font-size: 2rem; color: #2d5016;"></i>
                <h5 class="mt-2" style="color: #2d5016;">Issue/Return Books</h5>
                <p class="text-muted mb-0">Process book borrowing and returns</p>
            </div>
        </a>
    </div>
    <div class="col-md-6">
        <a href="library_overdue.php" class="card border-0 shadow-sm text-decoration-none">
            <div class="card-body text-center">
                <i class="bi bi-exclamation-triangle" style="font-size: 2rem; color: #dc3545;"></i>
                <h5 class="mt-2 text-danger">Overdue Books</h5>
                <p class="text-muted mb-0">View and manage overdue books</p>
            </div>
        </a>
    </div>
</div>
<?php endif; ?>

<!-- Books Table -->
<div class="card border-0 shadow-sm" id="resultsTable">
    <div class="card-header bg-white border-bottom">
        <h5 class="mb-0">
            <i class="bi bi-list-ul me-2"></i>Books Catalog
        </h5>
    </div>
    <div class="card-body p-0">
        <?php if (empty($books)): ?>
        <div class="text-center py-5">
            <i class="bi bi-inbox" style="font-size: 4rem; color: #ccc;"></i>
            <p class="text-muted mt-3">No books found</p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>ISBN</th>
                        <th>Title</th>
                        <th>Author</th>
                        <th>Category</th>
                        <th>Publisher</th>
                        <th>Copies</th>
                        <th>Available</th>
                        <th>Location</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($books as $book): ?>
                    <tr>
                        <td>
                            <span class="badge bg-light text-dark"><?= htmlspecialchars($book['ISBN']) ?></span>
                        </td>
                        <td>
                            <strong><?= htmlspecialchars($book['title']) ?></strong>
                        </td>
                        <td><?= htmlspecialchars($book['author']) ?></td>
                        <td>
                            <span class="badge" style="background-color: #e8f5e9; color: #2d5016;">
                                <?= htmlspecialchars($book['category']) ?>
                            </span>
                        </td>
                        <td class="text-muted"><?= htmlspecialchars($book['publisher']) ?></td>
                        <td>
                            <span class="badge bg-secondary"><?= $book['totalCopies'] ?></span>
                        </td>
                        <td>
                            <?php 
                            $available = $book['availableCopies'];
                            $badgeClass = $available > 0 ? 'bg-success' : 'bg-danger';
                            ?>
                            <span class="badge <?= $badgeClass ?>"><?= $available ?></span>
                        </td>
                        <td>
                            <small class="text-muted">
                                <i class="bi bi-geo-alt"></i> <?= htmlspecialchars($book['shelfLocation']) ?>
                            </small>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="library_view.php?id=<?= $book['bookID'] ?>" 
                                   class="btn btn-outline-primary" title="View">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <?php if (Auth::hasRole('admin')): ?>
                                <a href="library_form.php?id=<?= $book['bookID'] ?>" 
                                   class="btn btn-outline-warning" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="delete.php?table=books&id=<?= $book['bookID'] ?>&redirect=library_list.php" 
                                   class="btn btn-outline-danger" title="Delete"
                                   onclick="return confirm('Are you sure you want to delete this book?')">
                                    <i class="bi bi-trash"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($pagination->getTotalPages() > 1): ?>
        <div class="card-footer bg-white">
            <?= $pagination->render() ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script src="assets/js/live-search.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    new LiveSearch({
        searchInput: '#liveSearchInput',
        resultsContainer: '#resultsTable',
        apiEndpoint: 'api/search_library.php',
        emptyMessage: 'No books found',
        debounceDelay: 300,
        renderRow: function(book) {
            const available = book.availableCopies;
            const badgeClass = available > 0 ? 'bg-success' : 'bg-danger';
            const isAdmin = <?= Auth::hasRole('admin') ? 'true' : 'false' ?>;
            
            return `
                <tr>
                    <td>
                        <span class="badge bg-light text-dark">${escapeHtml(book.ISBN)}</span>
                    </td>
                    <td>
                        <strong>${escapeHtml(book.title)}</strong>
                    </td>
                    <td>${escapeHtml(book.author)}</td>
                    <td>
                        <span class="badge" style="background-color: #e8f5e9; color: #2d5016;">
                            ${escapeHtml(book.category)}
                        </span>
                    </td>
                    <td class="text-muted">${escapeHtml(book.publisher)}</td>
                    <td>
                        <span class="badge bg-secondary">${book.totalCopies}</span>
                    </td>
                    <td>
                        <span class="badge ${badgeClass}">${available}</span>
                    </td>
                    <td>
                        <small class="text-muted">
                            <i class="bi bi-geo-alt"></i> ${escapeHtml(book.shelfLocation)}
                        </small>
                    </td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <a href="library_view.php?id=${book.bookID}" 
                               class="btn btn-outline-primary" title="View">
                                <i class="bi bi-eye"></i>
                            </a>
                            ${isAdmin ? `
                            <a href="library_form.php?id=${book.bookID}" 
                               class="btn btn-outline-warning" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <a href="delete.php?table=books&id=${book.bookID}&redirect=library_list.php" 
                               class="btn btn-outline-danger" title="Delete"
                               onclick="return confirm('Are you sure you want to delete this book?')">
                                <i class="bi bi-trash"></i>
                            </a>
                            ` : ''}
                        </div>
                    </td>
                </tr>
            `;
        }
    });
    
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
