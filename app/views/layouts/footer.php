<!-- BEJELENTKEZÉS SZÜKSÉGES MODAL -->
<div id="loginRequiredModal" class="fixed inset-0 z-[200] hidden">
    <div class="absolute inset-0 bg-black/50" onclick="closeLoginModal()"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-sm w-full p-8 text-center transform scale-95 opacity-0 transition-all duration-300" id="loginModalContent">
            <!-- Szomorú ikon -->
            <div class="w-20 h-20 mx-auto mb-6 bg-gray-100 rounded-full flex items-center justify-center">
                <i class="lar la-sad-tear text-5xl text-gray-400"></i>
            </div>
            
            <h3 class="text-xl font-bold text-gray-900 mb-2">Hoppá!</h3>
            <p class="text-gray-500 mb-6">
                A kedvencek használatához be kell jelentkezned.
            </p>
            
            <div class="flex flex-col gap-3">
                <a href="/webshop/login" 
                   class="w-full bg-black text-white py-3 px-6 rounded-lg font-medium hover:bg-gray-800 transition">
                    Bejelentkezés
                </a>
                <button onclick="closeLoginModal()" 
                        class="w-full border border-gray-300 py-3 px-6 rounded-lg font-medium text-gray-600 hover:bg-gray-50 transition">
                    Később
                </button>
            </div>
            
            <p class="mt-4 text-sm text-gray-400">
                Még nincs fiókod? 
                <a href="/webshop/register" class="text-black underline hover:no-underline">Regisztrálj!</a>
            </p>
        </div>
    </div>
</div>

<script>
function showLoginModal() {
    const modal = document.getElementById('loginRequiredModal');
    const content = document.getElementById('loginModalContent');
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    setTimeout(() => {
        content.classList.remove('scale-95', 'opacity-0');
        content.classList.add('scale-100', 'opacity-100');
    }, 10);
}

function closeLoginModal() {
    const modal = document.getElementById('loginRequiredModal');
    const content = document.getElementById('loginModalContent');
    content.classList.remove('scale-100', 'opacity-100');
    content.classList.add('scale-95', 'opacity-0');
    setTimeout(() => {
        modal.classList.add('hidden');
        document.body.style.overflow = '';
    }, 300);
}
</script>

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
                    <li><a href="index.php?page=contact">Kapcsolat</a></li>
                    <li><a href="index.php?page=shipping">Szállítás/Visszaküldés</a></li>
                    <li><a href="index.php?page=aszf">ÁSZF</a></li>
                   <li><a href="index.php?page=privacy">Adatvédelem</a></li>
                </ul>
            </div>

            <!-- SOCIAL -->
            <div>
                <h4 class="text-white font-medium mb-4">Kövess minket</h4>
                <div class="flex gap-4 text-xl">
                    <a href="#" class="hover:text-white">
                        <i class="lab la-instagram"></i>
                    </a>
                    <a href="https://www.facebook.com/profile.php?id=61587284315953" class="hover:text-white">
                        <i class="lab la-facebook"></i>
                    </a>
                    <a href="#" class="hover:text-white">
                        <i class="lab la-tiktok"></i>
                    </a>
                </div>
            </div>

        </div>

        <!-- BOTTOM BAR -->
        <div class="border-t border-gray-700">
            <div class="max-w-7xl mx-auto px-6 py-6 flex flex-col md:flex-row justify-between text-sm">
                <span>© <?= date('Y') ?> Yoursy Wear. Minden jog fenntartva.</span>
                <span class="mt-2 md:mt-0">
                    <i class="las la-lock mr-1"></i> Biztonságos fizetés
                </span>
            </div>
        </div>
    </footer>
    <script src="js/script.js"></script>
    </body>

    </html>