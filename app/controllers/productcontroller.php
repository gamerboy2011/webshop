<?php

require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../models/ProductModel.php";



class ProductController
{
    public function index(): void
    {
        global $pdo;

        $stmt = $pdo->query("
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
            WHERE p.is_active = 1
        ");

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
                sv.size_id,
                sv.size_value,
                s.quantity
            FROM stock s
            JOIN size sv ON s.size_id = sv.size_id
            WHERE s.product_id = ?
              AND s.quantity > 0
            ORDER BY sv.size_id
        ");
        $stmt->execute([$productId]);
        $sizes = $stmt->fetchAll();

        require __DIR__ . '/../views/pages/product.php';
    }
}