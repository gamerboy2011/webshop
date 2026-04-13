<?php





global $pdo;
$couponCode = $segments[1] ?? null;
$action = $segments[2] ?? null;


$userId = $_SESSION['user_id'] ?? null;

switch ($method) {
    case 'GET':
        
        if (!$couponCode) {
            ApiResponse::badRequest('Hiányzó kuponkód');
        }
        
        if ($action !== 'validate') {
            ApiResponse::notFound('Ismeretlen művelet');
        }
        
        
        $stmt = $pdo->prepare("
            SELECT c.*, uc.user_id as owner_user_id, uc.used_at
            FROM coupons c
            LEFT JOIN user_coupons uc ON c.id = uc.coupon_id AND uc.user_id = ?
            WHERE c.coupon_pass = ? AND c.is_active = 1
            LIMIT 1
        ");
        $stmt->execute([$userId, $couponCode]);
        $coupon = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$coupon) {
            ApiResponse::notFound('A kupon nem található');
        }
        
        
        $today = date('Y-m-d');
        if ($today < $coupon['start_date']) {
            ApiResponse::badRequest('A kupon még nem aktív');
        }
        if ($today > $coupon['end_date']) {
            ApiResponse::badRequest('A kupon lejárt');
        }
        
        
        if (!empty($coupon['used_at'])) {
            ApiResponse::badRequest('A kupon már fel lett használva');
        }
        
        
        $cartTotal = (float)($queryParams['cart_total'] ?? 0);
        $discountPercent = (int)$coupon['amount'];
        $discountAmount = $cartTotal * ($discountPercent / 100);
        
        ApiResponse::success([
            'valid' => true,
            'code' => $coupon['coupon_pass'],
            'name' => $coupon['name'] ?? $coupon['description'],
            'discount_type' => 'percentage',
            'discount_value' => $discountPercent,
            'discount_amount' => round($discountAmount),
            'start_date' => $coupon['start_date'],
            'end_date' => $coupon['end_date']
        ]);
        break;
        
    case 'POST':
        // Kupon aktiválása
        if (!$userId) {
            ApiResponse::unauthorized('Jelentkezz be a kupon aktiválásához');
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $code = $input['code'] ?? $couponCode ?? null;
        
        if (!$code) {
            ApiResponse::badRequest('Hiányzó kuponkód');
        }
        
        // Kupon keresése
        $stmt = $pdo->prepare("
            SELECT * FROM coupons 
            WHERE coupon_pass = ? AND is_active = 1
            LIMIT 1
        ");
        $stmt->execute([$code]);
        $coupon = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$coupon) {
            ApiResponse::notFound('Érvénytelen kuponkód');
        }
        
        // Ellenőrzés - érvényesség
        $today = date('Y-m-d');
        if ($today < $coupon['start_date']) {
            ApiResponse::badRequest('A kupon még nem aktív');
        }
        if ($today > $coupon['end_date']) {
            ApiResponse::badRequest('A kupon lejárt');
        }
        
        // Ellenőrzés - már aktiválta-e
        $stmt = $pdo->prepare("SELECT id FROM user_coupons WHERE user_id = ? AND coupon_id = ?");
        $stmt->execute([$userId, $coupon['id']]);
        if ($stmt->fetch()) {
            ApiResponse::badRequest('Már aktiváltad ezt a kupont');
        }
        
        // Aktiválás
        $stmt = $pdo->prepare("INSERT INTO user_coupons (user_id, coupon_id, activated_at) VALUES (?, ?, NOW())");
        $stmt->execute([$userId, $coupon['id']]);
        
        ApiResponse::success([
            'message' => 'Kupon sikeresen aktiválva!',
            'coupon' => [
                'name' => $coupon['name'],
                'amount' => $coupon['amount'],
                'end_date' => $coupon['end_date']
            ]
        ], 201);
        break;
        
    default:
        ApiResponse::methodNotAllowed(['GET', 'POST']);
}
