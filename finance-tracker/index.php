<?php
/**
 * File: index.php
 * Purpose: Application entry point — redirect to dashboard or login
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/utils/functions.php';

if (!empty($_SESSION['user_id'])) {
    redirect(APP_URL . '/dashboard/index.php');
} else {
    redirect(APP_URL . '/auth/login.php');
}
