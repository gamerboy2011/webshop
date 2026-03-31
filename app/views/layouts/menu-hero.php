<?php
/**
 * HERO OVERLAY MENÜ
 * Csak a főoldalon jelenik meg - átlátszó háttérrel a hero képen
 */

$cartCount = 0;
if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cartCount += (int)$item['quantity'];
    }
}

$menuCategories = [
    [
        'name' => 'Ruházat',
        'slug' => 'ruhazat',
        'subtypes' => [
            ['name' => 'Pólók', 'slug' => 'polo'],
            ['name' => 'Pulóverek', 'slug' => 'pulover'],
            ['name' => 'Kabátok', 'slug' => 'kabat'],
            ['name' => 'Nadrágok', 'slug' => 'nadrag'],
            ['name' => 'Rövidnadrágok', 'slug' => 'rovidnadrag'],
            ['name' => 'Egyberuhák', 'slug' => 'egyberuha', 'female_only' => true],
        ]
    ],
    [
        'name' => 'Cipők',
        'slug' => 'cipok',
        'subtypes' => [
            ['name' => 'Cipők', 'slug' => 'cipo'],
            ['name' => 'Papucsok', 'slug' => 'papucs'],
        ]
    ],
    [
        'name' => 'Kiegészítők',
        'slug' => 'kiegeszitok',
        'subtypes' => [
            ['name' => 'Sapkák', 'slug' => 'sapka'],
            ['name' => 'Zoknik', 'slug' => 'zokni'],
            ['name' => 'Táskák', 'slug' => 'taska'],
            ['name' => 'Hátizsákok', 'slug' => 'hatizsak'],
        ]
    ],
];
?>

<!-- HERO OVERLAY NAVIGÁCIÓ -->
<nav id="heroNav" class="fixed top-0 left-0 right-0 z-50 transition-all duration-300">
    <div class="w-full py-4 px-4 lg:px-8 relative">
        
        <!-- KÖZÉP: LOGÓ - abszolút középen -->
        <a href="/webshop/" class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 text-2xl font-bold text-white tracking-wider drop-shadow-lg">
            Yoursy Wear
        </a>
        
        <div class="flex items-center justify-between">
            
            <!-- BAL: HAMBURGER MENÜ GOMB -->
            <button id="heroMenuBtn" class="text-white text-3xl hover:scale-110 transition-transform">
                <i class="las la-bars"></i>
            </button>
            
            <!-- JOBB: IKONOK -->
            <div class="flex gap-4 items-center">
                <!-- KERESÉS - desktop: mező, mobil: ikon -->
                <form method="get" action="/webshop/" class="hidden md:block">
                    <input type="text" name="q" placeholder="Keresés..." 
                           class="w-48 lg:w-56 px-4 py-2 text-sm rounded-full bg-white/20 text-white placeholder-white/70 border border-white/30 focus:outline-none focus:border-white focus:bg-white/30 transition">
                </form>
                <button id="heroSearchBtn" class="md:hidden text-white text-2xl hover:scale-110 transition-transform">
                    <i class="las la-search"></i>
                </button>
                
                <!-- KOSÁR -->
                <a href="/webshop/kosar" class="relative text-white text-2xl hover:scale-110 transition-transform">
                    <i class="las la-shopping-bag"></i>
                    <?php if ($cartCount > 0): ?>
                        <span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs w-5 h-5 rounded-full flex items-center justify-center">
                            <?= $cartCount ?>
                        </span>
                    <?php endif; ?>
                </a>
                
                <!-- USER IKON -->
                <?php if (empty($_SESSION['logged_in'])): ?>
                    <a href="/webshop/login" class="text-white text-2xl hover:scale-110 transition-transform">
                        <i class="lar la-user"></i>
                    </a>
                <?php else: ?>
                    <a href="/webshop/profil" class="text-white text-2xl hover:scale-110 transition-transform">
                        <i class="las la-user"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- KERESÉS PANEL -->
    <div id="heroSearchPanel" class="hidden w-full bg-black/80 backdrop-blur-sm py-4 px-4 lg:px-8">
        <form method="get" action="/webshop/" class="max-w-lg mx-auto">
            <input type="text" name="q" placeholder="Keresés..." 
                   class="w-full px-6 py-3 rounded-full bg-white/20 text-white placeholder-white/70 border border-white/30 focus:outline-none focus:border-white">
        </form>
    </div>
</nav>

