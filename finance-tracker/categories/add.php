<?php
/**
 * File: categories/add.php
 * Purpose: Add a new custom category
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../utils/functions.php';

$errors = $_SESSION['form_errors'] ?? [];
$old    = $_SESSION['form_old']    ?? [];
unset($_SESSION['form_errors'], $_SESSION['form_old']);

$icons = [
    'bi-tag','bi-house','bi-car-front','bi-cup-hot','bi-bag','bi-heart-pulse',
    'bi-controller','bi-book','bi-airplane','bi-lightning','bi-briefcase',
    'bi-laptop','bi-graph-up-arrow','bi-gift','bi-phone','bi-music-note',
    'bi-camera','bi-bicycle','bi-basket','bi-tools',
];

$pageTitle = 'Add Category';
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>

<div class="ft-layout">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="ft-main">
        <?php require_once __DIR__ . '/../includes/navbar.php'; ?>
        <div class="ft-content">
            <?php renderFlash(); ?>

            <div class="d-flex align-items-center mb-4">
                <a href="<?= APP_URL ?>/categories/list.php" class="btn btn-sm btn-outline-secondary me-3">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <h4 class="fw-bold mb-0">Add Category</h4>
            </div>

            <div class="row justify-content-center">
                <div class="col-12 col-md-7 col-lg-5">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4">

                            <?php if (!empty($errors['general'])): ?>
                                <div class="alert alert-danger"><?= e($errors['general']) ?></div>
                            <?php endif; ?>

                            <form action="<?= APP_URL ?>/categories/edit.php" method="POST" novalidate>
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="add">

                                <!-- Name -->
                                <div class="mb-3">
                                    <label for="name" class="form-label fw-semibold">Category Name</label>
                                    <input type="text" id="name" name="name"
                                           class="form-control <?= !empty($errors['name']) ? 'is-invalid' : '' ?>"
                                           value="<?= e($old['name'] ?? '') ?>"
                                           placeholder="e.g. Gym Membership" maxlength="100" required>
                                    <?php if (!empty($errors['name'])): ?>
                                        <div class="invalid-feedback"><?= e($errors['name']) ?></div>
                                    <?php endif; ?>
                                </div>

                                <!-- Type -->
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Type</label>
                                    <div class="d-flex gap-2">
                                        <input type="radio" class="btn-check" name="type" id="typeExpense"
                                               value="expense" <?= ($old['type'] ?? 'expense') === 'expense' ? 'checked' : '' ?>>
                                        <label class="btn btn-outline-danger flex-fill" for="typeExpense">
                                            Expense
                                        </label>
                                        <input type="radio" class="btn-check" name="type" id="typeIncome"
                                               value="income" <?= ($old['type'] ?? '') === 'income' ? 'checked' : '' ?>>
                                        <label class="btn btn-outline-success flex-fill" for="typeIncome">
                                            Income
                                        </label>
                                    </div>
                                </div>

                                <!-- Icon picker -->
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Icon</label>
                                    <div class="ft-icon-picker d-flex flex-wrap gap-2">
                                        <?php foreach ($icons as $icon): ?>
                                            <input type="radio" class="btn-check" name="icon"
                                                   id="icon_<?= str_replace('-','_',$icon) ?>"
                                                   value="<?= $icon ?>"
                                                   <?= ($old['icon'] ?? 'bi-tag') === $icon ? 'checked' : '' ?>>
                                            <label class="btn btn-outline-secondary btn-sm"
                                                   for="icon_<?= str_replace('-','_',$icon) ?>"
                                                   title="<?= $icon ?>">
                                                <i class="<?= $icon ?>"></i>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <!-- Color -->
                                <div class="mb-4">
                                    <label for="color" class="form-label fw-semibold">Color</label>
                                    <div class="d-flex align-items-center gap-3">
                                        <input type="color" id="color" name="color"
                                               class="form-control form-control-color"
                                               value="<?= e($old['color'] ?? '#6c757d') ?>">
                                        <span class="text-muted small">Pick a color for this category</span>
                                    </div>
                                </div>

                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary flex-fill">
                                        <i class="bi bi-check-lg me-1"></i>Save Category
                                    </button>
                                    <a href="<?= APP_URL ?>/categories/list.php"
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
