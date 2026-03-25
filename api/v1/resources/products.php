<?php
/**
 * Products API
 * GET /api/v1/products - Termékek listázása
 * GET /api/v1/products/{id} - Termék részletei
 */

require_once __DIR__ . '/../../../app/models/productmodel.php';

global $pdo;
$productModel = new ProductModel($pdo);
$resourceId = $segments[1] ?? null;

switch ($method) {
    case 'GET':
        if ($resourceId) {
            // GET /api/v1/products/{id}
            $product = $productModel->getProductById((int)$resourceId);
            
            if (!$product) {
                ApiResponse::notFound('A termék nem található');
            }
            
            // Képek és méretek hozzáadása
            $product['images'] = $productModel->getImages((int)$resourceId);
            $product['sizes'] = $productModel->getSizes((int)$resourceId);
            $product['variants'] = $productModel->getColorVariants((int)$resourceId);
            
            ApiResponse::success($product);
        } else {
            // GET /api/v1/products
            $search = $queryParams['search'] ?? null;
            
            if ($search) {
                // Keresés
                $products = $productModel->search($search);
            } else {
                // Összes termék
                $products = $productModel->getAll();
            }
            
            ApiResponse::success([
                'products' => $products,
                'total' => count($products)
            ]);
        }
        break;
        
    default:
        ApiResponse::methodNotAllowed(['GET']);
}
