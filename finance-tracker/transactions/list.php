<?php
/**
 * File: transactions/list.php
 * Purpose: Paginated, filterable transaction list
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/functions.php';
require_once __DIR__ . '/../models/Transaction.php';
require_once __DIR__ . '/../models/Category.php';

$userId   = (int)$_SESSION['user_id'];
$currency = userCurrency();

$txnModel = new Transaction($pdo);
$catModel = new Category($pdo);

// ── Filters ───────────────────────────────────────────────
$filters = [
    'type'        => $_GET['type']        ?? '',
    'category_id' => $_GET['category_id'] ?? '',
    'date_from'   => $_GET['date_from']   ?? '',
    'date_to'     => $_GET['date_to']     ?? '',
    'search'      => $_GET['search']      ?? '',
];

// ── Pagination ────────────────────────────────────────────
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = ROWS_PER_PAGE;
$total   = $txnModel->count($userId, $filters);
$pages   = (int)ceil($total / $perPage);
$page    = clamp($page, 1, max(1, $pages));

$transactions = $txnModel->getAll($userId, $filters, $page, $perPage);
$categories   = $catModel->getAllForUser($userId);

$pageTitle = 'Transactions';
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>

<div class="ft-layout">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="ft-main">
        <?php require_once __DIR__ . '/../includes/navbar.php'; ?>
        <div class="ft-content">
            <?php renderFlash(); ?>

            <div class="d-flex align-items-center justify-content-between mb-4">
                <h4 class="fw-bold mb-0">Transactions</h4>
                <a href="<?= APP_URL ?>/transactions/add.php" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-1"></i>Add Transaction
                </a>
            </div>

            <!-- Filter Form -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-2 align-items-end">
                        <div class="col-12 col-sm-6 col-md-3 col-lg-2">
                            <label class="form-label small fw-semibold">Type</label>
                            <select name="type" class="form-select form-select-sm">
                                <option value="">All Types</option>
                                <option value="income"  <?= $filters['type'] === 'income'  ? 'selected' : '' ?>>Income</option>
                                <option value="expense" <?= $filters['type'] === 'expense' ? 'selected' : '' ?>>Expense</option>
                            </select>
                        </div>
                        <div class="col-12 col-sm-6 col-md-3 col-lg-2">
                            <label class="form-label small fw-semibold">Category</label>
                            <select name="category_id" class="form-select form-select-sm">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"
                                        <?= (string)$filters['category_id'] === (string)$cat['id'] ? 'selected' : '' ?>>
                                        <?= e($cat['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-sm-6 col-md-2">
                            <label class="form-label small fw-semibold">From</label>
                            <input type="date" name="date_from" class="form-control form-control-sm"
                                   value="<?= e($filters['date_from']) ?>">
                        </div>
                        <div class="col-12 col-sm-6 col-md-2">
                            <label class="form-label small fw-semibold">To</label>
                            <input type="date" name="date_to" class="form-control form-control-sm"
                                   value="<?= e($filters['date_to']) ?>">
                        </div>
                        <div class="col-12 col-sm-6 col-md-3 col-lg-2">
                            <label class="form-label small fw-semibold">Search</label>
                            <input type="text" name="search" class="form-control form-control-sm"
                                   placeholder="Description…" value="<?= e($filters['search']) ?>">
                        </div>
                        <div class="col-12 col-sm-6 col-md-auto d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="bi bi-search me-1"></i>Filter
                            </button>
                            <a href="<?= APP_URL ?>/transactions/list.php" class="btn btn-outline-secondary btn-sm">
                                Reset
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Table -->
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <?php if (empty($transactions)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                            No transactions found.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Date</th>
                                        <th>Category</th>
                                        <th>Description</th>
                                        <th>Type</th>
                                        <th class="text-end">Amount</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($transactions as $i => $txn): ?>
                                    <tr>
                                        <td class="text-muted small">
                                            <?= ($page - 1) * $perPage + $i + 1 ?>
                                        </td>
                                        <td class="small">
                                            <?= date(DATE_FORMAT, strtotime($txn['date'])) ?>
                                        </td>
                                        <td>
                                            <span class="badge rounded-pill"
                                                  style="background-color:<?= e($txn['category_color']) ?>">
                                                <i class="<?= e($txn['category_icon']) ?> me-1"></i>
                                                <?= e($txn['category_name']) ?>
                                            </span>
                                        </td>
                                        <td class="text-truncate" style="max-width:220px;">
                                            <?= e($txn['description'] ?: '—') ?>
                                        </td>
                                        <td>
                                            <span class="badge <?= typeBadge($txn['type']) ?>">
                                                <?= ucfirst($txn['type']) ?>
                                            </span>
                                        </td>
                                        <td class="text-end fw-semibold <?= $txn['type'] === 'income' ? 'text-success' : 'text-danger' ?>">
                                            <?= signedAmount((float)$txn['amount'], $txn['type'], $currency) ?>
                                        </td>
                                        <td class="text-center">
                                            <a href="<?= APP_URL ?>/transactions/edit.php?id=<?= $txn['id'] ?>"
                                               class="btn btn-sm btn-outline-primary me-1" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-danger"
                                                    title="Delete"
                                                    onclick="confirmDelete(<?= $txn['id'] ?>, '<?= e(addslashes($txn['description'] ?: 'this transaction')) ?>')">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($pages > 1): ?>
                        <div class="d-flex align-items-center justify-content-between px-3 py-2 border-top">
                            <small class="text-muted">
                                Showing <?= ($page - 1) * $perPage + 1 ?>–<?= min($page * $perPage, $total) ?>
                                of <?= $total ?> records
                            </small>
                            <nav>
                                <ul class="pagination pagination-sm mb-0">
                                    <?php
                                    $q = $_GET;
                                    for ($p = 1; $p <= $pages; $p++):
                                        $q['page'] = $p;
                                        $href = '?' . http_build_query($q);
                                    ?>
                                    <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                                        <a class="page-link" href="<?= $href ?>"><?= $p ?></a>
                                    </li>
                                    <?php endfor; ?>
                                </ul>
                            </nav>
                        </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Delete confirmation modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger">
                    <i class="bi bi-exclamation-triangle me-2"></i>Delete Transaction
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete <strong id="deleteItemName"></strong>?
                This action cannot be undone.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteForm" method="POST" action="<?= APP_URL ?>/transactions/delete.php">
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
    document.getElementById('deleteId').value   = id;
    document.getElementById('deleteItemName').textContent = name;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
