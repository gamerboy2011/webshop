<h1 class="text-3xl font-bold mb-8">Kosár</h1>

<?php if (empty($_SESSION['cart'])): ?>
    <p>A kosár üres.</p>
<?php else: ?>

<?php foreach ($_SESSION['cart'] as $item): ?>

    <div class="border p-6 mb-4 flex justify-between items-center">

        <div>
            <p class="font-semibold">
                Termék ID: <?= $item['product_id'] ?>
            </p>
            <p class="text-sm">
                Méret ID: <?= $item['size_id'] ?>
            </p>
        </div>

        <form method="post" action="index.php?page=cart_update"
              class="flex gap-2 items-center">

            <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
            <input type="hidden" name="size_id" value="<?= $item['size_id'] ?>">

            <input type="number"
                   name="quantity"
                   value="<?= $item['quantity'] ?>"
                   min="1"
                   class="w-16 border text-center">

            <button class="border px-3">OK</button>
        </form>

        <form method="post" action="index.php?page=cart_remove">
            <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
            <input type="hidden" name="size_id" value="<?= $item['size_id'] ?>">
            <button class="text-red-600">Törlés</button>
        </form>

    </div>

<?php endforeach; ?>
<?php endif; ?>