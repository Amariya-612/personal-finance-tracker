<?php
/**
 * File: database/init.php
 * Purpose: Initialize database and create all required tables
 * Run ONCE during setup: php database/init.php
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'finance_tracker');

try {
    $pdo = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "`
                CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✔ Database '" . DB_NAME . "' ready.\n";

    $pdo->exec("USE `" . DB_NAME . "`");

    // ── TABLE: users ──────────────────────────────────────
    $pdo->exec("CREATE TABLE IF NOT EXISTS `users` (
        `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `name`       VARCHAR(100)  NOT NULL,
        `username`   VARCHAR(60)   NOT NULL UNIQUE,
        `email`      VARCHAR(150)  NOT NULL UNIQUE,
        `password`   VARCHAR(255)  NOT NULL,
        `currency`   VARCHAR(10)   NOT NULL DEFAULT 'ETB',
        `avatar`     VARCHAR(255)  NULL DEFAULT NULL COMMENT 'Profile picture path',
        `created_at` TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "✔ Table 'users' ready.\n";

    // ── TABLE: categories ─────────────────────────────────
    $pdo->exec("CREATE TABLE IF NOT EXISTS `categories` (
        `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `user_id`    INT UNSIGNED     NULL COMMENT 'NULL = global/default category',
        `name`       VARCHAR(100)  NOT NULL,
        `type`       ENUM('income','expense') NOT NULL DEFAULT 'expense',
        `icon`       VARCHAR(50)   NOT NULL DEFAULT 'bi-tag',
        `color`      VARCHAR(20)   NOT NULL DEFAULT '#6c757d',
        `created_at` TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT `fk_cat_user`
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
            ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "✔ Table 'categories' ready.\n";

    // ── TABLE: transactions ───────────────────────────────
    $pdo->exec("CREATE TABLE IF NOT EXISTS `transactions` (
        `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `user_id`     INT UNSIGNED NOT NULL,
        `category_id` INT UNSIGNED NOT NULL,
        `type`        ENUM('income','expense') NOT NULL,
        `amount`      DECIMAL(12,2) NOT NULL,
        `description` VARCHAR(255)  NOT NULL DEFAULT '',
        `date`        DATE          NOT NULL,
        `created_at`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT `fk_txn_user`
            FOREIGN KEY (`user_id`)     REFERENCES `users`(`id`)
            ON DELETE CASCADE ON UPDATE CASCADE,
        CONSTRAINT `fk_txn_category`
            FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`)
            ON DELETE RESTRICT ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "✔ Table 'transactions' ready.\n";

    // ── TABLE: budgets ────────────────────────────────────
    $pdo->exec("CREATE TABLE IF NOT EXISTS `budgets` (
        `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `user_id`     INT UNSIGNED NOT NULL,
        `category_id` INT UNSIGNED NOT NULL,
        `amount`      DECIMAL(12,2) NOT NULL,
        `month`       TINYINT UNSIGNED NOT NULL COMMENT '1-12',
        `year`        SMALLINT UNSIGNED NOT NULL,
        `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY `uq_budget` (`user_id`, `category_id`, `month`, `year`),
        CONSTRAINT `fk_bud_user`
            FOREIGN KEY (`user_id`)     REFERENCES `users`(`id`)
            ON DELETE CASCADE ON UPDATE CASCADE,
        CONSTRAINT `fk_bud_category`
            FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`)
            ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "✔ Table 'budgets' ready.\n";

    // ── Migrate existing tables (safe ALTER for upgrades) ─
    $cols = $pdo->query("SHOW COLUMNS FROM `users`")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('username', $cols)) {
        $pdo->exec("ALTER TABLE `users` ADD COLUMN `username` VARCHAR(60) NULL UNIQUE AFTER `name`");
        // Populate username from email prefix for existing rows
        $pdo->exec("UPDATE `users` SET `username` = LOWER(SUBSTRING_INDEX(`email`,'@',1)) WHERE `username` IS NULL");
        $pdo->exec("ALTER TABLE `users` MODIFY COLUMN `username` VARCHAR(60) NOT NULL");
        echo "✔ Column 'username' added to users.\n";
    }
    if (!in_array('avatar', $cols)) {
        $pdo->exec("ALTER TABLE `users` ADD COLUMN `avatar` VARCHAR(255) NULL DEFAULT NULL AFTER `currency`");
        echo "✔ Column 'avatar' added to users.\n";
    }

    echo "\n✅ Database initialisation complete.\n";

} catch (PDOException $e) {
    die("❌ DB Error: " . $e->getMessage() . "\n");
}
