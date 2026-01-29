<?php

class CartController
{
    public function add(): void
    {
        session_start();

        $productId = (int)$_POST['product_id'];
        $sizeId    = (int)$_POST['size_id'];

        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        // Ha már van ugyanaz a termék + méret → mennyiség növelés
        foreach ($_SESSION['cart'] as &$item) {
            if ($item['product_id'] === $productId && $item['size_id'] === $sizeId) {
                $item['quantity']++;
                header('Location: index.php?page=cart');
                exit;
            }
        }

        // Új kosártétel
        $_SESSION['cart'][] = [
            'product_id' => $productId,
            'size_id'    => $sizeId,
            'quantity'   => 1
        ];

        header('Location: index.php?page=cart');
        exit;
    }

    public function index(): void
    {
        
        require __DIR__ . '/../views/pages/cart.php';
    }

    public function update(): void
    {
        session_start();

        foreach ($_SESSION['cart'] as &$item) {
            if ($item['product_id'] == $_POST['product_id']
                && $item['size_id'] == $_POST['size_id']) {

                $item['quantity'] = max(1, (int)$_POST['quantity']);
            }
        }

        header('Location: index.php?page=cart');
        exit;
    }

    public function remove(): void
    {
        session_start();

        $_SESSION['cart'] = array_filter(
            $_SESSION['cart'],
            fn($item) =>
                !($item['product_id'] == $_POST['product_id']
                && $item['size_id'] == $_POST['size_id'])
        );

        header('Location: index.php?page=cart');
        exit;
    }
}