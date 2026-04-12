<?php


$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($productId <= 0) {
    http_response_code(404);
    require __DIR__ . '/../components/404.php';
    return;
}

$model = new ProductModel($pdo);




$product = $model->getProductById($productId);
if (!$product) {
    http_response_code(404);
    require __DIR__ . '/../components/404.php';
    return;
}


$genderLabels = ['m' => 'Férfi', 'f' => 'Női', 'u' => 'Uniszex'];
$genderLabel = $genderLabels[$product['gender']] ?? 'Uniszex';
$genderUrl = $product['gender'] === 'm' ? 'ferfi' : 'noi';




$images = $model->getImages($productId);
$mainImage = $images[0]['src'] ?? null;




$sizes = $model->getSizes($productId);




$colorVariants = $model->getColorVariants($productId);

// Lékérdezzük a termék színeit (több szín támogatás)
$productColors = $model->getProductColors($productId);




$related = $model->getRelated($product['subtype_id'], $productId);




$isFavorite = false;
if (isset($_SESSION['user_id'])) {
    $isFavorite = $model->isFavorite($_SESSION['user_id'], $productId);
}
?>

<div class="bg-gray-50 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <?php if (isset($_GET['error'])): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
                <i class="las la-exclamation-circle mr-2"></i>
                <?php
                $errorMessages = [
                    'no_size' => 'Kérlek válassz méretet a kosárba rakás előtt!',
                    'out_of_stock' => 'Sajnáljuk, ez a méret elfogyott.',
                    'invalid_product' => 'Hibás termék.',
                ];
                echo htmlspecialchars($errorMessages[$_GET['error']] ?? 'Ismeretlen hiba történt.');
                ?>
            </div>
        <?php endif; ?>

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
                    <!-- Fő kép nyilakkal -->
                    <div class="relative aspect-[3/4] bg-white rounded-lg overflow-hidden mb-4 flex items-center justify-center border group">
                        <?php if (count($images) > 1): ?>
                            <!-- Bal nyíl -->
                            <button type="button" onclick="prevImage()" 
                                    class="absolute left-2 top-1/2 -translate-y-1/2 w-10 h-10 bg-white/90 hover:bg-white rounded-full shadow-lg flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity z-10">
                                <i class="las la-angle-left text-2xl"></i>
                            </button>
                            <!-- Jobb nyíl -->
                            <button type="button" onclick="nextImage()" 
                                    class="absolute right-2 top-1/2 -translate-y-1/2 w-10 h-10 bg-white/90 hover:bg-white rounded-full shadow-lg flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity z-10">
                                <i class="las la-angle-right text-2xl"></i>
                            </button>
                            <!-- Képszámláló -->
                            <div class="absolute bottom-3 left-1/2 -translate-x-1/2 bg-black/60 text-white text-sm px-3 py-1 rounded-full">
                                <span id="currentImageNum">1</span> / <?= count($images) ?>
                            </div>
                        <?php endif; ?>
                        <img id="mainImage"
                             src="/webshop/<?= $mainImage ? htmlspecialchars($mainImage) : 'public/images/placeholder.svg' ?>"
                             alt="<?= htmlspecialchars($product['name']) ?>"
                             class="max-w-full max-h-full object-contain"
                             onerror="this.onerror=null; this.src='/webshop/public/images/placeholder.svg';">
                    </div>

                    <!-- Thumbnail galéria -->
                    <?php if (count($images) > 1): ?>
                        <div class="flex gap-3 overflow-x-auto pb-2">
                            <?php foreach ($images as $index => $img): ?>
                                <button
                                    type="button"
                                    onclick="changeImage(<?= $index ?>)"
                                    data-index="<?= $index ?>"
                                    class="flex-shrink-0 w-20 h-24 rounded-md overflow-hidden border-2 transition-all thumbnail bg-white flex items-center justify-center <?= $index === 0 ? 'border-black' : 'border-transparent hover:border-gray-300' ?>">
                                    <img src="/webshop/<?= htmlspecialchars($img['src']) ?>"
                                         alt=""
                                         class="max-w-full max-h-full object-contain">
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
                    aria-label="Kívánságlistához adás">
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

                        <!-- Szín és színváltozatok -->
                        <div class="mb-6">
                            <?php
                            $colorHex = [
                                'Fekete' => '#000000', 'Fehér' => '#FFFFFF', 'Piros' => '#EF4444',
                                'Kék' => '#3B82F6', 'Zöld' => '#22C55E', 'Barna' => '#92400E',
                                'Sárga' => '#EAB308', 'Narancssárga' => '#F97316', 'Szürke' => '#6B7280',
                                'Rózsaszín' => '#EC4899', 'Lila' => '#A855F7', 'Bézs' => '#D4C4A8',
                                'Sötétkék' => '#1E3A5F', 'Krém' => '#FFFDD0', 'Drapp' => '#C9B99A',
                                'Acélszürke' => '#48494B', 'Olívazöld' => '#808000', 'Türkíz' => '#14B8A6',
                                'Többszínű' => 'linear-gradient(135deg, #EF4444, #EAB308, #22C55E, #3B82F6, #A855F7)',
                                'Arany' => '#FFD700', 'Ezüst' => '#C0C0C0', 'Bordó' => '#800020', 'Korall' => '#FF7F50', 'Menta' => '#98FF98'
                            ];
                            
                            // Színek megjelenítése
                            $colorNames = array_column($productColors, 'name');
                            $colorDisplay = implode(' / ', $colorNames);
                            ?>
                            <p class="text-sm font-medium text-gray-700 mb-2">Szín: 
                                <span class="font-normal"><?= htmlspecialchars($colorDisplay) ?></span>
                                <?php if (count($productColors) > 1): ?>
                                    <span class="inline-flex ml-2 -space-x-1">
                                        <?php foreach ($productColors as $pc): ?>
                                            <?php $bg = $colorHex[$pc['name']] ?? '#CCCCCC'; ?>
                                            <span class="w-4 h-4 rounded-full border border-white shadow-sm" 
                                                  style="background: <?= $bg ?>"
                                                  title="<?= htmlspecialchars($pc['name']) ?>"></span>
                                        <?php endforeach; ?>
                                    </span>
                                <?php endif; ?>
                            </p>
                            
                            <?php if (!empty($colorVariants)): ?>
                                <div class="flex flex-wrap gap-2 mt-3">
                                    <?php foreach ($colorVariants as $variant): ?>
                                        <?php 
                                        // Lekérdezzük a változat színeit is
                                        $variantColors = $model->getProductColors($variant['product_id']);
                                        ?>
                                        <a href="/webshop/termek/<?= $variant['product_id'] ?>" 
                                           class="w-8 h-8 rounded-full border-2 transition-all overflow-hidden
                                                  <?= $variant['product_id'] == $productId ? 'border-black ring-2 ring-black ring-offset-2' : 'border-gray-300 hover:border-gray-500 hover:scale-110' ?>"
                                           title="<?= htmlspecialchars(implode(' / ', array_column($variantColors, 'name'))) ?>">
                                            <?php if (count($variantColors) > 1): ?>
                                                <!-- Két vagy több szín: megosztott kör -->
                                                <span class="flex w-full h-full">
                                                    <?php foreach ($variantColors as $i => $vc): ?>
                                                        <?php $bg = $colorHex[$vc['name']] ?? '#CCCCCC'; ?>
                                                        <span class="flex-1 h-full" style="background: <?= $bg ?>"></span>
                                                    <?php endforeach; ?>
                                                </span>
                                            <?php else: ?>
                                                <?php $bg = $colorHex[$variantColors[0]['name'] ?? $variant['color']] ?? '#CCCCCC'; ?>
                                                <span class="block w-full h-full" style="background: <?= $bg ?>"></span>
                                            <?php endif; ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
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
                                                    data-stock="<?= $size['quantity'] ?>"
                                                    class="peer sr-only size-radio"
                                                    onchange="updateMaxQty(<?= $size['quantity'] ?>)"
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

                            <!-- Mennyiség -->
                            <div class="mt-6">
                                <p class="font-medium mb-3">Mennyiség <span id="stockInfo" class="text-sm text-gray-500 font-normal"></span></p>
                                <div class="flex items-center gap-3">
                                    <button type="button" onclick="decreaseQty()" 
                                            class="w-10 h-10 border-2 border-gray-200 rounded-lg text-xl font-bold hover:border-gray-400 transition">
                                        -
                                    </button>
                                    <input type="number" name="quantity" id="qtyInput" value="1" min="1" max="10"
                                           class="w-16 h-10 border-2 border-gray-200 rounded-lg text-center font-medium focus:border-black focus:outline-none">
                                    <button type="button" onclick="increaseQty()" 
                                            class="w-10 h-10 border-2 border-gray-200 rounded-lg text-xl font-bold hover:border-gray-400 transition">
                                        +
                                    </button>
                                </div>
                                <p id="lowStockWarning" class="hidden text-sm text-orange-600 mt-2">
                                    <i class="las la-exclamation-triangle"></i> <span></span>
                                </p>
                            </div>

                            <!-- Kosárba gomb -->
                            <button
                                type="submit"
                                <?= empty($sizes) ? 'disabled' : '' ?>
                                class="mt-4 w-full bg-black text-white py-4 px-6 rounded-lg font-medium text-lg
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
                            <span><?= $isFavorite ? 'Eltávolítás a kívánságlistáról' : 'Kívánságlistára' ?></span>
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

            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <?php foreach ($related as $r): ?>
                    <a href="/webshop/termek/<?= $r['product_id'] ?>" class="group bg-white rounded-lg shadow-sm hover:shadow-lg transition-shadow overflow-hidden block">
                        <div class="aspect-[3/4] bg-white overflow-hidden flex items-center justify-center border-b">
                            <?php if (!empty($r['image'])): ?>
                                <img src="/webshop/<?= htmlspecialchars($r['image']) ?>" 
                                     alt="<?= htmlspecialchars($r['name']) ?>"
                                     class="max-w-full max-h-full object-contain group-hover:scale-105 transition-transform duration-300"
                                     loading="lazy">
                            <?php else: ?>
                                <div class="w-full h-full flex items-center justify-center text-gray-400">
                                    <i class="las la-image text-4xl"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="p-3">
                            <p class="font-medium text-sm line-clamp-2 group-hover:text-gray-600 transition-colors"><?= htmlspecialchars($r['name']) ?></p>
                            <p class="text-sm font-bold mt-1"><?= number_format($r['price'], 0, ',', ' ') ?> Ft</p>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    </div>
