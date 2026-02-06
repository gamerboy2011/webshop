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

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-12">

            <?php if (empty($products)): ?>
                <p class="col-span-3 text-center text-gray-500">
                    Nincs találat.
                </p>
            <?php endif; ?>

            <?php foreach ($products as $product): ?>
                <a
                    href="index.php?page=product&id=<?= (int)$product['product_id'] ?>"
                    class="bg-white p-6 shadow hover:shadow-xl transition text-center block"
                >
                    <div class="h-64 bg-gray-100 mb-4 flex items-center justify-center">
                        Kép helye
                    </div>

                    <h3 class="text-xl font-semibold">
                        <?= htmlspecialchars($product['name']) ?>
                    </h3>

                    <span class="font-bold text-lg">
                        <?= number_format($product['price'], 0, ',', ' ') ?> Ft
                    </span>
                </a>
            <?php endforeach; ?>

        </div>
    </div>
</section>
