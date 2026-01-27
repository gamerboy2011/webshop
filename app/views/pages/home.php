<?php
// $products a controllerből jön
?>
<?php
$products = $products ?? [];
?>

<!-- KÖZPONTI WRAPPER – EZ HIÁNYZOTT -->
<div class="max-w-7xl mx-auto px-6 overflow-x-hidden">

    <!-- HERO -->
    <section class="grid grid-cols-1 md:grid-cols-2 gap-12 items-center py-20">

        <!-- BAL OLDAL -->
        <div>
            <h1 class="text-5xl font-bold leading-tight mb-6">
                Yoursy Wear
            </h1>

            <p class="text-lg text-gray-600 mb-8">
                Your style speaks for you.
            </p>

            <a href="index.php"
               class="inline-block bg-black text-white px-8 py-4 uppercase tracking-wide">
                Fedezd fel a termékeket
            </a>
        </div>

        <!-- JOBB OLDAL -->
        <div class="flex justify-center">
            <div class="bg-black text-white p-10 w-72 h-96 flex flex-col justify-between">
                <div>
                    <h2 class="text-3xl font-bold mb-4">Yoursy Wear</h2>
                    <p class="text-sm">
                        Prémium streetwear és sneaker webshop.<br>
                        100% autentikus termékek.
                    </p>
                </div>
                <div class="text-sm opacity-80">
                    Újdonságok minden héten
                </div>
            </div>
        </div>

    </section>

    <!-- KIEMELT TERMÉKEK -->
    <section class="py-20">

        <h2 class="text-3xl font-semibold mb-10">
            Kiemelt termékek
        </h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-8">

            <?php if (!empty($products)): ?>
                <?php foreach ($products as $p): ?>
                    <a href="index.php?page=product&product=<?= $p['product_id'] ?>"
                       class="block group">

                        <div class="bg-gray-100 aspect-[3/4] mb-4 overflow-hidden">
                            <?php if (!empty($p['image'])): ?>
                                <img
                                    src="<?= htmlspecialchars($p['image']) ?>"
                                    alt="<?= htmlspecialchars($p['name']) ?>"
                                    class="w-full h-full object-cover group-hover:scale-105 transition"
                                >
                            <?php endif; ?>
                        </div>

                        <p class="font-medium">
                            <?= htmlspecialchars($p['name']) ?>
                        </p>

                        <p class="text-sm text-gray-600">
                            <?= number_format($p['price'], 0, ',', ' ') ?> Ft
                        </p>

                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Nincs megjeleníthető termék.</p>
            <?php endif; ?>

        </div>

    </section>

</div>
