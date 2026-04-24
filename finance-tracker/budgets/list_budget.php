<?php
/**
 * File: budgets/list_budget.php
 * Purpose: Display budgets with progress bars for selected month/year
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/functions.php';
require_once __DIR__ . '/../models/Budget.php';

$userId   = (int)$_SESSION['user_id'];
$currency = userCurrency();

$month = (int)($_GET['month'] ?? date('m'));
$year  = (int)($_GET['year']  ?? date('Y'));
$month = clamp($month, 1, 12);

$budModel = new Budget($pdo);
$budgets  = $budModel->getWithSpent($userId, $month, $year);
$overview = $budModel->monthlyOverview($userId, $month, $year);

$pageTitle = 'Budgets';
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
                    <h4 class="fw-bold mb-0">Budgets</h4>
                    <small class="text-muted">
                        <?= monthNames()[$month] ?> <?= $year ?>
                    </small>
                </div>
                <a href="<?= APP_URL ?>/budgets/set_budget.php" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-1"></i>Set Budget
                </a>
            </div>

            <!-- Month/Year selector -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body py-2">
                    <form method="GET" class="d-flex align-items-center gap-2 flex-wrap">
                        <label class="fw-semibold small mb-0">View:</label>
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
                        <button type="submit" class="btn btn-sm btn-outline-primary">Go</button>
                    </form>
                </div>
            </div>

            <!-- Overview card -->
            <?php if ($overview['total_budget'] > 0): ?>
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="fw-semibold">Overall Budget</span>
                        <span class="text-muted small">
                            <?= formatMoney($overview['total_spent'], $currency) ?>
                            / <?= formatMoney($overview['total_budget'], $currency) ?>
                        </span>
                    </div>
                    <?php
                    $pct = percentage($overview['total_spent'], $overview['total_budget']);
                    $barClass = $pct >= 90 ? 'bg-danger' : ($pct >= 70 ? 'bg-warning' : 'bg-success');
                    ?>
                    <div class="progress" style="height:12px;">
                        <div class="progress-bar <?= $barClass ?>"
                             style="width:<?= min($pct, 100) ?>%"
                             role="progressbar">
                            <?= $pct ?>%
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Budget cards -->
            <?php if (empty($budgets)): ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-5 text-muted">
                        <i class="bi bi-piggy-bank fs-1 d-block mb-2"></i>
                        No budgets set for this month.
                        <a href="<?= APP_URL ?>/budgets/set_budget.php">Set one now</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="row g-3">
                <?php foreach ($budgets as $bud):
                    $spent   = (float)$bud['spent'];
                    $budAmt  = (float)$bud['amount'];
                    $pct     = percentage($spent, $budAmt);
                    $barCls  = $pct >= 90 ? 'bg-danger' : ($pct >= 70 ? 'bg-warning' : 'bg-success');
                    $remaining = $budAmt - $spent;
                ?>
                    <div class="col-12 col-md-6 col-xl-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="fs-5" style="color:<?= e($bud['category_color']) ?>">
                                            <i class="<?= e($bud['category_icon']) ?>"></i>
                                        </span>
                                        <span class="fw-semibold"><?= e($bud['category_name']) ?></span>
                                    </div>
                                    <div class="d-flex gap-1">
                                        <a href="<?= APP_URL ?>/budgets/edit_budget.php?id=<?= $bud['id'] ?>"
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-outline-danger"
                                                onclick="confirmDelete(<?= $bud['id'] ?>, '<?= e(addslashes($bud['category_name'])) ?>')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between small text-muted mb-1">
                                    <span>Spent: <strong class="text-dark"><?= formatMoney($spent, $currency) ?></strong></span>
                                    <span>Budget: <strong class="text-dark"><?= formatMoney($budAmt, $currency) ?></strong></span>
                                </div>

                                <div class="progress mb-2" style="height:8px;">
                                    <div class="progress-bar <?= $barCls ?>"
                                         style="width:<?= min($pct, 100) ?>%"
                                         role="progressbar"></div>
                                </div>

                                <div class="d-flex justify-content-between small">
                                    <span class="<?= $pct ?>% used"><?= $pct ?>% used</span>
                                    <span class="<?= $remaining >= 0 ? 'text-success' : 'text-danger' ?>">
                                        <?= $remaining >= 0 ? 'Remaining: ' : 'Over by: ' ?>
                                        <strong><?= formatMoney(abs($remaining), $currency) ?></strong>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger">Delete Budget</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Delete budget for <strong id="deleteItemName"></strong>?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" action="<?= APP_URL ?>/budgets/set_budget.php">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="delete">
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
