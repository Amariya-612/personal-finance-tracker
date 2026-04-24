<?php
/**
 * File: categories/list.php
 * Purpose: List all categories for the logged-in user
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/functions.php';
require_once __DIR__ . '/../models/Category.php';

$userId     = (int)$_SESSION['user_id'];
$catModel   = new Category($pdo);
$categories = $catModel->getAllForUser($userId);

$expense = array_filter($categories, fn($c) => $c['type'] === 'expense');
$income  = array_filter($categories, fn($c) => $c['type'] === 'income');

$pageTitle = 'Categories';
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>

<div class="ft-layout">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="ft-main">
        <?php require_once __DIR__ . '/../includes/navbar.php'; ?>
        <div class="ft-content">
            <?php renderFlash(); ?>

            <div class="d-flex align-items-center justify-content-between mb-4">
                <h4 class="fw-bold mb-0">Categories</h4>
                <a href="<?= APP_URL ?>/categories/add.php" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-1"></i>Add Category
                </a>
            </div>

            <?php foreach (['expense' => $expense, 'income' => $income] as $type => $cats): ?>
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent border-0 pt-3">
                    <h6 class="fw-bold mb-0">
                        <span class="badge <?= $type === 'income' ? 'bg-success' : 'bg-danger' ?> me-2">
                            <?= ucfirst($type) ?>
                        </span>
                        Categories
                    </h6>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($cats)): ?>
                        <p class="text-muted text-center py-4">No <?= $type ?> categories yet.</p>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Icon</th>
                                    <th>Name</th>
                                    <th>Color</th>
                                    <th>Scope</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($cats as $cat): ?>
                                <tr>
                                    <td>
                                        <span class="fs-5" style="color:<?= e($cat['color']) ?>">
                                            <i class="<?= e($cat['icon']) ?>"></i>
                                        </span>
                                    </td>
                                    <td class="fw-semibold"><?= e($cat['name']) ?></td>
                                    <td>
                                        <span class="d-inline-block rounded-circle border"
                                              style="width:22px;height:22px;background:<?= e($cat['color']) ?>"></span>
                                        <code class="ms-1 small"><?= e($cat['color']) ?></code>
                                    </td>
                                    <td>
                                        <?php if ($cat['user_id'] === null): ?>
                                            <span class="badge bg-secondary">Global</span>
                                        <?php else: ?>
                                            <span class="badge bg-info text-dark">Custom</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($cat['user_id'] !== null): ?>
                                            <a href="<?= APP_URL ?>/categories/edit.php?id=<?= $cat['id'] ?>"
                                               class="btn btn-sm btn-outline-primary me-1">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-danger"
                                                    onclick="confirmDelete(<?= $cat['id'] ?>, '<?= e(addslashes($cat['name'])) ?>')">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        <?php else: ?>
                                            <span class="text-muted small">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>

        </div>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger">Delete Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Delete category <strong id="deleteItemName"></strong>?
                This cannot be undone and will fail if the category is in use.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteForm" method="POST" action="<?= APP_URL ?>/categories/delete.php">
                    <?= csrfField() ?>
                    <input type="hidden" name="id" id="deleteId">
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(id, name) {
    document.getElementById('deleteId').value = id;
    document.getElementById('deleteItemName').textContent = name;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
