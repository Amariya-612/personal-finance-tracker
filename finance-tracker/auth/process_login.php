<?php
/**
 * File: auth/process_login.php
 * Purpose: Handle login and registration form submissions (POST only)
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/functions.php';
require_once __DIR__ . '/../controllers/AuthController.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(APP_URL . '/auth/login.php');
}

$action     = $_POST['action'] ?? '';
$controller = new AuthController($pdo);

if ($action === 'login') {
    $result = $controller->login($_POST);

    if ($result['success']) {
        setFlash('success', 'Welcome back, ' . e($_SESSION['user_name']) . '!');
        redirect(APP_URL . '/dashboard/index.php');
    } else {
        $_SESSION['auth_errors'] = $result['errors'];
        $_SESSION['auth_old']    = ['username' => $_POST['username'] ?? ''];
        redirect(APP_URL . '/auth/login.php');
    }

} elseif ($action === 'register') {
    $result = $controller->register($_POST);

    if ($result['success']) {
        setFlash('success', 'Account created! Please log in.');
        redirect(APP_URL . '/auth/login.php');
    } else {
        $_SESSION['auth_errors'] = $result['errors'];
        $_SESSION['auth_old']    = [
            'name'     => $_POST['name']     ?? '',
            'username' => $_POST['username'] ?? '',
            'email'    => $_POST['email']    ?? '',
            'currency' => $_POST['currency'] ?? 'ETB',
        ];
        redirect(APP_URL . '/auth/register.php');
    }

} else {
    redirect(APP_URL . '/auth/login.php');
}
