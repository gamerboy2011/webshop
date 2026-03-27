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
                SELECT r.*, o.created_at as order_date
                FROM returns r
                JOIN orders o ON r.order_id = o.order_id
                WHERE r.return_id = ? AND r.user_id = ?
            ");
            $stmt->execute([$resourceId, $userId]);
            $return = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$return) {
                ApiResponse::notFound('A visszáru kérelem nem található');
            }
            
            ApiResponse::success($return);
        } else {
            
            $stmt = $pdo->prepare("
                SELECT r.*, o.created_at as order_date
                FROM returns r
                JOIN orders o ON r.order_id = o.order_id
                WHERE r.user_id = ?
                ORDER BY r.created_at DESC
            ");
            $stmt->execute([$userId]);
            $returns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            ApiResponse::success([
                'returns' => $returns,
                'count' => count($returns)
            ]);
        }
        break;
        
    case 'POST':
        
        $orderId = $input['order_id'] ?? null;
        $reason = $input['reason'] ?? null;
        $description = $input['description'] ?? null;
        
        if (!$orderId || !$reason) {
            ApiResponse::badRequest('Hiányzó paraméterek: order_id, reason');
        }
        
        
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_id = ? AND user_id = ?");
        $stmt->execute([$orderId, $userId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$order) {
            ApiResponse::notFound('A rendelés nem található');
        }
        
        
        $stmt = $pdo->prepare("
            INSERT INTO returns (user_id, order_id, problem_type, reason, status, created_at)
            VALUES (?, ?, ?, ?, 'pending', NOW())
        ");
        $result = $stmt->execute([$userId, $orderId, $reason, $description]);
        
        if ($result) {
            $returnId = $pdo->lastInsertId();
            ApiResponse::created(['return_id' => $returnId], 'Visszáru kérelem sikeresen leadva');
        } else {
            ApiResponse::serverError('Nem sikerült létrehozni a visszáru kérelmet');
        }
        break;
        
    default:
        ApiResponse::methodNotAllowed(['GET', 'POST']);
}
