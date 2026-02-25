<?php
// Fizetési adatok ellenőrzése
if (empty($_SESSION['pending_payment'])) {
    header('Location: /webshop/checkout');
    exit;
}

$payment = $_SESSION['pending_payment'];
$total = $payment['total'];
$orderId = $payment['order_id'];
?>

<div class="min-h-screen bg-gray-100 py-12">
    <div class="max-w-lg mx-auto">
        
        <!-- Fizetési kártya -->
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
            
            <!-- Fejléc -->
            <div class="bg-gradient-to-r from-blue-600 to-blue-800 px-6 py-8 text-white">
                <div class="flex items-center justify-between mb-6">
                    <h1 class="text-xl font-bold">Biztonságos fizetés</h1>
                    <div class="flex gap-2">
                        <i class="lab la-cc-visa text-3xl"></i>
                        <i class="lab la-cc-mastercard text-3xl"></i>
                    </div>
                </div>
                <div class="text-center">
                    <p class="text-blue-200 text-sm mb-1">Fizetendő összeg</p>
                    <p class="text-4xl font-bold"><?= number_format($total, 0, ',', ' ') ?> Ft</p>
                    <p class="text-blue-200 text-sm mt-2">Rendelés #<?= $orderId ?></p>
                </div>
            </div>
            
            <!-- Űrlap -->
            <form id="paymentForm" class="p-6 space-y-5">
                
                <!-- Kártyaszám -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Kártyaszám</label>
                    <div class="relative">
                        <input type="text" id="cardNumber" 
                               placeholder="1234 5678 9012 3456"
                               maxlength="19"
                               class="w-full border-2 border-gray-200 rounded-lg px-4 py-3 pl-12 focus:outline-none focus:border-blue-500 transition font-mono text-lg tracking-wider">
                        <i class="las la-credit-card absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-xl"></i>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Teszt kártya: 4242 4242 4242 4242</p>
                </div>
                
                <!-- Név -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Kártyán szereplő név</label>
                    <input type="text" id="cardName" 
                           placeholder="KOVÁCS JÁNOS"
                           class="w-full border-2 border-gray-200 rounded-lg px-4 py-3 focus:outline-none focus:border-blue-500 transition uppercase">
                </div>
                
                <!-- Lejárat és CVV -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Lejárat</label>
                        <input type="text" id="cardExpiry" 
                               placeholder="MM/ÉÉ"
                               maxlength="5"
                               class="w-full border-2 border-gray-200 rounded-lg px-4 py-3 focus:outline-none focus:border-blue-500 transition font-mono text-center">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">CVV</label>
                        <div class="relative">
                            <input type="text" id="cardCvv" 
                                   placeholder="123"
                                   maxlength="3"
                                   class="w-full border-2 border-gray-200 rounded-lg px-4 py-3 focus:outline-none focus:border-blue-500 transition font-mono text-center">
                            <i class="las la-lock absolute right-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        </div>
                    </div>
                </div>
                
                <!-- Hiba üzenet -->
                <div id="errorMessage" class="hidden bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
                    <i class="las la-exclamation-circle mr-2"></i>
                    <span></span>
                </div>
                
                <!-- Gombok -->
                <div class="pt-4 space-y-3">
                    <button type="submit" id="payButton"
                            class="w-full bg-green-500 hover:bg-green-600 text-white font-bold py-4 rounded-lg transition flex items-center justify-center gap-2 text-lg">
                        <i class="las la-lock"></i>
                        Fizetés most
                    </button>
                    
                    <a href="/webshop/checkout" 
                       class="block w-full text-center text-gray-500 hover:text-gray-700 py-2 text-sm">
                        <i class="las la-arrow-left mr-1"></i>
                        Vissza a pénztárhoz
                    </a>
                </div>
                
            </form>
            
            <!-- Biztonsági infó -->
            <div class="bg-gray-50 px-6 py-4 border-t">
                <div class="flex items-center justify-center gap-6 text-xs text-gray-500">
                    <span class="flex items-center gap-1">
                        <i class="las la-shield-alt text-green-500"></i>
                        SSL titkosított
                    </span>
                    <span class="flex items-center gap-1">
                        <i class="las la-lock text-green-500"></i>
                        PCI DSS megfelelő
                    </span>
                    <span class="flex items-center gap-1">
                        <i class="las la-check-circle text-green-500"></i>
                        Biztonságos
                    </span>
                </div>
            </div>
            
        </div>
        
        <!-- Teszt mód figyelmeztetés -->
        <div class="mt-4 bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-center">
            <p class="text-yellow-800 text-sm">
                <i class="las la-info-circle mr-1"></i>
                <strong>Teszt mód</strong> - Használd a 4242 4242 4242 4242 kártyaszámot
            </p>
        </div>
        
    </div>
