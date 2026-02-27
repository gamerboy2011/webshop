<?php
require_once __DIR__ . '/../../library/config.php';

// Kuponkód az URL-ből
$couponCode = $_GET['code'] ?? $_GET['id'] ?? null;

$message = null;
$messageType = null;
$coupon = null;

// Ha van kuponkód
if ($couponCode) {
    // Kupon keresése
    $stmt = $pdo->prepare("
        SELECT c.*, 
               pt.name as product_type_name,
               ps.name as product_subtype_name
        FROM coupons c
        LEFT JOIN product_type pt ON c.product_type_id = pt.product_type_id
        LEFT JOIN product_subtype ps ON c.product_subtype_id = ps.product_subtype_id
        WHERE c.coupon_pass = ? AND c.is_active = 1
    ");
    $stmt->execute([$couponCode]);
    $coupon = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$coupon) {
        $message = "Nincs ilyen kupon vagy már nem érvényes.";
        $messageType = "error";
    } else {
        // Dátum ellenőrzés
        $today = date('Y-m-d');
        if ($today < $coupon['start_date']) {
            $message = "Ez a kupon még nem aktív. Érvényesség kezdete: " . date('Y.m.d', strtotime($coupon['start_date']));
            $messageType = "warning";
        } elseif ($today > $coupon['end_date']) {
            $message = "Ez a kupon már lejárt. Érvényesség vége: " . date('Y.m.d', strtotime($coupon['end_date']));
            $messageType = "error";
        } else {
            // Kupon érvényes - bejelentkezés ellenőrzés
            if (empty($_SESSION['user_id'])) {
                $message = "A kupon aktiválásához kérjük, jelentkezz be!";
                $messageType = "login_required";
            } else {
                // Ellenőrzés: már aktiválta-e
                $stmt = $pdo->prepare("SELECT * FROM user_coupons WHERE user_id = ? AND coupon_id = ?");
                $stmt->execute([$_SESSION['user_id'], $coupon['id']]);
                $existing = $stmt->fetch();
                
                if ($existing) {
                    if ($existing['used_at']) {
                        $message = "Ezt a kupont már felhasználtad egy korábbi rendelésnél.";
                        $messageType = "info";
                    } else {
                        $message = "Ez a kupon már aktiválva van a fiókodban! A pénztárnál tudod felhasználni.";
                        $messageType = "success";
                    }
                } else {
                    // Aktiválás
                    $stmt = $pdo->prepare("INSERT INTO user_coupons (user_id, coupon_id) VALUES (?, ?)");
                    $stmt->execute([$_SESSION['user_id'], $coupon['id']]);
                    
                    $message = "Kupon sikeresen aktiválva! A pénztárnál tudod felhasználni.";
                    $messageType = "success";
                }
            }
        }
    }
}