<!-- HERO OLDALSÁV MENÜ (Drawer) -->
<div id="heroMenuDrawer" class="fixed inset-0 z-[100] hidden">
    <!-- Háttér overlay -->
    <div id="heroMenuOverlay" class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>
    
    <!-- Drawer panel -->
    <div id="heroMenuPanel" class="absolute left-0 top-0 h-full w-80 max-w-[85vw] bg-white shadow-2xl transform -translate-x-full transition-transform duration-300">
        
        <!-- Header -->
        <div class="flex items-center justify-between p-4 border-b bg-gray-50">
            <span class="text-xl font-bold">Menü</span>
            <button id="heroMenuClose" class="text-3xl text-gray-400 hover:text-black transition">
                <i class="las la-times"></i>
            </button>
        </div>
        
        <!-- Content -->
        <div class="overflow-y-auto h-[calc(100%-64px)]">
            
            <!-- Gender választó -->
            <div class="flex border-b">
                <a href="/webshop/noi" class="flex-1 py-4 text-center font-semibold text-gray-700 hover:bg-gray-50 transition">
                    <i class="las la-female mr-1"></i> Női
                </a>
                <a href="/webshop/ferfi" class="flex-1 py-4 text-center font-semibold text-gray-700 hover:bg-gray-50 transition border-l">
                    <i class="las la-male mr-1"></i> Férfi
                </a>
            </div>
            
            <!-- Kategóriák -->
            <div class="py-2">
                <?php foreach ($menuCategories as $category): ?>
                    <div class="hero-cat-group">
                        <button class="hero-cat-toggle w-full flex items-center justify-between px-4 py-3 text-left font-medium hover:bg-gray-50 transition">
                            <span><?= $category['name'] ?></span>
                            <i class="las la-angle-down text-gray-400 transition-transform"></i>
                        </button>
                        
                        <div class="hero-cat-submenu hidden bg-gray-50">
                            <div class="py-2 px-2">
                                <p class="px-4 py-1 text-xs text-gray-400 uppercase">Női</p>
                                <?php foreach ($category['subtypes'] as $subtype): ?>
                                    <a href="/webshop/noi/<?= $subtype['slug'] ?>"
                                       class="block px-6 py-2 text-sm text-gray-600 hover:text-black hover:bg-gray-100 rounded transition">
                                        <?= $subtype['name'] ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                            <div class="py-2 px-2 border-t">
                                <p class="px-4 py-1 text-xs text-gray-400 uppercase">Férfi</p>
                                <?php foreach ($category['subtypes'] as $subtype): ?>
                                    <?php if (!empty($subtype['female_only'])) continue; ?>
                                    <a href="/webshop/ferfi/<?= $subtype['slug'] ?>"
                                       class="block px-6 py-2 text-sm text-gray-600 hover:text-black hover:bg-gray-100 rounded transition">
                                        <?= $subtype['name'] ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Speciális linkek -->
            <div class="border-t py-2">
                <a href="/webshop/akcio" class="flex items-center px-4 py-3 hover:bg-gray-50 transition">
                    <i class="las la-percent text-red-500 mr-3 text-xl"></i>
                    <span class="font-medium">Akció</span>
                </a>
                <a href="/webshop/ujdonsagok" class="flex items-center px-4 py-3 hover:bg-gray-50 transition">
                    <i class="las la-star text-yellow-500 mr-3 text-xl"></i>
                    <span class="font-medium">Újdonságok</span>
                </a>
            </div>
            
            <!-- Felhasználói rész -->
            <div class="border-t py-2">
                <?php if (empty($_SESSION['logged_in'])): ?>
                    <a href="/webshop/login" class="flex items-center px-4 py-3 hover:bg-gray-50 transition">
                        <i class="las la-sign-in-alt mr-3 text-xl"></i>
                        <span>Bejelentkezés</span>
                    </a>
                    <a href="/webshop/register" class="flex items-center px-4 py-3 hover:bg-gray-50 transition">
                        <i class="las la-user-plus mr-3 text-xl"></i>
                        <span>Regisztráció</span>
                    </a>
                <?php else: ?>
                    <div class="px-4 py-3 bg-gray-50">
                        <p class="font-medium"><?= htmlspecialchars($_SESSION['username'] ?? 'Felhasználó'); ?></p>
                        <p class="text-sm text-gray-500"><?= htmlspecialchars($_SESSION['user_email'] ?? ''); ?></p>
                    </div>
                    <a href="/webshop/profil" class="flex items-center px-4 py-3 hover:bg-gray-50 transition">
                        <i class="las la-user mr-3 text-xl"></i>
                        <span>Profil</span>
                    </a>
                    <form method="POST" action="/webshop/logout">
                        <?= csrf_field(); ?>
                        <input type="hidden" name="action" value="logout">
                        <button type="submit" class="flex items-center w-full px-4 py-3 text-red-600 hover:bg-gray-50 transition">
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
// Hero menü JavaScript
document.addEventListener('DOMContentLoaded', function() {
    const heroMenuBtn = document.getElementById('heroMenuBtn');
    const heroMenuDrawer = document.getElementById('heroMenuDrawer');
    const heroMenuPanel = document.getElementById('heroMenuPanel');
    const heroMenuClose = document.getElementById('heroMenuClose');
    const heroMenuOverlay = document.getElementById('heroMenuOverlay');
    const heroSearchBtn = document.getElementById('heroSearchBtn');
    const heroSearchPanel = document.getElementById('heroSearchPanel');
    const heroNav = document.getElementById('heroNav');

    // Menü drawer megnyitás/bezárás
    function openHeroMenu() {
        heroMenuDrawer.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        setTimeout(() => {
            heroMenuPanel.classList.remove('-translate-x-full');
        }, 10);
    }

    function closeHeroMenu() {
        heroMenuPanel.classList.add('-translate-x-full');
        setTimeout(() => {
            heroMenuDrawer.classList.add('hidden');
            document.body.style.overflow = '';
        }, 300);
    }

    heroMenuBtn?.addEventListener('click', openHeroMenu);
    heroMenuClose?.addEventListener('click', closeHeroMenu);
    heroMenuOverlay?.addEventListener('click', closeHeroMenu);

    // Keresés panel toggle
    heroSearchBtn?.addEventListener('click', () => {
        heroSearchPanel.classList.toggle('hidden');
        if (!heroSearchPanel.classList.contains('hidden')) {
            heroSearchPanel.querySelector('input').focus();
        }
    });

    // Kategória toggle
    document.querySelectorAll('.hero-cat-toggle').forEach(btn => {
        btn.addEventListener('click', () => {
            const submenu = btn.nextElementSibling;
            const icon = btn.querySelector('i');
            submenu.classList.toggle('hidden');
            icon.classList.toggle('rotate-180');
        });
    });

    // Scroll hatás - háttér hozzáadása görgetéskor
    window.addEventListener('scroll', () => {
        if (window.scrollY > 100) {
            heroNav.classList.add('bg-black/70', 'backdrop-blur-sm');
        } else {
            heroNav.classList.remove('bg-black/70', 'backdrop-blur-sm');
        }
    });
});
</script>
