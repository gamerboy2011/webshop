#!/usr/bin/php
<?php
/**
 * CRON JOB: Kupon értesítő emailek küldése
 * 
 * Futtatás: Naponta egyszer (pl. reggel 8:00)
 * Crontab: 0 8 * * * /usr/bin/php /Applications/XAMPP/xamppfiles/htdocs/webshop/cron/send-coupon-emails.php
 * 
 * Ez a script:
 * 1. Megkeresi az aznap aktívvá váló kuponokat
 * 2. Összegyűjti őket egy emailbe
 * 3. Kiküldi minden regisztrált felhasználónak
 */

// Alap beállítások
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Útvonalak
define('BASE_PATH', dirname(__DIR__));

// Adatbázis kapcsolat
require_once BASE_PATH . '/app/config/database.php';

// Mail helper
require_once BASE_PATH . '/app/helpers/Mail.php';

$today = date('Y-m-d');
echo "[$today] Kupon értesítő futtatása...\n";

// 1. Aznap aktívvá váló kuponok keresése
$stmt = $pdo->prepare("
    SELECT c.*, pt.name as product_type_name
    FROM coupons c
    LEFT JOIN product_type pt ON c.product_type_id = pt.product_type_id
    WHERE c.is_active = 1 
      AND c.start_date = ?
      AND c.end_date >= ?
");
$stmt->execute([$today, $today]);
$todaysCoupons = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($todaysCoupons)) {
    echo "Nincs új kupon ma.\n";
    exit(0);
}

echo count($todaysCoupons) . " új kupon található.\n";

// Típus nevek magyarul
$typeNames = [
    'Accessory' => 'Kiegészítők',
    'Clothe' => 'Ruházat',
    'Shoe' => 'Cipők'
];

// 2. Regisztrált felhasználók lekérdezése
$stmt = $pdo->query("SELECT user_id, email, username FROM users WHERE is_activated = 1");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($users)) {
    echo "Nincs aktív felhasználó.\n";
    exit(0);
}

echo count($users) . " felhasználónak küldünk emailt.\n";

// 3. Email sablon generálása
$couponsHtml = '';
foreach ($todaysCoupons as $coupon) {
    $typeName = 'Minden termék';
    if (!empty($coupon['product_type_name'])) {
        $typeName = $typeNames[$coupon['product_type_name']] ?? $coupon['product_type_name'];
    }
    
    $validUntil = date('Y. m. d.', strtotime($coupon['end_date']));
    $couponUrl = "https://" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "/webshop/kuponok/" . $coupon['coupon_pass'];
    
    $couponsHtml .= "
    <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 16px; padding: 24px; margin-bottom: 16px; color: white;'>
        <div style='display: flex; align-items: center; justify-content: space-between;'>
            <div>
                <h3 style='margin: 0 0 8px 0; font-size: 20px;'>" . htmlspecialchars($coupon['name'] ?: $coupon['description']) . "</h3>
                <p style='margin: 0; opacity: 0.9; font-size: 14px;'>$typeName • Érvényes: $validUntil-ig</p>
            </div>
            <div style='text-align: center;'>
                <div style='font-size: 36px; font-weight: bold;'>-{$coupon['amount']}%</div>
            </div>
        </div>
        <div style='margin-top: 16px; padding-top: 16px; border-top: 1px solid rgba(255,255,255,0.3);'>
            <span style='background: rgba(255,255,255,0.2); padding: 6px 12px; border-radius: 6px; font-family: monospace; font-weight: bold;'>{$coupon['coupon_pass']}</span>
            <a href='$couponUrl' style='float: right; background: white; color: #764ba2; padding: 8px 16px; border-radius: 8px; text-decoration: none; font-weight: bold;'>Aktiválás</a>
        </div>
    </div>";
}

$subject = count($todaysCoupons) > 1 
    ? "🎉 " . count($todaysCoupons) . " új kupon vár rád a YoursyWear-nél!"
    : "🎁 Új kupon érkezett: -" . $todaysCoupons[0]['amount'] . "% kedvezmény!";

// 4. Emailek küldése
$successCount = 0;
$errorCount = 0;

foreach ($users as $user) {
    $htmlBody = "
    <!DOCTYPE html>
    <html>
    <head><meta charset='UTF-8'></head>
    <body style='font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px; margin: 0;'>
        <div style='max-width: 600px; margin: 0 auto; background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1);'>
            
            <!-- FEJLÉC -->
            <div style='background: #000; color: white; padding: 30px; text-align: center;'>
                <h1 style='margin: 0; font-size: 28px;'>YoursyWear</h1>
                <p style='margin: 10px 0 0 0; color: #ccc;'>Kedvezmények csak neked!</p>
            </div>
            
            <!-- TARTALOM -->
            <div style='padding: 30px;'>
                <h2 style='color: #333; margin: 0 0 20px 0;'>Szia " . htmlspecialchars($user['username']) . "! 👋</h2>
                <p style='color: #666; font-size: 16px; line-height: 1.6;'>
                    Remek hírünk van! Ma új kuponok váltak elérhetővé, amiket most aktiválhatsz és felhasználhatsz a következő vásárlásodnál.
                </p>
                
                <div style='margin: 30px 0;'>
                    $couponsHtml
                </div>
                
                <p style='color: #666; font-size: 14px;'>
                    A kuponok a pénztárnál automatikusan levonásra kerülnek, ha aktiváltad őket a fiókodban.
                </p>
                
                <div style='text-align: center; margin-top: 30px;'>
                    <a href='https://" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "/webshop/' 
                       style='display: inline-block; background: #000; color: white; padding: 16px 32px; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 16px;'>
                        Vásárlás most
                    </a>
                </div>
            </div>
            
            <!-- LÁBLÉC -->
            <div style='background: #f5f5f5; padding: 20px; text-align: center; font-size: 12px; color: #999;'>
                <p style='margin: 0 0 10px 0;'>Ha nem szeretnél több értesítést kapni, kérjük vedd fel velünk a kapcsolatot.</p>
                <p style='margin: 0;'>© " . date('Y') . " YoursyWear. Minden jog fenntartva.</p>
            </div>
            
        </div>
    </body>
    </html>";
    
    $result = Mail::send($user['email'], $subject, $htmlBody, $user['username']);
    
    if ($result['success']) {
        $successCount++;
        echo "✓ Email elküldve: {$user['email']}\n";
    } else {
        $errorCount++;
        echo "✗ Hiba ({$user['email']}): {$result['error']}\n";
    }
    
    // Rate limiting - ne terheljük túl a szervert
    usleep(100000); // 100ms várakozás
}

echo "\n=== ÖSSZESÍTÉS ===\n";
echo "Sikeres: $successCount\n";
echo "Hibás: $errorCount\n";
echo "==================\n";

exit($errorCount > 0 ? 1 : 0);
