<?php
/**
 * File: transactions/add.php
 * Purpose: Add new transaction form
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/functions.php';
require_once __DIR__ . '/../models/Category.php';

$userId   = (int)$_SESSION['user_id'];
$catModel = new Category($pdo);
$categories = $catModel->getAllForUser($userId);

$errors = $_SESSION['form_errors'] ?? [];
$old    = $_SESSION['form_old']    ?? [];
unset($_SESSION['form_errors'], $_SESSION['form_old']);

$pageTitle = 'Add Transaction';
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>

<div class="ft-layout">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="ft-main">
        <?php require_once __DIR__ . '/../includes/navbar.php'; ?>
        <div class="ft-content">
            <?php renderFlash(); ?>

            <div class="d-flex align-items-center mb-4">
                <a href="<?= APP_URL ?>/transactions/list.php" class="btn btn-sm btn-outline-secondary me-3">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <h4 class="fw-bold mb-0">Add Transaction</h4>
            </div>

            <div class="row justify-content-center">
                <div class="col-12 col-md-8 col-lg-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4">

                            <?php if (!empty($errors['general'])): ?>
                                <div class="alert alert-danger"><?= e($errors['general']) ?></div>
                            <?php endif; ?>

                            <form action="<?= APP_URL ?>/transactions/process.php" method="POST" novalidate>
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="add">

                                <!-- Type toggle -->
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Transaction Type</label>
                                    <div class="d-flex gap-2">
                                        <input type="radio" class="btn-check" name="type" id="typeExpense"
                                               value="expense" <?= ($old['type'] ?? 'expense') === 'expense' ? 'checked' : '' ?>>
                                        <label class="btn btn-outline-danger flex-fill" for="typeExpense">
                                            <i class="bi bi-arrow-up-circle me-1"></i>Expense
                                        </label>

                                        <input type="radio" class="btn-check" name="type" id="typeIncome"
                                               value="income" <?= ($old['type'] ?? '') === 'income' ? 'checked' : '' ?>>
                                        <label class="btn btn-outline-success flex-fill" for="typeIncome">
                                            <i class="bi bi-arrow-down-circle me-1"></i>Income
                                        </label>
                                    </div>
                                    <?php if (!empty($errors['type'])): ?>
                                        <div class="text-danger small mt-1"><?= e($errors['type']) ?></div>
                                    <?php endif; ?>
                                </div>

                                <!-- Amount -->
                                <div class="mb-3">
                                    <label for="amount" class="form-label fw-semibold">Amount</label>
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

                                <!-- Category -->
                                <div class="mb-3">
                                    <label for="category_id" class="form-label fw-semibold">Category</label>
                                    <select id="category_id" name="category_id"
                                            class="form-select <?= !empty($errors['category_id']) ? 'is-invalid' : '' ?>" required>
                                        <option value="">— Select Category —</option>
                                        <optgroup label="Expense">
                                        <?php foreach ($categories as $cat): if ($cat['type'] !== 'expense') continue; ?>
                                            <option value="<?= $cat['id'] ?>"
                                                <?= (string)($old['category_id'] ?? '') === (string)$cat['id'] ? 'selected' : '' ?>>
                                                <?= e($cat['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                        </optgroup>
                                        <optgroup label="Income">
                                        <?php foreach ($categories as $cat): if ($cat['type'] !== 'income') continue; ?>
                                            <option value="<?= $cat['id'] ?>"
                                                <?= (string)($old['category_id'] ?? '') === (string)$cat['id'] ? 'selected' : '' ?>>
                                                <?= e($cat['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                        </optgroup>
                                    </select>
                                    <?php if (!empty($errors['category_id'])): ?>
                                        <div class="invalid-feedback"><?= e($errors['category_id']) ?></div>
                                    <?php endif; ?>
                                </div>

                                <!-- Date -->
                                <div class="mb-3">
                                    <label for="date" class="form-label fw-semibold">Date</label>
                                    <input type="date" id="date" name="date"
                                           class="form-control <?= !empty($errors['date']) ? 'is-invalid' : '' ?>"
                                           value="<?= e($old['date'] ?? date('Y-m-d')) ?>" required>
                                    <?php if (!empty($errors['date'])): ?>
                                        <div class="invalid-feedback"><?= e($errors['date']) ?></div>
                                    <?php endif; ?>
                                </div>

                                <!-- Description -->
                                <div class="mb-4">
                                    <label for="description" class="form-label fw-semibold">
                                        Description <span class="text-muted fw-normal">(optional)</span>
                                    </label>
                                    <input type="text" id="description" name="description"
                                           class="form-control <?= !empty($errors['description']) ? 'is-invalid' : '' ?>"
                                           value="<?= e($old['description'] ?? '') ?>"
                                           placeholder="e.g. Grocery shopping" maxlength="255">
                                    <?php if (!empty($errors['description'])): ?>
                                        <div class="invalid-feedback"><?= e($errors['description']) ?></div>
                                    <?php endif; ?>
                                </div>

                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary flex-fill">
                                        <i class="bi bi-check-lg me-1"></i>Save Transaction
                                    </button>
                                    <a href="<?= APP_URL ?>/transactions/list.php"
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
