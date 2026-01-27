<?php die("EZ A MAIN FUT"); ?>

<main class="w-full">

    <!-- HERO -->
    <?php include __DIR__ . "/hero.php"; ?>

    <!-- KIEMELT TERMÉKEK -->
    <section id="products" class="py-24 bg-white">
        <div class="max-w-7xl mx-auto px-6">

            <h2 class="text-3xl font-bold text-center mb-16">
                Kiemelt termékek
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-12">

                <!-- TERMÉK KÁRTYA -->
                <div class="bg-white border p-6 text-center hover:shadow-lg transition">
                    <div class="h-64 bg-gray-100 mb-6 flex items-center justify-center">
                        <span class="text-gray-400">Termék kép</span>
                    </div>

                    <h3 class="text-lg font-semibold mb-2">
                        Basic Póló
                    </h3>

                    <p class="text-gray-600 text-sm mb-4">
                        Minimalista stílus, prémium anyag.
                    </p>

                    <span class="font-bold text-lg">
                        6 990 Ft
                    </span>
                </div>

                <div class="bg-white border p-6 text-center hover:shadow-lg transition">
                    <div class="h-64 bg-gray-100 mb-6 flex items-center justify-center">
                        <span class="text-gray-400">Termék kép</span>
                    </div>

                    <h3 class="text-lg font-semibold mb-2">
                        Oversize Pulóver
                    </h3>

                    <p class="text-gray-600 text-sm mb-4">
                        Kényelmes viselet, modern szabás.
                    </p>

                    <span class="font-bold text-lg">
                        14 990 Ft
                    </span>
                </div>

                <div class="bg-white border p-6 text-center hover:shadow-lg transition">
                    <div class="h-64 bg-gray-100 mb-6 flex items-center justify-center">
                        <span class="text-gray-400">Termék kép</span>
                    </div>

                    <h3 class="text-lg font-semibold mb-2">
                        Street Kabát
                    </h3>

                    <p class="text-gray-600 text-sm mb-4">
                        Városi stílus, erőteljes megjelenés.
                    </p>

                    <span class="font-bold text-lg">
                        24 990 Ft
                    </span>
                </div>

            </div>

        </div>
    </section>

</main>