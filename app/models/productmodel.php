<?php

class ProductModel
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    


    public function getProductById(int $productId): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                p.product_id,
                p.name,
                p.description,
                p.price,
                ROUND(p.price * 0.8) AS sale_price,
                p.is_sale,
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
     * Get all colors for a product (supports multi-color products)
     */
    public function getProductColors(int $productId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT c.color_id, c.name
            FROM product_colors pc
            JOIN color c ON pc.color_id = c.color_id
            WHERE pc.product_id = :id
            ORDER BY pc.id
        ");
        $stmt->execute(['id' => $productId]);
        $colors = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Ha nincs a product_colors táblában, próbáljuk a régi color_id-t
        if (empty($colors)) {
            $stmt = $this->pdo->prepare("
                SELECT c.color_id, c.name
                FROM product p
                JOIN color c ON p.color_id = c.color_id
                WHERE p.product_id = :id
            ");
            $stmt->execute(['id' => $productId]);
            $colors = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        return $colors;
    }

    


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

        
        if ($gender === 'ferfi') {
            $sql .= " AND g.gender IN ('m', 'u')";
        } elseif ($gender === 'noi') {
            $sql .= " AND g.gender IN ('f', 'u')";
        }

        
        if (!empty($category)) {
            $sql .= " AND (LOWER(pt.name) = LOWER(:cat1) OR LOWER(ps.name) = LOWER(:cat2))";
            $params['cat1'] = $category;
            $params['cat2'] = $category;
        }

        
        if (!empty($filters['brand'])) {
            $sql .= " AND v.name = :brand";
            $params['brand'] = $filters['brand'];
        }

        
        if (!empty($filters['color'])) {
            $sql .= " AND c.name = :color";
            $params['color'] = $filters['color'];
        }

        
        if (!empty($filters['size'])) {
            $sql .= " AND sz.size_value = :size";
            $params['size'] = $filters['size'];
        }

        
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
     * Ékezetek eltávolítása a szövegből
     */
    private function removeAccents(string $str): string
    {
        $accents = [
            'á' => 'a', 'Á' => 'A', 'é' => 'e', 'É' => 'E',
            'í' => 'i', 'Í' => 'I', 'ó' => 'o', 'Ó' => 'O',
            'ö' => 'o', 'Ö' => 'O', 'ő' => 'o', 'Ő' => 'O',
            'ú' => 'u', 'Ú' => 'U', 'ü' => 'u', 'Ü' => 'U',
            'ű' => 'u', 'Ű' => 'U'
        ];
        return strtr($str, $accents);
    }

    public function search(string $q): array
    {
        if (trim($q) === '') {
            return [];
        }

        // Ékezet nélküli változat
        $qNormalized = $this->removeAccents(mb_strtolower(trim($q)));
        
        $sql = "
            SELECT
                p.product_id,
                p.name,
                p.price,
                ROUND(p.price * 0.8) AS sale_price,
                p.is_sale,
                pi.src AS image,
                v.name AS vendor_name,
                ps.name AS subtype_name
            FROM product p
            LEFT JOIN product_img pi
                ON p.product_id = pi.product_id
                AND pi.position = 1
            LEFT JOIN vendor v
                ON p.vendor_id = v.vendor_id
            LEFT JOIN product_subtype ps
                ON p.subtype_id = ps.product_subtype_id
            WHERE p.is_active = 1
            ORDER BY p.product_id DESC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $allProducts = $stmt->fetchAll() ?: [];

        // Szűrés: ékezet-érzéketlen keresés csak a termék nevében, márkában és kategóriában
        $results = [];
        foreach ($allProducts as $product) {
            $nameNormalized = $this->removeAccents(mb_strtolower($product['name'] ?? ''));
            $vendorNormalized = $this->removeAccents(mb_strtolower($product['vendor_name'] ?? ''));
            $subtypeNormalized = $this->removeAccents(mb_strtolower($product['subtype_name'] ?? ''));
            
            // Pontos egyezés (ékezet nélkül)
            if (str_contains($nameNormalized, $qNormalized) ||
                str_contains($vendorNormalized, $qNormalized) ||
                str_contains($subtypeNormalized, $qNormalized)) {
                $results[] = $product;
            }
        }

        return $results;
    }

    


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

    


    public function getColorVariants(int $productId): array
    {
        
        $stmt = $this->pdo->prepare("
            SELECT product_id, parent_product_id 
            FROM product 
            WHERE product_id = :id
        ");
        $stmt->execute(['id' => $productId]);
        $current = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$current) return [];
        
        
        
        $parentId = $current['parent_product_id'] ?: $current['product_id'];
        
        $stmt = $this->pdo->prepare("
            SELECT 
                p.product_id,
                p.name,
                c.name AS color,
                c.color_id,
                (SELECT src FROM product_img WHERE product_id = p.product_id ORDER BY position LIMIT 1) AS image
            FROM product p
            JOIN color c ON p.color_id = c.color_id
            WHERE p.is_active = 1
              AND (p.product_id = :parent_id OR p.parent_product_id = :parent_id2)
            ORDER BY p.product_id
        ");
        $stmt->execute(['parent_id' => $parentId, 'parent_id2' => $parentId]);
        $variants = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        
        return count($variants) > 1 ? $variants : [];
    }

    



    public function getAllWithVariants(): array
    {
        $stmt = $this->pdo->query("
            SELECT
                p.product_id,
                p.name,
                p.price,
                ROUND(p.price * 0.8) AS sale_price,
                p.is_sale,
                p.parent_product_id,
                pi.src AS image,
                c.name AS color,
                c.color_id
            FROM product p
            LEFT JOIN product_img pi
                ON p.product_id = pi.product_id
                AND pi.position = 1
            JOIN color c ON p.color_id = c.color_id
            WHERE p.is_active = 1
              AND p.parent_product_id IS NULL
            ORDER BY p.price DESC
            LIMIT 12
        ");
        $products = $stmt->fetchAll() ?: [];
        
        
        foreach ($products as &$product) {
            $product['variants'] = $this->getColorVariants($product['product_id']);
        }
        
        return $products;
    }

    


    public function getFilterOptions(?string $gender = null, ?string $category = null, array $activeFilters = []): array
    {
        $baseConditions = "p.is_active = 1";
        $params = [];
        
        // Gender szűrő
        if ($gender === 'ferfi') {
            $baseConditions .= " AND g.gender IN ('m', 'u')";
        } elseif ($gender === 'noi') {
            $baseConditions .= " AND g.gender IN ('f', 'u')";
        }
        
        // Kategória szűrő
        if (!empty($category)) {
            $slugMap = [
                'ruhazat' => 'Ruházat', 'cipok' => 'Cipők', 'kiegeszitok' => 'Kiegészítők',
                'polo' => 'póló', 'pulover' => 'pulóver', 'nadrag' => 'nadrág',
                'rovidnadrag' => 'rövidnadrág', 'melegito' => 'melegítő', 'egyberuha' => 'egyberuha',
                'cipo' => 'cipő', 'papucs' => 'papucs',
                'sapka' => 'sapka', 'zokni' => 'zokni', 'taska' => 'táska', 'hatizsak' => 'hátizsák', 'figura' => 'figura',
            ];
            $catName = $slugMap[strtolower($category)] ?? $category;
            $baseConditions .= " AND (LOWER(pt.name) = LOWER(:cat1) OR LOWER(ps.name) = LOWER(:cat2))";
            $params['cat1'] = $catName;
            $params['cat2'] = $catName;
        }
        
        // Akciós szűrő
        $saleCondition = '';
        if (!empty($activeFilters['sale'])) {
            $saleCondition = " AND p.is_sale = 1";
        }
        
        // Márka szűrő feltétel
        $brandCondition = '';
        $brandParams = [];
        if (!empty($activeFilters['brands']) && is_array($activeFilters['brands'])) {
            $brandPlaceholders = [];
            foreach ($activeFilters['brands'] as $i => $brand) {
                $key = 'fbrand' . $i;
                $brandPlaceholders[] = ':' . $key;
                $brandParams[$key] = $brand;
            }
            $brandCondition = " AND v.name IN (" . implode(',', $brandPlaceholders) . ")";
        }
        
        // Szín szűrő feltétel
        $colorCondition = '';
        $colorParams = [];
        if (!empty($activeFilters['colors']) && is_array($activeFilters['colors'])) {
            $colorPlaceholders = [];
            foreach ($activeFilters['colors'] as $i => $color) {
                $key = 'fcolor' . $i;
                $colorPlaceholders[] = ':' . $key;
                $colorParams[$key] = $color;
            }
            $colorCondition = " AND c.name IN (" . implode(',', $colorPlaceholders) . ")";
        }
        
        // Méret szűrő feltétel
        $sizeCondition = '';
        $sizeParams = [];
        if (!empty($activeFilters['sizes']) && is_array($activeFilters['sizes'])) {
            $sizePlaceholders = [];
            foreach ($activeFilters['sizes'] as $i => $size) {
                $key = 'fsize' . $i;
                $sizePlaceholders[] = ':' . $key;
                $sizeParams[$key] = $size;
            }
            $sizeCondition = " AND p.product_id IN (SELECT s2.product_id FROM stock s2 JOIN size sz2 ON s2.size_id = sz2.size_id WHERE s2.quantity > 0 AND sz2.size_value IN (" . implode(',', $sizePlaceholders) . "))";
        }
        
        // Ár szűrő feltétel
        $priceCondition = '';
        $priceParams = [];
        if (!empty($activeFilters['min_price'])) {
            $priceCondition .= " AND p.price >= :fmin_price";
            $priceParams['fmin_price'] = (int)$activeFilters['min_price'];
        }
        if (!empty($activeFilters['max_price'])) {
            $priceCondition .= " AND p.price <= :fmax_price";
            $priceParams['fmax_price'] = (int)$activeFilters['max_price'];
        }
        
        // MÁRKÁK - az összes többi szűrő alapján (kivéve márka)
        $sql = "SELECT DISTINCT v.vendor_id, v.name 
                FROM product p 
                JOIN vendor v ON p.vendor_id = v.vendor_id
                JOIN gender g ON p.gender_id = g.gender_id
                JOIN color c ON p.color_id = c.color_id
                JOIN product_subtype ps ON p.subtype_id = ps.product_subtype_id
                JOIN product_type pt ON ps.product_type_id = pt.product_type_id
                WHERE $baseConditions $saleCondition $colorCondition $sizeCondition $priceCondition
                ORDER BY v.name";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_merge($params, $colorParams, $sizeParams, $priceParams));
        $brands = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // SZÍNEK - az összes többi szűrő alapján (kivéve szín)
        $sql = "SELECT DISTINCT c.color_id, c.name 
                FROM product p 
                JOIN color c ON p.color_id = c.color_id
                JOIN vendor v ON p.vendor_id = v.vendor_id
                JOIN gender g ON p.gender_id = g.gender_id
                JOIN product_subtype ps ON p.subtype_id = ps.product_subtype_id
                JOIN product_type pt ON ps.product_type_id = pt.product_type_id
                WHERE $baseConditions $saleCondition $brandCondition $sizeCondition $priceCondition
                ORDER BY c.name";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_merge($params, $brandParams, $sizeParams, $priceParams));
        $colors = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // MÉRETEK - az összes többi szűrő alapján (kivéve méret)
        $sql = "SELECT DISTINCT sz.size_id, sz.size_value, sz.product_type_id
                FROM product p 
                JOIN stock s ON p.product_id = s.product_id
                JOIN size sz ON s.size_id = sz.size_id
                JOIN vendor v ON p.vendor_id = v.vendor_id
                JOIN color c ON p.color_id = c.color_id
                JOIN gender g ON p.gender_id = g.gender_id
                JOIN product_subtype ps ON p.subtype_id = ps.product_subtype_id
                JOIN product_type pt ON ps.product_type_id = pt.product_type_id
                WHERE $baseConditions AND s.quantity > 0 $saleCondition $brandCondition $colorCondition $priceCondition
                ORDER BY sz.product_type_id, sz.size_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_merge($params, $brandParams, $colorParams, $priceParams));
        $sizes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // ÁR TARTOMÁNY - az összes többi szűrő alapján (kivéve ár)
        $sql = "SELECT MIN(p.price) as min_price, MAX(p.price) as max_price
                FROM product p
                JOIN vendor v ON p.vendor_id = v.vendor_id
                JOIN color c ON p.color_id = c.color_id
                JOIN gender g ON p.gender_id = g.gender_id
                JOIN product_subtype ps ON p.subtype_id = ps.product_subtype_id
                JOIN product_type pt ON ps.product_type_id = pt.product_type_id
                WHERE $baseConditions $saleCondition $brandCondition $colorCondition $sizeCondition";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_merge($params, $brandParams, $colorParams, $sizeParams));
        $priceRange = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'brands' => $brands,
            'colors' => $colors,
            'sizes' => $sizes,
            'price_min' => (int)($priceRange['min_price'] ?? 0),
            'price_max' => (int)($priceRange['max_price'] ?? 100000)
        ];
    }

    


    public function filterAdvanced(string $gender, ?string $category, array $filters): array
    {
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
            JOIN vendor v ON p.vendor_id = v.vendor_id
            JOIN color c ON p.color_id = c.color_id
            JOIN product_subtype ps ON p.subtype_id = ps.product_subtype_id
            JOIN product_type pt ON ps.product_type_id = pt.product_type_id
            JOIN gender g ON p.gender_id = g.gender_id
            WHERE p.is_active = 1
        ";

        $params = [];

        
        if ($gender === 'ferfi') {
            $sql .= " AND g.gender IN ('m', 'u')";
        } elseif ($gender === 'noi') {
            $sql .= " AND g.gender IN ('f', 'u')";
        }

        
        if (!empty($category)) {
            $slugMap = [
                
                'ruhazat' => 'Ruházat',
                'cipok' => 'Cipők',
                'kiegeszitok' => 'Kiegészítők',
                
                'polo' => 'póló',
                'pulover' => 'pulóver',
                'nadrag' => 'nadrág',
                'rovidnadrag' => 'rövidnadrág',
                'melegito' => 'melegítő',
                'egyberuha' => 'egyberuha',
                
                'cipo' => 'cipő',
                'papucs' => 'papucs',
                
                'sapka' => 'sapka',
                'zokni' => 'zokni',
                'taska' => 'táska',
                'hatizsak' => 'hátizsák',
                'figura' => 'figura',
            ];
            $catName = $slugMap[strtolower($category)] ?? $category;
            $sql .= " AND (LOWER(pt.name) = LOWER(:cat1) OR LOWER(ps.name) = LOWER(:cat2))";
            $params['cat1'] = $catName;
            $params['cat2'] = $catName;
        }

        
        if (!empty($filters['sale'])) {
            $sql .= " AND p.is_sale = 1";
        }

        
        if (!empty($filters['brands']) && is_array($filters['brands'])) {
            $brandPlaceholders = [];
            foreach ($filters['brands'] as $i => $brand) {
                $key = 'brand' . $i;
                $brandPlaceholders[] = ':' . $key;
                $params[$key] = $brand;
            }
            $sql .= " AND v.name IN (" . implode(',', $brandPlaceholders) . ")";
        }

        
        if (!empty($filters['colors']) && is_array($filters['colors'])) {
            $colorPlaceholders = [];
            foreach ($filters['colors'] as $i => $color) {
                $key = 'color' . $i;
                $colorPlaceholders[] = ':' . $key;
                $params[$key] = $color;
            }
            $sql .= " AND c.name IN (" . implode(',', $colorPlaceholders) . ")";
        }

        
        if (!empty($filters['sizes']) && is_array($filters['sizes'])) {
            $sizePlaceholders = [];
            foreach ($filters['sizes'] as $i => $size) {
                $key = 'size' . $i;
                $sizePlaceholders[] = ':' . $key;
                $params[$key] = $size;
            }
            $sql .= " AND p.product_id IN (
                SELECT s.product_id FROM stock s 
                JOIN size sz ON s.size_id = sz.size_id 
                WHERE s.quantity > 0 AND sz.size_value IN (" . implode(',', $sizePlaceholders) . ")
            )";
        }

        
        if (!empty($filters['min_price'])) {
            $sql .= " AND p.price >= :min_price";
            $params['min_price'] = (int)$filters['min_price'];
        }
        if (!empty($filters['max_price'])) {
            $sql .= " AND p.price <= :max_price";
            $params['max_price'] = (int)$filters['max_price'];
        }

        
        $sort = $filters['sort'] ?? 'newest';
        switch ($sort) {
            case 'price_asc':
                $sql .= " GROUP BY p.product_id ORDER BY p.price ASC";
                break;
            case 'price_desc':
                $sql .= " GROUP BY p.product_id ORDER BY p.price DESC";
                break;
            case 'name_asc':
                $sql .= " GROUP BY p.product_id ORDER BY p.name ASC";
                break;
            default: 
                $sql .= " GROUP BY p.product_id ORDER BY p.product_id DESC";
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll() ?: [];
    }
}
