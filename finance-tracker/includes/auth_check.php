<?php
/**
 * File: includes/auth_check.php
 * Purpose: Redirect unauthenticated users to the login page
 * Include at the top of every protected page.
 */

require_once __DIR__ . '/../config/config.php';

if (empty($_SESSION['user_id'])) {
    setFlash('warning', 'Please log in to access that page.');
    header('Location: ' . APP_URL . '/auth/login.php');
    exit;
}
