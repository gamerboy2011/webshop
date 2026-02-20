<?php
// app/views/pages/home.php
// EZ A VIEW FUT – ide kerül minden, ami a főoldalon látszik

// BIZTONSÁGI INICIALIZÁLÁS
// Ha a controller nem adott át termékeket,
// akkor ne haljon el a nézet
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
                        <div class="aspect-square bg-gray-100 overflow-hidden relative">
                            <?php if (!empty($product['is_sale'])): ?>
                                <span class="absolute top-2 left-2 bg-red-500 text-white text-xs font-bold px-2 py-1 rounded z-10">
                                    -20%
                                </span>
                            <?php endif; ?>
                            <?php if (!empty($product['image'])): ?>
                                <img src="/webshop/<?= htmlspecialchars($product['image']) ?>" 
                                     alt="<?= htmlspecialchars($product['name']) ?>"
                                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                            <?php else: ?>
                                <div class="w-full h-full flex items-center justify-center text-gray-400">
                                    <i class="las la-image text-4xl"></i>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="p-4">
                            <h3 class="text-lg font-semibold text-gray-900 group-hover:text-gray-600 transition-colors">
                                <?= htmlspecialchars($product['name']) ?>
                            </h3>
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

<script>
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