</div>

<script>
// Képek tömbje
const productImages = [
    <?php foreach ($images as $img): ?>
        '/webshop/<?= htmlspecialchars($img['src']) ?>',
    <?php endforeach; ?>
];
let currentImageIndex = 0;

function changeImage(index) {
    if (index < 0 || index >= productImages.length) return;
    currentImageIndex = index;
    document.getElementById('mainImage').src = productImages[index];
    
    // Thumbnail kiemelés
    document.querySelectorAll('.thumbnail').forEach((el, i) => {
        if (i === index) {
            el.classList.remove('border-transparent');
            el.classList.add('border-black');
        } else {
            el.classList.remove('border-black');
            el.classList.add('border-transparent');
        }
    });
    
    // Számláló frissítése
    const counter = document.getElementById('currentImageNum');
    if (counter) counter.textContent = index + 1;
}

function prevImage() {
    let newIndex = currentImageIndex - 1;
    if (newIndex < 0) newIndex = productImages.length - 1;
    changeImage(newIndex);
}

function nextImage() {
    let newIndex = currentImageIndex + 1;
    if (newIndex >= productImages.length) newIndex = 0;
    changeImage(newIndex);
}

// Billentyűzet navigáció
document.addEventListener('keydown', function(e) {
    if (e.key === 'ArrowLeft') prevImage();
    if (e.key === 'ArrowRight') nextImage();
});

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
                text.textContent = 'Eltávolítás a kívánságlistáról';
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

