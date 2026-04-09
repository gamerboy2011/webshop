<?php

if (!isset($products) || !is_array($products)) {
    $products = [];
}
?>

<!-- HERO -->
<?php
$showHero =
    empty($_GET['gender']) &&
    empty($_GET['type']) &&
    empty($_GET['sale']) &&
    empty($_GET['new']);

if ($showHero) {
    include __DIR__ . "/hero.php";
}
?>

<!-- KIEMELT TERMÉKEK -->
<section id="products" class="py-24 bg-white">
    <div class="max-w-7xl mx-auto px-6">

        <h2 class="text-3xl font-bold text-center mb-16">
            Kiemelt termékek
        </h2>

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-6">

            <?php if (empty($products)): ?>
                <p class="col-span-3 text-center text-gray-500">
                    Nincs találat.
                </p>
            <?php endif; ?>

            <?php foreach ($products as $product): ?>
                <div class="group bg-white rounded-lg shadow-sm hover:shadow-lg transition-shadow overflow-hidden relative">
                    
                    <!-- SZÍV GOMB -->
                    <?php $isFav = in_array($product['product_id'], $userFavoriteIds ?? []); ?>
                    <button type="button" 
                            onclick="toggleFavorite(<?= $product['product_id'] ?>, this, event)"
                            class="favorite-heart absolute top-2 right-2 z-20 w-8 h-8 <?= $isFav ? 'bg-red-50' : 'bg-white/80' ?> backdrop-blur rounded-full shadow flex items-center justify-center transition hover:scale-110">
                        <i class="<?= $isFav ? 'las la-heart text-lg text-red-500' : 'lar la-heart text-lg text-gray-400' ?> hover:text-red-500 transition"></i>
                    </button>
                    
                    <a href="/webshop/termek/<?= (int)$product['product_id'] ?>" class="block">
                        <div class="aspect-square bg-white overflow-hidden relative flex items-center justify-center border-b">
                            <?php if (!empty($product['is_sale'])): ?>
                                <span class="absolute top-2 left-2 bg-red-500 text-white text-xs font-bold px-2 py-1 rounded z-10">
                                    -20%
                                </span>
                            <?php endif; ?>
                            <img src="/webshop/<?= !empty($product['image']) ? htmlspecialchars($product['image']) : 'public/images/placeholder.svg' ?>" 
                                 alt="<?= htmlspecialchars($product['name']) ?>"
                                 class="max-w-full max-h-full object-contain group-hover:scale-105 transition-transform duration-300"
                                 onerror="this.onerror=null; this.src='/webshop/public/images/placeholder.svg';"
                                 loading="lazy">
                        </div>

                        <div class="p-4">
                            <h3 class="text-lg font-semibold text-gray-900 group-hover:text-gray-600 transition-colors line-clamp-2">
                                <?= htmlspecialchars($product['name']) ?>
                            </h3>
                            
                            <!-- Színváltozatok -->
                            <?php if (!empty($product['variants'])): ?>
                                <?php
                                $colorHex = [
                                    'Fekete' => '#000000', 'Fehér' => '#FFFFFF', 'Piros' => '#EF4444',
                                    'Kék' => '#3B82F6', 'Zöld' => '#22C55E', 'Barna' => '#92400E',
                                    'Sárga' => '#EAB308', 'Narancssárga' => '#F97316', 'Szürke' => '#6B7280',
                                    'Rózsaszín' => '#EC4899', 'Lila' => '#A855F7', 'Bézs' => '#D4C4A8',
                                    'Sötétkék' => '#1E3A5F', 'Krém' => '#FFFDD0', 'Drapp' => '#C9B99A',
                                    'Acélszürke' => '#48494B', 'Olívazöld' => '#808000', 'Türkiz' => '#14B8A6',
                                    'Többszínű' => 'linear-gradient(135deg, #EF4444, #EAB308, #22C55E, #3B82F6)',
                                    'Arany' => '#FFD700', 'Ezüst' => '#C0C0C0', 'Bordó' => '#800020', 'Korall' => '#FF7F50', 'Menta' => '#98FF98'
                                ];
                                ?>
                                <div class="flex items-center gap-1 mt-2">
                                    <?php foreach (array_slice($product['variants'], 0, 5) as $variant): ?>
                                        <?php $bg = $colorHex[$variant['color']] ?? '#CCCCCC'; ?>
                                        <a href="/webshop/termek/<?= $variant['product_id'] ?>" 
                                           onclick="event.stopPropagation();"
                                           class="w-5 h-5 rounded-full border border-gray-300 hover:scale-110 transition-all"
                                           style="background: <?= $bg ?>;"
                                           title="<?= htmlspecialchars($variant['color']) ?>"></a>
                                    <?php endforeach; ?>
                                    <?php if (count($product['variants']) > 5): ?>
                                        <span class="text-xs text-gray-500">+<?= count($product['variants']) - 5 ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($product['is_sale'])): ?>
                                <div class="mt-2">
                                    <span class="text-gray-400 line-through text-sm">
                                        <?= number_format($product['price'], 0, ',', ' ') ?> Ft
                                    </span>
                                    <span class="text-red-600 font-bold ml-2">
                                        <?= number_format($product['sale_price'], 0, ',', ' ') ?> Ft
                                    </span>
                                </div>
                            <?php else: ?>
                                <p class="font-bold text-lg mt-2">
                                    <?= number_format($product['price'], 0, ',', ' ') ?> Ft
                                </p>
                            <?php endif; ?>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>

        </div>
    </div>
