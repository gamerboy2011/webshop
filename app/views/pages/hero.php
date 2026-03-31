<!-- HERO REKLÁM BANNER -->
<section id="hero" class="relative w-full">
    <!-- Kép - külön mobil és desktop verzió -->
    <picture>
        <source media="(max-width: 767px)" srcset="/webshop/public/images/reklam-mobile.png">
        <img src="/webshop/public/images/reklam.png" 
             alt="Yoursy Wear - Lépj be a nyárba" 
             class="w-full h-auto md:h-screen md:object-cover">
    </picture>
    
    <!-- Overlay gradient -->
    <div class="absolute inset-0 bg-gradient-to-b from-transparent via-transparent to-black/30 md:from-black/20 md:to-black/40"></div>
    
    <!-- Gombok a kép alján -->
    <div class="absolute bottom-4 md:bottom-12 left-0 right-0 flex flex-col sm:flex-row justify-center items-center gap-3 px-4">
        <a href="#products" 
           class="bg-white text-gray-900 px-5 py-2.5 md:px-12 md:py-5 rounded-full font-bold text-xs md:text-base uppercase tracking-wide shadow-lg hover:bg-gray-100 hover:scale-105 transition-all">
            Fedezd fel a termékeket
        </a>
        <a href="/webshop/akcio" 
           class="bg-orange-500 text-white px-5 py-2.5 md:px-12 md:py-5 rounded-full font-bold text-xs md:text-base uppercase tracking-wide shadow-lg hover:bg-orange-600 hover:scale-105 transition-all">
            Ugrás az akciókhoz
        </a>
    </div>
</section>
