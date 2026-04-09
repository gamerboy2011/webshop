<?php
require_once __DIR__ . '/../app/bootstrap.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Jelentkezz be az értékeléshez!']);
    exit;
}

$userId = $_SESSION['user_id'];
$productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
$title = isset($_POST['title']) ? trim($_POST['title']) : '';
$comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';

if ($productId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Érvénytelen termék.']);
    exit;
}

if ($rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'error' => 'Válassz értékelést (1-5 csillag).']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT product_id FROM product WHERE product_id = ?");
    $stmt->execute([$productId]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'A termék nem található.']);
        exit;
    }
    
    $stmt = $pdo->prepare("SELECT review_id FROM product_reviews WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$userId, $productId]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Már értékelted ezt a terméket.']);
        exit;
    }
    
    $stmt = $pdo->prepare("
        SELECT o.order_id FROM orders o 
        JOIN order_item oi ON o.order_id = oi.order_id 
        WHERE o.user_id = ? AND oi.product_id = ? LIMIT 1
    ");
    $stmt->execute([$userId, $productId]);
    $purchase = $stmt->fetch();
    $isVerified = $purchase ? 1 : 0;
    $orderId = $purchase ? $purchase['order_id'] : null;
    
    $stmt = $pdo->prepare("
        INSERT INTO product_reviews (product_id, user_id, order_id, rating, title, comment, is_verified_purchase)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$productId, $userId, $orderId, $rating, $title ?: null, $comment ?: null, $isVerified]);
    
    echo json_encode(['success' => true, 'message' => 'Köszönjük az értékelést!', 'is_verified' => $isVerified]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Hiba történt az értékelés mentésekor.']);
}
