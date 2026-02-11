<?php
/* =========================
   KOSÁR DARABSZÁM
   ========================= */
$cartCount = 0;
if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cartCount += (int)$item['quantity'];
    }
}

/* =========================
   AKTUÁLIS URL ELEMZÉS
   ========================= */
$uri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$uri = str_replace('webshop/', '', $uri);
$parts = explode('/', $uri);

/* gender meghatározása URL-ből */
$currentGender = null;
if (in_array('ferfi', $parts)) {
    $currentGender = 'ferfi';
}
if (in_array('noi', $parts)) {
    $currentGender = 'noi';
}
?>

<nav class="w-full bg-white border-b">

    <!-- ===== FELSŐ SÁV ===== -->
    <div class="w-full py-4">
        <div class="grid grid-cols-3 items-center w-full px-8">

            <!-- BAL: GENDER -->
            <div class="flex gap-6 items-center justify-start">

                <a href="/webshop/noi"
                    class="<?= $currentGender === 'noi'
                                ? 'font-semibold border-b-2 border-black'
                                : 'text-gray-500 hover:text-black' ?>">
                    Női
                </a>

                <a href="/webshop/ferfi"
                    class="<?= $currentGender === 'ferfi'
                                ? 'font-semibold border-b-2 border-black'
                                : 'text-gray-500 hover:text-black' ?>">
                    Férfi
                </a>

            </div>

            <!-- KÖZÉP: LOGÓ -->
            <div class="flex justify-center">
                <a href="/webshop/" class="text-xl font-semibold tracking-wide">
                    Yoursy Wear
                </a>
            </div>

            <!-- JOBB: IKONOK -->
            <div class="flex gap-6 items-center justify-end">

                <!-- KERESÉS -->
                <form method="get" action="/webshop/">
                    <input
                        type="text"
                        name="q"
                        placeholder="Keresés…"
                        class="w-56 px-4 py-2 text-sm border rounded-full
                               focus:outline-none focus:ring-1 focus:ring-black">
                </form>

                <!-- KOSÁR -->
                <a href="/webshop/kosar" class="relative">
                    <i class="fa-solid fa-bag-shopping text-xl"></i>

                    <?php if ($cartCount > 0): ?>
                        <span class="absolute -top-2 -right-2
                     bg-black text-white text-xs
                     w-5 h-5 rounded-full
                     flex items-center justify-center">
                            <?= $cartCount ?>
                        </span>
                    <?php endif; ?>
                </a>

            </div>
        </div>
    </div>

    <!-- ===== ALMENÜ ===== -->
    <div class="w-full border-t bg-gray-50">
        <div class="w-full py-3 flex gap-8 text-sm font-medium text-gray-700 px-8">

            <?php if ($currentGender): ?>

                <a href="/webshop/<?= $currentGender ?>/ruhazat"
                    class="hover:text-black">
                    Ruházat
                </a>

                <a href="/webshop/<?= $currentGender ?>/cipok"
                    class="hover:text-black">
                    Cipők
                </a>

                <a href="/webshop/<?= $currentGender ?>/kiegeszitok"
                    class="hover:text-black">
                    Kiegészítők
                </a>

            <?php endif; ?>

            <a href="/webshop/akcio" class="hover:text-black">
                Akció
            </a>

            <a href="/webshop/ujdonsagok" class="hover:text-black">
                Újdonságok
            </a>

        </div>
    </div>

</nav>