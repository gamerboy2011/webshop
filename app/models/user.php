<?php

class User
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM users WHERE email = ? LIMIT 1"
        );
        $stmt->execute([$email]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function create(array $data): bool
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO users
            (username, email, password_hash, phone, role_id, is_active, activation_token)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        try {
            return $stmt->execute([
                $data['username'],
                $data['email'],
                $data['password_hash'],
                $data['phone'] ?? null,
                $data['role_id'] ?? 1,
                $data['is_active'] ?? 0,
                $data['activation_token'] ?? null
            ]);
        } catch (PDOException $e) {
            error_log("SQL HIBA: " . $e->getMessage());
            return false;
        }
    }

    public function findByToken(string $token): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM users WHERE activation_token = ? LIMIT 1"
        );
        $stmt->execute([$token]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function activateUser(int $userId): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE users
            SET is_active = 1, activation_token = NULL
            WHERE user_id = ?
        ");
        return $stmt->execute([$userId]);
    }
}
