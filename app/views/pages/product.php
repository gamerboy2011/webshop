<?php
require_once __DIR__ . "/../../library/config.php";
require_once __DIR__ . "/../../models/ProductModel.php";

$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($productId <= 0) {
    die("Érvénytelen termék");
}

$model = new ProductModel($pdo);

/* =========================
   TERMÉK ADATOK
   ========================= */
$product = $model->getProductById($productId);
if (!$product) {
    die("A termék nem található");
}

/* =========================
   KÉPEK
   ========================= */
$images = $model->getImages($productId);
$mainImage = $images[0]['src'] ?? null;

/* =========================
   MÉRETEK
   ========================= */
$sizes = $model->getSizes($productId);

/* =========================
   AJÁNLOTT TERMÉKEK
   ========================= */
$related = $model->getRelated($product['subtype_id'], $productId);

/* =========================
   KEDVENC-E
   ========================= */
$isFavorite = false;
if (isset($_SESSION['user_id'])) {
    $isFavorite = $model->isFavorite($_SESSION['user_id'], $productId);
}
?>

<div class="max-w-7xl mx-auto px-6 py-16">

    <!-- BREADCRUMB -->
    <div class="text-sm text-gray-500 mb-8">
        <?= $product['gender'] === 'm' ? 'Férfi' : 'Női' ?>
        / <?= ucfirst($product['type']) ?>
        / <?= ucfirst($product['subtype']) ?>
        / <span class="text-black"><?= htmlspecialchars($product['name']) ?></span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-14">

        <!-- KÉPGALÉRIA -->
        <div>
            <?php if ($mainImage): ?>
                <img id="mainImage"
                     src="<?= htmlspecialchars($mainImage) ?>"
                     class="w-full object-cover mb-6 border">
            <?php endif; ?>

            <div class="flex gap-3">
                <?php foreach ($images as $img): ?>
                    <img
                        src="<?= htmlspecialchars($img['src']) ?>"
                        class="w-20 h-20 object-cover border cursor-pointer thumbnail"
                        onclick="changeImage(this)">
                <?php endforeach; ?>
            </div>
        </div>

        <!-- INFO -->
        <div class="md:sticky md:top-24">

            <p class="text-sm uppercase tracking-widest text-gray-500 mb-2">
                <?= htmlspecialchars($product['vendor']) ?>
            </p>

            <div class="flex items-center justify-between gap-4 mb-4">
                <h1 class="text-3xl font-semibold">
                    <?= htmlspecialchars($product['name']) ?>
                </h1>

                <!-- KEDVENC GOMB -->
                <button
                    class="favorite-btn flex items-center justify-center w-10 h-10 rounded-full border border-gray-300 text-lg transition
                           <?= $isFavorite ? 'text-red-500 border-red-400' : 'text-gray-400 hover:text-gray-600' ?>"
                    data-product="<?= $productId ?>"
                    data-logged="<?= isset($_SESSION['user_id']) ? '1' : '0' ?>"
                    aria-label="Kedvencekhez adás">
                    ♥
                </button>
            </div>

            <p class="text-2xl font-bold mb-6">
                <?= number_format($product['price'], 0, ',', ' ') ?> Ft
            </p>

            <!-- TAGS -->
            <div class="flex gap-2 mb-8 text-xs uppercase tracking-wide">
                <span class="border px-2 py-1"><?= $product['gender'] === 'm' ? 'Férfi' : 'Női' ?></span>
                <span class="border px-2 py-1"><?= ucfirst($product['type']) ?></span>
                <span class="border px-2 py-1"><?= ucfirst($product['subtype']) ?></span>
                <span class="border px-2 py-1"><?= $product['color'] ?></span>
            </div>

            <!-- MÉRET + KOSÁR -->
            <form method="post" action="/webshop/index.php?page=cart_add">

                <input type="hidden" name="product_id" value="<?= $productId ?>">

                <p class="font-medium mb-3">Méret</p>

                <?php if (empty($sizes)): ?>
                    <p class="text-sm text-gray-500">Nincs elérhető méret.</p>
                <?php else: ?>
                    <div class="flex gap-3 flex-wrap mb-6">
                        <?php foreach ($sizes as $size): ?>
                            <label class="cursor-pointer">
                                <input
                                    type="radio"
                                    name="size_id"
                                    value="<?= $size['size_id'] ?>"
                                    class="hidden peer"
                                    required
                                >
                                <span class="border px-4 py-2 peer-checked:bg-black peer-checked:text-white">
                                    <?= htmlspecialchars($size['size_value']) ?>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <button
                    type="submit"
                    class="w-full bg-black text-white py-4 uppercase tracking-wider">
                    Kosárba
                </button>

            </form>

            <p class="text-gray-600 leading-relaxed mt-8">
                <?= nl2br(htmlspecialchars($product['description'])) ?>
            </p>

        </div>
    </div>

    <!-- AJÁNLOTT -->
    <?php if ($related): ?>
        <div class="mt-24">
            <h2 class="text-2xl font-semibold mb-8">You may also like</h2>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <?php foreach ($related as $r): ?>
                    <a href="/webshop/termek/<?= $r['product_id'] ?>">
                        <img src="<?= htmlspecialchars($r['image']) ?>" class="mb-3">
                        <p class="font-medium"><?= htmlspecialchars($r['name']) ?></p>
                        <p class="text-sm"><?= number_format($r['price'], 0, ',', ' ') ?> Ft</p>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

</div>

<script>
function changeImage(el) {
    document.getElementById('mainImage').src = el.src;
    document.querySelectorAll('.thumbnail').forEach(img => {
        img.classList.remove('ring-2', 'ring-black');
    });
    el.classList.add('ring-2', 'ring-black');
}
</script>
