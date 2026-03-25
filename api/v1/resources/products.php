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
            $product = $productModel->getProductById($resourceId);
            
            if (!$product) {
                ApiResponse::notFound('A termék nem található');
            }
            
            // Képek és méretek hozzáadása
            $images = $productModel->getProductImages($resourceId);
            $sizes = $productModel->getProductSizes($resourceId);
            
            $product['images'] = $images;
            $product['sizes'] = $sizes;
            
            ApiResponse::success($product);
        } else {
            // GET /api/v1/products
            $filters = [
                'category' => $queryParams['category'] ?? null,
                'vendor' => $queryParams['vendor'] ?? null,
                'min_price' => $queryParams['min_price'] ?? null,
                'max_price' => $queryParams['max_price'] ?? null,
                'search' => $queryParams['search'] ?? null,
                'sort' => $queryParams['sort'] ?? 'newest',
                'limit' => isset($queryParams['limit']) ? (int)$queryParams['limit'] : 20,
                'offset' => isset($queryParams['offset']) ? (int)$queryParams['offset'] : 0
            ];
            
            $products = $productModel->getProducts($filters);
            $total = $productModel->getProductsCount($filters);
            
            ApiResponse::success([
                'products' => $products,
                'total' => $total,
                'limit' => $filters['limit'],
                'offset' => $filters['offset']
            ]);
        }
        break;
        
    default:
        ApiResponse::methodNotAllowed(['GET']);
}
