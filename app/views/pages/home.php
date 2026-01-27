<?php
// app/views/pages/home.php
// EZ A VIEW FUT – ide kerül minden, ami a főoldalon látszik
?>

<!-- HERO -->
<?php include __DIR__ . "/hero.php"; ?>

<!-- KIEMELT TERMÉKEK -->
<section id="products" class="py-24 bg-white">
    <div class="max-w-7xl mx-auto px-6">

        <h2 class="text-3xl font-bold text-center mb-16">
            Kiemelt termékek
        </h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-12">

            <?php if (empty($products)): ?>
                <p class="text-center col-span-3">Nincs megjeleníthető termék.</p>
            <?php endif; ?>

            <?php foreach ($products as $product): ?>
                <div class="bg-white p-6 shadow text-center hover:shadow-xl transition">

                    <div class="h-64 bg-gray-100 mb-4 flex items-center justify-center overflow-hidden">
                        <?php if (!empty($product['image'])): ?>
                            <img
                                src="<?= htmlspecialchars($product['image']) ?>"
                                alt="<?= htmlspecialchars($product['name']) ?>"
                                class="w-full h-full object-cover"
                            >
                        <?php else: ?>
                            <span>Nincs kép</span>
                        <?php endif; ?>
                    </div>

                    <h3 class="text-xl font-semibold mb-2">
                        <?= htmlspecialchars($product['name']) ?>
                    </h3>

                    <span class="font-bold text-lg">
                        <?= number_format($product['price'], 0, ',', ' ') ?> Ft
                    </span>
                </div>
            <?php endforeach; ?>

        </div>

    </div>
</section>