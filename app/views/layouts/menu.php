<?php
$currentGender = $_GET['gender'] ?? null;
$currentType   = $_GET['type']   ?? null;
$isSale        = isset($_GET['sale']);
$isNew         = isset($_GET['new']);
?>

<nav class="w-full border-b bg-white">

    <!-- FELSŐ SÁV -->
    <div class="w-full px-8 py-5">
        <div class="grid grid-cols-3 items-center">

            <!-- BAL OLDAL -->
            <div class="flex items-center gap-10 justify-start">

                <!-- NEM -->
                <div class="flex gap-6 font-medium">
                    <a href="index.php?gender=male"
                       class="<?= $currentGender === 'male' ? 'underline' : '' ?>">
                        Férfi
                    </a>

                    <a href="index.php?gender=female"
                       class="<?= $currentGender === 'female' ? 'underline' : '' ?>">
                        Női
                    </a>
                </div>

                <!-- IKONOK -->
                <div class="flex gap-5 text-xl">
                    <a href="index.php?page=cart">
                        <i class="las la-shopping-cart"></i>
                    </a>
                    <a href="index.php?page=login">
                        <i class="las la-user"></i>
                    </a>
                </div>

            </div>

            <!-- LOGÓ -->
            <div class="flex justify-center">
                <a href="index.php" class="text-2xl font-bold tracking-wide">
                    Yoursy Wear
                </a>
            </div>

            <!-- KERESÉS -->
            <div class="flex justify-end">
                <input
                    type="text"
                    placeholder="Keresés…"
                    class="w-64 border px-4 py-2 rounded-full text-sm
                           focus:outline-none focus:ring-1 focus:ring-black"
                >
            </div>

        </div>
    </div>

    <!-- ALMENÜ -->
    <div class="w-full border-t">
        <div class="w-full px-8 py-3 flex gap-8 text-sm font-medium">

            <?php if ($currentGender): ?>

                <a href="index.php?gender=<?= $currentGender ?>&type=Clothe"
                   class="<?= $currentType === 'Clothe' ? 'underline font-bold' : '' ?>">
                    Ruházat
                </a>

                <a href="index.php?gender=<?= $currentGender ?>&type=Shoe"
                   class="<?= $currentType === 'Shoe' ? 'underline font-bold' : '' ?>">
                    Cipők
                </a>

                <a href="index.php?gender=<?= $currentGender ?>&type=Accessory"
                   class="<?= $currentType === 'Accessory' ? 'underline font-bold' : '' ?>">
                    Kiegészítők
                </a>

            <?php endif; ?>

            <a href="index.php?<?= $currentGender ? 'gender='.$currentGender.'&' : '' ?>sale=1"
               class="<?= $isSale ? 'underline font-bold' : '' ?>">
                Akció
            </a>

            <a href="index.php?<?= $currentGender ? 'gender='.$currentGender.'&' : '' ?>new=1"
               class="<?= $isNew ? 'underline font-bold' : '' ?>">
                Újdonságok
            </a>

        </div>
    </div>

</nav>