<?php
/**
 * Kuponkód ellenőrzés API
 * POST /webshop/api/check-coupon.php
 * 
 * Bemenet (JSON): 
 *   - code: string (kuponkód)
 *   - cart_type_ids: array (kosárban lévő termék típus ID-k)
 *   - cart_subtype_ids: array (kosárban lévő termék altípus ID-k)
 * 
 * Kimenet (JSON):
 *   - success: bool
 *   - message: string
 *   - coupon: object (ha success)
 */

header('Content-Type: application/json; charset=utf-8');

// Bootstrap betöltés (session, adatbázis, autoload)
require_once __DIR__ . '/../app/bootstrap.php';

// Csak POST kérés
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Be van-e jelentkezve?
if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Kérlek jelentkezz be a kupon használatához!']);
    exit;
}

$userId = $_SESSION['user_id'];

// JSON bemenet
$input = json_decode(file_get_contents('php://input'), true);
$code = strtoupper(trim($input['code'] ?? ''));
$cartTypeIds = $input['cart_type_ids'] ?? [];
$cartSubtypeIds = $input['cart_subtype_ids'] ?? [];

if (empty($code)) {
    echo json_encode(['success' => false, 'message' => 'Kérlek adj meg egy kuponkódot!']);
    exit;
}

try {
    // Kupon keresése kód alapján (case-insensitive)
    $stmt = $pdo->prepare("
        SELECT c.*, 
               pt.name as product_type_name,
               ps.name as product_subtype_name
        FROM coupons c
        LEFT JOIN product_type pt ON c.product_type_id = pt.product_type_id
        LEFT JOIN product_subtype ps ON c.product_subtype_id = ps.product_subtype_id
        WHERE UPPER(c.coupon_pass) = ? 
          AND c.is_active = 1
          AND c.start_date <= CURDATE()
          AND c.end_date >= CURDATE()
    ");
    $stmt->execute([$code]);
    $coupon = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$coupon) {
        error_log("Coupon not found for code: $code, user: $userId");
        echo json_encode(['success' => false, 'message' => 'Érvénytelen vagy lejárt kuponkód!']);
        exit;
    }
    
    // Ellenőrizzük, hogy a felhasználó nem használta-e már
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM user_coupons 
        WHERE user_id = ? AND coupon_id = ?
    ");
    $stmt->execute([$userId, $coupon['id']]);
    $alreadyHas = $stmt->fetchColumn() > 0;
    
    // Ellenőrizzük, hogy a kupon érvényes-e a kosár tartalmára
    $couponApplicable = false;
    
    if (empty($coupon['product_type_id']) && empty($coupon['product_subtype_id'])) {
        // Általános kupon - mindenre érvényes
        $couponApplicable = true;
    } elseif (!empty($coupon['product_subtype_id'])) {
        // Altípus specifikus kupon
        $couponApplicable = in_array($coupon['product_subtype_id'], $cartSubtypeIds);
    } elseif (!empty($coupon['product_type_id'])) {
        // Típus specifikus kupon
        $couponApplicable = in_array($coupon['product_type_id'], $cartTypeIds);
    }
    
    if (!$couponApplicable) {
        $targetText = $coupon['product_subtype_name'] ?? $coupon['product_type_name'] ?? 'a megadott termékekre';
        echo json_encode([
            'success' => false, 
            'message' => "Ez a kupon csak " . $targetText . " érvényes, és nincs ilyen termék a kosárban."
        ]);
        exit;
    }
    
    // Ha még nincs a felhasználónál, aktiváljuk
    if (!$alreadyHas) {
        $stmt = $pdo->prepare("
            INSERT INTO user_coupons (user_id, coupon_id, activated_at)
            VALUES (?, ?, NOW())
        ");
        $stmt->execute([$userId, $coupon['id']]);
    }
    
    // Sikeres válasz
    echo json_encode([
        'success' => true,
        'message' => $alreadyHas ? 'Kupon aktiválva!' : 'Kupon sikeresen aktiválva és alkalmazva!',
        'coupon' => [
            'id' => (int)$coupon['id'],
            'code' => $coupon['coupon_pass'],
            'name' => $coupon['name'] ?? $coupon['description'],
            'amount' => (int)$coupon['amount'],
            'product_type_id' => $coupon['product_type_id'] ? (int)$coupon['product_type_id'] : null,
            'product_subtype_id' => $coupon['product_subtype_id'] ? (int)$coupon['product_subtype_id'] : null,
            'product_type_name' => $coupon['product_type_name'],
            'product_subtype_name' => $coupon['product_subtype_name']
        ]
    ]);
    
} catch (PDOException $e) {
    error_log('Coupon check error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Hiba történt a kupon ellenőrzése közben.']);
}
