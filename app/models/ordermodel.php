<?php

class OrderModel
{
    /* =========================
       KOSÁR ÖSSZEGZÉS (CHECKOUT)
       ========================= */
    public static function getCartSummary(array $cart): array
    {
        global $pdo;

        $items = [];
        $total = 0;

        foreach ($cart as $item) {

            $stmt = $pdo->prepare("
                SELECT
                    p.product_id,
                    p.name,
                    p.price,
                    (
                        SELECT src
                        FROM product_img
                        WHERE product_id = p.product_id
                        ORDER BY position ASC
                        LIMIT 1
                    ) AS image,
                    sv.size_value AS size
                FROM product p
                JOIN size_value sv ON sv.size_value_id = ?
                WHERE p.product_id = ?
            ");

            $stmt->execute([
                $item['size_value_id'],
                $item['product_id']
            ]);

            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $row['quantity']   = (int)$item['quantity'];
                $row['line_total'] = $row['price'] * $item['quantity'];

                $total += $row['line_total'];
                $items[] = $row;
            }
        }

        return [
            'items' => $items,
            'total' => $total
        ];
    }

    /* =========================
       ORDER LÉTREHOZÁS
       ========================= */
    public static function createOrder(array $data, PDO $pdo): int
    {
        $stmt = $pdo->prepare("
            INSERT INTO orders (
                user_id,
                payment_method_id,
                delivery_method_id,
                pickup_point_id,
                billing_city_id,
                billing_street,
                shipping_city_id,
                shipping_street
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $data['user_id'],
            $data['payment_method_id'],
            $data['delivery_method_id'],
            $data['pickup_point_id'],
            $data['billing_city_id'],
            $data['billing_street'],
            $data['shipping_city_id'],
            $data['shipping_street']
        ]);

        return (int)$pdo->lastInsertId();
    }

    /* =========================
       ORDER TÉTEL + KÉSZLET
       ========================= */
    public static function addOrderItem(int $orderId, array $item, PDO $pdo): void
    {
        // ár lekérés DB-ből
        $stmt = $pdo->prepare("
            SELECT price
            FROM product
            WHERE product_id = ?
        ");
        $stmt->execute([$item['product_id']]);
        $price = (float)$stmt->fetchColumn();

        if ($price <= 0) {
            throw new Exception('Érvénytelen termék ár.');
        }

        // order_item beszúrás
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

        // készlet csökkentés
        $stmt = $pdo->prepare("
            UPDATE stock
            SET quantity = quantity - ?
            WHERE product_id = ?
              AND size_value_id = ?
              AND quantity >= ?
        ");
        $stmt->execute([
            $item['quantity'],
            $item['product_id'],
            $item['size_value_id'],
            $item['quantity']
        ]);

        if ($stmt->rowCount() === 0) {
            throw new Exception('Nincs elegendő készlet.');
        }
    }
}