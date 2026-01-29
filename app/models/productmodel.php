<?php

class ProductModel
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }
    public function getImagesByProductId(int $productId): array
    {
        $stmt = $this->pdo->prepare("
        SELECT src
        FROM product_img
        WHERE product_id = ?
        ORDER BY position
    ");
        $stmt->execute([$productId]);
        return $stmt->fetchAll();
    }
    public function getSizesByProductId(int $productId): array
    {
        $stmt = $this->pdo->prepare("
        SELECT
            sv.size_value_id,
            sv.size_value,
            st.size_type,
            s.quantity
        FROM stock s
        JOIN size_value sv ON s.size_value_id = sv.size_value_id
        JOIN size_type st ON sv.size_type_id = st.size_type_id
        WHERE s.product_id = :product_id
          AND s.quantity > 0
        ORDER BY sv.size_value_id
    ");

        $stmt->execute([
            'product_id' => $productId
        ]);

        return $stmt->fetchAll();
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
        AND p.subtype_id IN (
            SELECT ps.product_subtype_id
            FROM product_subtype ps
            JOIN product_type pt
                ON ps.product_type_id = pt.product_type_id
            WHERE pt.name = :type
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
