<?php
/**
 * File: reports/yearly.php
 * Purpose: Yearly income vs expense report
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/functions.php';
require_once __DIR__ . '/../models/Transaction.php';

$userId   = (int)$_SESSION['user_id'];
$currency = userCurrency();
$year     = (int)($_GET['year'] ?? date('Y'));

$txnModel = new Transaction($pdo);
$rows     = $txnModel->yearlySummary($userId, $year);

// Build month-indexed arrays
$months  = monthNames();
$income  = array_fill(1, 12, 0.0);
$expense = array_fill(1, 12, 0.0);

foreach ($rows as $row) {
    $m = (int)$row['mo'];
    if ($row['type'] === 'income') {
        $income[$m] = (float)$row['total'];
    } else {
        $expense[$m] = (float)$row['total'];
    }
}

$totalIncome  = array_sum($income);
$totalExpense = array_sum($expense);
$totalBalance = $totalIncome - $totalExpense;

$pageTitle  = 'Yearly Report';
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
                    <h4 class="fw-bold mb-0">Yearly Report</h4>
                    <small class="text-muted"><?= $year ?></small>
                </div>
                <a href="<?= APP_URL ?>/reports/export.php?year=<?= $year ?>"
                   class="btn btn-outline-success btn-sm">
                    <i class="bi bi-download me-1"></i>Export CSV
                </a>
            </div>

            <!-- Year selector -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body py-2">
                    <form method="GET" class="d-flex align-items-center gap-2">
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

            <!-- Summary -->
            <div class="row g-3 mb-4">
                <div class="col-12 col-sm-4">
                    <div class="card border-0 shadow-sm text-center">
                        <div class="card-body">
                            <p class="text-muted small mb-1">Total Income</p>
                            <h4 class="text-success fw-bold"><?= formatMoney($totalIncome, $currency) ?></h4>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-4">
                    <div class="card border-0 shadow-sm text-center">
                        <div class="card-body">
                            <p class="text-muted small mb-1">Total Expenses</p>
                            <h4 class="text-danger fw-bold"><?= formatMoney($totalExpense, $currency) ?></h4>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-4">
                    <div class="card border-0 shadow-sm text-center">
                        <div class="card-body">
                            <p class="text-muted small mb-1">Net Savings</p>
                            <h4 class="fw-bold <?= $totalBalance >= 0 ? 'text-success' : 'text-danger' ?>">
                                <?= formatMoney($totalBalance, $currency) ?>
                            </h4>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bar chart -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent border-0 pt-3">
                    <h6 class="fw-bold mb-0">Monthly Income vs Expenses — <?= $year ?></h6>
                </div>
                <div class="card-body">
                    <canvas id="yearlyBarChart" style="max-height:300px;"></canvas>
                </div>
            </div>

            <!-- Monthly breakdown table -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0 pt-3">
                    <h6 class="fw-bold mb-0">Monthly Breakdown</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Month</th>
                                    <th class="text-end text-success">Income</th>
                                    <th class="text-end text-danger">Expenses</th>
                                    <th class="text-end">Balance</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($months as $m => $name):
                                $inc = $income[$m];
                                $exp = $expense[$m];
                                $bal = $inc - $exp;
                            ?>
                                <tr>
                                    <td><?= $name ?></td>
                                    <td class="text-end text-success"><?= $inc > 0 ? formatMoney($inc, $currency) : '—' ?></td>
                                    <td class="text-end text-danger"><?= $exp > 0 ? formatMoney($exp, $currency) : '—' ?></td>
                                    <td class="text-end fw-semibold <?= $bal >= 0 ? 'text-success' : 'text-danger' ?>">
                                        <?= ($inc > 0 || $exp > 0) ? formatMoney($bal, $currency) : '—' ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-light fw-bold">
                                <tr>
                                    <td>Total</td>
                                    <td class="text-end text-success"><?= formatMoney($totalIncome, $currency) ?></td>
                                    <td class="text-end text-danger"><?= formatMoney($totalExpense, $currency) ?></td>
                                    <td class="text-end <?= $totalBalance >= 0 ? 'text-success' : 'text-danger' ?>">
                                        <?= formatMoney($totalBalance, $currency) ?>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Pass data to charts.js -->
<script>
window.FT_YEARLY_DATA = {
    labels:  <?= json_encode(array_values($months)) ?>,
    income:  <?= json_encode(array_values($income)) ?>,
    expense: <?= json_encode(array_values($expense)) ?>,
};
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
