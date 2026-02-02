<?php

class CartController
{
    private function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /* ===== KOSÁR MEGJELENÍTÉSE ===== */
    public function index(): void
    {
        global $pdo;
        $this->startSession();

        $cart = $_SESSION['cart'] ?? [];
        $items = [];
        $total = 0;

        foreach ($cart as $item) {

            // TERMÉK ADATOK
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
                    ) AS image
                FROM product p
                WHERE p.product_id = ?
            ");
            $stmt->execute([$item['product_id']]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            // MÉRET
            $stmt = $pdo->prepare("
                SELECT size_value
                FROM size_value
                WHERE size_value_id = ?
            ");
            $stmt->execute([$item['size_value_id']]);
            $size = $stmt->fetchColumn();

            if ($product && $size) {
                $subtotal = $product['price'] * $item['quantity'];
                $total += $subtotal;

                $items[] = [
                    'product_id'    => (int)$product['product_id'],
                    'name'          => $product['name'],
                    'price'         => (float)$product['price'],
                    'image'         => $product['image'],
                    'size'          => $size,
                    'size_value_id' => (int)$item['size_value_id'],
                    'quantity'      => (int)$item['quantity'],
                    'subtotal'      => $subtotal
                ];
            }
        }

        require __DIR__ . '/../views/pages/cart.php';
    }

    /* ===== KOSÁRBA TÉTEL ===== */
    public function add(): void
    {
        $this->startSession();

        $_SESSION['cart'] ??= [];

        $productId   = (int)($_POST['product_id'] ?? 0);
        $sizeValueId = (int)($_POST['size_value_id'] ?? 0);

        if ($productId <= 0 || $sizeValueId <= 0) {
            die('Hibás kosáradat');
        }

        foreach ($_SESSION['cart'] as &$item) {
            if (
                $item['product_id'] === $productId &&
                $item['size_value_id'] === $sizeValueId
            ) {
                $item['quantity']++;
                header('Location: index.php?page=cart');
                exit;
            }
        }
        unset($item); // referencia törlése

        $_SESSION['cart'][] = [
            'product_id'    => $productId,
            'size_value_id' => $sizeValueId,
            'quantity'      => 1
        ];

        header('Location: index.php?page=cart');
        exit;
    }

    /* ===== MENNYISÉG FRISSÍTÉS ===== */
    public function update(): void
    {
        $this->startSession();

        $productId   = (int)($_POST['product_id'] ?? 0);
        $sizeValueId = (int)($_POST['size_value_id'] ?? 0);
        $quantity    = max(1, (int)($_POST['quantity'] ?? 1));

        foreach ($_SESSION['cart'] as &$item) {
            if (
                $item['product_id'] === $productId &&
                $item['size_value_id'] === $sizeValueId
            ) {
                $item['quantity'] = $quantity;
                break;
            }
        }
        unset($item);

        header('Location: index.php?page=cart');
        exit;
    }

    /* ===== TÉTEL TÖRLÉS ===== */
    public function remove(): void
    {
        $this->startSession();

        $productId   = (int)($_POST['product_id'] ?? 0);
        $sizeValueId = (int)($_POST['size_value_id'] ?? 0);

        $_SESSION['cart'] = array_values(array_filter(
            $_SESSION['cart'],
            fn($i) =>
                !(
                    $i['product_id'] === $productId &&
                    $i['size_value_id'] === $sizeValueId
                )
        ));

        header('Location: index.php?page=cart');
        exit;
    }
}