<nav class="w-full bg-white border-b">

    <!-- FELSŐ SÁV -->
    <div class="max-w-7xl mx-auto px-6 py-4">
        <div class="grid grid-cols-3 items-center">

            <!-- BAL: IKONOK -->
            <div class="flex gap-6 text-xl text-gray-600">
                <a href="index.php?page=sale" class="hover:text-black">
                    <i class="fa-solid fa-percent"></i>
                </a>
                <a href="index.php?page=favorites" class="hover:text-black">
                    <i class="fa-regular fa-heart"></i>
                </a>
                <a href="index.php?page=cart" class="hover:text-black">
                    <i class="fa-solid fa-bag-shopping"></i>
                </a>
                <a href="index.php?page=profile" class="hover:text-black">
                    <i class="fa-regular fa-user"></i>
                </a>
            </div>

            <!-- LOGÓ -->
            <div class="flex justify-center">
                <a href="index.php" class="flex gap-1 text-xl font-semibold tracking-wide">
                    <span class="bg-black text-white px-3 py-1">Yoursy</span>
                    <span class="bg-black text-white px-3 py-1">Wear</span>
                </a>
            </div>

            <!-- SEARCH -->
            <div class="flex justify-end">
                <form method="get" action="index.php">
                    <input type="text" name="q"
                        placeholder="Keresés…"
                        class="w-56 px-4 py-2 text-sm border rounded-full focus:outline-none focus:ring-1 focus:ring-black">
                </form>
            </div>

        </div>
    </div>

    <!-- GENDER SÁV -->
    <div class="border-t">
        <div class="max-w-7xl mx-auto px-6">
            <div class="flex gap-12 py-4 text-lg font-medium">

                <a href="index.php?gender=female"
                   class="pb-1 border-b-2 transition
                   <?= $currentGender==='female'
                       ? 'border-black text-black'
                       : 'border-transparent text-gray-500 hover:text-black hover:border-black' ?>">
                    Női
                </a>

                <a href="index.php?gender=male"
                   class="pb-1 border-b-2 transition
                   <?= $currentGender==='male'
                       ? 'border-black text-black'
                       : 'border-transparent text-gray-500 hover:text-black hover:border-black' ?>">
                    Férfi
                </a>

            </div>
        </div>
    </div>

    <!-- SHOP MENÜ -->
    <div class="bg-gray-50 border-t">
        <div class="max-w-7xl mx-auto px-6 py-3 flex gap-8 text-sm font-medium text-gray-600">

            <a href="#">Kollekciók</a>
            <a href="#">Ruházat</a>
            <a href="#">Cipők</a>
            <a href="#">Kiegészítők</a>
            <a href="#">Streetwear</a>
            <a href="#">Premium</a>
            <a href="#">Top 100</a>
            <a href="index.php?brands=1" class="font-semibold text-black">Márkák</a>

        </div>
    </div>

    <!-- MÁRKÁK – DB-BŐL -->
    <?php if (isset($_GET['brands']) && !empty($brands)): ?>
        <div class="border-t bg-white">
            <div class="max-w-7xl mx-auto px-6 py-4 flex flex-wrap gap-6 text-sm">

                <?php foreach ($brands as $brand): ?>
                    <a
                        href="index.php?gender=<?= $currentGender ?>&brand=<?= $brand['vendor_id'] ?>"
                        class="<?= $currentBrand == $brand['vendor_id']
                            ? 'font-semibold underline'
                            : 'hover:underline' ?>">
                        <?= htmlspecialchars($brand['name']) ?>
                    </a>
                <?php endforeach; ?>

            </div>
        </div>
    <?php endif; ?>

</nav>
