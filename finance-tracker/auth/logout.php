<?php
/**
 * File: auth/logout.php
 * Purpose: Destroy session and redirect to login
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils/functions.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../config/database.php';

$controller = new AuthController($pdo);
$controller->logout();

setFlash('success', 'You have been logged out successfully.');
redirect(APP_URL . '/auth/login.php');
