<?php

require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../models/ProductModel.php";



class ProductController
{
    public function index(): void
{
    global $pdo;

    $gender = $_GET['gender'] ?? null;
    $type   = $_GET['type']   ?? null;
    $sale   = $_GET['sale']   ?? null;
    $new    = $_GET['new']    ?? null;

    $sql = "
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
        JOIN product_subtype ps ON p.subtype_id = ps.product_subtype_id
        JOIN product_type pt ON ps.product_type_id = pt.product_type_id
        WHERE p.is_active = 1
    ";

    $params = [];

    /* ===== GENDER SZŰRÉS ===== */
    if ($gender === 'male') {
        $sql .= "
            AND p.gender_id IN (
                SELECT gender_id FROM gender WHERE gender IN ('m','u')
            )
        ";
    }

    if ($gender === 'female') {
        $sql .= "
            AND p.gender_id IN (
                SELECT gender_id FROM gender WHERE gender IN ('f','u')
            )
        ";
    }

    /* ===== TÍPUS SZŰRÉS ===== */
    if ($type) {
        $sql .= " AND pt.name = :type";
        $params['type'] = ucfirst($type); // Clothe / Shoe / Accessory
    }

    

    /* ===== ÚJDONSÁG ===== */
    if ($new) {
        $sql .= " AND p.is_new = 1";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();

    require __DIR__ . '/../views/pages/home.php';
}

    public function show(): void
    {
        global $pdo;

        $productId = (int)($_GET['id'] ?? 0);
        if ($productId <= 0) {
            die('Érvénytelen termék');
        }

        /* ===== TERMÉK ===== */
        $stmt = $pdo->prepare("
            SELECT
                p.product_id,
                p.name,
                p.description,
                p.price,
                v.name AS vendor,
                g.gender,
                c.name AS color,
                pt.name AS type,
                ps.name AS subtype
            FROM product p
            JOIN vendor v ON p.vendor_id = v.vendor_id
            JOIN gender g ON p.gender_id = g.gender_id
            JOIN color c ON p.color_id = c.color_id
            JOIN product_subtype ps ON p.subtype_id = ps.product_subtype_id
            JOIN product_type pt ON ps.product_type_id = pt.product_type_id
            WHERE p.product_id = ?
              AND p.is_active = 1
        ");
        $stmt->execute([$productId]);
        $product = $stmt->fetch();

        if (!$product) {
            die('A termék nem található');
        }

        /* ===== KÉPEK ===== */
        $stmt = $pdo->prepare("
            SELECT src
            FROM product_img
            WHERE product_id = ?
            ORDER BY position
        ");
        $stmt->execute([$productId]);
        $images = $stmt->fetchAll();

        /* ===== MÉRETEK (ÚJ LOGIKA) ===== */
        $stmt = $pdo->prepare("
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
        $sizes = $stmt->fetchAll();

        require __DIR__ . '/../views/pages/product.php';
    }
}
