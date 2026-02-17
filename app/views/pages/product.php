<?php
// $pdo is available from index.php

/* =========================
   TERMÉK ID
   ========================= */
$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($productId <= 0) {
    http_response_code(404);
    require __DIR__ . '/../components/404.php';
    return;
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
    http_response_code(404);
    require __DIR__ . '/../components/404.php';
    return;
}

// Gender megjelenítés
$genderLabels = ['m' => 'Férfi', 'f' => 'Női', 'u' => 'Uniszex'];
$genderLabel = $genderLabels[$product['gender']] ?? 'Uniszex';
$genderUrl = $product['gender'] === 'm' ? 'ferfi' : 'noi';

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
        st.quantity
    FROM stock st
    JOIN size sz ON st.size_id = sz.size_id
    JOIN product p ON st.product_id = p.product_id
    JOIN product_subtype ps ON p.subtype_id = ps.product_subtype_id
    WHERE st.product_id = :id
      AND st.quantity > 0
      AND sz.product_type_id = ps.product_type_id
    ORDER BY sz.size_id
");
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
        v.name AS vendor,
        (
            SELECT src
            FROM product_img
            WHERE product_id = p.product_id
            ORDER BY position ASC
            LIMIT 1
        ) AS image
    FROM product p
    JOIN vendor v ON p.vendor_id = v.vendor_id
    WHERE p.subtype_id = :subtype
      AND p.product_id != :id
      AND p.is_active = 1
    LIMIT 4
");
$stmt->execute([
    'subtype' => $product['subtype_id'],
    'id'      => $productId
]);
$related = $stmt->fetchAll();
?>

