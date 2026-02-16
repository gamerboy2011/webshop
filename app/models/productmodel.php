<?php

class ProductModel
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Termék adatainak lekérése ID alapján
     */
    public function getProductById(int $productId): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                p.product_id,
                p.name,
                p.description,
                p.price,
                p.subtype_id,
                v.name AS vendor,
                pt.name AS type,
                ps.name AS subtype,
                g.gender,
                c.name AS color
            FROM product p
            JOIN vendor v ON p.vendor_id = v.vendor_id
            JOIN product_subtype ps ON p.subtype_id = ps.product_subtype_id
            JOIN product_type pt ON ps.product_type_id = pt.product_type_id
            JOIN gender g ON p.gender_id = g.gender_id
            JOIN color c ON p.color_id = c.color_id
            WHERE p.product_id = :id
              AND p.is_active = 1
        ");
        $stmt->execute(['id' => $productId]);

        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        return $product ?: null;
    }

    /**
     * Termék képeinek lekérése
     */
    public function getImages(int $productId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT src
            FROM product_img
            WHERE product_id = :id
            ORDER BY position
        ");
        $stmt->execute(['id' => $productId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Termék elérhető méretei + készlet
     */
    public function getSizes(int $productId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                sz.size_id,
                sz.size_value,
                st.quantity
            FROM stock st
            JOIN size sz ON st.size_id = sz.size_id
            JOIN product p ON st.product_id = p.product_id
            JOIN product_subtype ps ON p.subtype_id = ps.product_subtype_id
            WHERE st.product_id = :id
              AND st.quantity > 0
              AND sz.product_type_id = ps.product_type_id
            ORDER BY sz.size_id
        ");
        $stmt->execute(['id' => $productId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Ajánlott termékek lekérése
     */
    public function getRelated(int $subtypeId, int $productId): array
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
            FROM product p
            WHERE p.subtype_id = :subtype
              AND p.product_id != :id
              AND p.is_active = 1
            LIMIT 4
        ");
        $stmt->execute([
            'subtype' => $subtypeId,
            'id'      => $productId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Megnézi, hogy a termék kedvenc-e a felhasználónál
     */
    public function isFavorite(int $userId, int $productId): bool
    {
        $stmt = $this->pdo->prepare("
            SELECT id
            FROM favorites
            WHERE user_id = :uid AND product_id = :pid
            LIMIT 1
        ");
        $stmt->execute([
            'uid' => $userId,
            'pid' => $productId
        ]);

        return (bool)$stmt->fetch();
    }
}
