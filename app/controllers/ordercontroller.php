<?php

class OrderController
{
    private PDO $pdo;
    
    public function __construct()
    {
        global $pdo;
        $this->pdo = $pdo;
    }
    
    public function placeOrder(): void
    {
        if (empty($_SESSION['cart'])) {
            header('Location: /webshop/kosar');
            exit;
        }
        
        if (empty($_SESSION['user_id'])) {
            header('Location: /webshop/login');
            exit;
        }
        
        $userId = $_SESSION['user_id'];
        $paymentMethodId = (int)($_POST['payment_method_id'] ?? 0);
        $deliveryMethodId = (int)($_POST['delivery_method_id'] ?? 0);
        
        // Szállítási adatok
        $shippingName = trim($_POST['shipping_name'] ?? '');
        $shippingPhone = trim($_POST['shipping_phone'] ?? '');
        $shippingPostcode = trim($_POST['shipping_postcode'] ?? '');
        $shippingCity = trim($_POST['shipping_city'] ?? '');
        $shippingAddress = trim($_POST['shipping_address'] ?? '');
        
        // FoxPost adatok
        $foxpostPointId = trim($_POST['foxpost_point_id'] ?? '');
        $foxpostPointName = trim($_POST['foxpost_point_name'] ?? '');
        $foxpostPointAddress = trim($_POST['foxpost_point_address'] ?? '');
        
        if ($paymentMethodId <= 0 || $deliveryMethodId <= 0) {
            header('Location: /webshop/checkout?error=missing_method');
            exit;
        }
        
        $this->pdo->beginTransaction();
        
        try {
            // Rendelés létrehozása
            $stmt = $this->pdo->prepare("
                INSERT INTO orders (
                    user_id, payment_method_id, delivery_method_id,
                    shipping_name, shipping_phone, shipping_postcode, shipping_city, shipping_address,
                    foxpost_point_id, foxpost_point_name, foxpost_point_address,
                    status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
            ");
            $stmt->execute([
                $userId,
                $paymentMethodId,
                $deliveryMethodId,
                $shippingName ?: null,
                $shippingPhone ?: null,
                $shippingPostcode ?: null,
                $shippingCity ?: null,
                $shippingAddress ?: null,
                $foxpostPointId ?: null,
                $foxpostPointName ?: null,
                $foxpostPointAddress ?: null
            ]);
            
            $orderId = $this->pdo->lastInsertId();
            $orderTotal = 0;
            $orderItems = [];
            
            // Rendelés tételek
            foreach ($_SESSION['cart'] as $item) {
                // Termék adatok
                $stmt = $this->pdo->prepare("SELECT price, name FROM product WHERE product_id = ?");
                $stmt->execute([$item['product_id']]);
                $product = $stmt->fetch();
                $price = (float)$product['price'];
                
                // Méret
                $stmt = $this->pdo->prepare("SELECT size_value FROM size WHERE size_id = ?");
                $stmt->execute([$item['size_id']]);
                $sizeValue = $stmt->fetchColumn() ?: '-';
                
                // Stock ID lekérése
                $stmt = $this->pdo->prepare("SELECT stock_id FROM stock WHERE product_id = ? AND size_id = ?");
                $stmt->execute([$item['product_id'], $item['size_id']]);
                $stockId = $stmt->fetchColumn();
                
                if (!$stockId) {
                    throw new Exception('Stock not found for product ' . $item['product_id'] . ' size ' . $item['size_id']);
                }
                
                // Order item beszúrása stock_id-val
                $stmt = $this->pdo->prepare("
                    INSERT INTO order_item (order_id, stock_id, quantity)
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$orderId, $stockId, $item['quantity']]);
                
                // Készlet csökkentése
                $stmt = $this->pdo->prepare("UPDATE stock SET quantity = quantity - ? WHERE stock_id = ?");
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
            
            // Szállítási cím mentése a user profiljába (ha házhoz szállítás)
            if ($deliveryMethodId == 2 && $shippingPostcode && $shippingCity) {
                $stmt = $this->pdo->prepare("
                    UPDATE users SET 
                        shipping_postcode = ?,
                        shipping_city = ?,
                        shipping_street_name = ?,
                        phone = COALESCE(NULLIF(phone, ''), ?)
                    WHERE user_id = ?
                ");
                $stmt->execute([
                    $shippingPostcode,
                    $shippingCity,
                    $shippingAddress,
                    $shippingPhone,
                    $userId
                ]);
            }
            
            $this->pdo->commit();
            
            // Felhasználó email címe
            $stmt = $this->pdo->prepare("SELECT email, username FROM users WHERE user_id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            // Email küldése
            $this->sendOrderConfirmationEmail(
                $user['email'],
                $user['username'],
                $orderId,
                $orderItems,
                $orderTotal,
                $deliveryMethodId,
                $foxpostPointName ?: ($shippingCity . ', ' . $shippingAddress)
            );
            
            // Kosár ürítése
            unset($_SESSION['cart']);
            
            // Sikeres rendelés oldal
            $_SESSION['order_success'] = $orderId;
            header('Location: /webshop/rendeles-sikeres');
            exit;
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log('Order error: ' . $e->getMessage());
            header('Location: /webshop/checkout?error=order_failed');
            exit;
        }
    }
    
    private function sendOrderConfirmationEmail(
        string $email,
        string $name,
        int $orderId,
        array $items,
        float $total,
        int $deliveryMethodId,
        string $deliveryAddress
    ): bool {
        $subject = "YoursyWear - Rendelés visszaigazolás #$orderId";
        
        // Szállítási mód szöveg
        $deliveryText = $deliveryMethodId == 3 ? 'FoxPost csomagautomata' : 'Házhoz szállítás';
        
        // Termékek HTML
        $itemsHtml = '';
        foreach ($items as $item) {
            $itemsHtml .= "
                <tr>
                    <td style='padding: 10px; border-bottom: 1px solid #eee;'>{$item['name']}</td>
                    <td style='padding: 10px; border-bottom: 1px solid #eee; text-align: center;'>{$item['size']}</td>
                    <td style='padding: 10px; border-bottom: 1px solid #eee; text-align: center;'>{$item['quantity']} db</td>
                    <td style='padding: 10px; border-bottom: 1px solid #eee; text-align: right;'>" . number_format($item['total'], 0, ',', ' ') . " Ft</td>
                </tr>";
        }
        
        $totalFormatted = number_format($total, 0, ',', ' ');
        
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
                    <h2 style='color: #333;'>Kedves {$name}!</h2>
                    <p style='color: #666;'>Köszönjük a rendelésed! Az alábbi rendelést rögzítettük:</p>
                    
                    <div style='background: #f9f9f9; padding: 15px; border-radius: 8px; margin: 20px 0;'>
                        <p style='margin: 0; font-weight: bold;'>Rendelésszám: #{$orderId}</p>
                    </div>
                    
                    <table style='width: 100%; border-collapse: collapse; margin: 20px 0;'>
                        <thead>
                            <tr style='background: #f5f5f5;'>
                                <th style='padding: 10px; text-align: left;'>Termék</th>
                                <th style='padding: 10px; text-align: center;'>Méret</th>
                                <th style='padding: 10px; text-align: center;'>Mennyiség</th>
                                <th style='padding: 10px; text-align: right;'>Ár</th>
                            </tr>
                        </thead>
                        <tbody>
                            {$itemsHtml}
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan='3' style='padding: 15px; font-weight: bold; text-align: right;'>Összesen:</td>
                                <td style='padding: 15px; font-weight: bold; text-align: right; font-size: 18px;'>{$totalFormatted} Ft</td>
                            </tr>
                        </tfoot>
                    </table>
                    
                    <div style='background: #f0f7ff; padding: 15px; border-radius: 8px; margin: 20px 0;'>
                        <p style='margin: 0 0 5px 0; font-weight: bold;'>Szállítási mód:</p>
                        <p style='margin: 0; color: #666;'>{$deliveryText}</p>
                        <p style='margin: 5px 0 0 0; color: #666;'>{$deliveryAddress}</p>
                    </div>
                    
                    <p style='color: #666; font-size: 14px;'>
                        Ha kérdésed van, írj nekünk: <a href='mailto:info@yoursywear.hu'>info@yoursywear.hu</a>
                    </p>
                </div>
                
                <div style='background: #f5f5f5; padding: 20px; text-align: center; font-size: 12px; color: #999;'>
                    © " . date('Y') . " YoursyWear. Minden jog fenntartva.
                </div>
            </div>
        </body>
        </html>";
        
        require_once __DIR__ . '/../helpers/Mail.php';
        $result = Mail::send($email, $subject, $htmlBody, $name);
        return $result['success'];
    }
    
    // Régi checkout metódus (kompatibilitás)
    public function checkout(): void
    {
        $this->placeOrder();
    }
}
