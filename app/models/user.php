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
            (username, email, password_hash, phone, role_id, is_active, activation_token, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        try {
            return $stmt->execute([
                $data['username'],
                $data['email'],
                $data['password_hash'],
                $data['phone'] ?? null,
                $data['role_id'] ?? 1,
                $data['is_active'] ?? 1,
                $data['activation_token'] ?? null
            ]);
        } catch (PDOException $e) {
            // Naplózáshoz: error_log($e->getMessage());
            return false;
        }
    }
    
    /**
     * Új: Felhasználó keresése ID alapján (profil oldalhoz)
     */
    public function findById(int $userId): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT user_id, username, email, phone, role_id, created_at 
             FROM users WHERE user_id = ? LIMIT 1"
        );
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }
}