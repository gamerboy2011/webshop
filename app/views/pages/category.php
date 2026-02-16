<?php
// $products, $gender, $category változók a controllerből jönnek
$gender = $gender ?? null;
$category = $category ?? null;
?>

<div class="max-w-7xl mx-auto px-6 py-20">

    <!-- OLDAL CÍM -->
    <h1 class="text-4xl font-bold mb-8">
        <?= $gender === 'ferfi' ? 'Férfi' : ($gender === 'noi' ? 'Női' : 'Termékek') ?>
        <?= $category ? ' – ' . ucfirst($category) : '' ?>
    </h1>

    <!-- TERMÉKEK -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">

        <?php if (!empty($products)): ?>
            <?php foreach ($products as $product): ?>
                <a href="/webshop/termek/<?= $product['product_id'] ?>" 
                   class="bg-white border hover:shadow-lg transition group">
                    
                    <?php if (!empty($product['image'])): ?>
                        <img src="/webshop/<?= htmlspecialchars($product['image']) ?>" 
                             alt="<?= htmlspecialchars($product['name']) ?>"
                             class="w-full h-64 object-cover">
                    <?php else: ?>
                        <div class="w-full h-64 bg-gray-100 flex items-center justify-center">
                            <span class="text-gray-400">Nincs kép</span>
                        </div>
                    <?php endif; ?>
                    
                    <div class="p-4">
                        <h2 class="font-semibold text-lg group-hover:text-gray-600">
                            <?= htmlspecialchars($product['name']) ?>
                        </h2>
                        <p class="text-gray-900 font-bold mt-2">
                            <?= number_format($product['price'], 0, ',', ' ') ?> Ft
                        </p>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="text-gray-500 col-span-full">
                Nincs találat ebben a kategóriában.
            </p>
        <?php endif; ?>

    </div>

</div>
