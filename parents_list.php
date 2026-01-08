<?php
require_once 'includes/bootstrap.php';

RBAC::requireAuth();
RBAC::requirePermission('parents', 'read');

require_once 'modules/parents/ParentModel.php';

$parentModel = new ParentModel();

// Handle search
$searchTerm = $_GET['search'] ?? '';
$parents = $searchTerm ? $parentModel->search($searchTerm) : $parentModel->getAllWithChildrenCount();

$pageTitle = 'Parents Management';
$currentPage = 'parents';
require_once 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-people"></i> Parents</h2>
    <?php if (RBAC::hasPermission(Session::getUserRole(), 'parents', 'create')): ?>
    <a href="parents_form.php" class="btn btn-sm" style="background-color: #2d5016; color: white;">
        <i class="bi bi-plus-circle"></i> Add New Parent
    </a>
    <?php endif; ?>
</div>

<!-- Search Bar -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-10">
                <input type="text" class="form-control" name="search" placeholder="Search by name, email or phone..." value="<?= htmlspecialchars($searchTerm) ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn w-100" style="background-color: #2d5016; color: white;">
                    <i class="bi bi-search"></i> Search
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Parents Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead style="background-color: #f8f9fa;">
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Address</th>
                        <th>Children</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($parents)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4">
                            <i class="bi bi-inbox" style="font-size: 3rem; opacity: 0.3;"></i>
                            <p class="text-muted mt-2">No parents found</p>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($parents as $parent): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($parent['firstName'] . ' ' . $parent['lastName']) ?></strong>
                        </td>
                        <td><?= htmlspecialchars($parent['email']) ?></td>
                        <td><?= htmlspecialchars($parent['phoneNumber']) ?></td>
                        <td><?= htmlspecialchars($parent['address'] ?? 'N/A') ?></td>
                        <td>
                            <span class="badge" style="background-color: #f0ad4e;">
                                <?= $parent['childrenCount'] ?? 0 ?> child(ren)
                            </span>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="parents_view.php?id=<?= $parent['parentID'] ?>" class="btn btn-info btn-sm" title="View">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <?php if (RBAC::hasPermission(Session::getUserRole(), 'parents', 'update')): ?>
                                <a href="parents_form.php?id=<?= $parent['parentID'] ?>" class="btn btn-warning btn-sm" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
