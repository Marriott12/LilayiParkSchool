<?php
require_once 'includes/bootstrap.php';

RBAC::requireAuth();
RBAC::requirePermission('fees', 'read');

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
    <?php if (RBAC::hasPermission(Session::getUserRole(), 'fees', 'create')): ?>
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
                        <td><?= htmlspecialchars($fee['feeType'] ?? 'Tuition') ?></td>
                        <td>
                            <strong style="color: #2d5016;">
                                K <?= number_format($fee['amount'], 2) ?>
                            </strong>
                        </td>
                        <td><?= $fee['dueDate'] ? date('M d, Y', strtotime($fee['dueDate'])) : 'N/A' ?></td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="fees_view.php?id=<?= $fee['feeID'] ?>" class="btn btn-info btn-sm" title="View">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <?php if (RBAC::hasPermission(Session::getUserRole(), 'fees', 'update')): ?>
                                <a href="fees_form.php?id=<?= $fee['feeID'] ?>" class="btn btn-warning btn-sm" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <?php endif; ?>
                                <?php if (RBAC::hasPermission(Session::getUserRole(), 'fees', 'delete')): ?>
                                <a href="delete.php?module=fees&id=<?= $fee['feeID'] ?>" class="btn btn-danger" title="Delete" onclick="return confirm('Are you sure?')">
                                    <i class="bi bi-trash"></i>
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
