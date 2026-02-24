<?php
$orderId = $_SESSION['order_success'] ?? null;
unset($_SESSION['order_success']);

if (!$orderId) {
    header('Location: /webshop/');
    exit;
}
?>

<div class="max-w-2xl mx-auto px-4 py-16 text-center">
    <div class="bg-white border rounded-2xl p-12">
        <!-- Siker ikon -->
        <div class="w-24 h-24 mx-auto mb-8 bg-green-100 rounded-full flex items-center justify-center">
            <i class="las la-check text-5xl text-green-500"></i>
        </div>
        
        <h1 class="text-3xl font-bold mb-4">Köszönjük a rendelésed!</h1>
        
        <p class="text-gray-600 mb-2">
            Rendelésed sikeresen rögzítettük.
        </p>
        
        <div class="inline-block bg-gray-100 rounded-lg px-6 py-3 mb-8">
            <span class="text-gray-500">Rendelésszám:</span>
            <span class="font-bold text-xl ml-2">#<?= (int)$orderId ?></span>
        </div>
        
        <p class="text-gray-500 text-sm mb-8">
            Hamarosan küldünk egy visszaigazoló emailt a rendelésed részleteivel.
            <br>Kérdés esetén keress minket az <a href="mailto:info@yoursywear.hu" class="text-black underline">info@yoursywear.hu</a> címen.
        </p>
        
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="/webshop/" 
               class="inline-flex items-center justify-center gap-2 bg-black text-white px-8 py-4 rounded-lg font-medium hover:bg-gray-800 transition">
                <i class="las la-home"></i>
                Vissza a főoldalra
            </a>
            
            <?php if (!empty($_SESSION['user_id'])): ?>
                <a href="/webshop/profil" 
                   class="inline-flex items-center justify-center gap-2 border border-gray-300 px-8 py-4 rounded-lg font-medium hover:border-black transition">
                    <i class="las la-box"></i>
                    Rendeléseim
                </a>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Kapcsolat info -->
    <div class="mt-8 text-sm text-gray-500">
        <p>
            <i class="las la-truck mr-1"></i>
            Szállítási idő: 1-3 munkanap
        </p>
    </div>
</div>