</div>

<!-- Feldolgozás overlay -->
<div id="processingOverlay" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-2xl p-8 text-center max-w-sm mx-4">
        <div class="animate-spin w-16 h-16 border-4 border-blue-500 border-t-transparent rounded-full mx-auto mb-4"></div>
        <p class="text-lg font-semibold text-gray-800">Fizetés feldolgozása...</p>
        <p class="text-sm text-gray-500 mt-2">Kérjük, ne zárd be az ablakot</p>
    </div>
</div>

<script>
// Kártyaszám formázás
document.getElementById('cardNumber').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    value = value.replace(/(\d{4})(?=\d)/g, '$1 ');
    e.target.value = value.substring(0, 19);
});

// Lejárat formázás
document.getElementById('cardExpiry').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length >= 2) {
        value = value.substring(0, 2) + '/' + value.substring(2, 4);
    }
    e.target.value = value;
});

// CVV csak számok
document.getElementById('cardCvv').addEventListener('input', function(e) {
    e.target.value = e.target.value.replace(/\D/g, '').substring(0, 3);
});

// Form submit
document.getElementById('paymentForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const cardNumber = document.getElementById('cardNumber').value.replace(/\s/g, '');
    const cardName = document.getElementById('cardName').value.trim();
    const cardExpiry = document.getElementById('cardExpiry').value;
    const cardCvv = document.getElementById('cardCvv').value;
    const errorDiv = document.getElementById('errorMessage');
    
    // Validáció
    if (cardNumber.length !== 16) {
        showError('Érvénytelen kártyaszám (16 számjegy szükséges)');
        return;
    }
    
    if (cardName.length < 3) {
        showError('Add meg a kártyán szereplő nevet');
        return;
    }
    
    if (!/^\d{2}\/\d{2}$/.test(cardExpiry)) {
        showError('Érvénytelen lejárati dátum (MM/ÉÉ)');
        return;
    }
    
    if (cardCvv.length !== 3) {
        showError('Érvénytelen CVV kód (3 számjegy)');
        return;
    }
    
    // Teszt kártya ellenőrzés (csak 4242... fogadunk el)
    const validTestCards = ['4242424242424242', '5555555555554444', '4000056655665556'];
    if (!validTestCards.includes(cardNumber)) {
        showError('Érvénytelen kártya. Teszt módban használd: 4242 4242 4242 4242');
        return;
    }
    
    // Feldolgozás
    errorDiv.classList.add('hidden');
    document.getElementById('processingOverlay').classList.remove('hidden');
    document.getElementById('processingOverlay').classList.add('flex');
    document.getElementById('payButton').disabled = true;
    
    // Szimulált feldolgozás (2 mp)
    setTimeout(function() {
        // Sikeres fizetés - átirányítás
        window.location.href = '/webshop/fizetes-sikeres';
    }, 2000);
});

function showError(message) {
    const errorDiv = document.getElementById('errorMessage');
    errorDiv.querySelector('span').textContent = message;
    errorDiv.classList.remove('hidden');
}
</script>
