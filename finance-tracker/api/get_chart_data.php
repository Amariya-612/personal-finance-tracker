<?php
/**
 * File: api/get_chart_data.php
 * Purpose: Return JSON data for Chart.js charts
 *
 * Query params:
 *   type  = pie_expense | bar_trend
 *   month = 1-12  (for pie_expense)
 *   year  = YYYY  (for pie_expense)
 *   months = N    (for bar_trend, default 6)
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/functions.php';
require_once __DIR__ . '/../models/Transaction.php';

header('Content-Type: application/json');

// Must be logged in
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$userId   = (int)$_SESSION['user_id'];
$type     = $_GET['type'] ?? 'pie_expense';
$txnModel = new Transaction($pdo);

// ── Pie chart: expenses by category ──────────────────────
if ($type === 'pie_expense') {
    $month = clamp((int)($_GET['month'] ?? date('m')), 1, 12);
    $year  = (int)($_GET['year'] ?? date('Y'));

    $rows = $txnModel->expenseByCategory($userId, $month, $year);

    $labels = [];
    $data   = [];
    $colors = [];

    foreach ($rows as $row) {
        $labels[] = $row['name'];
        $data[]   = (float)$row['total'];
        $colors[] = $row['color'];
    }

    echo json_encode([
        'labels'          => $labels,
        'datasets'        => [[
            'data'            => $data,
            'backgroundColor' => $colors,
            'borderWidth'     => 2,
            'borderColor'     => '#fff',
        ]],
    ]);
    exit;
}

// ── Bar chart: monthly income vs expense trend ────────────
if ($type === 'bar_trend') {
    $numMonths = max(1, min(24, (int)($_GET['months'] ?? 6)));
    $rows      = $txnModel->monthlyTrend($userId, $numMonths);

    // Build a map: "YYYY-MM" => [income, expense]
    $map = [];
    foreach ($rows as $row) {
        $key = $row['yr'] . '-' . str_pad($row['mo'], 2, '0', STR_PAD_LEFT);
        if (!isset($map[$key])) {
            $map[$key] = ['income' => 0.0, 'expense' => 0.0];
        }
        $map[$key][$row['type']] = (float)$row['total'];
    }

    // Fill in missing months
    $labels  = [];
    $income  = [];
    $expense = [];

    for ($i = $numMonths - 1; $i >= 0; $i--) {
        $dt  = new DateTime("first day of -$i month");
        $key = $dt->format('Y-m');
        $labels[]  = $dt->format('M Y');
        $income[]  = $map[$key]['income']  ?? 0.0;
        $expense[] = $map[$key]['expense'] ?? 0.0;
    }

    echo json_encode([
        'labels'   => $labels,
        'datasets' => [
            [
                'label'           => 'Income',
                'data'            => $income,
                'backgroundColor' => 'rgba(39,174,96,0.7)',
                'borderColor'     => '#27ae60',
                'borderWidth'     => 1,
                'borderRadius'    => 4,
            ],
            [
                'label'           => 'Expenses',
                'data'            => $expense,
                'backgroundColor' => 'rgba(231,76,60,0.7)',
                'borderColor'     => '#e74c3c',
                'borderWidth'     => 1,
                'borderRadius'    => 4,
            ],
        ],
    ]);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Unknown chart type']);
