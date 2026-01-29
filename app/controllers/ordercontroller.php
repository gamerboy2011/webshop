<?php
class OrderController
{
    public function checkout(): void
    {
        session_start();
        global $pdo;

        if (empty($_SESSION['cart'])) {
            die('A kosár üres.');
        }

        $pdo->beginTransaction();

        try {
            //  Összeg számítás
            $total = 0;

            foreach ($_SESSION['cart'] as $item) {
                $stmt = $pdo->prepare("
                    SELECT price
                    FROM product
                    WHERE product_id = ?
                ");
                $stmt->execute([$item['product_id']]);
                $price = $stmt->fetchColumn();

                $total += $price * $item['quantity'];
            }

            // 2️⃣ ORDER beszúrás
            $stmt = $pdo->prepare("
                INSERT INTO `order` (total_price)
                VALUES (?)
            ");
            $stmt->execute([$total]);

            $orderId = $pdo->lastInsertId();

            // 3️⃣ ORDER_ITEM + STOCK csökkentés
            foreach ($_SESSION['cart'] as $item) {

                // ár újra DB-ből
                $stmt = $pdo->prepare("
                    SELECT price
                    FROM product
                    WHERE product_id = ?
                ");
                $stmt->execute([$item['product_id']]);
                $price = $stmt->fetchColumn();

                // order_item
                $stmt = $pdo->prepare("
                    INSERT INTO order_item
                    (order_id, product_id, size_id, quantity, price)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $orderId,
                    $item['product_id'],
                    $item['size_id'],
                    $item['quantity'],
                    $price
                ]);

                // stock csökkentés
                $stmt = $pdo->prepare("
                    UPDATE stock
                    SET quantity = quantity - ?
                    WHERE product_id = ?
                      AND size_id = ?
                ");
                $stmt->execute([
                    $item['quantity'],
                    $item['product_id'],
                    $item['size_id']
                ]);
            }

            $pdo->commit();

            // 4️⃣ Kosár ürítése
            unset($_SESSION['cart']);

            header("Location: index.php?page=order_success&id=$orderId");
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            die("Hiba történt: " . $e->getMessage());
        }
    }
}