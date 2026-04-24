<?php
/**
 * File: models/Transaction.php
 * Purpose: Database operations for the transactions table
 */

class Transaction
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Get paginated transactions for a user with optional filters.
     *
     * @param int    $userId
     * @param array  $filters  Keys: type, category_id, date_from, date_to, search
     * @param int    $page
     * @param int    $perPage
     */
    public function getAll(int $userId, array $filters = [], int $page = 1, int $perPage = 15): array
    {
        [$where, $params] = $this->buildWhere($userId, $filters);

        $offset = ($page - 1) * $perPage;
        $sql = "SELECT t.*, c.name AS category_name, c.icon AS category_icon, c.color AS category_color
                FROM transactions t
                JOIN categories c ON t.category_id = c.id
                WHERE $where
                ORDER BY t.date DESC, t.id DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /** Count total transactions matching filters (for pagination). */
    public function count(int $userId, array $filters = []): int
    {
        [$where, $params] = $this->buildWhere($userId, $filters);
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM transactions t WHERE $where"
        );
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    /** Find a single transaction by ID belonging to a user. */
    public function findById(int $id, int $userId): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT t.*, c.name AS category_name
             FROM transactions t
             JOIN categories c ON t.category_id = c.id
             WHERE t.id = ? AND t.user_id = ?
             LIMIT 1"
        );
        $stmt->execute([$id, $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Create a new transaction. */
    public function create(int $userId, int $categoryId, string $type, float $amount, string $description, string $date): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO transactions (user_id, category_id, type, amount, description, date)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$userId, $categoryId, $type, $amount, trim($description), $date]);
        return (int)$this->pdo->lastInsertId();
    }

    /** Update an existing transaction. */
    public function update(int $id, int $userId, int $categoryId, string $type, float $amount, string $description, string $date): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE transactions
             SET category_id = ?, type = ?, amount = ?, description = ?, date = ?
             WHERE id = ? AND user_id = ?"
        );
        return $stmt->execute([$categoryId, $type, $amount, trim($description), $date, $id, $userId]);
    }

    /** Delete a transaction. */
    public function delete(int $id, int $userId): bool
    {
        $stmt = $this->pdo->prepare(
            "DELETE FROM transactions WHERE id = ? AND user_id = ?"
        );
        return $stmt->execute([$id, $userId]);
    }

    // ── Aggregates ────────────────────────────────────────

    /** Total income and expense for a user in a given month/year. */
    public function monthlySummary(int $userId, int $month, int $year): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT type, SUM(amount) AS total
             FROM transactions
             WHERE user_id = ? AND MONTH(date) = ? AND YEAR(date) = ?
             GROUP BY type"
        );
        $stmt->execute([$userId, $month, $year]);
        $result = ['income' => 0.0, 'expense' => 0.0];
        foreach ($stmt->fetchAll() as $row) {
            $result[$row['type']] = (float)$row['total'];
        }
        return $result;
    }

    /** Expense breakdown by category for a given month/year. */
    public function expenseByCategory(int $userId, int $month, int $year): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT c.name, c.color, SUM(t.amount) AS total
             FROM transactions t
             JOIN categories c ON t.category_id = c.id
             WHERE t.user_id = ? AND t.type = 'expense'
               AND MONTH(t.date) = ? AND YEAR(t.date) = ?
             GROUP BY t.category_id
             ORDER BY total DESC"
        );
        $stmt->execute([$userId, $month, $year]);
        return $stmt->fetchAll();
    }

    /** Monthly income vs expense for the last N months. */
    public function monthlyTrend(int $userId, int $months = 6): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT YEAR(date) AS yr, MONTH(date) AS mo, type, SUM(amount) AS total
             FROM transactions
             WHERE user_id = ?
               AND date >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
             GROUP BY yr, mo, type
             ORDER BY yr ASC, mo ASC"
        );
        $stmt->execute([$userId, $months]);
        return $stmt->fetchAll();
    }

    /** Yearly summary (income vs expense per month) for a given year. */
    public function yearlySummary(int $userId, int $year): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT MONTH(date) AS mo, type, SUM(amount) AS total
             FROM transactions
             WHERE user_id = ? AND YEAR(date) = ?
             GROUP BY mo, type
             ORDER BY mo ASC"
        );
        $stmt->execute([$userId, $year]);
        return $stmt->fetchAll();
    }

    /** Recent transactions (for dashboard). */
    public function recent(int $userId, int $limit = 5): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT t.*, c.name AS category_name, c.icon AS category_icon, c.color AS category_color
             FROM transactions t
             JOIN categories c ON t.category_id = c.id
             WHERE t.user_id = ?
             ORDER BY t.date DESC, t.id DESC
             LIMIT ?"
        );
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll();
    }

    // ── Private helpers ───────────────────────────────────

    private function buildWhere(int $userId, array $filters): array
    {
        $conditions = ['t.user_id = :uid'];
        $params     = [':uid' => $userId];

        if (!empty($filters['type']) && in_array($filters['type'], ['income', 'expense'])) {
            $conditions[] = 't.type = :type';
            $params[':type'] = $filters['type'];
        }
        if (!empty($filters['category_id'])) {
            $conditions[] = 't.category_id = :cat';
            $params[':cat'] = (int)$filters['category_id'];
        }
        if (!empty($filters['date_from'])) {
            $conditions[] = 't.date >= :dfrom';
            $params[':dfrom'] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $conditions[] = 't.date <= :dto';
            $params[':dto'] = $filters['date_to'];
        }
        if (!empty($filters['search'])) {
            $conditions[] = 't.description LIKE :search';
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        return [implode(' AND ', $conditions), $params];
    }
}
