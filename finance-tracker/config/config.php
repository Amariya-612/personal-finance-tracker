<?php
/**
 * File: config/config.php
 * Purpose: Global application settings and constants
 */

// ── Application ──────────────────────────────────────────
define('APP_NAME',    'Finance Tracker');
define('APP_VERSION', '1.0.0');
define('APP_URL',     'http://localhost/finance-tracker'); // adjust for your environment

// ── Session ───────────────────────────────────────────────
define('SESSION_NAME',    'ft_session');
define('SESSION_LIFETIME', 7200); // seconds (2 hours)

// ── Security ──────────────────────────────────────────────
define('BCRYPT_COST', 12);

// ── Pagination ────────────────────────────────────────────
define('ROWS_PER_PAGE', 15);

// ── Date / Time ───────────────────────────────────────────
define('DATE_FORMAT',     'd M Y');
define('DATETIME_FORMAT', 'd M Y H:i');

// ── Supported currencies ──────────────────────────────────
define('CURRENCIES', ['USD', 'EUR', 'GBP', 'INR', 'CAD', 'AUD', 'JPY', 'CHF', 'ETB']);

// ── Start session securely ────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path'     => '/',
        'secure'   => false,   // set true when using HTTPS
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
