<!-- File: includes/sidebar.php -->
<?php
$currentFile = basename($_SERVER['PHP_SELF']);
$currentDir  = basename(dirname($_SERVER['PHP_SELF']));

function sidebarActive(string $dir, string $file = ''): string {
    global $currentDir, $currentFile;
    if ($file) {
        return ($currentDir === $dir && $currentFile === $file) ? 'active' : '';
    }
    return $currentDir === $dir ? 'active' : '';
}
?>
<aside class="ft-sidebar d-flex flex-column">

    <!-- Brand -->
    <div class="ft-sidebar-brand px-3 py-4">
        <a href="<?= APP_URL ?>/dashboard/index.php" class="text-decoration-none d-flex align-items-center gap-2">
            <i class="bi bi-wallet2 fs-4" style="color:var(--ft-brand);"></i>
            <span class="fw-bold fs-5" style="color:var(--ft-brand);"><?= e(APP_NAME) ?></span>
        </a>
    </div>

    <!-- User info -->
    <div class="px-3 pb-3" style="border-bottom:1px solid var(--ft-sidebar-border);">
        <div class="d-flex align-items-center gap-2">
            <?php if (!empty($_SESSION['avatar'])): ?>
                <img src="<?= APP_URL ?>/<?= e($_SESSION['avatar']) ?>" alt="Avatar"
                     style="width:36px;height:36px;border-radius:50%;object-fit:cover;flex-shrink:0;">
            <?php else: ?>
                <div class="ft-avatar">
                    <?= strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)) ?>
                </div>
            <?php endif; ?>
            <div class="overflow-hidden">
                <div class="fw-semibold text-truncate small" style="color:var(--ft-text-primary);">
                    <?= e($_SESSION['user_name'] ?? '') ?>
                </div>
                <div class="text-truncate" style="font-size:0.75rem;color:var(--ft-text-muted);">
                    <?= e($_SESSION['currency'] ?? 'ETB') ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="ft-sidebar-nav flex-grow-1 px-2 py-3">

        <div class="ft-nav-label">Main</div>
        <a href="<?= APP_URL ?>/dashboard/index.php"
           class="ft-nav-link <?= sidebarActive('dashboard', 'index.php') ?>">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>

        <div class="ft-nav-label mt-3">Transactions</div>
        <a href="<?= APP_URL ?>/transactions/list.php"
           class="ft-nav-link <?= sidebarActive('transactions', 'list.php') ?>">
            <i class="bi bi-list-ul"></i> All Transactions
        </a>
        <a href="<?= APP_URL ?>/transactions/add.php"
           class="ft-nav-link <?= sidebarActive('transactions', 'add.php') ?>">
            <i class="bi bi-plus-circle"></i> Add Transaction
        </a>

        <div class="ft-nav-label mt-3">Categories</div>
        <a href="<?= APP_URL ?>/categories/list.php"
           class="ft-nav-link <?= sidebarActive('categories', 'list.php') ?>">
            <i class="bi bi-tags"></i> All Categories
        </a>
        <a href="<?= APP_URL ?>/categories/add.php"
           class="ft-nav-link <?= sidebarActive('categories', 'add.php') ?>">
            <i class="bi bi-tag"></i> Add Category
        </a>

        <div class="ft-nav-label mt-3">Budgets</div>
        <a href="<?= APP_URL ?>/budgets/list_budget.php"
           class="ft-nav-link <?= sidebarActive('budgets', 'list_budget.php') ?>">
            <i class="bi bi-piggy-bank"></i> My Budgets
        </a>
        <a href="<?= APP_URL ?>/budgets/set_budget.php"
           class="ft-nav-link <?= sidebarActive('budgets', 'set_budget.php') ?>">
            <i class="bi bi-plus-square"></i> Set Budget
        </a>

        <div class="ft-nav-label mt-3">Reports</div>
        <a href="<?= APP_URL ?>/reports/monthly.php"
           class="ft-nav-link <?= sidebarActive('reports', 'monthly.php') ?>">
            <i class="bi bi-calendar-month"></i> Monthly
        </a>
        <a href="<?= APP_URL ?>/reports/yearly.php"
           class="ft-nav-link <?= sidebarActive('reports', 'yearly.php') ?>">
            <i class="bi bi-calendar3"></i> Yearly
        </a>
        <a href="<?= APP_URL ?>/reports/export.php"
           class="ft-nav-link <?= sidebarActive('reports', 'export.php') ?>">
            <i class="bi bi-download"></i> Export CSV
        </a>

    </nav>

    <!-- Bottom actions -->
    <div class="px-2 py-3" style="border-top:1px solid var(--ft-sidebar-border);">
        <a href="<?= APP_URL ?>/profile/edit.php" class="ft-nav-link <?= sidebarActive('profile', 'edit.php') ?>">
            <i class="bi bi-person-circle"></i> Edit Profile
        </a>
        <a href="<?= APP_URL ?>/auth/logout.php" class="ft-nav-link text-danger">
            <i class="bi bi-box-arrow-right"></i> Logout
        </a>

        <!-- Delete Account -->
        <button type="button"
                onclick="document.getElementById('deleteAccountModal').style.display='flex'"
                style="
                    display:flex; align-items:center; gap:0.6rem;
                    width:100%; padding:0.5rem 0.75rem;
                    border-radius:10px; border:none; background:transparent;
                    color:#c0392b; font-size:0.875rem; cursor:pointer;
                    transition:background 0.2s;
                    margin-top:2px;
                "
                onmouseover="this.style.background='#fff0f0'"
                onmouseout="this.style.background='transparent'">
            <i class="bi bi-trash3"></i> Delete Account
        </button>
    </div>

