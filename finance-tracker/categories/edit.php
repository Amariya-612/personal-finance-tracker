<?php
/**
 * File: categories/edit.php
 * Purpose: Edit an existing category OR process add/edit form submissions
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/functions.php';
require_once __DIR__ . '/../models/Category.php';
require_once __DIR__ . '/../utils/validator.php';

$userId   = (int)$_SESSION['user_id'];
$catModel = new Category($pdo);

// ── Handle POST (add or edit) ─────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Invalid request.');
        redirect(APP_URL . '/categories/list.php');
    }

    $v = new Validator($_POST);
    $v->required('name',  'Name')
      ->maxLength('name', 100, 'Name')
      ->required('type',  'Type')
      ->inList('type', ['income','expense'], 'Type')
      ->required('icon',  'Icon')
      ->required('color', 'Color');

    if ($v->fails()) {
        $_SESSION['form_errors'] = $v->errors();
        $_SESSION['form_old']    = $_POST;
        $action = $_POST['action'] ?? 'add';
        $id     = (int)($_POST['id'] ?? 0);
        redirect($action === 'edit'
            ? APP_URL . '/categories/edit.php?id=' . $id
            : APP_URL . '/categories/add.php');
    }

    $action = $_POST['action'] ?? 'add';

    if ($action === 'add') {
        $catModel->create($userId, $_POST['name'], $_POST['type'], $_POST['icon'], $_POST['color']);
        setFlash('success', 'Category created.');
        redirect(APP_URL . '/categories/list.php');

    } elseif ($action === 'edit') {
        $id  = (int)($_POST['id'] ?? 0);
        $cat = $catModel->findById($id, $userId);
        if (!$cat || $cat['user_id'] === null) {
            setFlash('danger', 'Cannot edit a global category.');
            redirect(APP_URL . '/categories/list.php');
        }
        $catModel->update($id, $userId, $_POST['name'], $_POST['type'], $_POST['icon'], $_POST['color']);
        setFlash('success', 'Category updated.');
        redirect(APP_URL . '/categories/list.php');
    }
}

// ── GET: show edit form ───────────────────────────────────
$id  = (int)($_GET['id'] ?? 0);
$cat = $catModel->findById($id, $userId);

if (!$cat || $cat['user_id'] === null) {
    setFlash('danger', 'Category not found or cannot be edited.');
    redirect(APP_URL . '/categories/list.php');
}

$errors = $_SESSION['form_errors'] ?? [];
$old    = $_SESSION['form_old']    ?? $cat;
unset($_SESSION['form_errors'], $_SESSION['form_old']);

$icons = [
    'bi-tag','bi-house','bi-car-front','bi-cup-hot','bi-bag','bi-heart-pulse',
    'bi-controller','bi-book','bi-airplane','bi-lightning','bi-briefcase',
    'bi-laptop','bi-graph-up-arrow','bi-gift','bi-phone','bi-music-note',
    'bi-camera','bi-bicycle','bi-basket','bi-tools',
];

$pageTitle = 'Edit Category';
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
                <h4 class="fw-bold mb-0">Edit Category</h4>
            </div>

            <div class="row justify-content-center">
                <div class="col-12 col-md-7 col-lg-5">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4">

                            <form method="POST" novalidate>
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="edit">
                                <input type="hidden" name="id"     value="<?= $id ?>">

                                <div class="mb-3">
                                    <label for="name" class="form-label fw-semibold">Category Name</label>
                                    <input type="text" id="name" name="name"
                                           class="form-control <?= !empty($errors['name']) ? 'is-invalid' : '' ?>"
                                           value="<?= e($old['name'] ?? '') ?>"
                                           maxlength="100" required>
                                    <?php if (!empty($errors['name'])): ?>
                                        <div class="invalid-feedback"><?= e($errors['name']) ?></div>
                                    <?php endif; ?>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Type</label>
                                    <div class="d-flex gap-2">
                                        <input type="radio" class="btn-check" name="type" id="typeExpense"
                                               value="expense" <?= ($old['type'] ?? '') === 'expense' ? 'checked' : '' ?>>
                                        <label class="btn btn-outline-danger flex-fill" for="typeExpense">Expense</label>
                                        <input type="radio" class="btn-check" name="type" id="typeIncome"
                                               value="income" <?= ($old['type'] ?? '') === 'income' ? 'checked' : '' ?>>
                                        <label class="btn btn-outline-success flex-fill" for="typeIncome">Income</label>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Icon</label>
                                    <div class="ft-icon-picker d-flex flex-wrap gap-2">
                                        <?php foreach ($icons as $icon): ?>
                                            <input type="radio" class="btn-check" name="icon"
                                                   id="icon_<?= str_replace('-','_',$icon) ?>"
                                                   value="<?= $icon ?>"
                                                   <?= ($old['icon'] ?? '') === $icon ? 'checked' : '' ?>>
                                            <label class="btn btn-outline-secondary btn-sm"
                                                   for="icon_<?= str_replace('-','_',$icon) ?>">
                                                <i class="<?= $icon ?>"></i>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label for="color" class="form-label fw-semibold">Color</label>
                                    <input type="color" id="color" name="color"
                                           class="form-control form-control-color"
                                           value="<?= e($old['color'] ?? '#6c757d') ?>">
                                </div>

                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary flex-fill">
                                        <i class="bi bi-check-lg me-1"></i>Update Category
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
