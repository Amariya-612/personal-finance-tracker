<?php
/**
 * File: budgets/edit_budget.php
 * Purpose: Edit an existing budget amount
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/functions.php';
require_once __DIR__ . '/../models/Budget.php';
require_once __DIR__ . '/../controllers/BudgetController.php';

$userId     = (int)$_SESSION['user_id'];
$controller = new BudgetController($pdo);
$budModel   = new Budget($pdo);

// ── Handle POST ───────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id     = (int)($_POST['id'] ?? 0);
    $result = $controller->update($id, $userId, $_POST);

    if ($result['success']) {
        setFlash('success', 'Budget updated.');
        redirect(APP_URL . '/budgets/list_budget.php');
    } else {
        $_SESSION['form_errors'] = $result['errors'];
        $_SESSION['form_old']    = $_POST;
        redirect(APP_URL . '/budgets/edit_budget.php?id=' . $id);
    }
}

// ── GET ───────────────────────────────────────────────────
$id  = (int)($_GET['id'] ?? 0);
$bud = $budModel->findById($id, $userId);

if (!$bud) {
    setFlash('danger', 'Budget not found.');
    redirect(APP_URL . '/budgets/list_budget.php');
}

$errors = $_SESSION['form_errors'] ?? [];
$old    = $_SESSION['form_old']    ?? $bud;
unset($_SESSION['form_errors'], $_SESSION['form_old']);

$pageTitle = 'Edit Budget';
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>

<div class="ft-layout">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="ft-main">
        <?php require_once __DIR__ . '/../includes/navbar.php'; ?>
        <div class="ft-content">
            <?php renderFlash(); ?>

            <div class="d-flex align-items-center mb-4">
                <a href="<?= APP_URL ?>/budgets/list_budget.php" class="btn btn-sm btn-outline-secondary me-3">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <h4 class="fw-bold mb-0">Edit Budget</h4>
            </div>

            <div class="row justify-content-center">
                <div class="col-12 col-md-7 col-lg-5">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4">

                            <div class="alert alert-info mb-3">
                                <strong><?= e($bud['category_name']) ?></strong> —
                                <?= monthNames()[(int)$bud['month']] ?> <?= $bud['year'] ?>
                            </div>

                            <form method="POST" novalidate>
                                <?= csrfField() ?>
                                <input type="hidden" name="id" value="<?= $id ?>">

                                <div class="mb-4">
                                    <label for="amount" class="form-label fw-semibold">Budget Amount</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><?= e(userCurrency()) ?></span>
                                        <input type="number" id="amount" name="amount"
                                               class="form-control <?= !empty($errors['amount']) ? 'is-invalid' : '' ?>"
                                               value="<?= e($old['amount'] ?? '') ?>"
                                               step="0.01" min="0.01" required>
                                        <?php if (!empty($errors['amount'])): ?>
                                            <div class="invalid-feedback"><?= e($errors['amount']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary flex-fill">
                                        <i class="bi bi-check-lg me-1"></i>Update Budget
                                    </button>
                                    <a href="<?= APP_URL ?>/budgets/list_budget.php"
                                       class="btn btn-outline-secondary">Cancel</a>
                                </div>
                            </form>

                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
