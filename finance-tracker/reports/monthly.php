<?php
/**
 * File: reports/monthly.php
 * Purpose: Monthly income vs expense report with charts
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/functions.php';
require_once __DIR__ . '/../models/Transaction.php';

$userId   = (int)$_SESSION['user_id'];
$currency = userCurrency();

$month = (int)($_GET['month'] ?? date('m'));
$year  = (int)($_GET['year']  ?? date('Y'));
$month = clamp($month, 1, 12);

$txnModel = new Transaction($pdo);
$summary  = $txnModel->monthlySummary($userId, $month, $year);
$byCategory = $txnModel->expenseByCategory($userId, $month, $year);

// All transactions for the month
$filters = [
    'date_from' => "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-01",
    'date_to'   => date('Y-m-t', mktime(0, 0, 0, $month, 1, $year)),
];
$transactions = $txnModel->getAll($userId, $filters, 1, 200);

$income  = $summary['income'];
$expense = $summary['expense'];
$balance = $income - $expense;

$pageTitle  = 'Monthly Report';
$loadCharts = true;
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>

<div class="ft-layout">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="ft-main">
        <?php require_once __DIR__ . '/../includes/navbar.php'; ?>
        <div class="ft-content">
            <?php renderFlash(); ?>

            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h4 class="fw-bold mb-0">Monthly Report</h4>
                    <small class="text-muted"><?= monthNames()[$month] ?> <?= $year ?></small>
                </div>
                <a href="<?= APP_URL ?>/reports/export.php?month=<?= $month ?>&year=<?= $year ?>"
                   class="btn btn-outline-success btn-sm">
                    <i class="bi bi-download me-1"></i>Export CSV
                </a>
            </div>

            <!-- Month selector -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body py-2">
                    <form method="GET" class="d-flex align-items-center gap-2 flex-wrap">
                        <select name="month" class="form-select form-select-sm" style="width:auto;">
                            <?php foreach (monthNames() as $m => $name): ?>
                                <option value="<?= $m ?>" <?= $m === $month ? 'selected' : '' ?>>
                                    <?= $name ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <select name="year" class="form-select form-select-sm" style="width:auto;">
                            <?php foreach (yearRange() as $y): ?>
                                <option value="<?= $y ?>" <?= $y === $year ? 'selected' : '' ?>>
                                    <?= $y ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-sm btn-primary">View</button>
                    </form>
                </div>
            </div>

            <!-- Summary cards -->
            <div class="row g-3 mb-4">
                <div class="col-12 col-sm-4">
                    <div class="card border-0 shadow-sm text-center">
                        <div class="card-body">
                            <p class="text-muted small mb-1">Income</p>
                            <h4 class="text-success fw-bold"><?= formatMoney($income, $currency) ?></h4>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-4">
                    <div class="card border-0 shadow-sm text-center">
                        <div class="card-body">
                            <p class="text-muted small mb-1">Expenses</p>
                            <h4 class="text-danger fw-bold"><?= formatMoney($expense, $currency) ?></h4>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-4">
                    <div class="card border-0 shadow-sm text-center">
                        <div class="card-body">
                            <p class="text-muted small mb-1">Net Balance</p>
                            <h4 class="fw-bold <?= $balance >= 0 ? 'text-success' : 'text-danger' ?>">
                                <?= formatMoney($balance, $currency) ?>
                            </h4>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts -->
            <div class="row g-3 mb-4">
                <div class="col-12 col-lg-5">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-transparent border-0 pt-3">
                            <h6 class="fw-bold mb-0">Expenses by Category</h6>
                        </div>
                        <div class="card-body d-flex align-items-center justify-content-center">
                            <div style="max-width:300px;width:100%;">
                                <canvas id="expensePieChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-lg-7">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-transparent border-0 pt-3">
                            <h6 class="fw-bold mb-0">Category Breakdown</h6>
                        </div>
                        <div class="card-body">
                            <?php if (empty($byCategory)): ?>
                                <p class="text-muted text-center py-4">No expense data.</p>
                            <?php else: ?>
                                <?php foreach ($byCategory as $cat):
                                    $pct = percentage((float)$cat['total'], $expense);
                                ?>
                                <div class="mb-2">
                                    <div class="d-flex justify-content-between small mb-1">
                                        <span><?= e($cat['name']) ?></span>
                                        <span><?= formatMoney((float)$cat['total'], $currency) ?> (<?= $pct ?>%)</span>
                                    </div>
                                    <div class="progress" style="height:6px;">
                                        <div class="progress-bar"
                                             style="width:<?= $pct ?>%;background-color:<?= e($cat['color']) ?>">
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Transaction table -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0 pt-3">
                    <h6 class="fw-bold mb-0">All Transactions — <?= monthNames()[$month] ?> <?= $year ?></h6>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($transactions)): ?>
                        <p class="text-muted text-center py-4">No transactions this month.</p>
                    <?php else: ?>
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
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Pass chart data to JS -->
<script>
window.FT_CHART_MONTH = <?= $month ?>;
window.FT_CHART_YEAR  = <?= $year ?>;
window.FT_API_BASE    = '<?= APP_URL ?>/api';
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
