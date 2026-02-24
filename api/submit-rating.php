<?php
require_once __DIR__ . '/../app/config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /webshop/');
    exit;
}

$orderId = (int)($_POST['order_id'] ?? 0);
$rating = (int)($_POST['rating'] ?? 0);
$comment = trim($_POST['comment'] ?? '');

// Validálás
if (!$orderId || $rating < 1 || $rating > 5) {
    header('Location: /webshop/ertekeles?order=' . $orderId . '&error=1');
    exit;
}

// Ellenőrizzük, hogy létezik-e a rendelés
$stmt = $pdo->prepare("SELECT order_id FROM orders WHERE order_id = ?");
$stmt->execute([$orderId]);
if (!$stmt->fetch()) {
    header('Location: /webshop/ertekeles?order=' . $orderId . '&error=1');
    exit;
}

// Ellenőrizzük, hogy már értékelte-e
$stmt = $pdo->prepare("SELECT rating_id FROM order_ratings WHERE order_id = ?");
$stmt->execute([$orderId]);
if ($stmt->fetch()) {
    // Már értékelte - átirányítás success-re
    header('Location: /webshop/ertekeles?order=' . $orderId . '&submitted=1');
    exit;
}

// Értékelés mentése
try {
    $stmt = $pdo->prepare("INSERT INTO order_ratings (order_id, rating, comment, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$orderId, $rating, $comment]);
    
    header('Location: /webshop/ertekeles?order=' . $orderId . '&submitted=1');
} catch (PDOException $e) {
    // Ha nincs ilyen tábla, hozzuk létre
    if (strpos($e->getMessage(), "doesn't exist") !== false) {
        $pdo->exec("
            CREATE TABLE order_ratings (
                rating_id INT AUTO_INCREMENT PRIMARY KEY,
                order_id INT NOT NULL,
                rating TINYINT NOT NULL,
                comment TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_order (order_id)
            )
        ");
        
        // Próbáljuk újra
        $stmt = $pdo->prepare("INSERT INTO order_ratings (order_id, rating, comment, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$orderId, $rating, $comment]);
        
        header('Location: /webshop/ertekeles?order=' . $orderId . '&submitted=1');
    } else {
        error_log("Rating error: " . $e->getMessage());
        header('Location: /webshop/ertekeles?order=' . $orderId . '&error=1');
    }
}
exit;
