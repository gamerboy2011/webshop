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

        /* ===== GENDER SZŰRÉS (male/female logika) ===== */
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

    /* =========================
       KERESÉS – márka, típus, név
       ========================= */
    public function search(string $q): array
    {
        if ($q === '') {
            return [];
        }

        $sql = "
            SELECT
                p.product_id,
                p.name,
                p.price,
                pi.src AS image,
                v.name AS vendor_name
            FROM product p
            LEFT JOIN product_img pi
                ON p.product_id = pi.product_id
                AND pi.position = 1
            LEFT JOIN vendor v
                ON p.vendor_id = v.vendor_id
            LEFT JOIN product_subtype ps
                ON p.subtype_id = ps.product_subtype_id
            LEFT JOIN product_type pt
                ON ps.product_type_id = pt.product_type_id
            WHERE p.is_active = 1
              AND (
                    p.name LIKE :q
                    OR p.description LIKE :q
                    OR v.name LIKE :q
                    OR pt.name LIKE :q
                    OR ps.name LIKE :q
                  )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['q' => "%$q%"]);

        return $stmt->fetchAll() ?: [];
    }

    /* =========================
       SZŰRŐ – minden adatra
       ========================= */
    public function filter(string $gender, ?string $category, array $filters): array
    {
        $sql = "
            SELECT
                p.product_id,
                p.name,
                p.price,
                pi.src AS image
            FROM product p
            LEFT JOIN product_img pi
                ON p.product_id = pi.product_id
                AND pi.position = 1
            LEFT JOIN vendor v
                ON p.vendor_id = v.vendor_id
            LEFT JOIN color c
                ON p.color_id = c.color_id
            LEFT JOIN stock s
                ON s.product_id = p.product_id
            LEFT JOIN size sz
                ON s.size_id = sz.size_id
            LEFT JOIN product_subtype ps
                ON p.subtype_id = ps.product_subtype_id
            LEFT JOIN product_type pt
                ON ps.product_type_id = pt.product_type_id
            LEFT JOIN gender g
                ON p.gender_id = g.gender_id
            WHERE p.is_active = 1
        ";

        $params = [];

        /* ===== GENDER (ferfi / noi → m/f/u) ===== */
        if ($gender === 'ferfi') {
            $sql .= "
                AND g.gender IN ('m', 'u')
            ";
        } elseif ($gender === 'noi') {
            $sql .= "
                AND g.gender IN ('f', 'u')
            ";
        }

        /* ===== KATEGÓRIA (pl. Ruházat, Cipők) – product_type vagy subtype alapján ===== */
        if (!empty($category)) {
            $sql .= " AND (pt.name = :category OR ps.name = :category)";
            $params['category'] = $category;
        }

        /* ===== MÁRKA ===== */
        if (!empty($filters['brand'])) {
            $sql .= " AND v.name = :brand";
            $params['brand'] = $filters['brand'];
        }

        /* ===== SZÍN ===== */
        if (!empty($filters['color'])) {
            $sql .= " AND c.name = :color";
            $params['color'] = $filters['color'];
        }

        /* ===== MÉRET ===== */
        if (!empty($filters['size'])) {
            $sql .= " AND sz.size_value = :size";
            $params['size'] = $filters['size'];
        }

        /* ===== ÁR ===== */
        if (!empty($filters['min'])) {
            $sql .= " AND p.price >= :min";
            $params['min'] = (int)$filters['min'];
        }

        if (!empty($filters['max'])) {
            $sql .= " AND p.price <= :max";
            $params['max'] = (int)$filters['max'];
        }

        $sql .= " GROUP BY p.product_id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll() ?: [];
    }
}
