<?php
/**
 * File: reports/filter.php
 * Purpose: Advanced filtered report (date range, type, category)
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
$categories = $catModel->getAllForUser($userId);

$filters = [
    'type'        => $_GET['type']        ?? '',
    'category_id' => $_GET['category_id'] ?? '',
    'date_from'   => $_GET['date_from']   ?? date('Y-m-01'),
    'date_to'     => $_GET['date_to']     ?? date('Y-m-d'),
    'search'      => $_GET['search']      ?? '',
];

$transactions = [];
$totalIncome  = 0.0;
$totalExpense = 0.0;

if (!empty($_GET)) {
    $transactions = $txnModel->getAll($userId, $filters, 1, 500);
    foreach ($transactions as $t) {
        if ($t['type'] === 'income')  $totalIncome  += (float)$t['amount'];
        if ($t['type'] === 'expense') $totalExpense += (float)$t['amount'];
    }
}

$pageTitle = 'Filter Report';
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>

<div class="ft-layout">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="ft-main">
        <?php require_once __DIR__ . '/../includes/navbar.php'; ?>
        <div class="ft-content">
            <?php renderFlash(); ?>

            <h4 class="fw-bold mb-4">Filter Report</h4>

            <!-- Filter form -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-12 col-sm-6 col-md-3">
                            <label class="form-label fw-semibold small">Type</label>
                            <select name="type" class="form-select form-select-sm">
                                <option value="">All</option>
                                <option value="income"  <?= $filters['type'] === 'income'  ? 'selected' : '' ?>>Income</option>
                                <option value="expense" <?= $filters['type'] === 'expense' ? 'selected' : '' ?>>Expense</option>
                            </select>
                        </div>
                        <div class="col-12 col-sm-6 col-md-3">
                            <label class="form-label fw-semibold small">Category</label>
                            <select name="category_id" class="form-select form-select-sm">
                                <option value="">All</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"
                                        <?= (string)$filters['category_id'] === (string)$cat['id'] ? 'selected' : '' ?>>
                                        <?= e($cat['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-sm-6 col-md-2">
                            <label class="form-label fw-semibold small">From</label>
                            <input type="date" name="date_from" class="form-control form-control-sm"
                                   value="<?= e($filters['date_from']) ?>">
                        </div>
                        <div class="col-12 col-sm-6 col-md-2">
                            <label class="form-label fw-semibold small">To</label>
                            <input type="date" name="date_to" class="form-control form-control-sm"
                                   value="<?= e($filters['date_to']) ?>">
                        </div>
                        <div class="col-12 col-md-2 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-primary btn-sm flex-fill">
                                <i class="bi bi-search me-1"></i>Run
                            </button>
                            <a href="<?= APP_URL ?>/reports/filter.php" class="btn btn-outline-secondary btn-sm">
                                Reset
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <?php if (!empty($_GET) && !empty($transactions)): ?>
            <!-- Summary -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="card border-0 shadow-sm text-center">
                        <div class="card-body py-3">
                            <p class="text-muted small mb-1">Records</p>
                            <h5 class="fw-bold"><?= count($transactions) ?></h5>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card border-0 shadow-sm text-center">
                        <div class="card-body py-3">
                            <p class="text-muted small mb-1">Income</p>
                            <h5 class="fw-bold text-success"><?= formatMoney($totalIncome, $currency) ?></h5>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card border-0 shadow-sm text-center">
                        <div class="card-body py-3">
                            <p class="text-muted small mb-1">Expenses</p>
                            <h5 class="fw-bold text-danger"><?= formatMoney($totalExpense, $currency) ?></h5>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card border-0 shadow-sm text-center">
                        <div class="card-body py-3">
                            <p class="text-muted small mb-1">Net</p>
                            <?php $net = $totalIncome - $totalExpense; ?>
                            <h5 class="fw-bold <?= $net >= 0 ? 'text-success' : 'text-danger' ?>">
                                <?= formatMoney($net, $currency) ?>
                            </h5>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Results table -->
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 small">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Category</th>
                                    <th>Description</th>
                                    <th>Type</th>
                                    <th class="text-end">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($transactions as $txn): ?>
                                <tr>
                                    <td><?= date(DATE_FORMAT, strtotime($txn['date'])) ?></td>
                                    <td>
                                        <span class="badge rounded-pill"
                                              style="background-color:<?= e($txn['category_color']) ?>">
                                            <?= e($txn['category_name']) ?>
                                        </span>
                                    </td>
                                    <td><?= e($txn['description'] ?: '—') ?></td>
                                    <td>
                                        <span class="badge <?= typeBadge($txn['type']) ?>">
                                            <?= ucfirst($txn['type']) ?>
                                        </span>
                                    </td>
                                    <td class="text-end fw-semibold <?= $txn['type'] === 'income' ? 'text-success' : 'text-danger' ?>">
                                        <?= signedAmount((float)$txn['amount'], $txn['type'], $currency) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php elseif (!empty($_GET)): ?>
                <div class="alert alert-info">No transactions found for the selected filters.</div>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
