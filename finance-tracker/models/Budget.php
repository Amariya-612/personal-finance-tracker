<?php
/**
 * File: models/Budget.php
 * Purpose: Database operations for the budgets table
 */

class Budget
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /** Get all budgets for a user in a given month/year with spent amounts. */
    public function getWithSpent(int $userId, int $month, int $year): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT b.*, c.name AS category_name, c.icon AS category_icon, c.color AS category_color,
                    COALESCE(SUM(t.amount), 0) AS spent
             FROM budgets b
             JOIN categories c ON b.category_id = c.id
             LEFT JOIN transactions t
                ON t.category_id = b.category_id
               AND t.user_id     = b.user_id
               AND t.type        = 'expense'
               AND MONTH(t.date) = b.month
               AND YEAR(t.date)  = b.year
             WHERE b.user_id = ? AND b.month = ? AND b.year = ?
             GROUP BY b.id
             ORDER BY c.name ASC"
        );
        $stmt->execute([$userId, $month, $year]);
        return $stmt->fetchAll();
    }

    /** Get all budgets for a user (all months). */
    public function getAll(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT b.*, c.name AS category_name, c.icon AS category_icon
             FROM budgets b
             JOIN categories c ON b.category_id = c.id
             WHERE b.user_id = ?
             ORDER BY b.year DESC, b.month DESC, c.name ASC"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /** Find a budget by ID for a user. */
    public function findById(int $id, int $userId): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT b.*, c.name AS category_name
             FROM budgets b
             JOIN categories c ON b.category_id = c.id
             WHERE b.id = ? AND b.user_id = ?
             LIMIT 1"
        );
        $stmt->execute([$id, $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Check if a budget already exists for user/category/month/year. */
    public function exists(int $userId, int $categoryId, int $month, int $year, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM budgets
                WHERE user_id = ? AND category_id = ? AND month = ? AND year = ?";
        $params = [$userId, $categoryId, $month, $year];

        if ($excludeId !== null) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn() > 0;
    }

    /** Create a new budget. */
    public function create(int $userId, int $categoryId, float $amount, int $month, int $year): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO budgets (user_id, category_id, amount, month, year)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([$userId, $categoryId, $amount, $month, $year]);
        return (int)$this->pdo->lastInsertId();
    }

    /** Update an existing budget. */
    public function update(int $id, int $userId, float $amount): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE budgets SET amount = ? WHERE id = ? AND user_id = ?"
        );
        return $stmt->execute([$amount, $id, $userId]);
    }

    /** Delete a budget. */
    public function delete(int $id, int $userId): bool
    {
        $stmt = $this->pdo->prepare(
            "DELETE FROM budgets WHERE id = ? AND user_id = ?"
        );
        return $stmt->execute([$id, $userId]);
    }

    /** Overall budget health for dashboard: total budget vs total spent this month. */
    public function monthlyOverview(int $userId, int $month, int $year): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT SUM(b.amount) AS total_budget,
                    COALESCE(SUM(t_agg.spent), 0) AS total_spent
             FROM budgets b
             LEFT JOIN (
                 SELECT category_id, SUM(amount) AS spent
                 FROM transactions
                 WHERE user_id = ? AND type = 'expense'
                   AND MONTH(date) = ? AND YEAR(date) = ?
                 GROUP BY category_id
             ) t_agg ON t_agg.category_id = b.category_id
             WHERE b.user_id = ? AND b.month = ? AND b.year = ?"
        );
        $stmt->execute([$userId, $month, $year, $userId, $month, $year]);
        $row = $stmt->fetch();
        return [
            'total_budget' => (float)($row['total_budget'] ?? 0),
            'total_spent'  => (float)($row['total_spent']  ?? 0),
        ];
    }
}
