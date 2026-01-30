<h1 class="text-3xl font-bold mb-10 text-center">Kosár</h1>

<?php if (empty($items)): ?>

    <div class="text-center text-2xl text-gray-400 py-24">
        🛒 A kosár üres
    </div>

<?php else: ?>

<div class="max-w-4xl mx-auto space-y-6">

<?php foreach ($items as $item): ?>

    <div class="border p-6 flex gap-6 items-center">

        <!-- KÉP -->
        <?php if ($item['image']): ?>
            <img src="<?= htmlspecialchars($item['image']) ?>"
                 class="w-24 h-24 object-cover border">
        <?php endif; ?>

        <!-- INFO -->
        <div class="flex-1">
            <p class="font-semibold text-lg">
                <a href="index.php?page=product&id=<?= $item['product_id'] ?>"
                   class="hover:underline">
                    <?= htmlspecialchars($item['name']) ?>
                </a>
            </p>

            <p class="text-sm text-gray-500">
                Méret: <strong><?= htmlspecialchars($item['size']) ?></strong>
            </p>

            <p class="font-medium mt-1">
                <?= number_format($item['price'], 0, ',', ' ') ?> Ft
            </p>
        </div>

        <!-- MENNYISÉG + TÖRLÉS -->
        <div class="text-right space-y-2">

            <form method="post" action="index.php?page=cart_update" class="flex gap-2 justify-end">
                <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
                <input type="hidden" name="size_value_id" value="<?= $item['size_value_id'] ?>">

                <input
                    type="number"
                    name="quantity"
                    value="<?= $item['quantity'] ?>"
                    min="1"
                    class="w-16 border text-center"
                >

                <button class="border px-3 py-1">OK</button>
            </form>

            <form method="post" action="index.php?page=cart_remove">
                <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
                <input type="hidden" name="size_value_id" value="<?= $item['size_value_id'] ?>">
                <button class="text-red-600 text-sm">Törlés</button>
            </form>

            <p class="font-bold">
                <?= number_format($item['subtotal'], 0, ',', ' ') ?> Ft
            </p>

        </div>

    </div>

<?php endforeach; ?>

<!-- ÖSSZEG -->
<div class="border-t pt-6 text-right">
    <p class="text-xl">
        Összesen:
        <span class="text-2xl font-bold">
            <?= number_format($total, 0, ',', ' ') ?> Ft
        </span>
    </p>

    <a href="index.php?page=checkout"
       class="inline-block mt-6 bg-black text-white px-8 py-3 uppercase tracking-wider">
        Tovább a fizetéshez
    </a>
</div>

</div>
<?php endif; ?>