<div class="bg-gray-50 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <!-- BREADCRUMB -->
        <nav class="flex items-center gap-2 text-sm mb-6">
            <a href="/webshop/" class="text-gray-500 hover:text-black">Főoldal</a>
            <span class="text-gray-300">/</span>
            <a href="/webshop/<?= $genderUrl ?>" class="text-gray-500 hover:text-black"><?= $genderLabel ?></a>
            <span class="text-gray-300">/</span>
            <a href="/webshop/<?= $genderUrl ?>/<?= strtolower($product['type']) ?>" class="text-gray-500 hover:text-black"><?= htmlspecialchars($product['type']) ?></a>
            <span class="text-gray-300">/</span>
            <span class="text-gray-900"><?= htmlspecialchars($product['name']) ?></span>
        </nav>

        <div class="bg-white rounded-lg shadow-sm">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-0">

                <!-- KÉPGALÉRIA -->
                <div class="p-6 lg:p-8">
                    <!-- Fő kép -->
                    <div class="aspect-[3/4] bg-gray-100 rounded-lg overflow-hidden mb-4">
                        <?php if ($mainImage): ?>
                            <img id="mainImage"
                                 src="/webshop/<?= htmlspecialchars($mainImage) ?>"
                                 alt="<?= htmlspecialchars($product['name']) ?>"
                                 class="w-full h-full object-cover">
                        <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center text-gray-400">
                                <i class="fas fa-image text-6xl"></i>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Thumbnail galéria -->
                    <?php if (count($images) > 1): ?>
                        <div class="flex gap-3 overflow-x-auto pb-2">
                            <?php foreach ($images as $index => $img): ?>
                                <button
                                    type="button"
                                    onclick="changeImage('/webshop/<?= htmlspecialchars($img['src']) ?>', this)"
                                    class="flex-shrink-0 w-20 h-24 rounded-md overflow-hidden border-2 transition-all thumbnail <?= $index === 0 ? 'border-black' : 'border-transparent hover:border-gray-300' ?>">
                                    <img src="/webshop/<?= htmlspecialchars($img['src']) ?>"
                                         alt=""
                                         class="w-full h-full object-cover">
                                </button>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- TERMÉK INFO -->
                <div class="p-6 lg:p-8 lg:border-l border-gray-100">
                    <div class="lg:sticky lg:top-8">
                        
                        <!-- Márka -->
                        <a href="#" class="inline-block text-sm font-medium text-gray-500 hover:text-black uppercase tracking-wider mb-2">
                            <?= htmlspecialchars($product['vendor']) ?>
                        </a>

                        <!-- Terméknév -->
                        <h1 class="text-2xl lg:text-3xl font-bold text-gray-900 mb-4">
                            <?= htmlspecialchars($product['name']) ?>
                        </h1>

                        <!-- Ár -->
                        <div class="flex items-baseline gap-3 mb-6">
                            <span class="text-2xl lg:text-3xl font-bold text-gray-900">
                                <?= number_format($product['price'], 0, ',', ' ') ?> Ft
                            </span>
                        </div>

                        <!-- Szín -->
                        <div class="mb-6">
                            <p class="text-sm font-medium text-gray-700 mb-2">Szín: <span class="font-normal"><?= htmlspecialchars($product['color']) ?></span></p>
                        </div>

                        <!-- MÉRET + KOSÁR FORM -->
                        <form method="post" action="/webshop/index.php">
                            <input type="hidden" name="action" value="cart_add">
                            <input type="hidden" name="product_id" value="<?= $productId ?>">
                            <?= csrf_field() ?>

                            <!-- Méret választó -->
                            <div class="mb-6">
                                <div class="flex items-center justify-between mb-3">
                                    <p class="text-sm font-medium text-gray-700">Méret kiválasztása</p>
                                    <button type="button" class="text-sm text-gray-500 hover:text-black underline">Mérettáblázat</button>
                                </div>

                                <?php if (empty($sizes)): ?>
                                    <p class="text-sm text-red-500 bg-red-50 rounded-lg p-3">
                                        <i class="fas fa-exclamation-circle mr-2"></i>
                                        Jelenleg nincs elérhető méret
                                    </p>
                                <?php else: ?>
                                    <div class="grid grid-cols-4 sm:grid-cols-6 gap-2">
                                        <?php foreach ($sizes as $size): ?>
                                            <label class="relative cursor-pointer">
                                                <input
                                                    type="radio"
                                                    name="size_id"
                                                    value="<?= $size['size_id'] ?>"
                                                    class="peer sr-only"
                                                    required>
                                                <div class="border-2 border-gray-200 rounded-md py-3 text-center text-sm font-medium 
                                                            peer-checked:border-black peer-checked:bg-black peer-checked:text-white
                                                            hover:border-gray-400 transition-all">
                                                    <?= htmlspecialchars($size['size_value']) ?>
                                                </div>
                                                <?php if ($size['quantity'] <= 3): ?>
                                                    <span class="absolute -top-1 -right-1 bg-orange-500 text-white text-xs px-1 rounded">
                                                        <?= $size['quantity'] ?>
                                                    </span>
                                                <?php endif; ?>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Kosárba gomb -->
                            <button
                                type="submit"
                                <?= empty($sizes) ? 'disabled' : '' ?>
                                class="w-full bg-black text-white py-4 px-6 rounded-lg font-medium text-lg
                                       hover:bg-gray-800 transition-colors
                                       disabled:bg-gray-300 disabled:cursor-not-allowed
                                       flex items-center justify-center gap-2">
                                <i class="fas fa-shopping-bag"></i>
                                Kosárba teszem
                            </button>
                        </form>

                        <!-- Kívánságlista gomb -->
                        <button type="button" class="w-full mt-3 border-2 border-gray-200 text-gray-700 py-3 px-6 rounded-lg font-medium
                                       hover:border-gray-400 transition-colors flex items-center justify-center gap-2">
                            <i class="far fa-heart"></i>
                            Kívánságlistára
                        </button>

                        <!-- Termékleírás -->
                        <?php if (!empty($product['description'])): ?>
                            <div class="mt-8 pt-8 border-t border-gray-100">
                                <details class="group" open>
                                    <summary class="flex items-center justify-between cursor-pointer list-none">
                                        <h3 class="text-sm font-medium text-gray-900">Termékleírás</h3>
                                        <i class="fas fa-chevron-down text-gray-400 group-open:rotate-180 transition-transform"></i>
                                    </summary>
                                    <div class="mt-4 text-sm text-gray-600 leading-relaxed">
                                        <?= nl2br(htmlspecialchars($product['description'])) ?>
                                    </div>
                                </details>
                            </div>
                        <?php endif; ?>

                        <!-- Termék részletek -->
                        <div class="mt-4 pt-4 border-t border-gray-100">
                            <details class="group">
                                <summary class="flex items-center justify-between cursor-pointer list-none">
                                    <h3 class="text-sm font-medium text-gray-900">Részletek</h3>
                                    <i class="fas fa-chevron-down text-gray-400 group-open:rotate-180 transition-transform"></i>
                                </summary>
                                <div class="mt-4 text-sm text-gray-600 space-y-2">
                                    <p><span class="font-medium">Márka:</span> <?= htmlspecialchars($product['vendor']) ?></p>
                                    <p><span class="font-medium">Kategória:</span> <?= htmlspecialchars($product['type']) ?> / <?= htmlspecialchars($product['subtype']) ?></p>
                                    <p><span class="font-medium">Szín:</span> <?= htmlspecialchars($product['color']) ?></p>
                                    <p><span class="font-medium">Nem:</span> <?= $genderLabel ?></p>
                                    <p><span class="font-medium">Cikkszám:</span> #<?= $product['product_id'] ?></p>
                                </div>
                            </details>
                        </div>

                        <!-- Szállítás info -->
                        <div class="mt-4 pt-4 border-t border-gray-100">
                            <div class="flex items-start gap-3 text-sm text-gray-600">
                                <i class="fas fa-truck text-gray-400 mt-0.5"></i>
                                <div>
                                    <p class="font-medium text-gray-900">Ingyenes szállítás 15.000 Ft felett</p>
                                    <p>Várható szállítás: 2-4 munkanap</p>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <!-- AJÁNLOTT TERMÉKEK -->
        <?php if (!empty($related)): ?>
            <div class="mt-12">
                <h2 class="text-xl font-bold text-gray-900 mb-6">Hasonló termékek</h2>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 lg:gap-6">
                    <?php foreach ($related as $r): ?>
                        <a href="/webshop/termek/<?= $r['product_id'] ?>" class="group bg-white rounded-lg shadow-sm overflow-hidden hover:shadow-md transition-shadow">
                            <div class="aspect-[3/4] bg-gray-100 overflow-hidden">
                                <?php if (!empty($r['image'])): ?>
                                    <img src="/webshop/<?= htmlspecialchars($r['image']) ?>"
                                         alt="<?= htmlspecialchars($r['name']) ?>"
                                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                <?php endif; ?>
                            </div>
                            <div class="p-4">
                                <p class="text-xs text-gray-500 uppercase tracking-wider mb-1"><?= htmlspecialchars($r['vendor']) ?></p>
                                <h3 class="text-sm font-medium text-gray-900 line-clamp-2 mb-2"><?= htmlspecialchars($r['name']) ?></h3>
                                <p class="text-sm font-bold"><?= number_format($r['price'], 0, ',', ' ') ?> Ft</p>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

    </div>
</div>

<script>
function changeImage(src, btn) {
    document.getElementById('mainImage').src = src;
    document.querySelectorAll('.thumbnail').forEach(el => {
        el.classList.remove('border-black');
        el.classList.add('border-transparent');
    });
    btn.classList.remove('border-transparent');
    btn.classList.add('border-black');
}
</script>
