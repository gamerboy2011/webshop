<?php

class CartController
{
    public function add(): void
    {
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        $productId    = (int)($_POST['product_id'] ?? 0);
        $sizeValueId  = (int)($_POST['size_id'] ?? 0);

        if ($productId <= 0 || $sizeValueId <= 0) {
            die('Hibás kosáradat');
        }

        foreach ($_SESSION['cart'] as &$item) {
            if (
                $item['product_id'] === $productId &&
                $item['size_id'] === $sizeValueId
            ) {
                $item['quantity']++;
                header('Location: index.php?page=cart');
                exit;
            }
        }

        $_SESSION['cart'][] = [
            'product_id'    => $productId,
            'size_id' => $sizeValueId,
            'quantity'      => 1
        ];

        header('Location: index.php?page=cart');
        exit;
    }

    public function index(): void
    {
        global $pdo;

        $cart = $_SESSION['cart'] ?? [];
        $items = [];

        foreach ($cart as $item) {

            /* TERMÉK */
            $stmt = $pdo->prepare("
                SELECT
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
            $product = $stmt->fetch();

            /* MÉRET */
            $stmt = $pdo->prepare("
                SELECT size_value
                FROM size
                WHERE size_id = ?
            ");
            $stmt->execute([$item['size_id']]);
            $size = $stmt->fetchColumn();

            if ($product && $size) {
                $items[] = [
                    'product_id' => $item['product_id'],
                    'name'       => $product['name'],
                    'price'      => $product['price'],
                    'image'      => $product['image'],
                    'size'       => $size,
                    'quantity'   => $item['quantity'],
                    'subtotal'   => $product['price'] * $item['quantity']
                ];
            }
        }

        require __DIR__ . '/../views/pages/cart.php';
    }

    public function remove(): void
    {
        $_SESSION['cart'] = array_filter(
            $_SESSION['cart'],
            fn($i) =>
                !(
                    $i['product_id'] == $_POST['product_id']
                    && $i['size_id'] == $_POST['size_id']
                )
        );

        header('Location: index.php?page=cart');
        exit;
    }
    public function update(): void
{
    if (empty($_SESSION['cart'])) {
        header('Location: index.php?page=cart');
        exit;
    }

    $productId   = (int)($_POST['product_id'] ?? 0);
    $sizeValueId = (int)($_POST['size_id'] ?? 0);
    $quantity    = max(1, (int)($_POST['quantity'] ?? 1));

    foreach ($_SESSION['cart'] as &$item) {
        if (
            $item['product_id'] === $productId &&
            $item['size_id'] === $sizeValueId
        ) {
            $item['quantity'] = $quantity;
            break;
        }
    }

    header('Location: index.php?page=cart');
    exit;
}
}