// Felhasználó aktivált kuponjai (ha be van jelentkezve)
$userCoupons = [];
if (!empty($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("
        SELECT c.*, uc.activated_at, uc.used_at,
               pt.name as product_type_name,
               ps.name as product_subtype_name
        FROM user_coupons uc
        JOIN coupons c ON uc.coupon_id = c.id
        LEFT JOIN product_type pt ON c.product_type_id = pt.product_type_id
        LEFT JOIN product_subtype ps ON c.product_subtype_id = ps.product_subtype_id
        WHERE uc.user_id = ?
        ORDER BY uc.used_at IS NULL DESC, c.end_date ASC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $userCoupons = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Típus nevek magyarul
$typeNames = [
    'Accessory' => 'Kiegészítők',
    'Clothe' => 'Ruházat',
    'Shoe' => 'Cipők'
];
?>

<div class="min-h-screen bg-gray-50 py-12">
    <div class="max-w-2xl mx-auto px-4">
        
        <h1 class="text-3xl font-bold text-center mb-8">
            <i class="las la-ticket-alt text-purple-500 mr-2"></i>Kuponok
        </h1>
        
        <?php if ($couponCode && $coupon): ?>
            <!-- KUPON KÁRTYA -->
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden mb-8">
                <!-- Fejléc -->
                <div class="bg-gradient-to-r from-purple-600 to-indigo-600 px-6 py-8 text-white text-center">
                    <div class="text-5xl font-bold mb-2"><?= (int)$coupon['amount'] ?>%</div>
                    <div class="text-purple-200 text-sm uppercase tracking-wider">kedvezmény</div>
                </div>
                
                <!-- Tartalom -->
                <div class="p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-2">
                        <?= htmlspecialchars($coupon['name'] ?: $coupon['description']) ?>
                    </h2>
                    
                    <div class="space-y-2 text-sm text-gray-600 mb-4">
                        <p class="flex items-center gap-2">
                            <i class="las la-tag text-purple-500"></i>
                            Érvényes: 
                            <?php
                            if ($coupon['product_type_name']) {
                                echo $typeNames[$coupon['product_type_name']] ?? $coupon['product_type_name'];
                            } elseif ($coupon['product_subtype_name']) {
                                echo htmlspecialchars($coupon['product_subtype_name']);
                            } else {
                                echo 'Minden termékre';
                            }
                            ?>
                        </p>
                        <p class="flex items-center gap-2">
                            <i class="las la-calendar text-purple-500"></i>
                            <?= date('Y.m.d', strtotime($coupon['start_date'])) ?> - <?= date('Y.m.d', strtotime($coupon['end_date'])) ?>
                        </p>
                        <p class="flex items-center gap-2">
                            <i class="las la-key text-purple-500"></i>
                            Kód: <span class="font-mono font-bold"><?= htmlspecialchars($coupon['coupon_pass']) ?></span>
                        </p>
                    </div>
                    
                    <!-- Üzenet -->
                    <?php if ($message): ?>
                        <?php
                        $bgColor = match($messageType) {
                            'success' => 'bg-green-50 border-green-200 text-green-700',
                            'error' => 'bg-red-50 border-red-200 text-red-700',
                            'warning' => 'bg-yellow-50 border-yellow-200 text-yellow-700',
                            'info' => 'bg-blue-50 border-blue-200 text-blue-700',
                            'login_required' => 'bg-orange-50 border-orange-200 text-orange-700',
                            default => 'bg-gray-50 border-gray-200 text-gray-700'
                        };
                        $icon = match($messageType) {
                            'success' => 'la-check-circle',
                            'error' => 'la-times-circle',
                            'warning' => 'la-exclamation-triangle',
                            'info' => 'la-info-circle',
                            'login_required' => 'la-user-lock',
                            default => 'la-info-circle'
                        };
                        ?>
                        <div class="border rounded-lg p-4 <?= $bgColor ?>">
                            <p class="flex items-center gap-2">
                                <i class="las <?= $icon ?> text-xl"></i>
                                <?= htmlspecialchars($message) ?>
                            </p>
                            
                            <?php if ($messageType === 'login_required'): ?>
                                <div class="mt-4 flex gap-3">
                                    <a href="/webshop/login?redirect=<?= urlencode('/webshop/kuponok/' . $couponCode) ?>" 
                                       class="flex-1 bg-black text-white text-center py-2 rounded-lg hover:bg-gray-800 transition">
                                        Bejelentkezés
                                    </a>
                                    <a href="/webshop/register?redirect=<?= urlencode('/webshop/kuponok/' . $couponCode) ?>" 
                                       class="flex-1 border border-gray-300 text-center py-2 rounded-lg hover:bg-gray-50 transition">
                                        Regisztráció
                                    </a>
                                </div>
                            <?php elseif ($messageType === 'success'): ?>
                                <a href="/webshop/kosar" 
                                   class="mt-4 block bg-black text-white text-center py-2 rounded-lg hover:bg-gray-800 transition">
                                    Tovább a kosárhoz
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php elseif ($couponCode && !$coupon): ?>
            <!-- HIBA: Nincs ilyen kupon -->
            <div class="bg-white rounded-2xl shadow-lg p-8 text-center mb-8">
                <div class="w-20 h-20 mx-auto mb-4 bg-red-100 rounded-full flex items-center justify-center">
                    <i class="las la-times text-4xl text-red-500"></i>
                </div>
                <h2 class="text-xl font-bold text-gray-900 mb-2">Érvénytelen kupon</h2>
                <p class="text-gray-500"><?= htmlspecialchars($message) ?></p>
            </div>
        <?php else: ?>
            <!-- NINCS KUPONKÓD -->
            <div class="bg-white rounded-2xl shadow-lg p-8 text-center mb-8">
                <div class="w-20 h-20 mx-auto mb-4 bg-purple-100 rounded-full flex items-center justify-center">
                    <i class="las la-ticket-alt text-4xl text-purple-500"></i>
                </div>
                <h2 class="text-xl font-bold text-gray-900 mb-2">Kupon aktiválás</h2>
                <p class="text-gray-500 mb-6">
                    Add meg a kuponkódot az aktiváláshoz, vagy használd a kapott QR kódot!
                </p>
                
                <form method="get" action="/webshop/kuponok" class="max-w-sm mx-auto">
                    <div class="flex gap-2">
                        <input type="text" name="code" placeholder="Kuponkód..." 
                               class="flex-1 border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-purple-500"
                               required>
                        <button type="submit" 
                                class="bg-purple-600 text-white px-6 py-3 rounded-lg hover:bg-purple-700 transition">
                            <i class="las la-search"></i>
                        </button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
        
        <!-- FELHASZNÁLÓ KUPONJAI -->
        <?php if (!empty($_SESSION['user_id'])): ?>
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                <div class="px-6 py-4 border-b bg-gray-50">
                    <h3 class="font-bold text-lg">Az én kuponjaim</h3>
                </div>
                
                <?php if (empty($userCoupons)): ?>
                    <div class="p-8 text-center text-gray-500">
                        <i class="las la-ticket-alt text-4xl text-gray-300 mb-2"></i>
                        <p>Még nincs aktivált kuponod.</p>
                    </div>
                <?php else: ?>
                    <div class="divide-y">
                        <?php foreach ($userCoupons as $uc): ?>
                            <?php
                            $isExpired = date('Y-m-d') > $uc['end_date'];
                            $isUsed = !empty($uc['used_at']);
                            $isAvailable = !$isExpired && !$isUsed;
                            ?>
                            <div class="p-4 flex items-center gap-4 <?= !$isAvailable ? 'opacity-50' : '' ?>">
                                <!-- Kedvezmény badge -->
                                <div class="w-16 h-16 flex-shrink-0 bg-gradient-to-br <?= $isAvailable ? 'from-purple-500 to-indigo-600' : 'from-gray-400 to-gray-500' ?> rounded-xl flex items-center justify-center text-white">
                                    <div class="text-center">
                                        <div class="text-lg font-bold"><?= (int)$uc['amount'] ?>%</div>
                                    </div>
                                </div>
                                
                                <!-- Info -->
                                <div class="flex-1 min-w-0">
                                    <h4 class="font-medium text-gray-900 truncate">
                                        <?= htmlspecialchars($uc['name'] ?: $uc['description']) ?>
                                    </h4>
                                    <p class="text-sm text-gray-500">
                                        <?php
                                        if ($uc['product_type_name']) {
                                            echo $typeNames[$uc['product_type_name']] ?? $uc['product_type_name'];
                                        } elseif ($uc['product_subtype_name']) {
                                            echo htmlspecialchars($uc['product_subtype_name']);
                                        } else {
                                            echo 'Minden termékre';
                                        }
                                        ?>
                                    </p>
                                    <p class="text-xs text-gray-400">
                                        Érvényes: <?= date('m.d', strtotime($uc['end_date'])) ?>-ig
                                    </p>
                                </div>
                                
                                <!-- Státusz -->
                                <div class="flex-shrink-0">
                                    <?php if ($isUsed): ?>
                                        <span class="px-3 py-1 bg-gray-100 text-gray-600 text-xs font-medium rounded-full">
                                            Felhasználva
                                        </span>
                                    <?php elseif ($isExpired): ?>
                                        <span class="px-3 py-1 bg-red-100 text-red-600 text-xs font-medium rounded-full">
                                            Lejárt
                                        </span>
                                    <?php else: ?>
                                        <span class="px-3 py-1 bg-green-100 text-green-600 text-xs font-medium rounded-full">
                                            Aktív
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <!-- VÁSÁRLÁS LINK -->
        <div class="mt-8 text-center">
            <a href="/webshop/" class="text-purple-600 hover:text-purple-800 font-medium">
                <i class="las la-arrow-left mr-1"></i>Vissza a vásárláshoz
            </a>
        </div>
        
    </div>
</div>
