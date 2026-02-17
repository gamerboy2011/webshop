<?php

class FavouriteModel
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Kedvenc hozzáadása / eltávolítása
     */
    public function toggle(int $userId, int $productId): bool
    {
        // Megnézzük, hogy már kedvenc-e
        $stmt = $this->pdo->prepare("
            SELECT id 
            FROM favorites 
            WHERE user_id = ? AND product_id = ?
        ");
        $stmt->execute([$userId, $productId]);

        // Ha már kedvenc → töröljük
        if ($stmt->fetch()) {
            $del = $this->pdo->prepare("
                DELETE FROM favorites 
                WHERE user_id = ? AND product_id = ?
            ");
            return $del->execute([$userId, $productId]);
        }

        // Ha még nem kedvenc → hozzáadjuk
        $add = $this->pdo->prepare("
            INSERT INTO favorites (user_id, product_id) 
            VALUES (?, ?)
        ");
        return $add->execute([$userId, $productId]);
    }

    /**
     * Felhasználó kedvenceinek lekérése
     */
    public function getUserFavorites(int $userId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                p.product_id,
                p.name,
                p.price,
                (
                    SELECT src 
                    FROM product_img 
                    WHERE product_id = p.product_id 
                    ORDER BY position ASC 
                    LIMIT 1
                ) AS image
            FROM favorites f
            JOIN product p ON p.product_id = f.product_id
            WHERE f.user_id = ?
            ORDER BY f.id DESC
        ");

        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
