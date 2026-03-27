<?php






require_once __DIR__ . '/../../../app/models/productmodel.php';

global $pdo;
$productModel = new ProductModel($pdo);
$resourceId = $segments[1] ?? null;

switch ($method) {
    case 'GET':
        if ($resourceId) {
            
            $product = $productModel->getProductById((int)$resourceId);
            
            if (!$product) {
                ApiResponse::notFound('A termék nem található');
            }
            
            
            $product['images'] = $productModel->getImages((int)$resourceId);
            $product['sizes'] = $productModel->getSizes((int)$resourceId);
            $product['variants'] = $productModel->getColorVariants((int)$resourceId);
            
            ApiResponse::success($product);
        } else {
            
            $search = $queryParams['search'] ?? null;
            
            if ($search) {
                
                $products = $productModel->search($search);
            } else {
                
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
