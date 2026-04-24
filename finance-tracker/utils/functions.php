<?php
/**
 * File: utils/functions.php
 * Purpose: Global helper / utility functions
 */

/**
 * Sanitize output to prevent XSS.
 */
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Redirect to a URL and exit.
 */
function redirect(string $url): void
{
    header("Location: $url");
    exit;
}

/**
 * Format a monetary amount with the user's currency symbol.
 */
function formatMoney(float $amount, string $currency = 'USD'): string
{
    $symbols = [
        'USD' => '$', 'EUR' => '€', 'GBP' => '£',
        'INR' => '₹', 'CAD' => 'C$', 'AUD' => 'A$',
        'JPY' => '¥', 'CHF' => 'Fr',
    ];
    $symbol = $symbols[$currency] ?? $currency . ' ';
    return $symbol . number_format($amount, 2);
}

/**
 * Return the logged-in user's currency (falls back to USD).
 */
function userCurrency(): string
{
    return $_SESSION['currency'] ?? 'USD';
}

/**
 * Flash message helpers.
 */
function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array
{
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Render a Bootstrap alert from a flash message.
 */
function renderFlash(): void
{
    $flash = getFlash();
    if (!$flash) return;

    $type = in_array($flash['type'], ['success','danger','warning','info'])
        ? $flash['type'] : 'info';

    echo '<div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">'
        . e($flash['message'])
        . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>'
        . '</div>';
}

/**
 * Generate a CSRF token and store it in the session.
 */
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify a submitted CSRF token.
 */
function verifyCsrf(string $token): bool
{
    return isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Return a hidden CSRF input field.
 */
function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrfToken()) . '">';
}

/**
 * Get the current month name and year string.
 */
function currentMonthLabel(): string
{
    return date('F Y');
}

/**
 * Clamp an integer between min and max.
 */
function clamp(int $value, int $min, int $max): int
{
    return max($min, min($max, $value));
}

/**
 * Return a Bootstrap badge class for transaction type.
 */
function typeBadge(string $type): string
{
    return $type === 'income' ? 'bg-success' : 'bg-danger';
}

/**
 * Return a sign-prefixed formatted amount string.
 */
function signedAmount(float $amount, string $type, string $currency = 'USD'): string
{
    $prefix = $type === 'income' ? '+' : '-';
    return $prefix . formatMoney($amount, $currency);
}

/**
 * Calculate percentage (safe division).
 */
function percentage(float $part, float $total): float
{
    if ($total == 0) return 0;
    return round(($part / $total) * 100, 1);
}

/**
 * Return an array of month names.
 */
function monthNames(): array
{
    return [
        1 => 'January', 2 => 'February', 3 => 'March',
        4 => 'April',   5 => 'May',       6 => 'June',
        7 => 'July',    8 => 'August',    9 => 'September',
        10 => 'October', 11 => 'November', 12 => 'December',
    ];
}

/**
 * Build a year range array for dropdowns.
 */
function yearRange(int $back = 5): array
{
    $current = (int)date('Y');
    return range($current - $back, $current + 1);
}
