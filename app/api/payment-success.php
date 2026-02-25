<?php
session_start();
require_once __DIR__ . '/../library/config.php';

// Ellenőrzés
if (empty($_SESSION['pending_order']) || empty($_SESSION['pending_payment'])) {
    header('Location: /webshop/checkout');
    exit;
}

$order = $_SESSION['pending_order'];
$userId = $order['user_id'];

$pdo->beginTransaction();

try {
    // Rendelés létrehozása
    $stmt = $pdo->prepare("
        INSERT INTO orders (
            user_id, payment_method_id, delivery_method_id,
            shipping_name, shipping_phone, shipping_postcode, shipping_city, shipping_address,
            foxpost_point_id, foxpost_point_name, foxpost_point_address,
            status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
    ");
    $stmt->execute([
        $userId,
        $order['payment_method_id'],
        $order['delivery_method_id'],
        $order['shipping_name'] ?: null,
        $order['shipping_phone'] ?: null,
        $order['shipping_postcode'] ?: null,
        $order['shipping_city'] ?: null,
        $order['shipping_address'] ?: null,
        $order['foxpost_point_id'] ?: null,
        $order['foxpost_point_name'] ?: null,
        $order['foxpost_point_address'] ?: null
    ]);
    
    $orderId = $pdo->lastInsertId();
    $orderTotal = 0;
    $orderItems = [];
    
    // Rendelés tételek
    foreach ($order['cart'] as $item) {
        // Termék adatok
        $stmt = $pdo->prepare("SELECT price, name FROM product WHERE product_id = ?");
        $stmt->execute([$item['product_id']]);
        $product = $stmt->fetch();
        $price = (float)$product['price'];
        
        // Méret
        $stmt = $pdo->prepare("SELECT size_value FROM size WHERE size_id = ?");
        $stmt->execute([$item['size_id']]);
        $sizeValue = $stmt->fetchColumn() ?: '-';
        
        // Stock ID lekérése
        $stmt = $pdo->prepare("SELECT stock_id FROM stock WHERE product_id = ? AND size_id = ?");
        $stmt->execute([$item['product_id'], $item['size_id']]);
        $stockId = $stmt->fetchColumn();
        
        if (!$stockId) {
            throw new Exception('Stock not found');
        }
        
        // Order item beszúrása
        $stmt = $pdo->prepare("INSERT INTO order_item (order_id, stock_id, quantity) VALUES (?, ?, ?)");
        $stmt->execute([$orderId, $stockId, $item['quantity']]);
        
        // Készlet csökkentése
        $stmt = $pdo->prepare("UPDATE stock SET quantity = quantity - ? WHERE stock_id = ?");
        $stmt->execute([$item['quantity'], $stockId]);
        
        $itemTotal = $price * $item['quantity'];
        $orderTotal += $itemTotal;
        
        $orderItems[] = [
            'name' => $product['name'],
            'size' => $sizeValue,
            'quantity' => $item['quantity'],
            'price' => $price,
            'total' => $itemTotal
        ];
    }
    
    $pdo->commit();
    
    // Felhasználó adatok (email és számlázási cím)
    $stmt = $pdo->prepare("
        SELECT email, username,
               billing_postcode, billing_city, billing_street_name, 
               billing_street_type, billing_house_number, billing_floor_door
        FROM users WHERE user_id = ?
    ");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    // Számlázási cím összeállítása
    $billingAddress = '';
    if ($user['billing_postcode'] && $user['billing_city']) {
        $billingAddress = $user['billing_postcode'] . ' ' . $user['billing_city'];
        if ($user['billing_street_name']) {
            $billingAddress .= ', ' . $user['billing_street_name'];
            if ($user['billing_street_type']) {
                $billingAddress .= ' ' . $user['billing_street_type'];
            }
            if ($user['billing_house_number']) {
                $billingAddress .= ' ' . $user['billing_house_number'];
            }
            if ($user['billing_floor_door']) {
                $billingAddress .= ', ' . $user['billing_floor_door'];
            }
        }
    }
    
    // Email küldése
    $deliveryAddress = $order['foxpost_point_name'] ?: ($order['shipping_city'] . ', ' . $order['shipping_address']);
    
    require_once __DIR__ . '/../helpers/Mail.php';
    
    $subject = "YoursyWear - Rendelés visszaigazolás #$orderId";
    $deliveryMethodId = $order['delivery_method_id'];
    $deliveryText = $deliveryMethodId == 3 ? 'FoxPost csomagautomata' : 'Házhoz szállítás (GLS)';
    $shippingCost = $orderTotal >= 15000 ? 0 : ($deliveryMethodId == 3 ? 890 : 1490);
    $grandTotal = $orderTotal + $shippingCost;
    
    $invoiceNumber = 'YW-' . date('Y') . '-' . str_pad($orderId, 6, '0', STR_PAD_LEFT);
    $invoiceDate = date('Y. m. d.');
    
    $itemsHtml = '';
    foreach ($orderItems as $item) {
        $unitPrice = number_format($item['price'], 0, ',', ' ');
        $lineTotal = number_format($item['total'], 0, ',', ' ');
        $itemsHtml .= "
            <tr>
                <td style='padding: 12px 8px; border-bottom: 1px solid #e5e5e5;'>
                    <strong>{$item['name']}</strong><br>
                    <span style='color: #666; font-size: 12px;'>Méret: {$item['size']}</span>
                </td>
                <td style='padding: 12px 8px; border-bottom: 1px solid #e5e5e5; text-align: center;'>{$item['quantity']}</td>
                <td style='padding: 12px 8px; border-bottom: 1px solid #e5e5e5; text-align: right;'>{$unitPrice} Ft</td>
                <td style='padding: 12px 8px; border-bottom: 1px solid #e5e5e5; text-align: right;'>{$lineTotal} Ft</td>
            </tr>";
    }
    
    $subtotalFormatted = number_format($orderTotal, 0, ',', ' ');
    $shippingFormatted = $shippingCost == 0 ? 'INGYENES' : number_format($shippingCost, 0, ',', ' ') . ' Ft';
    $grandTotalFormatted = number_format($grandTotal, 0, ',', ' ');
    $nettoTotal = number_format(round($grandTotal / 1.27), 0, ',', ' ');
    $vatAmount = number_format(round($grandTotal - ($grandTotal / 1.27)), 0, ',', ' ');
    $name = $user['username'];
    $email = $user['email'];
    
    $htmlBody = "
    <!DOCTYPE html>
    <html>
    <head><meta charset='UTF-8'></head>
    <body style='font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px; margin: 0;'>
        <div style='max-width: 650px; margin: 0 auto; background: white; border: 1px solid #ddd;'>
            
            <div style='background: #000; color: white; padding: 25px 30px;'>
                <table style='width: 100%;'>
                    <tr>
                        <td>
                            <h1 style='margin: 0; font-size: 28px;'>YoursyWear</h1>
                            <p style='margin: 5px 0 0 0; font-size: 12px; color: #ccc;'>www.yoursywear.hu</p>
                        </td>
                        <td style='text-align: right;'>
                            <p style='margin: 0; font-size: 20px; font-weight: bold;'>SZÁMLA</p>
                            <p style='margin: 5px 0 0 0; font-size: 14px; color: #ccc;'>{$invoiceNumber}</p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div style='padding: 25px 30px; border-bottom: 2px solid #eee;'>
                <table style='width: 100%;'>
                    <tr>
                        <td style='width: 50%; vertical-align: top;'>
                            <p style='margin: 0 0 5px 0; font-size: 11px; color: #999; text-transform: uppercase;'>Eladó</p>
                            <p style='margin: 0; font-weight: bold;'>YoursyWear Kft.</p>
                            <p style='margin: 3px 0; color: #666; font-size: 13px;'>1234 Budapest, Példa utca 123.</p>
                            <p style='margin: 3px 0; color: #666; font-size: 13px;'>Adószám: 12345678-2-42</p>
                        </td>
                        <td style='width: 50%; vertical-align: top; text-align: right;'>
                            <p style='margin: 0 0 5px 0; font-size: 11px; color: #999; text-transform: uppercase;'>Vevő</p>
                            <p style='margin: 0; font-weight: bold;'>{$name}</p>
                            " . ($billingAddress ? "<p style='margin: 3px 0; color: #666; font-size: 13px;'>{$billingAddress}</p>" : "") . "
                            <p style='margin: 3px 0; color: #666; font-size: 13px;'>{$email}</p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div style='padding: 15px 30px; background: #fafafa; border-bottom: 1px solid #eee;'>
                <table style='width: 100%; font-size: 13px;'>
                    <tr>
                        <td><strong>Számla kelte:</strong> {$invoiceDate}</td>
                        <td style='text-align: center;'><strong>Fizetési mód:</strong> Bankkártya (Fizetve)</td>
                        <td style='text-align: right;'><strong>Rendelésszám:</strong> #{$orderId}</td>
                    </tr>
                </table>
            </div>
            
            <div style='padding: 20px 30px;'>
                <table style='width: 100%; border-collapse: collapse;'>
                    <thead>
                        <tr style='background: #f5f5f5;'>
                            <th style='padding: 12px 8px; text-align: left; font-size: 12px; color: #666; text-transform: uppercase;'>Termék</th>
                            <th style='padding: 12px 8px; text-align: center; font-size: 12px; color: #666; text-transform: uppercase;'>Menny.</th>
                            <th style='padding: 12px 8px; text-align: right; font-size: 12px; color: #666; text-transform: uppercase;'>Egységár</th>
                            <th style='padding: 12px 8px; text-align: right; font-size: 12px; color: #666; text-transform: uppercase;'>Összeg</th>
                        </tr>
                    </thead>
                    <tbody>
                        {$itemsHtml}
                    </tbody>
                </table>
            </div>
            
            <div style='padding: 20px 30px; background: #fafafa;'>
                <table style='width: 100%; font-size: 14px;'>
                    <tr>
                        <td style='padding: 5px 0;'>Részösszeg:</td>
                        <td style='padding: 5px 0; text-align: right;'>{$subtotalFormatted} Ft</td>
                    </tr>
                    <tr>
                        <td style='padding: 5px 0;'>Szállítási költség ({$deliveryText}):</td>
                        <td style='padding: 5px 0; text-align: right;'>{$shippingFormatted}</td>
                    </tr>
                    <tr style='border-top: 2px solid #ddd;'>
                        <td style='padding: 10px 0; font-size: 11px; color: #666;'>Nettó összeg:</td>
                        <td style='padding: 10px 0; text-align: right; font-size: 11px; color: #666;'>{$nettoTotal} Ft</td>
                    </tr>
                    <tr>
                        <td style='padding: 5px 0; font-size: 11px; color: #666;'>ÁFA (27%):</td>
                        <td style='padding: 5px 0; text-align: right; font-size: 11px; color: #666;'>{$vatAmount} Ft</td>
                    </tr>
                    <tr style='border-top: 2px solid #000;'>
                        <td style='padding: 15px 0; font-size: 18px; font-weight: bold;'>Fizetendő összeg:</td>
                        <td style='padding: 15px 0; text-align: right; font-size: 22px; font-weight: bold;'>{$grandTotalFormatted} Ft</td>
                    </tr>
                </table>
            </div>
            
            <div style='padding: 20px 30px; border-top: 1px solid #eee;'>
                <p style='margin: 0 0 10px 0; font-weight: bold; color: #333;'>
                    <span style='color: #666;'>✉</span> Szállítási cím:
                </p>
                <p style='margin: 0; color: #666;'>{$deliveryAddress}</p>
            </div>
            
            <div style='padding: 20px 30px; background: #f5f5f5; text-align: center; font-size: 12px; color: #999;'>
                <p style='margin: 0 0 10px 0;'>Köszönjük a vásárlást!</p>
                <p style='margin: 0;'>Kérdés esetén: <a href='mailto:info@yoursywear.hu' style='color: #666;'>info@yoursywear.hu</a></p>
                <p style='margin: 10px 0 0 0; font-size: 10px;'>© " . date('Y') . " YoursyWear. Minden jog fenntartva.</p>
            </div>
            
        </div>
    </body>
    </html>";
    
    Mail::send($email, $subject, $htmlBody, $name);
    
    // Session tisztítás
    unset($_SESSION['pending_order']);
    unset($_SESSION['pending_payment']);
    unset($_SESSION['cart']);
    
    // Sikeres rendelés
    $_SESSION['order_success'] = $orderId;
    header('Location: /webshop/rendeles-sikeres');
    exit;
    
} catch (Exception $e) {
    $pdo->rollBack();
    error_log('Payment order error: ' . $e->getMessage());
    header('Location: /webshop/checkout?error=order_failed');
    exit;
}
