<?php
/**
 * File: api/get_summary.php
 * Purpose: Return JSON summary stats for the dashboard
 *
 * Query params:
 *   month = 1-12  (default: current month)
 *   year  = YYYY  (default: current year)
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/functions.php';
require_once __DIR__ . '/../models/Transaction.php';
require_once __DIR__ . '/../models/Budget.php';

header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$userId   = (int)$_SESSION['user_id'];
$month    = clamp((int)($_GET['month'] ?? date('m')), 1, 12);
$year     = (int)($_GET['year'] ?? date('Y'));
$currency = userCurrency();

$txnModel = new Transaction($pdo);
$budModel = new Budget($pdo);

$summary  = $txnModel->monthlySummary($userId, $month, $year);
$overview = $budModel->monthlyOverview($userId, $month, $year);

$income  = $summary['income'];
$expense = $summary['expense'];
$balance = $income - $expense;

echo json_encode([
    'month'         => $month,
    'year'          => $year,
    'currency'      => $currency,
    'income'        => $income,
    'expense'       => $expense,
    'balance'       => $balance,
    'income_fmt'    => formatMoney($income,  $currency),
    'expense_fmt'   => formatMoney($expense, $currency),
    'balance_fmt'   => formatMoney(abs($balance), $currency),
    'balance_sign'  => $balance >= 0 ? '+' : '-',
    'budget_total'  => $overview['total_budget'],
    'budget_spent'  => $overview['total_spent'],
    'budget_pct'    => percentage($overview['total_spent'], $overview['total_budget']),
]);
