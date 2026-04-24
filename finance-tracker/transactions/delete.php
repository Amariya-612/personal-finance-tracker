<?php
/**
 * File: transactions/delete.php
 * Purpose: Handle transaction deletion (POST only)
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/functions.php';
require_once __DIR__ . '/../controllers/TransactionController.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(APP_URL . '/transactions/list.php');
}

$userId     = (int)$_SESSION['user_id'];
$id         = (int)($_POST['id'] ?? 0);
$csrfToken  = $_POST['csrf_token'] ?? '';
$controller = new TransactionController($pdo);

$result = $controller->destroy($id, $userId, $csrfToken);

if ($result['success']) {
    setFlash('success', 'Transaction deleted.');
} else {
    setFlash('danger', $result['message']);
}

redirect(APP_URL . '/transactions/list.php');
