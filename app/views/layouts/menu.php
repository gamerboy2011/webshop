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

/* =========================
   KATEGÓRIÁK BETÖLTÉSE
   ========================= */
$menuCategories = [];
if (isset($pdo)) {
    $productModel = new ProductModel($pdo);
    $menuCategories = $productModel->getCategories();
}
?>

<nav class="w-full bg-white border-b">

    <!-- ===== FELSŐ SÁV ===== -->
    <div class="w-full py-4">
        <div class="flex items-center w-full px-8">

            <!-- BAL: GENDER -->
            <div class="w-1/3 flex gap-6 items-center">

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

            <!-- KÖZÉP: LOGÓ (TÖKÉLETESEN KÖZÉPEN) -->
            <div class="w-1/3 flex justify-center">
                <a href="/webshop/" class="text-xl font-semibold tracking-wide">
                    Yoursy Wear
                </a>
            </div>

            <!-- JOBB: IKONOK -->
            <div class="w-1/3 flex gap-6 items-center justify-end">

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

                <!-- Felhasználói menü -->
                <div class="relative group">
                    <button class="cursor-pointer text-gray-700 hover:text-black transition focus:outline-none">
                        <i class="fa-regular fa-user text-xl"></i>
                    </button>

                    <div class="absolute right-0 top-full mt-2 w-48 bg-white border rounded-lg shadow-lg
                                opacity-0 invisible group-hover:opacity-100 group-hover:visible
                                transition-all duration-200 z-50">

                        <?php if (empty($_SESSION['logged_in'])): ?>

                            <a href="/webshop/login" class="block px-4 py-3 hover:bg-gray-50 transition">
                                <i class="fas fa-sign-in-alt mr-2"></i> Bejelentkezés
                            </a>

                            <a href="/webshop/register" class="block px-4 py-3 hover:bg-gray-50 transition">
                                <i class="fas fa-user-plus mr-2"></i> Regisztráció
                            </a>

                        <?php else: ?>

                            <div class="px-4 py-2 border-b">
                                <p class="text-sm font-medium"><?= htmlspecialchars($_SESSION['username'] ?? 'Felhasználó'); ?></p>
                                <p class="text-xs text-gray-500"><?= htmlspecialchars($_SESSION['user_email'] ?? ''); ?></p>
                            </div>

                            <a href="/webshop/profil" class="block px-4 py-3 hover:bg-gray-50 transition">
                                <i class="fas fa-user mr-2"></i> Profil
                            </a>

                            <form method="POST" action="/webshop/logout" class="border-t">
                                <?= csrf_field(); ?>
                                <input type="hidden" name="action" value="logout">
                                <button type="submit" class="w-full text-left px-4 py-3 text-red-600 hover:bg-gray-50 transition">
                                    <i class="fas fa-sign-out-alt mr-2"></i> Kijelentkezés
                                </button>
                            </form>

                        <?php endif; ?>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== ALMENÜ ===== -->
    <div class="w-full border-t bg-gray-50">
        <div class="w-full py-3 flex gap-8 text-sm font-medium text-gray-700 px-8">

            <?php if ($currentGender && !empty($menuCategories)): ?>
                <?php foreach ($menuCategories as $category): ?>
                    <div class="relative group/cat">
                        <a href="/webshop/<?= $currentGender ?>/<?= urlencode(strtolower($category['name'])) ?>"
                           class="hover:text-black flex items-center gap-1 py-1">
                            <?= htmlspecialchars($category['name']) ?>
                            <?php if (!empty($category['subtypes'])): ?>
                                <i class="fas fa-chevron-down text-xs text-gray-400 group-hover/cat:text-black transition"></i>
                            <?php endif; ?>
                        </a>
                        
                        <?php if (!empty($category['subtypes'])): ?>
                            <div class="absolute left-0 top-full pt-2 opacity-0 invisible 
                                        group-hover/cat:opacity-100 group-hover/cat:visible 
                                        transition-all duration-200 z-50">
                                <div class="bg-white border rounded-lg shadow-lg py-2 min-w-48">
                                    <a href="/webshop/<?= $currentGender ?>/<?= urlencode(strtolower($category['name'])) ?>"
                                       class="block px-4 py-2 hover:bg-gray-50 font-medium border-b mb-1">
                                        Összes <?= htmlspecialchars($category['name']) ?>
                                    </a>
                                    <?php foreach ($category['subtypes'] as $subtype): ?>
                                        <a href="/webshop/<?= $currentGender ?>/<?= urlencode(strtolower($subtype['name'])) ?>"
                                           class="block px-4 py-2 hover:bg-gray-50 text-gray-600 hover:text-black">
                                            <?= htmlspecialchars($subtype['name']) ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <a href="/webshop/akcio" class="hover:text-black py-1">
                <i class="fas fa-percent text-red-500 mr-1"></i>
                Akció
            </a>

            <a href="/webshop/ujdonsagok" class="hover:text-black py-1">
                <i class="fas fa-sparkles text-yellow-500 mr-1"></i>
                Újdonságok
            </a>

        </div>
    </div>

</nav>
