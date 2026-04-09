<?php
require_once __DIR__ . '/../app/bootstrap.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Jelentkezz be az értékeléshez!']);
    exit;
}

$userId = $_SESSION['user_id'];
$orderId = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
$rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
$comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';

if ($orderId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Érvénytelen rendelés.']);
    exit;
}

if ($rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'error' => 'Válassz értékelést (1-5 csillag).']);
    exit;
}

try {
    // Ellenőrizzük, hogy a rendelés a felhasználóé
    $stmt = $pdo->prepare("SELECT order_id FROM orders WHERE order_id = ? AND user_id = ?");
    $stmt->execute([$orderId, $userId]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'A rendelés nem található.']);
        exit;
    }
    
    // Ellenőrizzük, hogy már értékelte-e
    $stmt = $pdo->prepare("SELECT rating_id FROM order_ratings WHERE order_id = ?");
    $stmt->execute([$orderId]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Már értékelted ezt a rendelést.']);
        exit;
    }
    
    // Értékelés mentése
    $stmt = $pdo->prepare("INSERT INTO order_ratings (order_id, rating, comment) VALUES (?, ?, ?)");
    $stmt->execute([$orderId, $rating, $comment ?: null]);
    
    echo json_encode(['success' => true, 'message' => 'Köszönjük az értékelést!']);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Hiba történt az értékelés mentésekor.']);
}
