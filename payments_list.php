<?php
require_once 'includes/bootstrap.php';

RBAC::requireAuth();
RBAC::requirePermission('payments', 'read');

require_once 'modules/payments/PaymentModel.php';

$paymentModel = new PaymentModel();

// Filter by parent for parent role
if (Session::getUserRole() === 'parent') {
    // Assuming parent has parentID linked to user
    $parentID = Session::get('parent_id');
    $payments = $paymentModel->getPaymentsByParent($parentID);
} else {
    $payments = $paymentModel->getAllWithDetails();
}

$pageTitle = 'Payments Management';
$currentPage = 'payments';
require_once 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-credit-card"></i> Payments</h2>
    <?php if (RBAC::hasPermission(Session::getUserRole(), 'payments', 'create')): ?>
    <a href="payments_form.php" class="btn btn-sm" style="background-color: #2d5016; color: white;">
        <i class="bi bi-plus-circle"></i> Record Payment
    </a>
    <?php endif; ?>
</div>

<!-- Payments Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead style="background-color: #f8f9fa;">
                    <tr>
                        <th>Date</th>
                        <th>Pupil</th>
                        <th>Parent</th>
                        <th>Term</th>
                        <th>Amount (ZMW)</th>
                        <th>Method</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($payments)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-4">
                            <i class="bi bi-inbox" style="font-size: 3rem; opacity: 0.3;"></i>
                            <p class="text-muted mt-2">No payments recorded</p>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($payments as $payment): ?>
                    <tr>
                        <td><?= date('M d, Y', strtotime($payment['paymentDate'])) ?></td>
                        <td>
                            <strong><?= htmlspecialchars(($payment['pupilFirstName'] ?? '') . ' ' . ($payment['pupilLastName'] ?? '')) ?></strong>
                        </td>
                        <td><?= htmlspecialchars(($payment['parentFirstName'] ?? '') . ' ' . ($payment['parentLastName'] ?? '')) ?></td>
                        <td>
                            <span class="badge" style="background-color: #f0ad4e;">
                                Term <?= $payment['term'] ?? 'N/A' ?>
                            </span>
                        </td>
                        <td>
                            <strong style="color: #2d5016;">
                                K <?= number_format($payment['amount'], 2) ?>
                            </strong>
                        </td>
                        <td><?= htmlspecialchars($payment['paymentMethod'] ?? 'Cash') ?></td>
                        <td>
                            <span class="badge bg-success">Paid</span>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="payments_view.php?id=<?= $payment['paymentID'] ?>" class="btn btn-info btn-sm" title="View Receipt">
                                    <i class="bi bi-receipt"></i>
                                </a>
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
