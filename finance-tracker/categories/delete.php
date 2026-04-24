<?php
/**
 * File: categories/delete.php
 * Purpose: Handle category deletion (POST only)
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/functions.php';
require_once __DIR__ . '/../models/Category.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(APP_URL . '/categories/list.php');
}

if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
    setFlash('danger', 'Invalid request.');
    redirect(APP_URL . '/categories/list.php');
}

$userId   = (int)$_SESSION['user_id'];
$id       = (int)($_POST['id'] ?? 0);
$catModel = new Category($pdo);

$cat = $catModel->findById($id, $userId);

if (!$cat) {
    setFlash('danger', 'Category not found.');
    redirect(APP_URL . '/categories/list.php');
}

if ($cat['user_id'] === null) {
    setFlash('danger', 'Global categories cannot be deleted.');
    redirect(APP_URL . '/categories/list.php');
}

if ($catModel->isInUse($id)) {
    setFlash('danger', 'Cannot delete: this category is used in one or more transactions.');
    redirect(APP_URL . '/categories/list.php');
}

$catModel->delete($id, $userId);
setFlash('success', 'Category deleted.');
redirect(APP_URL . '/categories/list.php');
