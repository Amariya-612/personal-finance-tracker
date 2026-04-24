<?php
/**
 * File: models/User.php
 * Purpose: Database operations for the users table
 */

class User
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /** Find a user by username. */
    public function findByUsername(string $username): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([strtolower(trim($username))]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Find a user by email (kept for OAuth). */
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([strtolower(trim($email))]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Find a user by ID. */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Create a new user. Returns the new user ID. */
    public function create(string $name, string $username, string $email, string $password, string $currency = 'ETB'): int
    {
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
        $stmt = $this->pdo->prepare(
            "INSERT INTO users (name, username, email, password, currency) VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([trim($name), strtolower(trim($username)), strtolower(trim($email)), $hash, $currency]);
        return (int)$this->pdo->lastInsertId();
    }

    /** Update user profile. */
    public function update(int $id, string $name, string $currency): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE users SET name = ?, currency = ? WHERE id = ?"
        );
        return $stmt->execute([trim($name), $currency, $id]);
    }

    /** Update profile picture path. */
    public function updateAvatar(int $id, string $avatarPath): bool
    {
        $stmt = $this->pdo->prepare("UPDATE users SET avatar = ? WHERE id = ?");
        return $stmt->execute([$avatarPath, $id]);
    }

    /** Update password. */
    public function updatePassword(int $id, string $newPassword): bool
    {
        $hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
        $stmt = $this->pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        return $stmt->execute([$hash, $id]);
    }

    /** Verify a plain-text password against the stored hash. */
    public function verifyPassword(string $plain, string $hash): bool
    {
        return password_verify($plain, $hash);
    }

    /** Check if a username is already taken. */
    public function usernameExists(string $username): bool
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmt->execute([strtolower(trim($username))]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /** Check if an email is already registered. */
    public function emailExists(string $email): bool
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute([strtolower(trim($email))]);
        return (int)$stmt->fetchColumn() > 0;
    }
}
