<?php

class CartController
{
    public function add(): void
    {
        global $pdo;
        
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        $productId    = (int)($_POST['product_id'] ?? 0);
        $sizeValueId  = (int)($_POST['size_id'] ?? 0);
        $quantity     = max(1, min(10, (int)($_POST['quantity'] ?? 1))); 

        if ($productId <= 0) {
            header('Location: /webshop/?error=invalid_product');
            exit;
        }
        
        if ($sizeValueId <= 0) {
            header('Location: /webshop/termek/' . $productId . '?error=no_size');
            exit;
        }

        
        $stmt = $pdo->prepare("SELECT quantity FROM stock WHERE product_id = ? AND size_id = ?");
        $stmt->execute([$productId, $sizeValueId]);
        $stockResult = $stmt->fetchColumn();
        
        
        $stockQty = ($stockResult !== false) ? (int)$stockResult : 10;
        
        if ($stockQty <= 0) {
            header('Location: /webshop/termek/' . $productId . '?error=out_of_stock');
            exit;
        }
        
        
        $inCartQty = 0;
        foreach ($_SESSION['cart'] as $item) {
            if ($item['product_id'] === $productId && $item['size_id'] === $sizeValueId) {
                $inCartQty = $item['quantity'];
                break;
            }
        }
        
        
        $maxCanAdd = $stockQty - $inCartQty;
        if ($maxCanAdd <= 0) {
            header('Location: /webshop/kosar?error=out_of_stock');
            exit;
        }
        
        
        $quantity = min($quantity, $maxCanAdd);

        
        foreach ($_SESSION['cart'] as &$item) {
            if (
                $item['product_id'] === $productId &&
                $item['size_id'] === $sizeValueId
            ) {
                $item['quantity'] += $quantity;
                header('Location: /webshop/kosar');
                exit;
            }
        }

        
        $_SESSION['cart'][] = [
            'product_id'    => $productId,
            'size_id' => $sizeValueId,
            'quantity'      => $quantity
        ];

        header('Location: /webshop/kosar');
        exit;
    }

    public function index()
    {
        global $pdo;

        $cart = $_SESSION['cart'] ?? [];
        $items = [];
        $total = 0;

        foreach ($cart as $item) {

            
            $stmt = $pdo->prepare("
            SELECT
                p.name,
                p.price,
                p.is_sale,
                ROUND(p.price * 0.8) AS sale_price,
                (
                    SELECT src
                    FROM product_img
                    WHERE product_id = p.product_id
                    ORDER BY position
                    LIMIT 1
                ) AS image
            FROM product p
            WHERE p.product_id = ?
        ");
            $stmt->execute([$item['product_id']]);
            $product = $stmt->fetch();

            if (!$product) {
                continue;
            }

            
            $stmt = $pdo->prepare("
            SELECT size_value
            FROM size
            WHERE size_id = ?
            LIMIT 1
        ");
            $stmt->execute([$item['size_id']]);
            $sizeValue = $stmt->fetchColumn() ?: '–';

            // Akciós ár használata ha akciós a termék
            $actualPrice = $product['is_sale'] ? $product['sale_price'] : $product['price'];
            $subtotal = $actualPrice * $item['quantity'];
            $total += $subtotal;

            $items[] = [
                'product_id' => $item['product_id'],
                'size_id'    => $item['size_id'],
                'name'       => $product['name'],
                'price'      => $product['price'],
                'sale_price' => $product['sale_price'],
                'is_sale'    => $product['is_sale'],
                'image'      => $product['image'],
                'size'       => $sizeValue,
                'quantity'   => $item['quantity'],
                'subtotal'   => $subtotal
            ];
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

        header('Location: /webshop/kosar');
        exit;
    }
    
    public function update()
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

        header('Location: /webshop/kosar');
        exit;
    }

    public function clear(): void
    {
        $_SESSION['cart'] = [];
        header('Location: /webshop/kosar');
        exit;
    }
}
