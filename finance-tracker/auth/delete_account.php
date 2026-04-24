<?php
/**
 * File: auth/delete_account.php
 * Purpose: Permanently delete the logged-in user's account and all their data.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/functions.php';
require_once __DIR__ . '/../models/User.php';

// POST only
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(APP_URL . '/dashboard/index.php');
}

// CSRF check
if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
    setFlash('danger', 'Invalid request. Please try again.');
    redirect(APP_URL . '/dashboard/index.php');
}

$userId    = (int)$_SESSION['user_id'];
$userModel = new User($pdo);
$user      = $userModel->findById($userId);

if (!$user) {
    redirect(APP_URL . '/auth/logout.php');
}

// Verify password before deleting
$confirmPassword = $_POST['confirm_password'] ?? '';

if (empty($confirmPassword) || !$userModel->verifyPassword($confirmPassword, $user['password'])) {
    setFlash('danger', 'Incorrect password. Account was not deleted.');
    redirect(APP_URL . '/dashboard/index.php');
}

// ── Delete avatar file if exists ──────────────────────────
if (!empty($user['avatar'])) {
    $avatarFile = __DIR__ . '/../' . $user['avatar'];
    if (file_exists($avatarFile)) {
        @unlink($avatarFile);
    }
}

// ── Delete user (CASCADE removes transactions, budgets, categories) ──
try {
    $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$userId]);
} catch (PDOException $e) {
    setFlash('danger', 'Could not delete account. Please try again.');
    redirect(APP_URL . '/dashboard/index.php');
}

// ── Destroy session ───────────────────────────────────────
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();

// ── Redirect to login with message ───────────────────────
session_start();
setFlash('success', 'Your account has been permanently deleted.');
redirect(APP_URL . '/auth/login.php');
