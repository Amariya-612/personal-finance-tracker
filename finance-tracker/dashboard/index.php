<?php
/**
 * File: dashboard/index.php
 * Purpose: Main dashboard with summary cards and charts
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/functions.php';
require_once __DIR__ . '/../models/Transaction.php';
require_once __DIR__ . '/../models/Budget.php';

$userId   = (int)$_SESSION['user_id'];
$currency = userCurrency();
$txnModel = new Transaction($pdo);
$budModel = new Budget($pdo);

$month = (int)date('m');
$year  = (int)date('Y');

// Summary for current month
$summary = $txnModel->monthlySummary($userId, $month, $year);
$income  = $summary['income'];
$expense = $summary['expense'];
$balance = $income - $expense;

// Budget overview
$budgetOverview = $budModel->monthlyOverview($userId, $month, $year);

// Recent transactions
$recent = $txnModel->recent($userId, 8);

$pageTitle  = 'Dashboard';
$loadCharts = true;
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>

<div class="ft-layout">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="ft-main">
        <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

        <div class="ft-content">
            <?php renderFlash(); ?>

            <!-- Page heading -->
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h4 class="fw-bold mb-0">Dashboard</h4>
                    <small class="text-muted"><?= currentMonthLabel() ?></small>
                </div>
                <a href="<?= APP_URL ?>/transactions/add.php" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-1"></i>Add Transaction
                </a>
            </div>

            <!-- ── Summary Cards ─────────────────────────────── -->
            <div class="row g-3 mb-4">
                <!-- Balance -->
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="card ft-stat-card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <p class="text-muted small mb-1">Net Balance</p>
                                    <h4 class="fw-bold mb-0 <?= $balance >= 0 ? 'text-success' : 'text-danger' ?>">
                                        <?= formatMoney($balance, $currency) ?>
                                    </h4>
                                </div>
                                <div class="ft-stat-icon bg-primary-subtle text-primary">
                                    <i class="bi bi-wallet2"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Income -->
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="card ft-stat-card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <p class="text-muted small mb-1">Total Income</p>
                                    <h4 class="fw-bold mb-0 text-success">
                                        <?= formatMoney($income, $currency) ?>
                                    </h4>
                                </div>
                                <div class="ft-stat-icon bg-success-subtle text-success">
                                    <i class="bi bi-arrow-down-circle"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Expense -->
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="card ft-stat-card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <p class="text-muted small mb-1">Total Expenses</p>
                                    <h4 class="fw-bold mb-0 text-danger">
                                        <?= formatMoney($expense, $currency) ?>
                                    </h4>
                                </div>
                                <div class="ft-stat-icon bg-danger-subtle text-danger">
                                    <i class="bi bi-arrow-up-circle"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Budget -->
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="card ft-stat-card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <p class="text-muted small mb-1">Budget Used</p>
                                    <?php
                                    $budPct = percentage($budgetOverview['total_spent'], $budgetOverview['total_budget']);
                                    $budClass = $budPct >= 90 ? 'text-danger' : ($budPct >= 70 ? 'text-warning' : 'text-success');
                                    ?>
                                    <h4 class="fw-bold mb-0 <?= $budClass ?>">
                                        <?= $budPct ?>%
                                    </h4>
                                    <small class="text-muted">
                                        <?= formatMoney($budgetOverview['total_spent'], $currency) ?>
                                        / <?= formatMoney($budgetOverview['total_budget'], $currency) ?>
                                    </small>
                                </div>
                                <div class="ft-stat-icon bg-warning-subtle text-warning">
                                    <i class="bi bi-piggy-bank"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Charts Row ─────────────────────────────────── -->
            <div class="row g-3 mb-4">
                <!-- Expense by Category (Pie) -->
                <div class="col-12 col-lg-5">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-transparent border-0 pt-3 pb-0">
                            <h6 class="fw-bold mb-0">
                                <i class="bi bi-pie-chart me-2 text-primary"></i>Expenses by Category
                            </h6>
                            <small class="text-muted"><?= currentMonthLabel() ?></small>
                        </div>
                        <div class="card-body d-flex align-items-center justify-content-center">
                            <div style="position:relative; width:100%; max-width:320px;">
                                <canvas id="expensePieChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Monthly Trend (Bar) -->
                <div class="col-12 col-lg-7">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-transparent border-0 pt-3 pb-0">
                            <h6 class="fw-bold mb-0">
                                <i class="bi bi-bar-chart me-2 text-primary"></i>Income vs Expenses
                            </h6>
                            <small class="text-muted">Last 6 months</small>
                        </div>
                        <div class="card-body">
                            <canvas id="monthlyBarChart" style="max-height:260px;"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Recent Transactions ────────────────────────── -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0 pt-3 d-flex align-items-center justify-content-between">
                    <h6 class="fw-bold mb-0">
                        <i class="bi bi-clock-history me-2 text-primary"></i>Recent Transactions
                    </h6>
                    <a href="<?= APP_URL ?>/transactions/list.php" class="btn btn-sm btn-outline-primary">
                        View All
                    </a>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($recent)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                            No transactions yet.
                            <a href="<?= APP_URL ?>/transactions/add.php">Add one now</a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Category</th>
                                        <th>Description</th>
                                        <th>Date</th>
                                        <th class="text-end">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($recent as $txn): ?>
                                    <tr>
                                        <td>
                                            <span class="badge rounded-pill"
                                                  style="background-color:<?= e($txn['category_color']) ?>">
                                                <i class="<?= e($txn['category_icon']) ?> me-1"></i>
                                                <?= e($txn['category_name']) ?>
                                            </span>
                                        </td>
                                        <td class="text-truncate" style="max-width:200px;">
                                            <?= e($txn['description'] ?: '—') ?>
                                        </td>
                                        <td class="text-muted small">
                                            <?= date(DATE_FORMAT, strtotime($txn['date'])) ?>
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

        </div><!-- /.ft-content -->
    </div><!-- /.ft-main -->
</div><!-- /.ft-layout -->

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
