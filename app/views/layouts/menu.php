<nav class="w-full border-b bg-white">

    <!-- FELSŐ SÁV -->
    <div class="w-full px-8 py-5">
        <div class="grid grid-cols-3 items-center">

            <!-- BAL OLDAL: SHOP MENÜ + IKONOK -->
            <div class="flex items-center gap-10 justify-start">

                <!-- SHOP MENÜ -->
                <div class="flex gap-6 font-medium">
                    <a href="?gender=male" class="hover:underline">
                        Férfi
                    </a>
                    <a href="?gender=female" class="hover:underline">
                        Női
                    </a>
                </div>

                <!-- IKONOK -->
                <div class="flex gap-5 text-xl">
                    <a href="index.php?page=cart" class="hover:text-black">
                        <i class="las la-shopping-cart"></i>
                    </a>
                    <a href="index.php?page=login" class="hover:text-black">
                        <i class="las la-user"></i>
                    </a>
                </div>

            </div>

            <!-- KÖZÉP: LOGÓ -->
            <div class="flex justify-center">
                <a href="index.php" class="text-2xl font-bold tracking-wide">
                    Yoursy Wear
                </a>
            </div>

            <!-- JOBB OLDAL: KERESÉS -->
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

    <!-- ALMENÜ (FULL WIDTH, SZÉLEKRE) -->
    <div class="w-full border-t">
        <div class="w-full px-8 py-3 flex justify-between text-sm font-medium">

            <!-- BAL -->
            <div class="flex gap-8">
                <a href="?category=clothes" class="hover:underline">Ruházat</a>
                <a href="?category=shoes" class="hover:underline">Cipők</a>
                <a href="?category=accessories" class="hover:underline">Kiegészítők</a>
                <a href="?sale=1" class="hover:underline">Akció</a>
                <a href="?new=1" class="hover:underline">Újdonságok</a>
            </div>

        </div>
    </div>

</nav>