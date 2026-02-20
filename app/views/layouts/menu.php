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
   KATEGÓRIÁK (FIX MAGYAR)
   ========================= */
$menuCategories = [
    [
        'name' => 'Ruházat',
        'slug' => 'clothe',
        'subtypes' => [
            ['name' => 'Pólók', 'slug' => 't-shirt'],
            ['name' => 'Pulcsik', 'slug' => 'hoodie'],
            ['name' => 'Pulóverek', 'slug' => 'sweater'],
            ['name' => 'Farmerek', 'slug' => 'jeans'],
            ['name' => 'Kabátok', 'slug' => 'jacket'],
            ['name' => 'Télikabátok', 'slug' => 'winter coat'],
            ['name' => 'Leggingsek', 'slug' => 'leggings'],
        ]
    ],
    [
        'name' => 'Cipők',
        'slug' => 'shoe',
        'subtypes' => [
            ['name' => 'Cipők', 'slug' => 'shoes'],
            ['name' => 'Szandálok', 'slug' => 'sandals'],
        ]
    ],
    [
        'name' => 'Kiegészítők',
        'slug' => 'accessory',
        'subtypes' => [
            ['name' => 'Sapkák', 'slug' => 'cap'],
            ['name' => 'Kalapok', 'slug' => 'hat'],
            ['name' => 'Táskák', 'slug' => 'bag'],
        ]
    ],
];
?>

<nav class="w-full bg-white border-b">

    <!-- ===== FELSŐ SÁV ===== -->
    <div class="w-full py-4">
        <div class="flex items-center w-full px-4 lg:px-8">

            <!-- MOBIL: HAMBURGER -->
            <button id="mobileMenuBtn" class="lg:hidden text-2xl mr-4">
                <i class="las la-bars"></i>
            </button>

            <!-- BAL: GENDER (csak desktop) -->
            <div class="hidden lg:flex w-1/3 gap-6 items-center">

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
            <div class="flex-1 lg:w-1/3 flex justify-center lg:justify-center">
                <a href="/webshop/" class="text-xl font-semibold tracking-wide">
                    Yoursy Wear
                </a>
            </div>

            <!-- JOBB: IKONOK -->
            <div class="flex lg:w-1/3 gap-4 lg:gap-6 items-center justify-end">

                <!-- KERESÉS (csak desktop) -->
                <form method="get" action="/webshop/" class="hidden lg:block">
                    <input
                        type="text"
                        name="q"
                        placeholder="Keresés…"
                        class="w-56 px-4 py-2 text-sm border rounded-full
                               focus:outline-none focus:ring-1 focus:ring-black">
                </form>

                <!-- KERESÉS IKON (mobil) -->
                <button id="mobileSearchBtn" class="lg:hidden text-2xl">
                    <i class="las la-search"></i>
                </button>

                <!-- KOSÁR -->
                <a href="/webshop/kosar" class="relative">
                    <i class="las la-shopping-bag text-2xl"></i>

                    <?php if ($cartCount > 0): ?>
                        <span class="absolute -top-2 -right-2
                                     bg-red-500 text-white text-xs
                                     w-5 h-5 rounded-full
                                     flex items-center justify-center">
                            <?= $cartCount ?>
                        </span>
                    <?php endif; ?>
                </a>

                <!-- Felhasználói menü (csak desktop) -->
                <div class="relative group hidden lg:block">
                    <button class="cursor-pointer text-gray-700 hover:text-black transition focus:outline-none">
                        <i class="lar la-user text-2xl"></i>
                    </button>

                    <div class="absolute right-0 top-full mt-2 w-48 bg-white border rounded-lg shadow-lg
                                opacity-0 invisible group-hover:opacity-100 group-hover:visible
                                transition-all duration-200 z-50">

                        <?php if (empty($_SESSION['logged_in'])): ?>

                            <a href="/webshop/login" class="block px-4 py-3 hover:bg-gray-50 transition">
                                <i class="las la-sign-in-alt mr-2"></i> Bejelentkezés
                            </a>

                            <a href="/webshop/register" class="block px-4 py-3 hover:bg-gray-50 transition">
                                <i class="las la-user-plus mr-2"></i> Regisztráció
                            </a>

                        <?php else: ?>

                            <div class="px-4 py-2 border-b">
                                <p class="text-sm font-medium"><?= htmlspecialchars($_SESSION['username'] ?? 'Felhasználó'); ?></p>
                                <p class="text-xs text-gray-500"><?= htmlspecialchars($_SESSION['user_email'] ?? ''); ?></p>
                            </div>

                            <a href="/webshop/profil" class="block px-4 py-3 hover:bg-gray-50 transition">
                                <i class="las la-user mr-2"></i> Profil
                            </a>

                            <form method="POST" action="/webshop/logout" class="border-t">
                                <?= csrf_field(); ?>
                                <input type="hidden" name="action" value="logout">
                                <button type="submit" class="w-full text-left px-4 py-3 text-red-600 hover:bg-gray-50 transition">
                                    <i class="las la-sign-out-alt mr-2"></i> Kijelentkezés
                                </button>
                            </form>

                        <?php endif; ?>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MOBIL KERESÉS SÁVE -->
    <div id="mobileSearchBar" class="hidden lg:hidden px-4 pb-4">
        <form method="get" action="/webshop/">
            <input
                type="text"
                name="q"
                placeholder="Keresés…"
                class="w-full px-4 py-2 text-sm border rounded-full
                       focus:outline-none focus:ring-1 focus:ring-black">
        </form>
    </div>

    <!-- ===== ALMENÜ (csak desktop) ===== -->
    <div class="hidden lg:block w-full border-t bg-gray-50">
        <div class="w-full py-3 flex gap-8 text-sm font-medium text-gray-700 px-8">

            <?php if ($currentGender): ?>
                <?php foreach ($menuCategories as $category): ?>
                    <div class="relative group/cat">
                        <a href="/webshop/<?= $currentGender ?>/<?= $category['slug'] ?>"
                           class="hover:text-black flex items-center gap-1 py-1">
                            <?= $category['name'] ?>
                            <?php if (!empty($category['subtypes'])): ?>
                                <i class="las la-angle-down text-xs text-gray-400 group-hover/cat:text-black transition"></i>
                            <?php endif; ?>
                        </a>
                        
                        <?php if (!empty($category['subtypes'])): ?>
                            <div class="absolute left-0 top-full pt-2 opacity-0 invisible 
                                        group-hover/cat:opacity-100 group-hover/cat:visible 
                                        transition-all duration-200 z-50">
                                <div class="bg-white border rounded-lg shadow-lg py-2 min-w-48">
                                    <a href="/webshop/<?= $currentGender ?>/<?= $category['slug'] ?>"
                                       class="block px-4 py-2 hover:bg-gray-50 font-medium border-b mb-1">
                                        Összes <?= $category['name'] ?>
                                    </a>
                                    <?php foreach ($category['subtypes'] as $subtype): ?>
                                        <a href="/webshop/<?= $currentGender ?>/<?= $subtype['slug'] ?>"
                                           class="block px-4 py-2 hover:bg-gray-50 text-gray-600 hover:text-black">
                                            <?= $subtype['name'] ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <a href="/webshop/akcio" class="hover:text-black py-1">
                <i class="las la-percent text-red-500 mr-1"></i>
                Akció
            </a>

            <a href="/webshop/ujdonsagok" class="hover:text-black py-1">
                <i class="las la-star text-yellow-500 mr-1"></i>
                Újdonságok
            </a>

        </div>
    </div>

