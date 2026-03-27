<?php









global $pdo;
$resourceId = $segments[1] ?? null;


$userId = $_SESSION['user_id'] ?? null;

switch ($method) {
    case 'GET':
        
        if ($userId) {
            
            $cartItems = $_SESSION['cart'] ?? [];
            $cartTotal = 0;
            foreach ($cartItems as $item) {
                $cartTotal += ($item['price'] ?? 0) * ($item['quantity'] ?? 1);
            }
        } else {
            
            $cartItems = $_SESSION['cart'] ?? [];
            $cartTotal = 0;
            foreach ($cartItems as $item) {
                $cartTotal += $item['price'] * $item['quantity'];
            }
        }
        
        ApiResponse::success([
            'items' => $cartItems,
            'total' => $cartTotal,
            'item_count' => count($cartItems)
        ]);
        break;
        
    case 'POST':
        
        $productId = $input['product_id'] ?? null;
        $sizeId = $input['size_id'] ?? null;
        $quantity = isset($input['quantity']) ? (int)$input['quantity'] : 1;
        
        if (!$productId || !$sizeId) {
            ApiResponse::badRequest('Hiányzó paraméterek: product_id, size_id');
        }
        
        if ($quantity < 1) {
            ApiResponse::badRequest('A mennyiség legalább 1 kell legyen');
        }
        
        
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        
        $key = $productId . '_' . $sizeId;
        if (isset($_SESSION['cart'][$key])) {
            $_SESSION['cart'][$key]['quantity'] += $quantity;
        } else {
            
            $stmt = $pdo->prepare("
                SELECT name, price 
                FROM product 
                WHERE product_id = ?
            ");
            $stmt->execute([$productId]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $_SESSION['cart'][$key] = [
                'product_id' => $productId,
                'size_id' => $sizeId,
                'quantity' => $quantity,
                'name' => $product['name'] ?? '',
                'price' => $product['price'] ?? 0
            ];
        }
        $result = true;
        
        if ($result) {
            ApiResponse::created(['message' => 'Termék hozzáadva a kosárhoz']);
        } else {
            ApiResponse::serverError('Nem sikerült hozzáadni a kosárhoz');
        }
        break;
        
    case 'PUT':
        
        if (!$resourceId) {
            ApiResponse::badRequest('Hiányzó kosár elem ID');
        }
        
        $quantity = isset($input['quantity']) ? (int)$input['quantity'] : null;
        
        if ($quantity === null || $quantity < 1) {
            ApiResponse::badRequest('Érvénytelen mennyiség');
        }
        
        
        if (isset($_SESSION['cart'][$resourceId])) {
            $_SESSION['cart'][$resourceId]['quantity'] = $quantity;
            $result = true;
        } else {
            $result = false;
        }
        
        if ($result) {
            ApiResponse::success(['message' => 'Kosár frissítve']);
        } else {
            ApiResponse::notFound('A kosár elem nem található');
        }
        break;
        
    case 'DELETE':
        if ($resourceId) {
            
            if (isset($_SESSION['cart'][$resourceId])) {
                unset($_SESSION['cart'][$resourceId]);
                ApiResponse::noContent();
            } else {
                ApiResponse::notFound('A kosár elem nem található');
            }
        } else {
            
            $_SESSION['cart'] = [];
            ApiResponse::noContent();
        }
        break;
        
    default:
        ApiResponse::methodNotAllowed(['GET', 'POST', 'PUT', 'DELETE']);
}
