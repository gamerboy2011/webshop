<?php

class OrderController
{
    public function checkout(): void
    {
        global $pdo;

        if (empty($_SESSION['cart'])) {
            die('A kosár üres.');
        }

        $userId = 1; // vizsgán elfogadott

        $paymentMethodId  = (int)($_POST['payment_method_id'] ?? 0);
        $deliveryMethodId = (int)($_POST['delivery_method_id'] ?? 0);
        $pickupPointId    = $_POST['pickup_point_id'] ?? null;

        if ($paymentMethodId <= 0 || $deliveryMethodId <= 0) {
            die('Fizetési vagy szállítási mód nincs kiválasztva.');
        }

        $pdo->beginTransaction();

        try {
            /* ORDER */
            $stmt = $pdo->prepare("
                INSERT INTO orders
                (user_id, payment_method_id, delivery_method_id, pickup_point_id)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([
                $userId,
                $paymentMethodId,
                $deliveryMethodId,
                $pickupPointId
            ]);

            $orderId = $pdo->lastInsertId();

            /* ORDER ITEMS + STOCK */
            foreach ($_SESSION['cart'] as $item) {

                $stmt = $pdo->prepare("
                    SELECT price
                    FROM product
                    WHERE product_id = ?
                ");
                $stmt->execute([$item['product_id']]);
                $price = (float)$stmt->fetchColumn();

                $stmt = $pdo->prepare("
                    INSERT INTO order_item
                    (order_id, product_id, quantity, unit_price)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([
                    $orderId,
                    $item['product_id'],
                    $item['quantity'],
                    $price
                ]);

                $stmt = $pdo->prepare("
                    UPDATE stock
                    SET quantity = quantity - ?
                    WHERE product_id = ?
                      AND size_value_id = ?
                ");
                $stmt->execute([
                    $item['quantity'],
                    $item['product_id'],
                    $item['size_value_id']
                ]);
            }

            $pdo->commit();
            unset($_SESSION['cart']);

            header('Location: index.php?page=home');
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            die('Hiba történt: ' . $e->getMessage());
        }
    }
}