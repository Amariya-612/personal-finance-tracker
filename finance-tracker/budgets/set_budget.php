<?php
/**
 * File: budgets/set_budget.php
 * Purpose: Set a new budget OR handle delete action
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/functions.php';
require_once __DIR__ . '/../models/Category.php';
require_once __DIR__ . '/../controllers/BudgetController.php';

$userId     = (int)$_SESSION['user_id'];
$controller = new BudgetController($pdo);

// ── Handle POST ───────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $result = $controller->destroy((int)($_POST['id'] ?? 0), $userId, $_POST['csrf_token'] ?? '');
        setFlash($result['success'] ? 'success' : 'danger', $result['message']);
        redirect(APP_URL . '/budgets/list_budget.php');
    }

    // Default: add
    $result = $controller->store($userId, $_POST);
    if ($result['success']) {
        setFlash('success', 'Budget set successfully.');
        redirect(APP_URL . '/budgets/list_budget.php');
    } else {
        $_SESSION['form_errors'] = $result['errors'];
        $_SESSION['form_old']    = $_POST;
        redirect(APP_URL . '/budgets/set_budget.php');
    }
}

// ── GET: show form ────────────────────────────────────────
$catModel   = new Category($pdo);
$categories = $catModel->getByType($userId, 'expense');

$errors = $_SESSION['form_errors'] ?? [];
$old    = $_SESSION['form_old']    ?? [];
unset($_SESSION['form_errors'], $_SESSION['form_old']);

$pageTitle = 'Set Budget';
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
                <h4 class="fw-bold mb-0">Set Budget</h4>
            </div>

            <div class="row justify-content-center">
                <div class="col-12 col-md-7 col-lg-5">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4">

                            <?php if (!empty($errors['general'])): ?>
                                <div class="alert alert-danger"><?= e($errors['general']) ?></div>
                            <?php endif; ?>

                            <form method="POST" novalidate>
                                <?= csrfField() ?>

                                <!-- Category -->
                                <div class="mb-3">
                                    <label for="category_id" class="form-label fw-semibold">Expense Category</label>
                                    <select id="category_id" name="category_id"
                                            class="form-select <?= !empty($errors['category_id']) ? 'is-invalid' : '' ?>" required>
                                        <option value="">— Select Category —</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?= $cat['id'] ?>"
                                                <?= (string)($old['category_id'] ?? '') === (string)$cat['id'] ? 'selected' : '' ?>>
                                                <?= e($cat['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if (!empty($errors['category_id'])): ?>
                                        <div class="invalid-feedback"><?= e($errors['category_id']) ?></div>
                                    <?php endif; ?>
                                </div>

                                <!-- Amount -->
                                <div class="mb-3">
                                    <label for="amount" class="form-label fw-semibold">Budget Amount</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><?= e(userCurrency()) ?></span>
                                        <input type="number" id="amount" name="amount"
                                               class="form-control <?= !empty($errors['amount']) ? 'is-invalid' : '' ?>"
                                               value="<?= e($old['amount'] ?? '') ?>"
                                               step="0.01" min="0.01" placeholder="0.00" required>
                                        <?php if (!empty($errors['amount'])): ?>
                                            <div class="invalid-feedback"><?= e($errors['amount']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Month -->
                                <div class="mb-3">
                                    <label for="month" class="form-label fw-semibold">Month</label>
                                    <select id="month" name="month"
                                            class="form-select <?= !empty($errors['month']) ? 'is-invalid' : '' ?>" required>
                                        <?php foreach (monthNames() as $m => $name): ?>
                                            <option value="<?= $m ?>"
                                                <?= (int)($old['month'] ?? date('m')) === $m ? 'selected' : '' ?>>
                                                <?= $name ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if (!empty($errors['month'])): ?>
                                        <div class="invalid-feedback"><?= e($errors['month']) ?></div>
                                    <?php endif; ?>
                                </div>

                                <!-- Year -->
                                <div class="mb-4">
                                    <label for="year" class="form-label fw-semibold">Year</label>
                                    <select id="year" name="year"
                                            class="form-select <?= !empty($errors['year']) ? 'is-invalid' : '' ?>" required>
                                        <?php foreach (yearRange(2) as $y): ?>
                                            <option value="<?= $y ?>"
                                                <?= (int)($old['year'] ?? date('Y')) === $y ? 'selected' : '' ?>>
                                                <?= $y ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if (!empty($errors['year'])): ?>
                                        <div class="invalid-feedback"><?= e($errors['year']) ?></div>
                                    <?php endif; ?>
                                </div>

                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary flex-fill">
                                        <i class="bi bi-check-lg me-1"></i>Save Budget
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
