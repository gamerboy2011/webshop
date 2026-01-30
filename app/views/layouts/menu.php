
<?php
$cartCount = 0;
if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cartCount += (int)$item['quantity'];
    }
}
$currentGender = $_GET['gender'] ?? null;
?>

<nav class="w-full bg-white border-b">


    <!-- FELSŐ SÁV -->
    <div class="w-full  py-4">
        <div class="grid grid-cols-3 items-center w-full">

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

                <form method="get" action="index.php">
                    <input
                        type="text"
                        name="q"
                        placeholder="Keresés…"
                        class="w-56 px-4 py-2 text-sm border rounded-full
                               focus:outline-none focus:ring-1 focus:ring-black">
                </form>

                <a href="index.php?page=profile">
                    <i class="fa-regular fa-user text-xl"></i>
                </a>

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
        <div class="w-full  py-3 flex gap-8 text-sm font-medium text-gray-700">
            <a href="index.php?type=clothe">Ruházat</a>
            <a href="index.php?type=shoe">Cipők</a>
            <a href="index.php?type=accessory">Kiegészítők</a>
            <a href="index.php?sale=1">Akció</a>
            <a href="index.php?new=1">Újdonságok</a>
        </div>
    </div>

</nav>