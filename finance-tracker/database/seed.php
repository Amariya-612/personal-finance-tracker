<?php
/**
 * File: database/seed.php
 * Purpose: Insert default categories and a demo user
 * Safe to run multiple times (idempotent via INSERT IGNORE / duplicate checks)
 */

require_once __DIR__ . '/../config/database.php';

try {
    // -------------------------------------------------------
    // 1. Demo user  (username: demo  password: demo1234)
    // -------------------------------------------------------
    $demoEmail    = 'demo@financetracker.com';
    $demoUsername = 'demo';

    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$demoEmail]);
    $demoUser = $stmt->fetch();

    if (!$demoUser) {
        $hash = password_hash('demo1234', PASSWORD_BCRYPT);
        $ins  = $pdo->prepare(
            "INSERT INTO users (name, username, email, password, currency) VALUES (?, ?, ?, ?, 'ETB')"
        );
        $ins->execute(['Demo User', $demoUsername, $demoEmail, $hash]);
        $demoUserId = (int)$pdo->lastInsertId();
        echo "✔ Demo user created  (username: $demoUsername  password: demo1234)\n";
    } else {
        $demoUserId = (int)$demoUser['id'];

        // Patch: ensure username column is populated for existing demo user
        $cols = $pdo->query("SHOW COLUMNS FROM `users`")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('username', $cols)) {
            $pdo->exec("ALTER TABLE `users` ADD COLUMN `username` VARCHAR(60) NULL UNIQUE AFTER `name`");
        }
        if (!in_array('avatar', $cols)) {
            $pdo->exec("ALTER TABLE `users` ADD COLUMN `avatar` VARCHAR(255) NULL DEFAULT NULL AFTER `currency`");
        }
        // Set username if still null
        $pdo->prepare("UPDATE users SET username = ? WHERE id = ? AND (username IS NULL OR username = '')")
            ->execute([$demoUsername, $demoUserId]);

        echo "✔ Demo user already exists (id: $demoUserId) — username set to '$demoUsername'\n";
    }

    // -------------------------------------------------------
    // 2. Global default categories  (user_id = NULL)
    // -------------------------------------------------------
    $defaultCategories = [
        // Expense categories
        ['name' => 'Food & Dining',    'type' => 'expense', 'icon' => 'bi-cup-hot',        'color' => '#e74c3c'],
        ['name' => 'Rent / Housing',   'type' => 'expense', 'icon' => 'bi-house',           'color' => '#e67e22'],
        ['name' => 'Transport',        'type' => 'expense', 'icon' => 'bi-car-front',       'color' => '#f39c12'],
        ['name' => 'Utilities',        'type' => 'expense', 'icon' => 'bi-lightning',       'color' => '#9b59b6'],
        ['name' => 'Healthcare',       'type' => 'expense', 'icon' => 'bi-heart-pulse',     'color' => '#e91e63'],
        ['name' => 'Entertainment',    'type' => 'expense', 'icon' => 'bi-controller',      'color' => '#3498db'],
        ['name' => 'Shopping',         'type' => 'expense', 'icon' => 'bi-bag',             'color' => '#1abc9c'],
        ['name' => 'Education',        'type' => 'expense', 'icon' => 'bi-book',            'color' => '#2ecc71'],
        ['name' => 'Travel',           'type' => 'expense', 'icon' => 'bi-airplane',        'color' => '#00bcd4'],
        ['name' => 'Other Expense',    'type' => 'expense', 'icon' => 'bi-three-dots',      'color' => '#95a5a6'],
        // Income categories
        ['name' => 'Salary',           'type' => 'income',  'icon' => 'bi-briefcase',       'color' => '#27ae60'],
        ['name' => 'Freelance',        'type' => 'income',  'icon' => 'bi-laptop',          'color' => '#2980b9'],
        ['name' => 'Investments',      'type' => 'income',  'icon' => 'bi-graph-up-arrow',  'color' => '#8e44ad'],
        ['name' => 'Gifts',            'type' => 'income',  'icon' => 'bi-gift',            'color' => '#f06292'],
        ['name' => 'Other Income',     'type' => 'income',  'icon' => 'bi-plus-circle',     'color' => '#16a085'],
    ];

    $checkCat = $pdo->prepare(
        "SELECT id FROM categories WHERE name = ? AND type = ? AND user_id IS NULL"
    );
    $insCat = $pdo->prepare(
        "INSERT INTO categories (user_id, name, type, icon, color) VALUES (NULL, ?, ?, ?, ?)"
    );

    $categoryIds = []; // name => id
    foreach ($defaultCategories as $cat) {
        $checkCat->execute([$cat['name'], $cat['type']]);
        $existing = $checkCat->fetch();
        if (!$existing) {
            $insCat->execute([$cat['name'], $cat['type'], $cat['icon'], $cat['color']]);
            $categoryIds[$cat['name']] = (int)$pdo->lastInsertId();
            echo "  + Category '{$cat['name']}' inserted.\n";
        } else {
            $categoryIds[$cat['name']] = (int)$existing['id'];
        }
    }
    echo "✔ Default categories ready.\n";

    // -------------------------------------------------------
    // 3. Sample transactions for demo user (last 3 months)
    // -------------------------------------------------------
    $checkTxn = $pdo->prepare("SELECT COUNT(*) FROM transactions WHERE user_id = ?");
    $checkTxn->execute([$demoUserId]);
    $txnCount = (int)$checkTxn->fetchColumn();

    if ($txnCount === 0) {
        $insTxn = $pdo->prepare(
            "INSERT INTO transactions (user_id, category_id, type, amount, description, date)
             VALUES (?, ?, ?, ?, ?, ?)"
        );

        $now   = new DateTime();
        $month = (int)$now->format('m');
        $year  = (int)$now->format('Y');

        // Helper: build date string for a given month offset and day
        $d = function (int $monthOffset, int $day) use ($month, $year): string {
            $dt = new DateTime("$year-$month-01");
            $dt->modify("$monthOffset month");
            $m = (int)$dt->format('m');
            $y = (int)$dt->format('Y');
            $maxDay = (int)(new DateTime("$y-$m-01"))->format('t');
            $day = min($day, $maxDay);
            return "$y-" . str_pad($m, 2, '0', STR_PAD_LEFT) . "-" . str_pad($day, 2, '0', STR_PAD_LEFT);
        };

        $samples = [
            // Current month
            [$demoUserId, $categoryIds['Salary'],        'income',  3500.00, 'Monthly salary',          $d(0, 1)],
            [$demoUserId, $categoryIds['Freelance'],     'income',   800.00, 'Web project payment',     $d(0, 5)],
            [$demoUserId, $categoryIds['Rent / Housing'],'expense', 1200.00, 'Monthly rent',            $d(0, 2)],
            [$demoUserId, $categoryIds['Food & Dining'], 'expense',  320.00, 'Groceries & restaurants', $d(0, 8)],
            [$demoUserId, $categoryIds['Transport'],     'expense',   95.00, 'Fuel & bus pass',         $d(0, 10)],
            [$demoUserId, $categoryIds['Utilities'],     'expense',  140.00, 'Electricity & internet',  $d(0, 12)],
            [$demoUserId, $categoryIds['Entertainment'], 'expense',   60.00, 'Netflix & cinema',        $d(0, 15)],
            // Previous month
            [$demoUserId, $categoryIds['Salary'],        'income',  3500.00, 'Monthly salary',          $d(-1, 1)],
            [$demoUserId, $categoryIds['Investments'],   'income',   250.00, 'Dividend payout',         $d(-1, 14)],
            [$demoUserId, $categoryIds['Rent / Housing'],'expense', 1200.00, 'Monthly rent',            $d(-1, 2)],
            [$demoUserId, $categoryIds['Food & Dining'], 'expense',  290.00, 'Groceries',               $d(-1, 7)],
            [$demoUserId, $categoryIds['Shopping'],      'expense',  180.00, 'Clothing',                $d(-1, 18)],
            [$demoUserId, $categoryIds['Healthcare'],    'expense',   75.00, 'Doctor visit',            $d(-1, 22)],
            [$demoUserId, $categoryIds['Transport'],     'expense',   85.00, 'Fuel',                    $d(-1, 9)],
            // Two months ago
            [$demoUserId, $categoryIds['Salary'],        'income',  3500.00, 'Monthly salary',          $d(-2, 1)],
            [$demoUserId, $categoryIds['Freelance'],     'income',   600.00, 'Design project',          $d(-2, 20)],
            [$demoUserId, $categoryIds['Rent / Housing'],'expense', 1200.00, 'Monthly rent',            $d(-2, 2)],
            [$demoUserId, $categoryIds['Food & Dining'], 'expense',  310.00, 'Groceries',               $d(-2, 6)],
            [$demoUserId, $categoryIds['Travel'],        'expense',  450.00, 'Weekend trip',            $d(-2, 14)],
            [$demoUserId, $categoryIds['Education'],     'expense',  120.00, 'Online course',           $d(-2, 25)],
            [$demoUserId, $categoryIds['Utilities'],     'expense',  130.00, 'Utilities',               $d(-2, 11)],
        ];

        foreach ($samples as $row) {
            $insTxn->execute($row);
        }
        echo "✔ Sample transactions inserted (" . count($samples) . " records).\n";
    } else {
        echo "✔ Transactions already exist for demo user, skipping.\n";
    }

    // -------------------------------------------------------
    // 4. Sample budgets for demo user (current month)
    // -------------------------------------------------------
    $checkBud = $pdo->prepare("SELECT COUNT(*) FROM budgets WHERE user_id = ?");
    $checkBud->execute([$demoUserId]);
    $budCount = (int)$checkBud->fetchColumn();

    if ($budCount === 0) {
        $insBud = $pdo->prepare(
            "INSERT IGNORE INTO budgets (user_id, category_id, amount, month, year)
             VALUES (?, ?, ?, ?, ?)"
        );
        $cm = (int)date('m');
        $cy = (int)date('Y');

        $budgets = [
            [$demoUserId, $categoryIds['Food & Dining'],  400.00, $cm, $cy],
            [$demoUserId, $categoryIds['Rent / Housing'], 1200.00, $cm, $cy],
            [$demoUserId, $categoryIds['Transport'],       150.00, $cm, $cy],
            [$demoUserId, $categoryIds['Entertainment'],   100.00, $cm, $cy],
            [$demoUserId, $categoryIds['Shopping'],        200.00, $cm, $cy],
            [$demoUserId, $categoryIds['Utilities'],       160.00, $cm, $cy],
        ];

        foreach ($budgets as $b) {
            $insBud->execute($b);
        }
        echo "✔ Sample budgets inserted.\n";
    } else {
        echo "✔ Budgets already exist for demo user, skipping.\n";
    }

    echo "\n✅ Seeding complete.\n";
    echo "   Login — username: $demoUsername  /  password: demo1234\n";

} catch (PDOException $e) {
    die("❌ Seed Error: " . $e->getMessage() . "\n");
}
