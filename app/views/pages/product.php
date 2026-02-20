<?php
// $pdo és az autoloader már az index.php-ból elérhető

$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($productId <= 0) {
    http_response_code(404);
    require __DIR__ . '/../components/404.php';
    return;
}

$model = new ProductModel($pdo);

/* =========================
   TERMÉK ADATOK
   ========================= */
$product = $model->getProductById($productId);
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
$images = $model->getImages($productId);
$mainImage = $images[0]['src'] ?? null;

/* =========================
   MÉRETEK
   ========================= */
$sizes = $model->getSizes($productId);

// Cipők esetén dinamikus méretek gender alapján
$isShoe = in_array(strtolower($product['type']), ['shoe', 'cipők', 'cipő', 'shoes']);
if ($isShoe) {
    // Méret tartományok gender alapján
    if ($product['gender'] === 'f') {
        // Női: 35-42
        $minSize = 35;
        $maxSize = 42;
    } elseif ($product['gender'] === 'm') {
        // Férfi: 39-47
        $minSize = 39;
        $maxSize = 47;
    } else {
        // Uniszex: teljes tartomány 35-47
        $minSize = 35;
        $maxSize = 47;
    }
    
    // Dinamikus méretek generálása félméretekkel
    $dynamicSizes = [];
    for ($s = $minSize; $s <= $maxSize; $s += 0.5) {
        $sizeValue = ($s == floor($s)) ? (string)(int)$s : number_format($s, 1, '.', '');
        $dynamicSizes[] = [
            'size_id' => $s * 10, // Virtuális ID
            'size_value' => $sizeValue,
            'quantity' => 5 // Alapértelmezett készlet
        ];
    }
    $sizes = $dynamicSizes;
}

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
                                <i class="las la-image text-6xl"></i>
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
                        
                        <!-- Akció badge -->
                        <?php if (!empty($product['is_sale'])): ?>
                            <span class="inline-block bg-red-500 text-white text-sm font-bold px-3 py-1 rounded mb-3">
                                -20% AKCIÓ
                            </span>
                        <?php endif; ?>
                        
                        <!-- Márka -->
                        <a href="#" class="inline-block text-sm font-medium text-gray-500 hover:text-black uppercase tracking-wider mb-2">
                            <?= htmlspecialchars($product['vendor']) ?>
                        </a>

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

                        <!-- Ár -->
                        <div class="flex items-baseline gap-3 mb-6">
                            <?php if (!empty($product['is_sale'])): ?>
                                <span class="text-xl text-gray-400 line-through">
                                    <?= number_format($product['price'], 0, ',', ' ') ?> Ft
                                </span>
                                <span class="text-2xl lg:text-3xl font-bold text-red-600">
                                    <?= number_format($product['sale_price'], 0, ',', ' ') ?> Ft
                                </span>
                            <?php else: ?>
                                <span class="text-2xl lg:text-3xl font-bold text-gray-900">
                                    <?= number_format($product['price'], 0, ',', ' ') ?> Ft
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- Szín -->
                        <div class="mb-6">
                            <p class="text-sm font-medium text-gray-700 mb-2">Szín: <span class="font-normal"><?= htmlspecialchars($product['color']) ?></span></p>
                        </div>

            <!-- MÉRET + KOSÁR -->
            <form method="post" action="/webshop/index.php">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="cart_add">
                <input type="hidden" name="product_id" value="<?= $productId ?>">

                <p class="font-medium mb-3">Méret</p>

                                <?php if (empty($sizes)): ?>
                                    <p class="text-sm text-red-500 bg-red-50 rounded-lg p-3">
                                        <i class="las la-exclamation-circle mr-2"></i>
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

                            <!-- Kosárba gomb -->
                            <button
                                type="submit"
                                <?= empty($sizes) ? 'disabled' : '' ?>
                                class="mt-6 w-full bg-black text-white py-4 px-6 rounded-lg font-medium text-lg
                                       hover:bg-gray-800 transition-colors
                                       disabled:bg-gray-300 disabled:cursor-not-allowed
                                       flex items-center justify-center gap-2">
                                <i class="las la-shopping-bag"></i>
                                Kosárba teszem
                            </button>
                        </form>

                        <!-- Kívánságlista gomb -->
                        <button type="button" 
                                id="wishlistBtn"
                                onclick="toggleWishlist(<?= $productId ?>)"
                                class="wishlist-btn w-full mt-3 border-2 py-3 px-6 rounded-lg font-medium
                                       transition-colors flex items-center justify-center gap-2
                                       <?= $isFavorite ? 'border-red-400 text-red-500 bg-red-50' : 'border-gray-200 text-gray-700 hover:border-gray-400' ?>">
                            <i class="<?= $isFavorite ? 'las' : 'lar' ?> la-heart text-xl"></i>
                            <span><?= $isFavorite ? 'Eltávolítás a kedvencekből' : 'Kívánságlistára' ?></span>
                        </button>

                        <!-- Termékleírás -->
                        <?php if (!empty($product['description'])): ?>
                            <div class="mt-8 pt-8 border-t border-gray-100">
                                <details class="group" open>
                                    <summary class="flex items-center justify-between cursor-pointer list-none">
                                        <h3 class="text-sm font-medium text-gray-900">Termékleírás</h3>
                                        <i class="las la-angle-down text-gray-400 group-open:rotate-180 transition-transform"></i>
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
                                    <i class="las la-angle-down text-gray-400 group-open:rotate-180 transition-transform"></i>
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
                                <i class="las la-truck text-gray-400 mt-0.5"></i>
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

/* ===== KÍVÁNSÁGLISTA TOGGLE ===== */
function toggleWishlist(productId) {
    const isLoggedIn = '<?= isset($_SESSION['user_id']) ? '1' : '0' ?>' === '1';
    
    if (!isLoggedIn) {
        showLoginModal();
        return;
    }
    
    fetch('/webshop/favorite-toggle', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'product_id=' + productId
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const btn = document.getElementById('wishlistBtn');
            const icon = btn.querySelector('i');
            const text = btn.querySelector('span');
            const heartBtn = document.querySelector('.favorite-btn');
            
            // Toggle állapot
            const isNowFavorite = btn.classList.contains('border-gray-200');
            
            if (isNowFavorite) {
                // Hozzáadva
                btn.classList.remove('border-gray-200', 'text-gray-700', 'hover:border-gray-400');
                btn.classList.add('border-red-400', 'text-red-500', 'bg-red-50');
                icon.classList.remove('lar');
                icon.classList.add('las');
                text.textContent = 'Eltávolítás a kedvencekből';
                if (heartBtn) {
                    heartBtn.classList.remove('text-gray-400');
                    heartBtn.classList.add('text-red-500', 'border-red-400');
                }
            } else {
                // Eltávolítva
                btn.classList.remove('border-red-400', 'text-red-500', 'bg-red-50');
                btn.classList.add('border-gray-200', 'text-gray-700', 'hover:border-gray-400');
                icon.classList.remove('las');
                icon.classList.add('lar');
                text.textContent = 'Kívánságlistára';
                if (heartBtn) {
                    heartBtn.classList.remove('text-red-500', 'border-red-400');
                    heartBtn.classList.add('text-gray-400');
                }
            }
        }
    })
    .catch(err => console.error('Hiba:', err));
}

/* ===== SZÍV GOMB KATTINTÁS ===== */
document.querySelector('.favorite-btn')?.addEventListener('click', function() {
    const productId = this.dataset.product;
    toggleWishlist(parseInt(productId));
});
</script>
