<?php
/* KOSÁR DARABSZÁM */
$cartCount = 0;
if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cartCount += (int)$item['quantity'];
    }
}

/* GENDER */
$currentGender = $_GET['gender'] ?? null;

/* LOGIN ÁLLAPOT */
$isLoggedIn = isset($_SESSION['user_id']);
?>

<nav class="w-full bg-white border-b">

    <!-- FELSŐ SÁV -->
    <div class="w-full py-4">
        <div class="grid grid-cols-3 items-center w-full px-8">

            <!-- BAL -->
            <div class="flex gap-6 items-center justify-start">
                <a href="index.php?gender=female"
                   class="<?= $currentGender === 'female'
                       ? 'font-semibold border-b-2 border-black'
                       : 'text-gray-500 hover:text-black' ?>">
                    Női
                </a>
                <a href="index.php?gender=male"
                   class="<?= $currentGender === 'male'
                       ? 'font-semibold border-b-2 border-black'
                       : 'text-gray-500 hover:text-black' ?>">
                    Férfi
                </a>
            </div>

            <!-- KÖZÉP -->
            <div class="flex justify-center">
                <a href="index.php" class="text-xl font-semibold tracking-wide">
                    Yoursy Wear
                </a>
            </div>

            <!-- JOBB -->
            <div class="flex gap-6 items-center justify-end">

                <!-- KERESÉS -->
                <form method="get" action="index.php">
                    <input
                        type="text"
                        name="q"
                        placeholder="Keresés…"
                        class="w-56 px-4 py-2 text-sm border rounded-full
                               focus:outline-none focus:ring-1 focus:ring-black">
                </form>

                <!-- USER MENU -->
                <div class="relative">
                    <button id="userMenuBtn" class="text-xl focus:outline-none">
                        <i class="fa-regular fa-user"></i>
                    </button>

                    <div
                        id="userDropdown"
                        class="hidden absolute right-0 mt-2 w-44
                               bg-white border shadow-lg z-50"
                    >
                        <?php if (!$isLoggedIn): ?>
                            <a href="login.php"
                               class="block px-4 py-2 text-sm hover:bg-gray-100">
                                Login
                            </a>
                            <a href="register.php"
                               class="block px-4 py-2 text-sm hover:bg-gray-100">
                                Register
                            </a>
                        <?php else: ?>
                            <a href="index.php?page=profile"
                               class="block px-4 py-2 text-sm hover:bg-gray-100">
                                Profile
                            </a>
                            <a href="logout.php"
                               class="block px-4 py-2 text-sm hover:bg-gray-100 text-red-600">
                                Logout
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- KOSÁR -->
                <a href="index.php?page=cart" class="relative">
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

    <!-- ALMENÜ -->
    <div class="w-full border-t bg-gray-50">
        <div class="w-full py-3 flex gap-8 text-sm font-medium text-gray-700 px-8">
            <a href="index.php?type=clothe">Ruházat</a>
            <a href="index.php?type=shoe">Cipők</a>
            <a href="index.php?type=accessory">Kiegészítők</a>
            <a href="index.php?sale=1">Akció</a>
            <a href="index.php?new=1">Újdonságok</a>
        </div>
    </div>

</nav>

<!-- USER DROPDOWN JS -->
<script>
document.addEventListener("DOMContentLoaded", function () {
    const btn = document.getElementById("userMenuBtn");
    const dropdown = document.getElementById("userDropdown");

    btn.addEventListener("click", function (e) {
        e.stopPropagation();
        dropdown.classList.toggle("hidden");
    });

    document.addEventListener("click", function () {
        dropdown.classList.add("hidden");
    });
});
</script>
