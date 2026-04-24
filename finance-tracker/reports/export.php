<?php
/**
 * File: reports/export.php
 * Purpose: Export transactions as CSV
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/functions.php';
require_once __DIR__ . '/../models/Transaction.php';

$userId = (int)$_SESSION['user_id'];

// Build filters from GET params
$filters = [];

if (!empty($_GET['month']) && !empty($_GET['year'])) {
    $month = clamp((int)$_GET['month'], 1, 12);
    $year  = (int)$_GET['year'];
    $filters['date_from'] = "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-01";
    $filters['date_to']   = date('Y-m-t', mktime(0, 0, 0, $month, 1, $year));
    $filename = "transactions_{$year}_" . str_pad($month, 2, '0', STR_PAD_LEFT) . ".csv";
} elseif (!empty($_GET['year'])) {
    $year = (int)$_GET['year'];
    $filters['date_from'] = "$year-01-01";
    $filters['date_to']   = "$year-12-31";
    $filename = "transactions_{$year}.csv";
} else {
    $filename = "transactions_all.csv";
}

if (!empty($_GET['type'])) {
    $filters['type'] = $_GET['type'];
}

$txnModel     = new Transaction($pdo);
$transactions = $txnModel->getAll($userId, $filters, 1, 10000);

// Output CSV headers
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$out = fopen('php://output', 'w');

// BOM for Excel UTF-8 compatibility
fwrite($out, "\xEF\xBB\xBF");

// CSV header row
fputcsv($out, ['Date', 'Type', 'Category', 'Description', 'Amount', 'Currency']);

foreach ($transactions as $txn) {
    fputcsv($out, [
        $txn['date'],
        ucfirst($txn['type']),
        $txn['category_name'],
        $txn['description'],
        number_format((float)$txn['amount'], 2, '.', ''),
        userCurrency(),
    ]);
}

fclose($out);
exit;
