<?php
$products = $products ?? [];
?>

<div class="max-w-7xl mx-auto px-6 py-12">

    <!-- OLDAL CÍM -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold mb-2">
            <i class="fas fa-percent text-red-500 mr-2"></i>
            Akciós termékek
        </h1>
        <p class="text-gray-500">
            <?= count($products) ?> termék akciósan
        </p>
    </div>

    <!-- TERMÉKEK -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-6">

        <?php if (!empty($products)): ?>
            <?php foreach ($products as $product): ?>
                <a href="/webshop/termek/<?= $product['product_id'] ?>" 
                   class="group bg-white rounded-lg shadow-sm hover:shadow-lg transition-shadow overflow-hidden block relative">
                    
                    <!-- Akció badge -->
                    <span class="absolute top-2 left-2 bg-red-500 text-white text-xs font-bold px-2 py-1 rounded z-10">
                        AKCIÓ
                    </span>
                    
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
                        <h2 class="font-semibold text-gray-900 group-hover:text-gray-600 transition-colors line-clamp-2">
                            <?= htmlspecialchars($product['name']) ?>
                        </h2>
                        <p class="text-red-600 font-bold mt-2">
                            <?= number_format($product['price'], 0, ',', ' ') ?> Ft
                        </p>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-span-full text-center py-12">
                <i class="fas fa-tag text-gray-300 text-6xl mb-4"></i>
                <p class="text-gray-500 text-lg">
                    Jelenleg nincs akciós termék.
                </p>
                <a href="/webshop/" class="inline-block mt-4 text-black underline hover:no-underline">
                    Vissza a főoldalra
                </a>
            </div>
        <?php endif; ?>

    </div>

</div>
