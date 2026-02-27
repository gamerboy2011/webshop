<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../app/config/database.php';

// Bejelentkezés ellenőrzése
if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Bejelentkezés szükséges!']);
    exit;
}

// JSON input feldolgozása
$input = json_decode(file_get_contents('php://input'), true);
$code = trim($input['code'] ?? '');
$cartTypeIds = $input['cart_type_ids'] ?? [];
$cartSubtypeIds = $input['cart_subtype_ids'] ?? [];

if (empty($code)) {
    echo json_encode(['success' => false, 'message' => 'Hiányzó kuponkód!']);
    exit;
}

// Kupon keresése
$stmt = $pdo->prepare("
    SELECT c.*, pt.name as product_type_name, ps.name as product_subtype_name
    FROM coupons c
    LEFT JOIN product_type pt ON c.product_type_id = pt.product_type_id
    LEFT JOIN product_subtype ps ON c.product_subtype_id = ps.product_subtype_id
    WHERE c.coupon_pass = ? AND c.is_active = 1
");
$stmt->execute([$code]);
$coupon = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$coupon) {
    echo json_encode(['success' => false, 'message' => 'Érvénytelen kuponkód!']);
    exit;
}

// Dátum ellenőrzés
$today = date('Y-m-d');
if ($today < $coupon['start_date']) {
    echo json_encode(['success' => false, 'message' => 'Ez a kupon még nem aktív. Kezdődik: ' . date('Y.m.d', strtotime($coupon['start_date']))]);
    exit;
}
if ($today > $coupon['end_date']) {
    echo json_encode(['success' => false, 'message' => 'Ez a kupon már lejárt!']);
    exit;
}

// Alkategória nevek magyarul
$subtypeNames = [
    'bag' => 'Táska', 'cap' => 'Sapka', 'hat' => 'Kalap', 'hoodie' => 'Kapucnis pulcsi',
    'jacket' => 'Dzseki', 'jeans' => 'Farmer', 'leggings' => 'Leggings', 'sweater' => 'Pulóver',
    't-shirt' => 'Póló', 'winter coat' => 'Télikabát', 'sandals' => 'Szandál', 'shoes' => 'Cipő'
];

// Alkategória ellenőrzés (ha van megadva)
if (!empty($coupon['product_subtype_id'])) {
    $cartSubtypeIds = array_filter($cartSubtypeIds); // NULL értékek kiszűrése
    if (!in_array($coupon['product_subtype_id'], $cartSubtypeIds)) {
        $subtypeName = $subtypeNames[$coupon['product_subtype_name']] ?? ucfirst($coupon['product_subtype_name']);
        echo json_encode(['success' => false, 'message' => 'Ez a kupon csak "' . $subtypeName . '" termékekre érvényes, de ilyen nincs a kosaradban!']);
        exit;
    }
}
// Főkategória ellenőrzés (ha nincs alkategória, de van főkategória)
elseif (!empty($coupon['product_type_id'])) {
    $cartTypeIds = array_filter($cartTypeIds); // NULL értékek kiszűrése
    if (!in_array($coupon['product_type_id'], $cartTypeIds)) {
        $typeNames = [
            'Accessory' => 'Kiegészítők',
            'Clothe' => 'Ruházat',
            'Shoe' => 'Cipők'
        ];
        $typeName = $typeNames[$coupon['product_type_name']] ?? $coupon['product_type_name'];
        echo json_encode(['success' => false, 'message' => 'Ez a kupon csak "' . $typeName . '" termékekre érvényes, de ilyen nincs a kosaradban!']);
        exit;
    }
}

// Ellenőrzés: már felhasználta-e
$stmt = $pdo->prepare("SELECT * FROM user_coupons WHERE user_id = ? AND coupon_id = ?");
$stmt->execute([$_SESSION['user_id'], $coupon['id']]);
$existing = $stmt->fetch();

if ($existing && !empty($existing['used_at'])) {
    echo json_encode(['success' => false, 'message' => 'Ezt a kupont már felhasználtad egy korábbi rendelésnél!']);
    exit;
}

// Ha még nem aktivált, aktiváljuk
if (!$existing) {
    $stmt = $pdo->prepare("INSERT INTO user_coupons (user_id, coupon_id) VALUES (?, ?)");
    $stmt->execute([$_SESSION['user_id'], $coupon['id']]);
}

// Típus nevek magyarul
$typeNames = [
    'Accessory' => 'Kiegészítők',
    'Clothe' => 'Ruházat',
    'Shoe' => 'Cipők'
];

$categoryName = 'Minden termék';
if (!empty($coupon['product_subtype_name'])) {
    $categoryName = $subtypeNames[$coupon['product_subtype_name']] ?? ucfirst($coupon['product_subtype_name']);
} elseif (!empty($coupon['product_type_name'])) {
    $categoryName = $typeNames[$coupon['product_type_name']] ?? $coupon['product_type_name'];
}

echo json_encode([
    'success' => true,
    'message' => 'Kupon sikeresen alkalmazva: -' . (int)$coupon['amount'] . '% kedvezmény (' . $categoryName . ')',
    'coupon' => [
        'id' => (int)$coupon['id'],
        'amount' => (int)$coupon['amount'],
        'product_type_id' => $coupon['product_type_id'] ? (int)$coupon['product_type_id'] : null,
        'product_subtype_id' => $coupon['product_subtype_id'] ? (int)$coupon['product_subtype_id'] : null,
        'name' => $coupon['name'] ?: $coupon['description']
    ]
]);
