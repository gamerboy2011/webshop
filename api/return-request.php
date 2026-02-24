<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../app/library/config.php';
require_once __DIR__ . '/../app/helpers/Mail.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Nincs bejelentkezve']);
    exit;
}

$userId = $_SESSION['user_id'];
$orderId = $_POST['order_id'] ?? null;
$problemType = $_POST['problem_type'] ?? '';
$reason = trim($_POST['reason'] ?? '');

if (!$orderId || !$reason || !$problemType) {
    echo json_encode(['success' => false, 'error' => 'Hiányzó adatok']);
    exit;
}

// Probléma típusok magyarul
$problemTypes = [
    'damaged' => 'Sérült termék',
    'wrong_size' => 'Nem megfelelő méret',
    'wrong_product' => 'Nem ezt a terméket rendeltem',
    'quality' => 'Minőségi probléma',
    'not_as_described' => 'Nem felel meg a leírásnak',
    'changed_mind' => 'Meggondoltam magam',
    'other' => 'Egyéb'
];
$problemText = $problemTypes[$problemType] ?? $problemType;

// Felhasználó adatai
$stmt = $pdo->prepare("SELECT username, email FROM users WHERE user_id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

// Ellenőrizzük, hogy a rendelés a felhasználóé-e
$stmt = $pdo->prepare("SELECT * FROM orders WHERE order_id = ? AND user_id = ?");
$stmt->execute([$orderId, $userId]);
$order = $stmt->fetch();

if (!$order) {
    echo json_encode(['success' => false, 'error' => 'Rendelés nem található']);
    exit;
}

// Ellenőrizzük, hogy nincs-e már visszaküldési kérelem
$stmt = $pdo->prepare("SELECT * FROM returns WHERE order_id = ? AND user_id = ?");
$stmt->execute([$orderId, $userId]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'error' => 'Már van visszaküldési kérelem ehhez a rendeléshez']);
    exit;
}

// Teljes indoklás (típus + leírás)
$fullReason = "[$problemText] $reason";

// Visszaküldési kérelem létrehozása
$stmt = $pdo->prepare("INSERT INTO returns (order_id, user_id, reason) VALUES (?, ?, ?)");
$result = $stmt->execute([$orderId, $userId, $fullReason]);

if ($result) {
    // Email küldése a felhasználónak
    $subject = "YoursyWear - Visszaküldési kérelem beérkezett #$orderId";
    $htmlBody = "
    <!DOCTYPE html>
    <html>
    <head><meta charset='UTF-8'></head>
    <body style='font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px;'>
        <div style='max-width: 600px; margin: 0 auto; background: white; border-radius: 10px; overflow: hidden;'>
            <div style='background: #000; color: white; padding: 30px; text-align: center;'>
                <h1 style='margin: 0;'>YoursyWear</h1>
            </div>
            
            <div style='padding: 30px;'>
                <h2 style='color: #333;'>Kedves {$user['username']}!</h2>
                <p style='color: #666;'>Visszaküldési kérelmed sikeresen beérkezett.</p>
                
                <div style='background: #fff3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 8px; margin: 20px 0;'>
                    <p style='margin: 0 0 10px 0;'><strong>Rendelésszám:</strong> #$orderId</p>
                    <p style='margin: 0 0 10px 0;'><strong>Probléma típusa:</strong> $problemText</p>
                    <p style='margin: 0;'><strong>Leírás:</strong> " . htmlspecialchars($reason) . "</p>
                </div>
                
                <p style='color: #666;'>Kérelmedet hamarosan elbíráljuk és értesítünk az eredményről.</p>
                
                <p style='color: #999; font-size: 14px; margin-top: 20px;'>
                    A visszaküldés állapotát a profilodban is nyomon követheted.
                </p>
            </div>
            
            <div style='background: #f5f5f5; padding: 20px; text-align: center; font-size: 12px; color: #999;'>
                © " . date('Y') . " YoursyWear. Minden jog fenntartva.
            </div>
        </div>
    </body>
    </html>";
    
    Mail::send($user['email'], $subject, $htmlBody, $user['username']);
    
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Adatbázis hiba']);
}
