<?php
/**
 * File: transactions/process.php
 * Purpose: Handle add/edit transaction form submissions
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
$controller = new TransactionController($pdo);
$action     = $_POST['action'] ?? '';

if ($action === 'add') {
    $result = $controller->store($userId, $_POST);

    if ($result['success']) {
        setFlash('success', 'Transaction added successfully.');
        redirect(APP_URL . '/transactions/list.php');
    } else {
        $_SESSION['form_errors'] = $result['errors'];
        $_SESSION['form_old']    = $_POST;
        redirect(APP_URL . '/transactions/add.php');
    }

} elseif ($action === 'edit') {
    $id     = (int)($_POST['id'] ?? 0);
    $result = $controller->update($id, $userId, $_POST);

    if ($result['success']) {
        setFlash('success', 'Transaction updated successfully.');
        redirect(APP_URL . '/transactions/list.php');
    } else {
        $_SESSION['form_errors'] = $result['errors'];
        $_SESSION['form_old']    = $_POST;
        redirect(APP_URL . '/transactions/edit.php?id=' . $id);
    }

} else {
    redirect(APP_URL . '/transactions/list.php');
}
