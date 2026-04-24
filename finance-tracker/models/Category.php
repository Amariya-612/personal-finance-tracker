<?php
/**
 * File: models/Category.php
 * Purpose: Database operations for the categories table
 */

class Category
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Get all categories visible to a user
     * (global categories + user's own categories).
     */
    public function getAllForUser(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM categories
             WHERE user_id IS NULL OR user_id = ?
             ORDER BY type ASC, name ASC"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /** Get categories filtered by type for a user. */
    public function getByType(int $userId, string $type): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM categories
             WHERE (user_id IS NULL OR user_id = ?) AND type = ?
             ORDER BY name ASC"
        );
        $stmt->execute([$userId, $type]);
        return $stmt->fetchAll();
    }

    /** Find a single category by ID (must belong to user or be global). */
    public function findById(int $id, int $userId): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM categories
             WHERE id = ? AND (user_id IS NULL OR user_id = ?)
             LIMIT 1"
        );
        $stmt->execute([$id, $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Create a user-specific category. */
    public function create(int $userId, string $name, string $type, string $icon, string $color): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO categories (user_id, name, type, icon, color)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([$userId, trim($name), $type, $icon, $color]);
        return (int)$this->pdo->lastInsertId();
    }

    /** Update a user-owned category. */
    public function update(int $id, int $userId, string $name, string $type, string $icon, string $color): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE categories
             SET name = ?, type = ?, icon = ?, color = ?
             WHERE id = ? AND user_id = ?"
        );
        return $stmt->execute([trim($name), $type, $icon, $color, $id, $userId]);
    }

    /** Delete a user-owned category (cannot delete global ones). */
    public function delete(int $id, int $userId): bool
    {
        $stmt = $this->pdo->prepare(
            "DELETE FROM categories WHERE id = ? AND user_id = ?"
        );
        return $stmt->execute([$id, $userId]);
    }

    /** Check if a category is used in any transaction. */
    public function isInUse(int $id): bool
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM transactions WHERE category_id = ?"
        );
        $stmt->execute([$id]);
        return (int)$stmt->fetchColumn() > 0;
    }
}