/* ===== MENNYISÉG KEZELÉS ===== */
let currentMaxQty = 10; // Alapértelmezett max

function updateMaxQty(stock) {
    const input = document.getElementById('qtyInput');
    const stockInfo = document.getElementById('stockInfo');
    const warning = document.getElementById('lowStockWarning');
    
    currentMaxQty = Math.min(stock, 10); // Max 10 vagy a készlet
    input.max = currentMaxQty;
    
    // Ha a jelenlegi érték nagyobb mint az új max, csökkentjük
    if (parseInt(input.value) > currentMaxQty) {
        input.value = currentMaxQty;
    }
    
    // Készlet info megjelenítése
    if (stock <= 5) {
        stockInfo.textContent = `(${stock} db elérhető)`;
        warning.classList.remove('hidden');
        warning.querySelector('span').textContent = `Csak ${stock} db maradt raktáron!`;
    } else {
        stockInfo.textContent = '';
        warning.classList.add('hidden');
    }
}

function decreaseQty() {
    const input = document.getElementById('qtyInput');
    let val = parseInt(input.value) || 1;
    if (val > 1) input.value = val - 1;
}

function increaseQty() {
    const input = document.getElementById('qtyInput');
    let val = parseInt(input.value) || 1;
    if (val < currentMaxQty) {
        input.value = val + 1;
    }
}

// Manuális beírás korlátozása
document.getElementById('qtyInput')?.addEventListener('change', function() {
    let val = parseInt(this.value) || 1;
    if (val < 1) this.value = 1;
    if (val > currentMaxQty) this.value = currentMaxQty;
});

/* ===== MAGYAR VALIDÁCIÓS ÜZENET ===== */
document.querySelectorAll('input[name="size_id"]').forEach(input => {
    input.addEventListener('invalid', function() {
        this.setCustomValidity('Kérlek válassz méretet!');
    });
});

// Amikor bármelyik méretet kiválasztják, az összes radio gomb custom validity-jét töröljük
document.querySelectorAll('input[name="size_id"]').forEach(input => {
    input.addEventListener('change', function() {
        // Töröljük az összes radio gomb custom validity-jét
        document.querySelectorAll('input[name="size_id"]').forEach(radio => {
            radio.setCustomValidity('');
        });
    });
});

/* ===== FORM SUBMIT VALIDÁCIÓ ===== */
const cartForm = document.querySelector('form[action="/webshop/index.php"]');
if (cartForm) {
    cartForm.addEventListener('submit', function(e) {
        const selectedSize = document.querySelector('input[name="size_id"]:checked');
        if (!selectedSize) {
            e.preventDefault();
            alert('Kérlek válassz méretet!');
            // Scroll to size section
            document.querySelector('p.font-medium')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return false;
        }
        
        const quantity = parseInt(document.getElementById('qtyInput')?.value) || 1;
        if (quantity < 1) {
            e.preventDefault();
            alert('Érvénytelen mennyiség!');
            return false;
        }
        
        // Minden rendben, engedjük a submitot
        return true;
    });
}
</script>
