    <script src="./assets/js/main.js" ></script>
    <footer class="bg-black text-gray-300 mt-24">
    <div class="max-w-7xl mx-auto px-6 py-16 grid grid-cols-1 md:grid-cols-4 gap-10">

        <!-- BRAND -->
        <div>
            <h3 class="text-white text-xl font-semibold mb-4">
                Yoursy Wear
            </h3>
            <p class="text-sm leading-relaxed">
                Prémium streetwear és sneaker webshop.<br>
                100% autentikus termékek.
            </p>
        </div>

        <!-- SHOP -->
        <div>
            <h4 class="text-white font-medium mb-4">Shop</h4>
            <ul class="space-y-2 text-sm">
                <li><a href="?gender=female" class="hover:text-white">Női</a></li>
                <li><a href="?gender=male" class="hover:text-white">Férfi</a></li>
                <li><a href="?page=sale" class="hover:text-white">Akciók</a></li>
                <li><a href="?page=new" class="hover:text-white">Újdonságok</a></li>
            </ul>
        </div>

        <!-- INFO -->
        <div>
            <h4 class="text-white font-medium mb-4">Információ</h4>
            <ul class="space-y-2 text-sm">
                <li><a href="#" class="hover:text-white">Szállítás</a></li>
                <li><a href="#" class="hover:text-white">Visszaküldés</a></li>
                <li><a href="#" class="hover:text-white">ÁSZF</a></li>
                <li><a href="#" class="hover:text-white">Adatvédelem</a></li>
            </ul>
        </div>

        <!-- SOCIAL -->
        <div>
            <h4 class="text-white font-medium mb-4">Kövess minket</h4>
            <div class="flex gap-4 text-xl">
                <a href="#" class="hover:text-white">
                    <i class="fa-brands fa-instagram"></i>
                </a>
                <a href="#" class="hover:text-white">
                    <i class="fa-brands fa-facebook"></i>
                </a>
                <a href="#" class="hover:text-white">
                    <i class="fa-brands fa-tiktok"></i>
                </a>
            </div>
        </div>

    </div>

    <!-- BOTTOM BAR -->
    <div class="border-t border-gray-700">
        <div class="max-w-7xl mx-auto px-6 py-6 flex flex-col md:flex-row justify-between text-sm">
            <span>© <?= date('Y') ?> Yoursy Wear. Minden jog fenntartva.</span>
            <span class="mt-2 md:mt-0">
                <i class="fa-solid fa-lock mr-1"></i> Biztonságos fizetés
            </span>
        </div>
    </div>
</footer>
<script src="js/script.js"></script>
</body>
</html>