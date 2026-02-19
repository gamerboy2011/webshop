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

    /**
     * Összes aktív termék lekérése (főoldal)
     */
    public function getAll(): array
    {
        $stmt = $this->pdo->query("
            SELECT
                p.product_id,
                p.name,
                p.price,
                ROUND(p.price * 0.8) AS sale_price,
                p.is_sale,
                pi.src AS image
            FROM product p
            LEFT JOIN product_img pi
                ON p.product_id = pi.product_id
                AND pi.position = 1
            WHERE p.is_active = 1
            LIMIT 12
        ");

        return $stmt->fetchAll() ?: [];
    }

    /**
     * Termékek szűrése gender és kategória alapján
     */
    public function filter(string $gender, ?string $category, array $filters): array
    {
        $sql = "
            SELECT
                p.product_id,
                p.name,
                p.price,
                ROUND(p.price * 0.8) AS sale_price,
                p.is_sale,
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

        // Gender szűrés (uniszex termékek mindkét nemnél megjelennek)
        if ($gender === 'ferfi') {
            $sql .= " AND g.gender IN ('m', 'u')";
        } elseif ($gender === 'noi') {
            $sql .= " AND g.gender IN ('f', 'u')";
        }

        // Kategória szűrés (típus vagy altípus)
        if (!empty($category)) {
            $sql .= " AND (LOWER(pt.name) = LOWER(:cat1) OR LOWER(ps.name) = LOWER(:cat2))";
            $params['cat1'] = $category;
            $params['cat2'] = $category;
        }

        // Márka szűrés
        if (!empty($filters['brand'])) {
            $sql .= " AND v.name = :brand";
            $params['brand'] = $filters['brand'];
        }

        // Szín szűrés
        if (!empty($filters['color'])) {
            $sql .= " AND c.name = :color";
            $params['color'] = $filters['color'];
        }

        // Méret szűrés
        if (!empty($filters['size'])) {
            $sql .= " AND sz.size_value = :size";
            $params['size'] = $filters['size'];
        }

        // Ár szűrés
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

    /**
     * Keresés - márka, típus, név, szín alapján
     */
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
                ROUND(p.price * 0.8) AS sale_price,
                p.is_sale,
                pi.src AS image,
                v.name AS vendor_name
            FROM product p
            LEFT JOIN product_img pi
                ON p.product_id = pi.product_id
                AND pi.position = 1
            LEFT JOIN vendor v
                ON p.vendor_id = v.vendor_id
            LEFT JOIN color c
                ON p.color_id = c.color_id
            LEFT JOIN product_subtype ps
                ON p.subtype_id = ps.product_subtype_id
            LEFT JOIN product_type pt
                ON ps.product_type_id = pt.product_type_id
            WHERE p.is_active = 1
              AND (
                    p.name LIKE :q1
                    OR p.description LIKE :q2
                    OR v.name LIKE :q3
                    OR pt.name LIKE :q4
                    OR ps.name LIKE :q5
                    OR c.name LIKE :q6
                  )
            ORDER BY p.product_id DESC
        ";

        $searchTerm = "%$q%";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'q1' => $searchTerm,
            'q2' => $searchTerm,
            'q3' => $searchTerm,
            'q4' => $searchTerm,
            'q5' => $searchTerm,
            'q6' => $searchTerm
        ]);

        return $stmt->fetchAll() ?: [];
    }

    /**
     * Kategóriák és alkategóriák lekérése (menühöz)
     */
    public function getCategories(): array
    {
        $stmt = $this->pdo->query("
            SELECT 
                pt.product_type_id,
                pt.name AS type_name,
                ps.product_subtype_id,
                ps.name AS subtype_name
            FROM product_type pt
            LEFT JOIN product_subtype ps ON pt.product_type_id = ps.product_type_id
            ORDER BY pt.name, ps.name
        ");
        
        $rows = $stmt->fetchAll();
        $categories = [];
        
        foreach ($rows as $row) {
            $typeId = $row['product_type_id'];
            if (!isset($categories[$typeId])) {
                $categories[$typeId] = [
                    'id' => $typeId,
                    'name' => $row['type_name'],
                    'subtypes' => []
                ];
            }
            if ($row['product_subtype_id']) {
                $categories[$typeId]['subtypes'][] = [
                    'id' => $row['product_subtype_id'],
                    'name' => $row['subtype_name']
                ];
            }
        }
        
        return array_values($categories);
    }

    /**
     * Akciós termékek (20% kedvezmény)
     */
    public function getSaleProducts(): array
    {
        $stmt = $this->pdo->query("
            SELECT
                p.product_id,
                p.name,
                p.price,
                ROUND(p.price * 0.8) AS sale_price,
                p.is_sale,
                pi.src AS image
            FROM product p
            LEFT JOIN product_img pi
                ON p.product_id = pi.product_id
                AND pi.position = 1
            WHERE p.is_active = 1
              AND p.is_sale = 1
            ORDER BY p.product_id DESC
        ");

        return $stmt->fetchAll() ?: [];
    }

    /**
     * Új termékek (utolsó 30 nap)
     */
    public function getNewProducts(): array
    {
        $stmt = $this->pdo->query("
            SELECT
                p.product_id,
                p.name,
                p.price,
                ROUND(p.price * 0.8) AS sale_price,
                p.is_sale,
                pi.src AS image
            FROM product p
            LEFT JOIN product_img pi
                ON p.product_id = pi.product_id
                AND pi.position = 1
            WHERE p.is_active = 1
            ORDER BY p.product_id DESC
            LIMIT 12
        ");

        return $stmt->fetchAll() ?: [];
    }
}
