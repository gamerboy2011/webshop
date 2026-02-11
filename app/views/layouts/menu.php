<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<nav class="w-full bg-white border-b shadow-sm">
    <div class="w-full py-4">
        <div class="grid grid-cols-3 items-center px-4 md:px-8">

            <!-- Bal oldali menü -->
            <div class="flex gap-4 md:gap-6">
                <a href="/webshop/noi" class="text-gray-700 hover:text-black transition">Női</a>
                <a href="/webshop/ferfi" class="text-gray-700 hover:text-black transition">Férfi</a>
            </div>

            <!-- Középső logo -->
            <div class="text-center">
                <a href="/webshop/home" class="text-xl font-bold tracking-tight">
                    Yoursy Wear
                </a>
            </div>

            <!-- Jobb oldali ikonok -->
            <div class="flex justify-end gap-4 md:gap-6 items-center">

                <!-- Kosár ikon -->
                <a href="/webshop/kosar" class="relative text-gray-700 hover:text-black transition">
                    <i class="fa-solid fa-bag-shopping text-lg"></i>
                    <?php if (!empty($_SESSION['cart_count'])): ?>
                        <span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">
                            <?= $_SESSION['cart_count']; ?>
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
</nav>