</aside>

<!-- ── Delete Account Confirmation Modal ── -->
<div id="deleteAccountModal" style="
    display:none; position:fixed; inset:0; z-index:9999;
    background:rgba(0,0,0,0.45); backdrop-filter:blur(3px);
    align-items:center; justify-content:center;
">
    <div style="
        background:#fff; border-radius:18px; padding:2rem;
        max-width:420px; width:90%; box-shadow:0 20px 60px rgba(0,0,0,0.25);
        animation:modalPop 0.25s cubic-bezier(0.34,1.56,0.64,1) both;
    ">
        <!-- Icon -->
        <div style="text-align:center; margin-bottom:1rem;">
            <div style="
                width:64px; height:64px; border-radius:50%;
                background:#fff0f0; display:inline-flex;
                align-items:center; justify-content:center;
                font-size:1.8rem; color:#c0392b;
            ">
                <i class="bi bi-exclamation-triangle-fill"></i>
            </div>
        </div>

        <h5 style="text-align:center; font-weight:800; color:#0d1b4b; margin-bottom:0.5rem;">
            Delete Your Account?
        </h5>
        <p style="text-align:center; color:#828282; font-size:0.875rem; margin-bottom:1.5rem;">
            This will permanently delete your account, all transactions, budgets, and categories.
            <strong style="color:#c0392b;">This action cannot be undone.</strong>
        </p>

        <!-- Password confirmation -->
        <form action="<?= APP_URL ?>/auth/delete_account.php" method="POST">
            <?= csrfField() ?>
            <div class="mb-3">
                <label style="font-size:0.78rem; font-weight:700; color:#333; text-transform:uppercase; letter-spacing:0.07em; display:block; margin-bottom:0.35rem;">
                    Confirm your password
                </label>
                <input type="password" name="confirm_password"
                       placeholder="Enter your password to confirm"
                       required
                       style="
                           width:100%; padding:0.55rem 0.85rem;
                           border:1.5px solid #c8d6e5; border-radius:10px;
                           font-size:0.9rem; color:#0d1b4b; background:#f5f7fa;
                           outline:none; box-sizing:border-box;
                       "
                       onfocus="this.style.borderColor='#1565c0'"
                       onblur="this.style.borderColor='#c8d6e5'">
            </div>

            <div style="display:flex; gap:0.75rem;">
                <button type="button"
                        onclick="document.getElementById('deleteAccountModal').style.display='none'"
                        style="
                            flex:1; padding:0.6rem; border-radius:50px;
                            border:1.5px solid #dce6f5; background:#fff;
                            color:#333; font-weight:600; font-size:0.875rem; cursor:pointer;
                        ">
                    Cancel
                </button>
                <button type="submit"
                        style="
                            flex:1; padding:0.6rem; border-radius:50px;
                            border:none; background:linear-gradient(135deg,#e74c3c,#c0392b);
                            color:#fff; font-weight:700; font-size:0.875rem; cursor:pointer;
                            box-shadow:0 3px 12px rgba(192,57,43,0.35);
                        ">
                    <i class="bi bi-trash3 me-1"></i> Yes, Delete
                </button>
            </div>
        </form>
    </div>
</div>

<style>
@keyframes modalPop {
    from { opacity:0; transform:scale(0.9) translateY(10px); }
    to   { opacity:1; transform:scale(1)   translateY(0); }
}
</style>
