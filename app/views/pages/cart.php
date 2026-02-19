<?php
// Kosár elemek betöltése session-ből
$cart = $_SESSION['cart'] ?? [];
$items = [];
$total = 0;

foreach ($cart as $cartItem) {
    // Termék adatok lekérése
    $stmt = $pdo->prepare("
        SELECT
            p.product_id,
            p.name,
            p.price,
            (SELECT src FROM product_img WHERE product_id = p.product_id ORDER BY position LIMIT 1) AS image
        FROM product p
        WHERE p.product_id = ?
    ");
    $stmt->execute([$cartItem['product_id']]);
    $product = $stmt->fetch();
    
    if (!$product) continue;
    
    // Méret lekérése
    $stmt = $pdo->prepare("SELECT size_value FROM size WHERE size_id = ?");
    $stmt->execute([$cartItem['size_id']]);
    $sizeValue = $stmt->fetchColumn() ?: '-';
    
    $subtotal = $product['price'] * $cartItem['quantity'];
    $total += $subtotal;
    
    $items[] = [
        'product_id' => $cartItem['product_id'],
        'size_id' => $cartItem['size_id'],
        'name' => $product['name'],
        'price' => $product['price'],
        'image' => $product['image'],
        'size' => $sizeValue,
        'quantity' => $cartItem['quantity'],
        'subtotal' => $subtotal
    ];
}
?>

<div class="max-w-4xl mx-auto px-4 py-12">
    <h1 class="text-3xl font-bold mb-8">
        <i class="las la-shopping-bag mr-3"></i>Kosár
    </h1>

    <?php if (empty($items)): ?>
        <div class="bg-gray-50 rounded-lg p-12 text-center">
            <i class="las la-shopping-cart text-gray-300 text-6xl mb-4"></i>
            <p class="text-gray-500 text-xl mb-6">A kosarad üres</p>
            <a href="/webshop/" class="inline-block bg-black text-white px-6 py-3 rounded-lg hover:bg-gray-800 transition">
                Vásárlás folytatása
            </a>
        </div>
    <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($items as $item): ?>
                <div class="bg-white border rounded-lg p-4 flex gap-4 items-center">
                    <!-- KÉP -->
                    <a href="/webshop/termek/<?= $item['product_id'] ?>" class="flex-shrink-0">
                        <?php if (!empty($item['image'])): ?>
                            <img src="/webshop/<?= htmlspecialchars($item['image']) ?>"
                                 alt="<?= htmlspecialchars($item['name']) ?>"
                                 class="w-24 h-24 object-cover rounded-lg">
                        <?php else: ?>
                            <div class="w-24 h-24 bg-gray-100 rounded-lg flex items-center justify-center">
                                <i class="las la-image text-gray-400"></i>
                            </div>
                        <?php endif; ?>
                    </a>

                    <!-- INFO -->
                    <div class="flex-1 min-w-0">
                        <a href="/webshop/termek/<?= $item['product_id'] ?>" class="font-semibold text-gray-900 hover:text-gray-600">
                            <?= htmlspecialchars($item['name']) ?>
                        </a>
                        <p class="text-sm text-gray-500 mt-1">
                            Méret: <span class="font-medium"><?= htmlspecialchars($item['size']) ?></span>
                        </p>
                        <p class="font-medium mt-1">
                            <?= number_format($item['price'], 0, ',', ' ') ?> Ft
                        </p>
                    </div>

                    <!-- MENNYISÉG -->
                    <div class="flex items-center gap-2">
                        <form method="post" action="/webshop/index.php" class="inline">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="cart_update">
                            <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
                            <input type="hidden" name="size_id" value="<?= $item['size_id'] ?>">
                            <input type="hidden" name="quantity" value="<?= max(1, $item['quantity'] - 1) ?>">
                            <button type="submit" class="w-8 h-8 border rounded-lg hover:bg-gray-100 transition"
                                    <?= $item['quantity'] <= 1 ? 'disabled' : '' ?>>
                                <i class="las la-minus text-xs"></i>
                            </button>
                        </form>
                        
                        <span class="w-10 text-center font-medium"><?= $item['quantity'] ?></span>
                        
                        <form method="post" action="/webshop/index.php" class="inline">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="cart_update">
                            <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
                            <input type="hidden" name="size_id" value="<?= $item['size_id'] ?>">
                            <input type="hidden" name="quantity" value="<?= $item['quantity'] + 1 ?>">
                            <button type="submit" class="w-8 h-8 border rounded-lg hover:bg-gray-100 transition">
                                <i class="las la-plus text-xs"></i>
                            </button>
                        </form>
                    </div>

                    <!-- RÉSZÖSSZEG -->
                    <div class="text-right w-28">
                        <p class="font-bold text-lg">
                            <?= number_format($item['subtotal'], 0, ',', ' ') ?> Ft
                        </p>
                        
                        <!-- TÖRLÉS -->
                        <form method="post" action="/webshop/index.php" class="mt-2">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="cart_remove">
                            <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
                            <input type="hidden" name="size_id" value="<?= $item['size_id'] ?>">
                            <button type="submit" class="text-red-500 text-sm hover:text-red-700 transition">
                                <i class="las la-trash-alt mr-1"></i>Törlés
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- ÖSSZEGZÉS -->
        <div class="mt-8 bg-gray-50 rounded-lg p-6">
            <div class="flex justify-between items-center mb-4">
                <span class="text-gray-600">Részösszeg:</span>
                <span class="font-medium"><?= number_format($total, 0, ',', ' ') ?> Ft</span>
            </div>
            <div class="flex justify-between items-center mb-4">
                <span class="text-gray-600">Szállítás:</span>
                <span class="font-medium"><?= $total >= 15000 ? 'Ingyenes' : '1 490 Ft' ?></span>
            </div>
            <div class="border-t pt-4 flex justify-between items-center">
                <span class="text-xl font-bold">Összesen:</span>
                <span class="text-2xl font-bold">
                    <?= number_format($total >= 15000 ? $total : $total + 1490, 0, ',', ' ') ?> Ft
                </span>
            </div>
            
            <form method="post" action="/webshop/index.php" class="mt-6">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="checkout">
                <button type="submit" class="w-full bg-black text-white py-4 rounded-lg font-medium text-lg hover:bg-gray-800 transition flex items-center justify-center gap-2">
                    <i class="las la-lock"></i>
                    Tovább a fizetéshez
                </button>
            </form>
            
            <a href="/webshop/" class="block text-center mt-4 text-gray-600 hover:text-black transition">
                <i class="las la-arrow-left mr-2"></i>Vásárlás folytatása
            </a>
        </div>
    <?php endif; ?>
</div>
