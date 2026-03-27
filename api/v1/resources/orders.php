<?php







global $pdo;
$resourceId = $segments[1] ?? null;


$userId = $_SESSION['user_id'] ?? null;

if (!$userId) {
    ApiResponse::unauthorized('Bejelentkezés szükséges');
}

switch ($method) {
    case 'GET':
        if ($resourceId) {
            
            $stmt = $pdo->prepare("
                SELECT o.*, dm.name as delivery_method_name, pm.name as payment_method_name
                FROM orders o
                LEFT JOIN delivery_method dm ON o.delivery_method_id = dm.delivery_method_id
                LEFT JOIN payment_method pm ON o.payment_method_id = pm.payment_method_id
                WHERE o.order_id = ? AND o.user_id = ?
            ");
            $stmt->execute([$resourceId, $userId]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$order) {
                ApiResponse::notFound('A rendelés nem található');
            }
            
            
            $stmt = $pdo->prepare("
                SELECT oi.*, p.name as product_name, sz.size_value
                FROM order_item oi
                JOIN stock s ON oi.stock_id = s.stock_id
                JOIN product p ON s.product_id = p.product_id
                LEFT JOIN size sz ON s.size_id = sz.size_id
                WHERE oi.order_id = ?
            ");
            $stmt->execute([$resourceId]);
            $order['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            ApiResponse::success($order);
        } else {
            
            $stmt = $pdo->prepare("
                SELECT o.*, dm.name as delivery_method_name, pm.name as payment_method_name
                FROM orders o
                LEFT JOIN delivery_method dm ON o.delivery_method_id = dm.delivery_method_id
                LEFT JOIN payment_method pm ON o.payment_method_id = pm.payment_method_id
                WHERE o.user_id = ?
                ORDER BY o.created_at DESC
            ");
            $stmt->execute([$userId]);
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            ApiResponse::success([
                'orders' => $orders,
                'count' => count($orders)
            ]);
        }
        break;
        
    case 'POST':
        
        
        ApiResponse::badRequest('Rendelés leadása csak a checkout oldalon lehetséges');
        break;
        
    default:
        ApiResponse::methodNotAllowed(['GET', 'POST']);
}