</section>

<!-- VÁSÁRLÓI VÉLEMÉNYEK -->
<?php
$reviewsStmt = $pdo->query("
    SELECT r.rating, r.comment, r.created_at, u.username
    FROM order_ratings r
    JOIN orders o ON r.order_id = o.order_id
    JOIN users u ON o.user_id = u.user_id
    WHERE r.comment IS NOT NULL AND r.comment != ''
    ORDER BY r.created_at DESC
    LIMIT 4
");
$homeReviews = $reviewsStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php if (!empty($homeReviews)): ?>
<section id="reviews" class="py-12 bg-gray-50">
    <div class="max-w-7xl mx-auto px-6">
        <div class="text-center mb-10">
            <h2 class="text-3xl font-bold text-gray-900 mb-2">Vásárlóink mondják</h2>
            <p class="text-gray-500">Valódi értékelések elégedett vásárlóinktól</p>
        </div>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php foreach ($homeReviews as $review): ?>
                <div class="bg-white rounded-xl p-5 shadow-sm hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between mb-3">
                        <div class="text-yellow-500 text-lg">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <?= $i <= $review['rating'] ? '★' : '☆' ?>
                            <?php endfor; ?>
                        </div>
                        <span class="text-xs text-gray-400"><?= date('Y.m.d', strtotime($review['created_at'])) ?></span>
                    </div>
                    <p class="text-gray-700 text-sm italic line-clamp-3">"<?= htmlspecialchars($review['comment']) ?>"</p>
                    <p class="text-xs text-gray-500 mt-3 font-medium">— <?= htmlspecialchars($review['username']) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- GY.I.K. -->
<section id="gyik" class="py-6 bg-white">
    <div class="max-w-4xl mx-auto px-6">
        
        <details class="faq-main group">
            <summary class="flex items-center justify-center gap-3 cursor-pointer list-none py-4">
                <i class="las la-question-circle text-3xl text-gray-400"></i>
                <span class="text-xl font-semibold text-gray-700 group-hover:text-black transition">Gyakran Ismételt Kérdések</span>
                <i class="las la-angle-down text-xl text-gray-400 faq-main-icon transition-transform"></i>
            </summary>
            
            <div class="mt-8 space-y-4">
            
            <!-- Kérdés 1 -->
            <details class="bg-white rounded-xl shadow-sm faq-item">
                <summary class="flex items-center justify-between p-6 cursor-pointer list-none">
                    <span class="font-semibold text-lg pr-4">Mennyi idő alatt érkezik meg a rendelésem?</span>
                    <i class="las la-plus text-2xl text-gray-400 faq-icon transition-transform"></i>
                </summary>
                <div class="px-6 pb-6 text-gray-600 leading-relaxed">
                    <p>A rendelések általában <strong>2-4 munkanapon</strong> belül megérkeznek. 
                    GLS futárszolgálattal szállítunk, így munkanapokon 8:00-17:00 között várható a kézbesítés. 
                    FoxPost csomagpontra rendelés esetén e-mailben értesítünk, amint a csomag átvehető.</p>
                </div>
            </details>
            
            <!-- Kérdés 2 -->
            <details class="bg-white rounded-xl shadow-sm faq-item">
                <summary class="flex items-center justify-between p-6 cursor-pointer list-none">
                    <span class="font-semibold text-lg pr-4">Hogyan tudom visszaküldeni a terméket?</span>
                    <i class="las la-plus text-2xl text-gray-400 faq-icon transition-transform"></i>
                </summary>
                <div class="px-6 pb-6 text-gray-600 leading-relaxed">
                    <p>A vásárlástól számított <strong>14 napon belül</strong> indoklás nélkül visszaküldheted a terméket. 
                    A fiókodban a "Rendeléseim" menüpont alatt találod a "Visszaküldés kérése" gombot. 
                    A visszaküldés díjmentes, mi állunk a szállítási költséget. A pénzt 5 munkanapon belül 
                    visszautaljuk az eredeti fizetési módra.</p>
                </div>
            </details>
            
            <!-- Kérdés 3 -->
            <details class="bg-white rounded-xl shadow-sm faq-item">
                <summary class="flex items-center justify-between p-6 cursor-pointer list-none">
                    <span class="font-semibold text-lg pr-4">Milyen fizetési módokat fogadtok el?</span>
                    <i class="las la-plus text-2xl text-gray-400 faq-icon transition-transform"></i>
                </summary>
                <div class="px-6 pb-6 text-gray-600 leading-relaxed">
                    <p>Többféle fizetési módot elfogadunk:</p>
                    <ul class="list-disc list-inside mt-2 space-y-1">
                        <li><strong>Banki átutalás</strong> - előre utalással</li>
                        <li><strong>Online bankkártyás fizetés</strong> - Swiper biztonságos fizetési rendszeren keresztül</li>
                        <li><strong>Utánvét</strong> - fizetés a futárnál készpénzzel vagy kártyával (+490 Ft)</li>
                    </ul>
                </div>
            </details>
            
            <!-- Kérdés 4 -->
            <details class="bg-white rounded-xl shadow-sm faq-item">
                <summary class="flex items-center justify-between p-6 cursor-pointer list-none">
                    <span class="font-semibold text-lg pr-4">Eredeti termékeket árultok?</span>
                    <i class="las la-plus text-2xl text-gray-400 faq-icon transition-transform"></i>
                </summary>
                <div class="px-6 pb-6 text-gray-600 leading-relaxed">
                    <p><strong>Igen, kizárólag 100% eredeti termékeket értékesítünk.</strong> 
                    Minden termék hivatalos forgalmazóktól származik és eredeti címkével, dobozzal érkezik. 
                    Ha bármilyen kétséged van a termék eredetiségével kapcsolatban, írj nekünk és segítünk!</p>
                </div>
            </details>
            
            <!-- Kérdés 5 -->
            <details class="bg-white rounded-xl shadow-sm faq-item">
                <summary class="flex items-center justify-between p-6 cursor-pointer list-none">
                    <span class="font-semibold text-lg pr-4">Hogyan választom ki a megfelelő méretet?</span>
                    <i class="las la-plus text-2xl text-gray-400 faq-icon transition-transform"></i>
                </summary>
                <div class="px-6 pb-6 text-gray-600 leading-relaxed">
                    <p>Minden termékoldalon találsz mérettáblázatot, ami segít a választásban. 
                    <strong>Tipp:</strong> Ha két méret között vagy, válaszd a nagyobbat! 
                    Ha mégsem jó a méret, 14 napon belül ingyenesen cserélheted.</p>
                </div>
            </details>
            
            <!-- Kérdés 6 -->
            <details class="bg-white rounded-xl shadow-sm faq-item">
                <summary class="flex items-center justify-between p-6 cursor-pointer list-none">
                    <span class="font-semibold text-lg pr-4">Mikor ingyenes a szállítás?</span>
                    <i class="las la-plus text-2xl text-gray-400 faq-icon transition-transform"></i>
                </summary>
                <div class="px-6 pb-6 text-gray-600 leading-relaxed">
                    <p><strong>15 000 Ft feletti rendelés esetén a szállítás ingyenes!</strong> 
                    Ez vonatkozik mind a házhoz szállításra, mind a FoxPost csomagpontra kért rendelésekre. 
                    15 000 Ft alatt a szállítási díj 1 490 Ft (GLS) vagy 990 Ft (FoxPost).</p>
                </div>
            </details>
            
            <!-- Kérdés 7 -->
            <details class="bg-white rounded-xl shadow-sm faq-item">
                <summary class="flex items-center justify-between p-6 cursor-pointer list-none">
                    <span class="font-semibold text-lg pr-4">Hogyan tudok kapcsolatba lépni veletek?</span>
                    <i class="las la-plus text-2xl text-gray-400 faq-icon transition-transform"></i>
                </summary>
                <div class="px-6 pb-6 text-gray-600 leading-relaxed">
                    <p>Több csatornán is elérsz minket:</p>
                    <ul class="list-disc list-inside mt-2 space-y-1">
                        <li><strong>Email:</strong> info@yoursywear.hu</li>
                        <li><strong>Telefon:</strong> +36 30 123 4567 (H-P 9:00-17:00)</li>
                        <li><strong>Instagram:</strong> @yoursywear</li>
                    </ul>
                    <p class="mt-2">Általában 24 órán belül válaszolunk minden megkeresésre.</p>
                </div>
            </details>
            
            <!-- Kérdés 8 -->
            <details class="bg-white rounded-xl shadow-sm faq-item">
                <summary class="flex items-center justify-between p-6 cursor-pointer list-none">
                    <span class="font-semibold text-lg pr-4">Van fizikai üzletetek is?</span>
                    <i class="las la-plus text-2xl text-gray-400 faq-icon transition-transform"></i>
                </summary>
                <div class="px-6 pb-6 text-gray-600 leading-relaxed">
                    <p>Jelenleg <strong>kizárólag online webshopként</strong> működünk, így tudjuk a legjobb árakat biztosítani. 
                    Tervezünk pop-up store eseményeket Budapesten - kövesd az Instagram oldalunkat, 
                    hogy ne maradj le róluk!</p>
                </div>
            </details>

        </div>
        
            <!-- További kérdés -->
            <div class="text-center mt-8">
                <p class="text-gray-500 text-sm">Nem találtad a választ? <a href="mailto:info@yoursywear.hu" class="text-black font-medium hover:underline">Írj nekünk!</a></p>
            </div>
        </div>
        </details>
    </div>
</section>

<script>
// GYIK ikon váltás
document.querySelectorAll('.faq-item').forEach(item => {
    item.addEventListener('toggle', () => {
        const icon = item.querySelector('.faq-icon');
        if (item.open) {
            icon.classList.remove('la-plus');
            icon.classList.add('la-minus');
        } else {
            icon.classList.remove('la-minus');
            icon.classList.add('la-plus');
        }
    });
});

// Fő GYIK szekció ikon forgatás
document.querySelector('.faq-main')?.addEventListener('toggle', function() {
    const icon = this.querySelector('.faq-main-icon');
    if (this.open) {
        icon.style.transform = 'rotate(180deg)';
    } else {
        icon.style.transform = 'rotate(0deg)';
    }
});

function toggleFavorite(productId, btn, event) {
    event.preventDefault();
    event.stopPropagation();
    
    const isLoggedIn = <?= isset($_SESSION['user_id']) ? 'true' : 'false' ?>;
    
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
            const icon = btn.querySelector('i');
            const isFavorite = icon.classList.contains('lar');
            
            if (isFavorite) {
                icon.classList.remove('lar', 'text-gray-400');
                icon.classList.add('las', 'text-red-500');
                btn.classList.add('bg-red-50');
            } else {
                icon.classList.remove('las', 'text-red-500');
                icon.classList.add('lar', 'text-gray-400');
                btn.classList.remove('bg-red-50');
            }
            
            btn.style.transform = 'scale(1.2)';
            setTimeout(() => btn.style.transform = 'scale(1)', 150);
        }
    })
    .catch(err => console.error('Hiba:', err));
}
</script>
