<!-- File: includes/navbar.php -->
<nav class="navbar navbar-expand-lg ft-navbar px-3">

    <a class="navbar-brand fw-bold" href="<?= APP_URL ?>/dashboard/index.php"
       style="color:var(--ft-brand)!important;">
        <i class="bi bi-wallet2 me-2" style="color:var(--ft-brand);"></i><?= e(APP_NAME) ?>
    </a>

    <!-- Mobile toggle — opens sidebar -->
    <button class="navbar-toggler border-0 ms-auto me-2" type="button" id="sidebarToggle">
        <i class="bi bi-list fs-4" style="color:var(--ft-text-primary);"></i>
    </button>

    <div class="collapse navbar-collapse" id="mainNav">
        <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">

            <li class="nav-item">
                <a class="nav-link" href="<?= APP_URL ?>/dashboard/index.php"
                   style="color:var(--ft-text-primary);">
                    <i class="bi bi-speedometer2 me-1"></i>Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?= APP_URL ?>/transactions/list.php"
                   style="color:var(--ft-text-primary);">
                    <i class="bi bi-arrow-left-right me-1"></i>Transactions
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?= APP_URL ?>/budgets/list_budget.php"
                   style="color:var(--ft-text-primary);">
                    <i class="bi bi-piggy-bank me-1"></i>Budgets
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?= APP_URL ?>/reports/monthly.php"
                   style="color:var(--ft-text-primary);">
                    <i class="bi bi-bar-chart-line me-1"></i>Reports
                </a>
            </li>

            <!-- User dropdown -->
            <li class="nav-item dropdown ms-lg-2">
                <a class="nav-link dropdown-toggle d-flex align-items-center gap-2"
                   href="#" role="button" data-bs-toggle="dropdown"
                   style="color:var(--ft-text-primary);">
                    <?php if (!empty($_SESSION['avatar'])): ?>
                        <img src="<?= APP_URL ?>/<?= e($_SESSION['avatar']) ?>" alt="Avatar"
                             style="width:30px;height:30px;border-radius:50%;object-fit:cover;">
                    <?php else: ?>
                        <div class="ft-avatar" style="width:30px;height:30px;font-size:0.8rem;">
                            <?= strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                    <span><?= e($_SESSION['user_name'] ?? 'Account') ?></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow">
                    <li>
                        <span class="dropdown-item-text small" style="color:var(--ft-text-muted);">
                            @<?= e($_SESSION['username'] ?? '') ?>
                        </span>
                    </li>
                    <li><hr class="dropdown-divider" style="border-color:#dce6f5;"></li>
                    <li>
                        <a class="dropdown-item" href="<?= APP_URL ?>/profile/edit.php">
                            <i class="bi bi-person-circle me-2"></i>Edit Profile
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="<?= APP_URL ?>/auth/logout.php"
                           style="color:var(--ft-danger);">
                            <i class="bi bi-box-arrow-right me-2"></i>Logout
                        </a>
                    </li>
                </ul>
            </li>

        </ul>
    </div>
</nav>

<script>
// Wire sidebar toggle button in navbar (mobile)
document.addEventListener('DOMContentLoaded', () => {
    const btn     = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('.ft-sidebar');
    if (btn && sidebar) {
        btn.addEventListener('click', () => sidebar.classList.toggle('show'));
    }
});
</script>
