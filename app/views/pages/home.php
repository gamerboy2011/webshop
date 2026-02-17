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
                <a
                    href="/webshop/termek/<?= (int)$product['product_id'] ?>"
                    class="group bg-white rounded-lg shadow-sm hover:shadow-lg transition-shadow overflow-hidden block"
                >
                    <div class="aspect-square bg-gray-100 overflow-hidden">
                        <?php if (!empty($product['image'])): ?>
                            <img src="/webshop/<?= htmlspecialchars($product['image']) ?>" 
                                 alt="<?= htmlspecialchars($product['name']) ?>"
                                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                        <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center text-gray-400">
                                <i class="fas fa-image text-4xl"></i>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="p-4">
                        <h3 class="text-lg font-semibold text-gray-900 group-hover:text-gray-600 transition-colors">
                            <?= htmlspecialchars($product['name']) ?>
                        </h3>
                        <p class="font-bold text-lg mt-2">
                            <?= number_format($product['price'], 0, ',', ' ') ?> Ft
                        </p>
                    </div>
                </a>
            <?php endforeach; ?>

        </div>
    </div>
</section>