</nav>

<!-- ===== MOBIL MENÜ DRAWER ===== -->
<div id="mobileMenuDrawer" class="fixed inset-0 z-[100] hidden lg:hidden">
    <!-- Háttér overlay -->
    <div id="mobileMenuOverlay" class="absolute inset-0 bg-black/50"></div>
    
    <!-- Drawer panel -->
    <div id="mobileMenuPanel" class="absolute left-0 top-0 h-full w-80 max-w-[85vw] bg-white shadow-xl transform -translate-x-full transition-transform duration-300">
        
        <!-- Header -->
        <div class="flex items-center justify-between p-4 border-b">
            <span class="text-lg font-semibold">Menü</span>
            <button id="mobileMenuClose" class="text-2xl">
                <i class="las la-times"></i>
            </button>
        </div>
        
        <!-- Content -->
        <div class="overflow-y-auto h-[calc(100%-60px)]">
            
            <!-- Gender választó -->
            <div class="flex border-b">
                <a href="/webshop/noi" 
                   class="flex-1 py-4 text-center font-medium <?= $currentGender === 'noi' ? 'bg-black text-white' : 'text-gray-600' ?>">
                    Női
                </a>
                <a href="/webshop/ferfi" 
                   class="flex-1 py-4 text-center font-medium <?= $currentGender === 'ferfi' ? 'bg-black text-white' : 'text-gray-600' ?>">
                    Férfi
                </a>
            </div>
            
            <!-- Kategóriák -->
            <?php if ($currentGender): ?>
                <div class="py-2">
                    <?php foreach ($menuCategories as $category): ?>
                        <div class="mobile-cat-group">
                            <button class="mobile-cat-toggle w-full flex items-center justify-between px-4 py-3 text-left font-medium hover:bg-gray-50">
                                <span><?= $category['name'] ?></span>
                                <i class="las la-angle-down text-gray-400 transition-transform"></i>
                            </button>
                            
                            <div class="mobile-cat-submenu hidden bg-gray-50">
                                <a href="/webshop/<?= $currentGender ?>/<?= $category['slug'] ?>"
                                   class="block px-6 py-2 text-sm font-medium text-gray-700 hover:text-black">
                                    Összes <?= $category['name'] ?>
                                </a>
                                <?php foreach ($category['subtypes'] as $subtype): ?>
                                    <a href="/webshop/<?= $currentGender ?>/<?= $subtype['slug'] ?>"
                                       class="block px-6 py-2 text-sm text-gray-600 hover:text-black">
                                        <?= $subtype['name'] ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <!-- Speciális linkek -->
            <div class="border-t py-2">
                <a href="/webshop/akcio" class="flex items-center px-4 py-3 hover:bg-gray-50">
                    <i class="las la-percent text-red-500 mr-3 text-xl"></i>
                    <span class="font-medium">Akció</span>
                </a>
                <a href="/webshop/ujdonsagok" class="flex items-center px-4 py-3 hover:bg-gray-50">
                    <i class="las la-star text-yellow-500 mr-3 text-xl"></i>
                    <span class="font-medium">Újdonságok</span>
                </a>
            </div>
            
            <!-- Felhasználói rész -->
            <div class="border-t py-2">
                <?php if (empty($_SESSION['logged_in'])): ?>
                    <a href="/webshop/login" class="flex items-center px-4 py-3 hover:bg-gray-50">
                        <i class="las la-sign-in-alt mr-3 text-xl"></i>
                        <span>Bejelentkezés</span>
                    </a>
                    <a href="/webshop/register" class="flex items-center px-4 py-3 hover:bg-gray-50">
                        <i class="las la-user-plus mr-3 text-xl"></i>
                        <span>Regisztráció</span>
                    </a>
                <?php else: ?>
                    <div class="px-4 py-3 bg-gray-50">
                        <p class="font-medium"><?= htmlspecialchars($_SESSION['username'] ?? 'Felhasználó'); ?></p>
                        <p class="text-sm text-gray-500"><?= htmlspecialchars($_SESSION['user_email'] ?? ''); ?></p>
                    </div>
                    <a href="/webshop/profil" class="flex items-center px-4 py-3 hover:bg-gray-50">
                        <i class="las la-user mr-3 text-xl"></i>
                        <span>Profil</span>
                    </a>
                    <form method="POST" action="/webshop/logout">
                        <?= csrf_field(); ?>
                        <input type="hidden" name="action" value="logout">
                        <button type="submit" class="flex items-center w-full px-4 py-3 text-red-600 hover:bg-gray-50">
                            <i class="las la-sign-out-alt mr-3 text-xl"></i>
                            <span>Kijelentkezés</span>
                        </button>
                    </form>
                <?php endif; ?>
            </div>
            
        </div>
    </div>
