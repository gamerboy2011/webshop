<?php
$email = $_SESSION['registration_email'] ?? null;
$name = $_SESSION['registration_name'] ?? null;
$devLink = $_SESSION['dev_activation_link'] ?? null;

// Session adatok törlése (csak egyszer jelenjen meg)
unset($_SESSION['registration_email'], $_SESSION['registration_name'], $_SESSION['dev_activation_link']);

if (!$email) {
    header('Location: /webshop/');
    exit;
}
?>

<div class="max-w-lg mx-auto px-4 py-16">
    <div class="bg-white border rounded-2xl p-10 text-center">
        <!-- Email ikon -->
        <div class="w-24 h-24 mx-auto mb-6 bg-blue-100 rounded-full flex items-center justify-center">
            <i class="las la-envelope text-5xl text-blue-500"></i>
        </div>
        
        <h1 class="text-2xl font-bold mb-4">Ellenőrizd az email fiókodat!</h1>
        
        <p class="text-gray-600 mb-2">
            Küldtünk egy aktivációs linket a következő címre:
        </p>
        
        <p class="font-semibold text-lg mb-6">
            <?= htmlspecialchars($email) ?>
        </p>
        
        <div class="bg-gray-50 rounded-lg p-4 mb-6 text-left">
            <p class="text-sm text-gray-600">
                <i class="las la-info-circle mr-1"></i>
                Kattints az emailben található linkre a fiókod aktiválásához. 
                Ha nem találod az emailt, nézd meg a spam/levélszemét mappát is.
            </p>
        </div>
        
        <?php if ($devLink): ?>
            <!-- Fejlesztői mód - aktivációs link -->
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                <p class="text-sm text-yellow-800 mb-3">
                    <i class="las la-code mr-1"></i>
                    <strong>Fejlesztői mód:</strong> Email szerver nincs beállítva.
                </p>
                <a href="<?= htmlspecialchars($devLink) ?>" 
                   class="inline-block bg-yellow-500 text-white px-6 py-3 rounded-lg font-medium hover:bg-yellow-600 transition">
                    <i class="las la-external-link-alt mr-1"></i>
                    Fiók aktiválása most
                </a>
            </div>
        <?php endif; ?>
        
        <div class="border-t pt-6">
            <p class="text-sm text-gray-500 mb-4">Nem kaptál emailt?</p>
            <a href="/webshop/register" class="text-black font-medium hover:underline">
                <i class="las la-redo-alt mr-1"></i>
                Próbáld újra a regisztrációt
            </a>
        </div>
    </div>
    
    <p class="text-center text-sm text-gray-400 mt-6">
        Már aktiváltad? 
        <a href="/webshop/login" class="text-black hover:underline">Jelentkezz be</a>
    </p>
</div>
