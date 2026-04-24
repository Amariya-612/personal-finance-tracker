<?php
/**
 * File: auth/login.php
 * Purpose: Login page — White/Cyan/Blue gradient split card with OAuth
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils/functions.php';

if (!empty($_SESSION['user_id'])) {
    redirect(APP_URL . '/dashboard/index.php');
}

$pageTitle = 'Login';
$errors    = $_SESSION['auth_errors'] ?? [];
$old       = $_SESSION['auth_old']    ?? [];
unset($_SESSION['auth_errors'], $_SESSION['auth_old']);

// ── OAuth notice (flash from callback) ──────────────────
$oauthNotice = $_SESSION['oauth_notice'] ?? '';
unset($_SESSION['oauth_notice']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> — <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/vendor/bootstrap-icons/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
    <style>
        /* ── Page background: Pastel Grid ── */
        html, body.ft-auth-body {
            margin: 0;
            padding: 0;
            min-height: 100%;
        }

        body.ft-auth-body {
            background-color: #ffffff;
            overflow-y: auto;
            position: relative;
        }

        /* Fixed pastel grid fills the viewport behind everything */
        .pastel-grid {
            position: fixed;
            inset: 0;
            z-index: 0;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            grid-template-rows: repeat(4, 1fr);
            gap: 18px;
            padding: 18px;
            pointer-events: none;
            background: #ffffff;
        }

        .pastel-grid .pg-cell {
            border-radius: 18px;
            box-shadow: 0 4px 18px rgba(0,0,0,0.07), 0 1px 4px rgba(0,0,0,0.04);
            opacity: 0.82;
        }

        /* Row 1 */
        .pg-cell:nth-child(1)  { background: linear-gradient(135deg, #d0e8f7 0%, #b8d4f0 100%); }
        .pg-cell:nth-child(2)  { background: linear-gradient(135deg, #ddd6f3 0%, #c9bfee 100%); }
        .pg-cell:nth-child(3)  { background: linear-gradient(135deg, #fde8f0 0%, #f8cfe3 100%); }
        .pg-cell:nth-child(4)  { background: linear-gradient(135deg, #d4f0e8 0%, #b8e6d8 100%); }
        /* Row 2 */
        .pg-cell:nth-child(5)  { background: linear-gradient(135deg, #fde8d8 0%, #f9d4bc 100%); }
        .pg-cell:nth-child(6)  { background: linear-gradient(135deg, #e8eef5 0%, #d4dde8 100%); }
        .pg-cell:nth-child(7)  { background: linear-gradient(135deg, #c8e8f8 0%, #aed6f1 100%); }
        .pg-cell:nth-child(8)  { background: linear-gradient(135deg, #e8d8f5 0%, #d4c0ee 100%); }
        /* Row 3 */
        .pg-cell:nth-child(9)  { background: linear-gradient(135deg, #d8f0d8 0%, #c0e8c0 100%); }
        .pg-cell:nth-child(10) { background: linear-gradient(135deg, #fdf0d8 0%, #f8e0b8 100%); }
        .pg-cell:nth-child(11) { background: linear-gradient(135deg, #f0d8e8 0%, #e8c0d8 100%); }
        .pg-cell:nth-child(12) { background: linear-gradient(135deg, #d8eef8 0%, #bcddf5 100%); }
        /* Row 4 */
        .pg-cell:nth-child(13) { background: linear-gradient(135deg, #ece8f8 0%, #dcd4f4 100%); }
        .pg-cell:nth-child(14) { background: linear-gradient(135deg, #d8f4ee 0%, #b8ece0 100%); }
        .pg-cell:nth-child(15) { background: linear-gradient(135deg, #f8e8d8 0%, #f0d4bc 100%); }
        .pg-cell:nth-child(16) { background: linear-gradient(135deg, #e0eaf0 0%, #ccd8e4 100%); }

        /* Main content wrapper — sits above the grid, centers everything */
        .auth-center {
            position: relative;
            z-index: 1;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
            box-sizing: border-box;
        }

        /* ── Page brand logo above card ── */
        .page-brand-wrap {
            text-align: center;
            margin-bottom: 1.6rem;
            position: relative;
        }

        /* Main logo SVG container */
        .brand-logo-svg {
            display: block;
            margin: 0 auto 0.5rem;
            width: 100%;
            max-width: 560px;
            height: auto;
            filter: drop-shadow(0 4px 18px rgba(0,180,255,0.35));
        }

        .page-brand-sub {
            font-family: 'Segoe UI', system-ui, sans-serif;
            font-size: 0.88rem;
            font-weight: 600;
            letter-spacing: 0.22em;
            text-transform: uppercase;
            /* metallic gradient text */
            background: linear-gradient(90deg, #4a9fd4 0%, #1a1a2e 40%, #0057ff 70%, #00b4d8 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* ── Outer glow wrapper ── */
        .login-card-wrapper {
            width: 100%;
            max-width: 880px;
            border-radius: 22px;
            padding: 3px;
            background: linear-gradient(135deg, #00e5ff, #1565c0, #00e5ff);
            box-shadow:
                0 0 20px rgba(0,229,255,0.45),
                0 0 50px rgba(0,229,255,0.2),
                0 20px 60px rgba(0,0,0,0.35);
            animation: borderGlow 3.5s ease-in-out infinite;
        }

        @keyframes borderGlow {
            0%,100% { box-shadow: 0 0 20px rgba(0,229,255,0.45), 0 0 50px rgba(0,229,255,0.2), 0 20px 60px rgba(0,0,0,0.35); }
            50%      { box-shadow: 0 0 35px rgba(0,229,255,0.75), 0 0 80px rgba(0,229,255,0.35), 0 20px 60px rgba(0,0,0,0.35); }
        }

        /* ── Inner card ── */
        .login-card {
            border-radius: 20px;
            overflow: hidden;
            display: flex;
            min-height: 540px;
            background: #ffffff;
        }

        /* ════════════════════════════════════════
           LEFT PANEL — Pure White / Deep Navy
        ════════════════════════════════════════ */
        .login-left {
            flex: 1;
            background: #ffffff;
            padding: 3rem 2.75rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            clip-path: polygon(0 0, 93% 0, 100% 100%, 0 100%);
        }

        .login-left h2 {
            color: #0d1b4b;
            font-size: 2rem;
            font-weight: 800;
            letter-spacing: 0.02em;
            margin-bottom: 0.2rem;
        }

        .login-left .subtitle {
            color: #828282;
            font-size: 0.85rem;
            margin-bottom: 1.75rem;
        }

        /* Labels */
        .login-left .form-label {
            color: #333333;
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            margin-bottom: 0.35rem;
        }

        /* Input group */
        .login-left .input-group-text {
            background: #f5f7fa;
            border: 1.5px solid #c8d6e5;
            border-right: none;
            color: #1565c0;
            font-size: 0.9rem;
        }

        .login-left .form-control {
            background: #f5f7fa;
            border: 1.5px solid #c8d6e5;
            border-left: none;
            color: #0d1b4b;
            font-size: 0.9rem;
        }

        .login-left .form-control::placeholder { color: #b0bec5; }

        .login-left .form-control:focus {
            background: #fff;
            border-color: #00b0ff;
            box-shadow: 0 0 0 3px rgba(0,176,255,0.15);
            color: #0d1b4b;
        }

        .login-left .input-group:focus-within .input-group-text {
            border-color: #00b0ff;
            background: #fff;
        }

        .login-left .btn-outline-secondary {
            background: #f5f7fa;
            border: 1.5px solid #c8d6e5;
            border-left: none;
            color: #828282;
        }

        .login-left .btn-outline-secondary:hover { color: #1565c0; background: #f5f7fa; }

        /* ── Cyan-to-Blue gradient login button ── */
        .btn-login-primary {
            background: linear-gradient(135deg, #00e5ff 0%, #1565c0 100%);
            border: none;
            color: #ffffff;
            font-weight: 700;
            font-size: 0.95rem;
            border-radius: 50px;
            padding: 0.65rem 1.5rem;
            letter-spacing: 0.05em;
            transition: all 0.3s ease;
            box-shadow: 0 4px 18px rgba(21,101,192,0.4);
        }

        .btn-login-primary:hover {
            background: linear-gradient(135deg, #33eeff 0%, #1976d2 100%);
            box-shadow: 0 6px 26px rgba(21,101,192,0.55);
            transform: translateY(-1px);
            color: #fff;
        }

        /* ── Social divider ── */
        .social-divider {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin: 1.2rem 0 1rem;
        }

        .social-divider::before,
        .social-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #e0e7ef;
        }

        .social-divider span {
            color: #828282;
            font-size: 0.75rem;
            white-space: nowrap;
        }

        /* ── Social buttons ── */
        .btn-social {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            padding: 0.5rem 0.5rem;
            border-radius: 10px;
            font-size: 0.78rem;
            font-weight: 600;
            border: 1.5px solid #e0e7ef;
            background: #ffffff;
            color: #333333;
            text-decoration: none;
            transition: all 0.25s ease;
            cursor: pointer;
        }

        .btn-social:hover { transform: translateY(-1px); }
        .btn-social.google:hover   { border-color: #ea4335; box-shadow: 0 4px 14px rgba(234,67,53,0.2); color: #ea4335; }
        .btn-social.facebook:hover { border-color: #1877f2; box-shadow: 0 4px 14px rgba(24,119,242,0.2); color: #1877f2; }
        .btn-social.twitter:hover  { border-color: #000000; box-shadow: 0 4px 14px rgba(0,0,0,0.15); color: #000; }

        .btn-social .social-icon { width: 17px; height: 17px; flex-shrink: 0; }

        /* ── Register link ── */
        .register-link {
            color: #828282;
            font-size: 0.82rem;
            text-align: center;
            margin-top: 1rem;
        }

        .register-link a { color: #1565c0; font-weight: 700; text-decoration: none; }
        .register-link a:hover { text-decoration: underline; }

        /* ── Demo hint ── */
        .demo-hint {
            background: #e3f2fd;
            border: 1px solid #90caf9;
            border-radius: 8px;
            padding: 0.5rem 0.75rem;
            color: #1565c0;
            font-size: 0.75rem;
            margin-top: 0.75rem;
        }

        /* ── Error alert ── */
        .alert-danger {
            background: #fff5f5;
            border-color: #feb2b2;
            color: #c53030;
            font-size: 0.85rem;
            border-radius: 10px;
        }

        /* ════════════════════════════════════════
           RIGHT PANEL — Electric Cyan → Royal Blue
        ════════════════════════════════════════ */
        .login-right {
            width: 42%;
            background: linear-gradient(160deg, #00e5ff 0%, #1565c0 55%, #0d1b4b 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 3rem 2rem;
            text-align: center;
            flex-shrink: 0;
        }

        .login-right .welcome-badge {
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(10px);
            border: 1.5px solid rgba(255,255,255,0.35);
            border-radius: 50px;
            padding: 0.4rem 1.2rem;
            color: #fff;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            margin-bottom: 1.25rem;
        }

        .login-right h1 {
            color: #ffffff;
            font-size: 2rem;
            font-weight: 900;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            margin-bottom: 1rem;
            line-height: 1.2;
            text-shadow: 0 2px 16px rgba(0,0,0,0.25);
        }

        .login-right p {
            color: rgba(255,255,255,0.82);
            font-size: 0.87rem;
            line-height: 1.75;
            max-width: 230px;
        }

        .login-right .feature-list {
            list-style: none;
            padding: 0;
            margin: 1.5rem 0 0;
            text-align: left;
            width: 100%;
            max-width: 210px;
        }

        .login-right .feature-list li {
            color: rgba(255,255,255,0.92);
            font-size: 0.82rem;
            padding: 0.3rem 0;
            display: flex;
            align-items: center;
            gap: 0.55rem;
        }

        .login-right .feature-list li i {
            color: #00e5ff;
            font-size: 0.95rem;
            flex-shrink: 0;
        }

        /* ── Responsive ── */
        @media (max-width: 640px) {
            .login-right { display: none; }
            .login-left { clip-path: none; padding: 2.5rem 1.75rem; }
            .login-card-wrapper { max-width: 420px; }
        }
    </style>
</head>
<body class="ft-auth-body">

<!-- 4×4 pastel gradient grid background -->
<div class="pastel-grid" aria-hidden="true">
    <div class="pg-cell"></div><div class="pg-cell"></div>
    <div class="pg-cell"></div><div class="pg-cell"></div>
    <div class="pg-cell"></div><div class="pg-cell"></div>
    <div class="pg-cell"></div><div class="pg-cell"></div>
    <div class="pg-cell"></div><div class="pg-cell"></div>
    <div class="pg-cell"></div><div class="pg-cell"></div>
    <div class="pg-cell"></div><div class="pg-cell"></div>
    <div class="pg-cell"></div><div class="pg-cell"></div>
</div>

<div class="auth-center">

    <!-- ══ Page title above card ══ -->
    <div class="page-brand-wrap">
        <!--
            SVG logo: "FINANCE TRACKER" in bold charcoal/metallic black
            with a cerulean-to-electric-blue swoosh cutting through the letters.
        -->
        <svg class="brand-logo-svg" viewBox="0 0 560 80" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Finance Tracker">
            <defs>
                <!-- Metallic charcoal gradient for letters -->
                <linearGradient id="metalGrad" x1="0%" y1="0%" x2="0%" y2="100%">
                    <stop offset="0%"   stop-color="#4a4a5a"/>
                    <stop offset="35%"  stop-color="#1a1a2e"/>
                    <stop offset="65%"  stop-color="#0d0d1a"/>
                    <stop offset="100%" stop-color="#2a2a3e"/>
                </linearGradient>
                <!-- Cerulean-to-electric-blue swoosh gradient -->
                <linearGradient id="swooshGrad" x1="0%" y1="0%" x2="100%" y2="0%">
                    <stop offset="0%"   stop-color="#00b4d8"/>
                    <stop offset="35%"  stop-color="#0096ff"/>
                    <stop offset="70%"  stop-color="#0057ff"/>
                    <stop offset="100%" stop-color="#00e5ff"/>
                </linearGradient>
                <!-- Bevel highlight for 3D effect -->
                <linearGradient id="bevelGrad" x1="0%" y1="0%" x2="0%" y2="100%">
                    <stop offset="0%"  stop-color="#ffffff" stop-opacity="0.18"/>
                    <stop offset="50%" stop-color="#ffffff" stop-opacity="0"/>
                </linearGradient>
                <!-- Clip path: hide swoosh outside the text bounding box -->
                <clipPath id="textClip">
                    <text x="50%" y="58" text-anchor="middle"
                          font-family="'Segoe UI Black','Arial Black',sans-serif"
                          font-size="52" font-weight="900" letter-spacing="3">FINANCE TRACKER</text>
                </clipPath>
            </defs>

            <!-- ── Letter base: metallic charcoal ── -->
            <text x="50%" y="58" text-anchor="middle"
                  font-family="'Segoe UI Black','Arial Black',sans-serif"
                  font-size="52" font-weight="900" letter-spacing="3"
                  fill="url(#metalGrad)">FINANCE TRACKER</text>

            <!-- ── Bevel highlight layer ── -->
            <text x="50%" y="58" text-anchor="middle"
                  font-family="'Segoe UI Black','Arial Black',sans-serif"
                  font-size="52" font-weight="900" letter-spacing="3"
                  fill="url(#bevelGrad)">FINANCE TRACKER</text>

            <!-- ── Swoosh: fluid wave cutting through letters ── -->
            <!-- The swoosh is clipped to only show INSIDE the letters -->
            <g clip-path="url(#textClip)">
                <path d="M-10 38 Q80 10 180 42 Q280 72 380 30 Q460 5 570 38 L570 52 Q460 20 380 44 Q280 82 180 54 Q80 24 -10 50 Z"
                      fill="url(#swooshGrad)" opacity="0.92"/>
            </g>

            <!-- ── Full swoosh line visible outside letters (motion trail) ── -->
            <path d="M-10 38 Q80 10 180 42 Q280 72 380 30 Q460 5 570 38 L570 41 Q460 8 380 33 Q280 75 180 45 Q80 13 -10 41 Z"
                  fill="url(#swooshGrad)" opacity="0.22"/>
        </svg>

        <p class="page-brand-sub">Your personal finance companion</p>
    </div>

    <div class="login-card-wrapper">
        <div class="login-card">

            <!-- ══ LEFT PANEL ══ -->
            <div class="login-left">

                <h2>Login</h2>
                <p class="subtitle">Sign in to your Finance Tracker account</p>

                <?php if ($oauthNotice): ?>
                    <div class="alert alert-info py-2 small"><?= e($oauthNotice) ?></div>
                <?php endif; ?>

                <?php if (!empty($errors['general'])): ?>
                    <div class="alert alert-danger py-2"><?= e($errors['general']) ?></div>
                <?php endif; ?>

                <form action="<?= APP_URL ?>/auth/process_login.php" method="POST" novalidate>
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="login">

                    <!-- Username -->
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-person-fill"></i></span>
                            <input type="text" id="username" name="username"
                                   class="form-control <?= !empty($errors['username']) ? 'is-invalid' : '' ?>"
                                   value="<?= e($old['username'] ?? 'demo') ?>"
                                   placeholder="your_username" required autofocus>
                            <?php if (!empty($errors['username'])): ?>
                                <div class="invalid-feedback"><?= e($errors['username']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Password -->
                    <div class="mb-4">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                            <input type="password" id="password" name="password"
                                   class="form-control <?= !empty($errors['password']) ? 'is-invalid' : '' ?>"
                                   placeholder="••••••••" value="demo1234" required>
                            <button class="btn btn-outline-secondary" type="button"
                                    onclick="togglePassword('password')">
                                <i class="bi bi-eye" id="password-eye"></i>
                            </button>
                            <?php if (!empty($errors['password'])): ?>
                                <div class="invalid-feedback"><?= e($errors['password']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-login-primary w-100">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
                    </button>
                </form>

                <p class="register-link">
                    Don't have an account? <a href="<?= APP_URL ?>/auth/register.php">Register</a>
                </p>

                <div class="demo-hint">
                    <i class="bi bi-info-circle me-1"></i>
                    <strong>Demo:</strong> username: <strong>demo</strong> &nbsp;/&nbsp; password: <strong>demo1234</strong>
                </div>
            </div>

            <!-- ══ RIGHT PANEL ══ -->
            <div class="login-right">
                <h1>Welcome<br>Back!</h1>
                <p>
                    Smart Insights to Secure Your Financial Freedom<br>
                    <span style="font-size:0.82rem;opacity:0.9;">የገንዘብ ነጻነትዎን በእጅዎ ያረጋግጡ!!!</span>
                </p>
                <ul class="feature-list">
                    <li><i class="bi bi-check-circle-fill"></i> Track income &amp; expenses</li>
                    <li><i class="bi bi-check-circle-fill"></i> Set monthly budgets</li>
                    <li><i class="bi bi-check-circle-fill"></i> Visual reports &amp; charts</li>
                    <li><i class="bi bi-check-circle-fill"></i> Export CSV reports</li>
                    <li><i class="bi bi-check-circle-fill"></i> Secure &amp; private</li>
                </ul>
            </div>

        </div><!-- /.login-card -->
    </div><!-- /.login-card-wrapper -->
</div>

<script src="<?= APP_URL ?>/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
function togglePassword(id) {
    const input = document.getElementById(id);
    const eye   = document.getElementById(id + '-eye');
    if (input.type === 'password') { input.type = 'text'; eye.className = 'bi bi-eye-slash'; }
    else                           { input.type = 'password'; eye.className = 'bi bi-eye'; }
}
</script>

<!-- Developer badge with contact popup -->
<div style="position:fixed; bottom:1.2rem; right:1.2rem; z-index:9999; font-family:'Segoe UI',system-ui,sans-serif;">

    <!-- Toggle button -->
    <button onclick="toggleDevCard()" style="
        display: flex;
        align-items: center;
        gap: 0.5rem;
        background: linear-gradient(135deg, #0057ff 0%, #00b4d8 100%);
        color: #fff;
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.06em;
        padding: 0.45rem 1rem 0.45rem 0.75rem;
        border-radius: 50px;
        border: none;
        box-shadow: 0 4px 18px rgba(0,87,255,0.35);
        cursor: pointer;
        animation: devBadgePop 0.5s cubic-bezier(0.34,1.56,0.64,1) both;
        transition: box-shadow 0.25s, transform 0.25s;
    "
    onmouseover="this.style.boxShadow='0 6px 24px rgba(0,87,255,0.55)';this.style.transform='translateY(-1px)'"
    onmouseout="this.style.boxShadow='0 4px 18px rgba(0,87,255,0.35)';this.style.transform='translateY(0)'">
        <span style="width:22px;height:22px;background:rgba(255,255,255,0.2);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:0.8rem;flex-shrink:0;">👨‍💻</span>
        Developer &nbsp;<strong>Amariya T</strong>
        <i class="bi bi-chevron-up" id="devChevron" style="font-size:0.65rem;margin-left:0.2rem;transition:transform 0.25s;"></i>
    </button>

    <!-- Contact card — hidden by default -->
    <div id="devContactCard" style="
        display: none;
        position: absolute;
        bottom: calc(100% + 10px);
        right: 0;
        background: #ffffff;
        border: 1.5px solid #dce6f5;
        border-radius: 14px;
        box-shadow: 0 8px 28px rgba(21,101,192,0.18);
        padding: 1rem 1.25rem;
        min-width: 230px;
        text-align: left;
        animation: devCardPop 0.2s cubic-bezier(0.34,1.56,0.64,1) both;
    ">
        <p style="font-size:0.78rem;font-weight:700;color:#0d1b4b;margin:0 0 0.6rem;">👨‍💻 Amariya T</p>
        <a href="mailto:amariyatesfaw@gmail.com" style="display:flex;align-items:center;gap:0.4rem;font-size:0.75rem;color:#1565c0;text-decoration:none;margin-bottom:0.4rem;">
            <i class="bi bi-envelope-fill"></i> amariyatesfaw@gmail.com
        </a>
        <a href="tel:+251927618147" style="display:flex;align-items:center;gap:0.4rem;font-size:0.75rem;color:#1565c0;text-decoration:none;">
            <i class="bi bi-telephone-fill"></i> +251 927 618 147
        </a>
    </div>

</div>

<style>
@keyframes devBadgePop {
    from { opacity:0; transform:translateY(20px) scale(0.85); }
    to   { opacity:1; transform:translateY(0) scale(1); }
}
@keyframes devCardPop {
    from { opacity:0; transform:translateY(8px) scale(0.95); }
    to   { opacity:1; transform:translateY(0) scale(1); }
}
</style>

<script>
function toggleDevCard() {
    const card    = document.getElementById('devContactCard');
    const chevron = document.getElementById('devChevron');
    const visible = card.style.display === 'block';
    card.style.display = visible ? 'none' : 'block';
    chevron.style.transform = visible ? 'rotate(0deg)' : 'rotate(180deg)';
    if (!visible) {
        setTimeout(() => {
            document.addEventListener('click', function handler(e) {
                if (!card.contains(e.target) && !e.target.closest('button[onclick="toggleDevCard()"]')) {
                    card.style.display = 'none';
                    chevron.style.transform = 'rotate(0deg)';
                    document.removeEventListener('click', handler);
                }
            });
        }, 10);
    }
}
</script>
</body>
</html>