</div>

<script>
// Mobil menü
const mobileMenuBtn = document.getElementById('mobileMenuBtn');
const mobileMenuDrawer = document.getElementById('mobileMenuDrawer');
const mobileMenuPanel = document.getElementById('mobileMenuPanel');
const mobileMenuClose = document.getElementById('mobileMenuClose');
const mobileMenuOverlay = document.getElementById('mobileMenuOverlay');

function openMobileMenu() {
    mobileMenuDrawer.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    setTimeout(() => {
        mobileMenuPanel.classList.remove('-translate-x-full');
    }, 10);
}

function closeMobileMenu() {
    mobileMenuPanel.classList.add('-translate-x-full');
    setTimeout(() => {
        mobileMenuDrawer.classList.add('hidden');
        document.body.style.overflow = '';
    }, 300);
}

mobileMenuBtn?.addEventListener('click', openMobileMenu);
mobileMenuClose?.addEventListener('click', closeMobileMenu);
mobileMenuOverlay?.addEventListener('click', closeMobileMenu);

// Kategória toggle
document.querySelectorAll('.mobile-cat-toggle').forEach(btn => {
    btn.addEventListener('click', () => {
        const submenu = btn.nextElementSibling;
        const icon = btn.querySelector('i');
        submenu.classList.toggle('hidden');
        icon.classList.toggle('rotate-180');
    });
});

// Mobil keresés toggle
const mobileSearchBtn = document.getElementById('mobileSearchBtn');
const mobileSearchBar = document.getElementById('mobileSearchBar');

mobileSearchBtn?.addEventListener('click', () => {
    mobileSearchBar.classList.toggle('hidden');
    if (!mobileSearchBar.classList.contains('hidden')) {
        mobileSearchBar.querySelector('input').focus();
    }
});
</script>
