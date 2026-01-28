<?php

class ProductModel
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getAll(): array
    {
        $stmt = $this->pdo->query("
            SELECT
                p.product_id,
                p.name,
                p.price,
                pi.src AS image
            FROM product p
            LEFT JOIN product_img pi
                ON p.product_id = pi.product_id
                AND pi.position = 1
            WHERE p.is_active = 1
            LIMIT 9
        ");

        return $stmt->fetchAll() ?: [];
    }

    public function getById(int $id): array|false
    {
        $stmt = $this->pdo->prepare("
            SELECT product_id, name, description, price
            FROM product
            WHERE product_id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function getFiltered($gender, $type, $sale): array
    {
        $sql = "
        SELECT p.product_id, p.name, p.price, pi.src AS image
        FROM product p
        LEFT JOIN product_img pi
            ON p.product_id = pi.product_id
            AND pi.position = 1
        WHERE p.is_active = 1
    ";

        $params = [];

        /* ===== GENDER SZŰRÉS ===== */
        /* ===== GENDER SZŰRÉS ===== */
        if ($gender === 'male') {
            $sql .= "
        AND p.gender_id IN (
            SELECT gender_id
            FROM gender
            WHERE gender IN ('m', 'u')
        )
    ";
        }

        if ($gender === 'female') {
            $sql .= "
        AND p.gender_id IN (
            SELECT gender_id
            FROM gender
            WHERE gender IN ('f', 'u')
        )
    ";
        }

        /* ===== TÍPUS ===== */
        if ($type) {
            $sql .= "
            AND p.product_type_id = (
                SELECT product_type_id FROM product_type WHERE name = :type
            )
        ";
            $params['type'] = $type;
        }

        /* ===== AKCIÓ ===== */
        if ($sale) {
            $sql .= " AND p.is_sale = 1";
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }
}
