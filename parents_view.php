<?php
require_once 'includes/bootstrap.php';

RBAC::requireAuth();
RBAC::requirePermission('parents', 'read');

require_once 'modules/parents/ParentModel.php';

$parentID = $_GET['id'] ?? null;
if (empty($parentID)) {
    header('Location: parents_list.php');
    exit;
}

$parentModel = new ParentModel();
$parent = $parentModel->getParentWithUser($parentID) ?: $parentModel->getById($parentID);
$children = method_exists($parentModel, 'getChildren') ? $parentModel->getChildren($parentID) : [];

$pageTitle = 'Parent Details';
$currentPage = 'parents';
require_once 'includes/header.php';
?>

<div class="mb-4">
    <a href="parents_list.php" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back to Parents
    </a>
</div>

<div class="card">
    <div class="card-header" style="background: linear-gradient(135deg, #2d5016 0%, #5cb85c 100%); color: white;">
        <h5 class="mb-0"><?= htmlspecialchars($parent['fName'] ?? $parent['firstName'] ?? '') . ' ' . htmlspecialchars($parent['lName'] ?? $parent['lastName'] ?? '') ?></h5>
    </div>
    <div class="card-body">
        <dl class="row">
            <dt class="col-sm-3">Email</dt>
            <dd class="col-sm-9"><?= htmlspecialchars($parent['userEmail'] ?? $parent['email'] ?? '') ?></dd>

            <dt class="col-sm-3">Phone</dt>
            <dd class="col-sm-9"><?= htmlspecialchars($parent['phoneNumber'] ?? $parent['phone'] ?? '') ?></dd>

            <dt class="col-sm-3">Children</dt>
            <dd class="col-sm-9">
                <?php if (!empty($children)): ?>
                    <ul class="mb-0">
                        <?php foreach ($children as $ch): ?>
                            <li><?= htmlspecialchars(($ch['fName'] ?? $ch['firstName'] ?? '') . ' ' . ($ch['lName'] ?? $ch['lastName'] ?? '')) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <em>No children linked</em>
                <?php endif; ?>
            </dd>
        </dl>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
