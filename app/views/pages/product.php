<?php

require_once __DIR__ . "/../../../library/config.php";

/* =========================
   TERMÉK ID
   ========================= */
$productId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($productId <= 0) {
    die("Érvénytelen termék");
}

/* =========================
   KOSÁR LOGIKA
   ========================= */
foreach ($_SESSION['cart'] as &$item) {
    if (
        $item['product_id'] == $productId &&
        $item['size_value_id'] == $sizeValueId
    ) {
        $item['quantity']++;
        header('Location: index.php?page=cart');
        exit;
    }
}

$_SESSION['cart'][] = [
    'product_id'     => $productId,
    'size_value_id'  => $sizeValueId,
    'quantity'       => 1
];

/* =========================
   TERMÉK ADATOK
   ========================= */
$stmt = $pdo->prepare("
    SELECT
        p.product_id,
        p.name,
        p.description,
        p.price,
        p.is_sale,
        p.subtype_id,
        v.name AS vendor,
        pt.name AS type,
        ps.name AS subtype,
        g.gender,
        c.name AS color
    FROM product p
    JOIN vendor v ON p.vendor_id = v.vendor_id
    JOIN product_subtype ps ON p.subtype_id = ps.product_subtype_id
    JOIN product_type pt ON ps.product_type_id = pt.product_type_id
    JOIN gender g ON p.gender_id = g.gender_id
    JOIN color c ON p.color_id = c.color_id
    WHERE p.product_id = :id
      AND p.is_active = 1
");
$stmt->execute(['id' => $productId]);
$product = $stmt->fetch();

if (!$product) {
    die("A termék nem található");
}

/* =========================
   KÉPEK
   ========================= */
$stmt = $pdo->prepare("
    SELECT src
    FROM product_img
    WHERE product_id = :id
    ORDER BY position
");
$stmt->execute(['id' => $productId]);
$images = $stmt->fetchAll();
$mainImage = $images[0]['src'] ?? null;

/* =========================
   MÉRETEK + KÉSZLET
   ========================= */
$stmt = $pdo->prepare("
    SELECT
        sz.size_id,
        sz.size_value,
        sz.size_text,
        st.Quantity
    FROM Stock st
    JOIN size sz ON st.size_id = sz.size_id
    WHERE st.ProductID = :id
      AND st.Quantity > 0
    ORDER BY sz.size_id
");

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
    WHERE p.subtype_id = :subtype
      AND p.product_id != :id
      AND p.is_active = 1
    LIMIT 4
");

$stmt->execute([
    'subtype' => $product['subtype_id'],
    'id' => $productId
]);

$related = $stmt->fetchAll();
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
            <img id="mainImage" src="<?= htmlspecialchars($mainImage) ?>" class="w-full object-cover mb-6 border">

            <div class="flex gap-3">
                <?php foreach ($images as $img): ?>
                    <img src="<?= htmlspecialchars($img['src']) ?>"
                        class="w-20 h-20 object-cover border cursor-pointer thumbnail" onclick="changeImage(this)">
                <?php endforeach; ?>
            </div>
        </div>

        <!-- INFO (STICKY) -->
        <div class="md:sticky md:top-24">

            <p class="text-sm uppercase tracking-widest text-gray-500 mb-2">
                <?= htmlspecialchars($product['vendor']) ?>
            </p>

            <h1 class="text-3xl font-semibold mb-4">
                <?= htmlspecialchars($product['name']) ?>
            </h1>

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

            <!-- MÉRETEK -->
            <!-- TERMÉK ADATOK -->
            <h1><?= htmlspecialchars($product['name']) ?></h1>

            <!-- MÉRETEK – EGYSZER -->
            <form method="post">
                <input type="hidden" name="product_id" value="<?= $productId ?>">

                <p class="font-medium mb-3">Méret</p>

                <?php if (empty($sizes)): ?>
                    <p class="text-sm text-gray-500">Nincs elérhető méret.</p>
                <?php else: ?>
                    <div class="flex gap-3 flex-wrap mb-6">
                        <?php foreach ($sizes as $size): ?>
                            <label class="cursor-pointer">
                                <input type="radio" name="size_value_id" value="<?= $size['size_value_id'] ?>"
                                    class="hidden peer" required>
                                <span class=" border px-4 py-2 peer-checked:bg-black peer-checked:text-white ">
                                    <?= htmlspecialchars($size['size_value']) ?>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
        </div>
        </form>

        <button type="submit" name="add_to_cart" class="w-full bg-black text-white py-4 uppercase tracking-wider">
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
                <a href="?product=<?= $r['product_id'] ?>">
                    <img src="<?= htmlspecialchars($r['image']) ?>" class="mb-3">
                    <p class="font-medium"><?= htmlspecialchars($r['name']) ?></p>
                    <p class="text-sm"><?= number_format($r['price'], 0, ',', ' ') ?> Ft</p>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<!-- KOSÁR -->

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