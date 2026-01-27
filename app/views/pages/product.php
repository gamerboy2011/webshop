<?php
$product = $product ?? null;
?>


<?php
session_start();
require_once __DIR__ . "/../../../library/config.php";

/* =========================
   TERMÉK ID
   ========================= */
$productId = isset($_GET['product']) ? (int)$_GET['product'] : 0;
if ($productId <= 0) {
    die("Érvénytelen termék");
}

/* =========================
   KOSÁR LOGIKA
   ========================= */
if (isset($_POST['add_to_cart'])) {
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    $_SESSION['cart'][] = [
        'product_id' => (int)$_POST['product_id'],
        'size'       => $_POST['size'],
        'quantity'   => 1,
        'price'      => (int)$_POST['price']
    ];

    header("Location: ?product=$productId&added=1");
    exit;
}

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
        p.product_subtype_id,
        v.name AS vendor,
        pt.name AS type,
        ps.name AS subtype,
        g.gender,
        c.name AS color
    FROM product p
    JOIN vendor v ON p.vendor_id = v.vendor_id
    JOIN product_type pt ON p.product_type_id = pt.product_type_id
    JOIN product_subtype ps ON p.product_subtype_id = ps.product_subtype_id
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
    ORDER BY position ASC
");
$stmt->execute(['id' => $productId]);
$images = $stmt->fetchAll();
$mainImage = $images[0]['src'] ?? null;

/* =========================
   MÉRETEK
   ========================= */
if ($product['type'] === 'ruhazat') {
    $stmt = $pdo->prepare("
        SELECT cs.size
        FROM stock s
        JOIN clothe_size cs ON s.clothe_size_id = cs.clothe_size_id
        WHERE s.product_id = :id
        AND s.quantity > 0
        ORDER BY cs.clothe_size_id
    ");
} else {
    $stmt = $pdo->prepare("
        SELECT ss.size
        FROM stock s
        JOIN shoe_size ss ON s.shoe_size_id = ss.shoe_size_id
        WHERE s.product_id = :id
        AND s.quantity > 0
        ORDER BY ss.size
    ");
}
$stmt->execute(['id' => $productId]);
$sizes = $stmt->fetchAll();

/* =========================
   AJÁNLOTT TERMÉKEK
   ========================= */
$stmt = $pdo->prepare("
    SELECT
        p.product_id,
        p.name,
        p.price,
        (
            SELECT src FROM product_img
            WHERE product_id = p.product_id
            ORDER BY position ASC LIMIT 1
        ) AS image
    FROM product p
    WHERE p.product_subtype_id = :subtype
    AND p.product_id != :id
    AND p.is_active = 1
    LIMIT 4
");
$stmt->execute([
    'subtype' => $product['product_subtype_id'],
    'id' => $productId
]);
$related = $stmt->fetchAll();
?>

<div class="max-w-7xl mx-auto px-6 py-16">

    <div class="grid grid-cols-1 md:grid-cols-2 gap-14">

        <!-- KÉPEK -->
        <div>
            <?php if ($mainImage): ?>
                <img id="mainImage"
                     src="<?= htmlspecialchars($mainImage) ?>"
                     class="w-full object-cover mb-6">
            <?php endif; ?>

            <div class="flex gap-3">
                <?php foreach ($images as $img): ?>
                    <img
                        src="<?= htmlspecialchars($img['src']) ?>"
                        class="w-20 h-20 object-cover border cursor-pointer"
                        onclick="changeImage(this.src)"
                    >
                <?php endforeach; ?>
            </div>
        </div>

        <!-- INFO -->
        <div>

            <p class="text-sm uppercase tracking-widest text-gray-500 mb-2">
                <?= htmlspecialchars($product['vendor']) ?>
            </p>

            <h1 class="text-3xl font-semibold mb-4">
                <?= htmlspecialchars($product['name']) ?>
            </h1>

            <?php if ($product['is_sale']): ?>
                <p class="text-2xl font-bold text-red-600">
                    <?= number_format($product['price'], 0, ',', ' ') ?> Ft
                </p>
            <?php else: ?>
                <p class="text-2xl font-bold">
                    <?= number_format($product['price'], 0, ',', ' ') ?> Ft
                </p>
            <?php endif; ?>

            <div class="flex gap-2 mb-8 text-xs uppercase tracking-wide mt-4">
                <span class="border px-2 py-1">
                    <?= $product['gender'] === 'm' ? 'Férfi' : 'Női' ?>
                </span>
                <span class="border px-2 py-1"><?= ucfirst($product['type']) ?></span>
                <span class="border px-2 py-1"><?= ucfirst($product['subtype']) ?></span>
                <span class="border px-2 py-1"><?= $product['color'] ?></span>
            </div>

            <form method="post">
                <input type="hidden" name="product_id" value="<?= $productId ?>">
                <input type="hidden" name="price" value="<?= $product['price'] ?>">

                <p class="font-medium mb-3">Méret</p>
                <div class="flex gap-3 flex-wrap mb-6">
                    <?php foreach ($sizes as $size): ?>
                        <label class="cursor-pointer">
                            <input type="radio" name="size"
                                   value="<?= htmlspecialchars($size['size']) ?>"
                                   class="hidden peer" required>
                            <span class="border px-4 py-2 peer-checked:bg-black peer-checked:text-white">
                                <?= htmlspecialchars($size['size']) ?>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>

                <button
                    type="submit"
                    name="add_to_cart"
                    class="w-full bg-black text-white py-4 uppercase tracking-wider">
                    Kosárba
                </button>
            </form>

            <p class="text-gray-600 leading-relaxed mt-8">
                <?= nl2br(htmlspecialchars($product['description'])) ?>
            </p>

        </div>
    </div>

    <?php if ($related): ?>
    <div class="mt-24">
        <h2 class="text-2xl font-semibold mb-8">You may also like</h2>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <?php foreach ($related as $r): ?>
                <a href="?product=<?= $r['product_id'] ?>" class="block">
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
function changeImage(src) {
    document.getElementById('mainImage').src = src;
}
</script>
