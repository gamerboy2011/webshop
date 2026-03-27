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
        
    default:
        ApiResponse::methodNotAllowed(['GET']);
}
