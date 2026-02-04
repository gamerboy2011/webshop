<?php
// BIZTONSÁG
$items = $items ?? [];
$total = 0;

// ÖSSZEG SZÁMÍTÁS
foreach ($items as $item) {
    $total += $item['subtotal'];
}
?>

<h1 class="text-3xl font-bold mb-10 text-center">Kosár</h1>

<?php if (empty($items)): ?>

    <div class="flex justify-center items-center py-32">
        <div class="text-center text-3xl text-gray-400">
            🛒 A kosár üres
        </div>
    </div>

<?php else: ?>

    <div class="max-w-4xl mx-auto space-y-6">

        <?php foreach ($items as $item): ?>

            <div class="border p-6 flex gap-6 items-center">

                <!-- KÉP -->
                <?php if (!empty($item['image'])): ?>
                    <img
                        src="<?= htmlspecialchars($item['image']) ?>"
                        alt="<?= htmlspecialchars($item['name']) ?>"
                        class="w-24 h-24 object-cover border"
                    >
                <?php endif; ?>

                <!-- INFO -->
                <div class="flex-1">
                    <p class="font-semibold text-lg">
                        <a
                            href="index.php?page=product&id=<?= $item['product_id'] ?>"
                            class="hover:underline"
                        >
                            <?= htmlspecialchars($item['name']) ?>
                        </a>
                    </p>

                    <p class="text-sm text-gray-500">
                        Méret:
                        <strong><?= htmlspecialchars($item['size']) ?></strong>
                    </p>

                    <p class="font-medium mt-1">
                        <?= number_format($item['price'], 0, ',', ' ') ?> Ft
                    </p>
                </div>

                <!-- MENNYISÉG / TÖRLÉS -->
                <div class="text-right space-y-3">

                    <!-- MENNYISÉG -->
                    <form
                        method="post"
                        action="index.php?page=cart_update"
                        class="flex gap-2 justify-end items-center"
                    >
                        <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
                        <input type="hidden" name="size_id" value="<?= $item['size_id'] ?>">

                        <input
                            type="number"
                            name="quantity"
                            value="<?= $item['quantity'] ?>"
                            min="1"
                            class="w-16 border text-center"
                        >

                        <button
                            type="submit"
                            class="border px-3 py-1 hover:bg-gray-100"
                        >
                            OK
                        </button>
                    </form>

                    <!-- TÖRLÉS -->
                    <form method="post" action="index.php?page=cart_remove">
                        <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
                        <input type="hidden" name="size_id" value="<?= $item['size_id'] ?>">

                        <button
                            type="submit"
                            class="text-red-600 text-sm hover:underline"
                        >
                            Törlés
                        </button>
                    </form>

                    <!-- RÉSZÖSSZEG -->
                    <p class="font-bold">
                        <?= number_format($item['subtotal'], 0, ',', ' ') ?> Ft
                    </p>

                </div>
            </div>

        <?php endforeach; ?>

        <!-- ÖSSZEG -->
        <div class="border-t pt-6 text-right">

            <p class="text-xl mb-4">
                Összesen:
                <span class="text-2xl font-bold">
                    <?= number_format($total, 0, ',', ' ') ?> Ft
                </span>
            </p>

            <form method="POST" action="index.php?page=checkout">
                <button
                    type="submit"
                    class="inline-block bg-black text-white px-8 py-4 uppercase tracking-wider text-sm hover:bg-gray-900 transition"
                >
                    Tovább a fizetéshez
                </button>
            </form>

        </div>

    </div>

<?php endif; ?>