<?php
// $products és $searchQuery változók az index.php-ból jönnek
$products = $products ?? [];
$searchQuery = $searchQuery ?? '';
?>

<div class="max-w-7xl mx-auto px-6 py-12">

    <!-- OLDAL CÍM -->
    <h1 class="text-3xl font-bold mb-2">
        <i class="las la-search text-gray-400 mr-2"></i>
        Keresési eredmények
    </h1>
    <p class="text-gray-500 mb-8">
        "<span class="font-medium text-gray-700"><?= htmlspecialchars($searchQuery) ?></span>" kifejezésre
        <span class="font-medium"><?= count($products) ?></span> találat
    </p>

    <!-- TERMÉKEK -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-6">

        <?php if (!empty($products)): ?>
            <?php foreach ($products as $product): ?>
                <a href="/webshop/termek/<?= $product['product_id'] ?>" 
                   class="group bg-white rounded-lg shadow-sm hover:shadow-lg transition-shadow overflow-hidden block">
                    
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
                        <?php if (!empty($product['vendor_name'])): ?>
                            <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">
                                <?= htmlspecialchars($product['vendor_name']) ?>
                            </p>
                        <?php endif; ?>
                        <h2 class="font-semibold text-gray-900 group-hover:text-gray-600 transition-colors line-clamp-2">
                            <?= htmlspecialchars($product['name']) ?>
                        </h2>
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
                            <p class="text-gray-900 font-bold mt-2">
                                <?= number_format($product['price'], 0, ',', ' ') ?> Ft
                            </p>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-span-full text-center py-12">
                <i class="las la-search text-gray-300 text-6xl mb-4"></i>
                <p class="text-gray-500 text-lg mb-2">
                    Nincs találat a "<?= htmlspecialchars($searchQuery) ?>" keresésre.
                </p>
                <p class="text-gray-400 text-sm mb-4">
                    Próbálj keresést márkanévre, terméktípusra vagy terméknévre.
                </p>
                <a href="/webshop/" class="inline-block mt-4 text-black underline hover:no-underline">
                    Vissza a főoldalra
                </a>
            </div>
        <?php endif; ?>

    </div>

</div>
