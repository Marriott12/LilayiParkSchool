<?php
require_once 'includes/bootstrap.php';
require_once 'includes/Auth.php';
require_once 'includes/PermissionHelper.php';

Auth::requireLogin();

require_once 'modules/roles/RolesModel.php';
$rolesModel = new RolesModel();
if (!$rolesModel->userHasPermission(Auth::id(), 'view_fees')) {
    Session::setFlash('error', 'You do not have permission to view fees.');
    header('Location: /LilayiParkSchool/403.php');
    exit;
}

require_once 'modules/fees/FeesModel.php';

$feesModel = new FeesModel();

// Pagination
$page = $_GET['page'] ?? 1;
$perPage = 20;
$totalRecords = $feesModel->count();
$pagination = new Pagination($totalRecords, $perPage, $page);
$fees = $feesModel->getAllWithClass($pagination->getLimit(), $pagination->getOffset());

$pageTitle = 'Fees Management';
$currentPage = 'fees';
require_once 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-cash-coin"></i> Fee Structure</h2>
    <?php if (PermissionHelper::canManage('fees')): ?>
    <a href="fees_form.php" class="btn btn-sm" style="background-color: #2d5016; color: white;">
        <i class="bi bi-plus-circle"></i> Add New Fee
    </a>
    <?php endif; ?>
</div>

<!-- Fees Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead style="background-color: #f8f9fa;">
                    <tr>
                        <th>Class</th>
                        <th>Term</th>
                        <th>Fee Type</th>
                        <th>Amount (ZMW)</th>
                        <th>Due Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($fees)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4">
                            <i class="bi bi-inbox" style="font-size: 3rem; opacity: 0.3;"></i>
                            <p class="text-muted mt-2">No fees configured</p>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($fees as $fee): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($fee['className'] ?? 'All Classes') ?></strong></td>
                        <td>
                            <span class="badge" style="background-color: #f0ad4e;">
                                Term <?= $fee['term'] ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($fee['year']) ?></td>
                        <td>
                            <strong style="color: #2d5016;">
                                K <?= number_format($fee['feeAmt'], 2) ?>
                            </strong>
                        </td>
                        <td><?= date('M d, Y', strtotime($fee['createdAt'])) ?></td>
                        <td>
                            <div class="btn-group btn-group-sm" role="group">
                                <a href="fees_view.php?id=<?= $fee['feeID'] ?>" class="btn btn-outline-info btn-sm">
                                    <i class="bi bi-eye"></i> View
                                </a>
                                <?php if (PermissionHelper::canManage('fees')): ?>
                                <a href="fees_form.php?id=<?= $fee['feeID'] ?>" class="btn btn-outline-warning btn-sm">
                                    <i class="bi bi-pencil"></i> Edit
                                </a>
                                <a href="delete.php?module=fees&id=<?= $fee['feeID'] ?>" 
                                   class="btn btn-outline-danger btn-sm" 
                                   onclick="return confirm('Are you sure you want to delete this fee?');">
                                    <i class="bi bi-trash"></i> Delete
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
    <?php if ($pagination->hasPages()): ?>
    <div class="card-footer">
        <?= $pagination->render() ?>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
