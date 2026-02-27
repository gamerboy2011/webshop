<?php
// Bejelentkezés ellenőrzése
if (empty($_SESSION['user_id'])) {
    header('Location: /webshop/login?redirect=checkout');
    exit;
}

// Kosár ellenőrzése
$cart = $_SESSION['cart'] ?? [];
if (empty($cart)) {
    header('Location: /webshop/kosar');
    exit;
}

// Felhasználó adatai
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Kosár elemek betöltése
$items = [];
$subtotal = 0;
$cartProductTypeIds = []; // Kosárban lévő terméktípusok ID-i
$cartProductSubtypeIds = []; // Kosárban lévő alkategóriák ID-i

foreach ($cart as $cartItem) {
    $stmt = $pdo->prepare("
        SELECT p.product_id, p.name, p.price, p.subtype_id as product_subtype_id,
               ps.product_type_id,
               (SELECT src FROM product_img WHERE product_id = p.product_id ORDER BY position LIMIT 1) AS image
        FROM product p
        LEFT JOIN product_subtype ps ON p.subtype_id = ps.product_subtype_id
        WHERE p.product_id = ?
    ");
    $stmt->execute([$cartItem['product_id']]);
    $product = $stmt->fetch();
    
    if (!$product) continue;
    
    // Termék típus és alkategória ID gyűjtése
    if (!empty($product['product_type_id'])) {
        $cartProductTypeIds[$product['product_type_id']] = true;
    }
    if (!empty($product['product_subtype_id'])) {
        $cartProductSubtypeIds[$product['product_subtype_id']] = true;
    }
    
    $stmt = $pdo->prepare("SELECT size_value FROM size WHERE size_id = ?");
    $stmt->execute([$cartItem['size_id']]);
    $sizeValue = $stmt->fetchColumn() ?: '-';
    
    $itemTotal = $product['price'] * $cartItem['quantity'];
    $subtotal += $itemTotal;
    
    $items[] = [
        'product_id' => $cartItem['product_id'],
        'size_id' => $cartItem['size_id'],
        'name' => $product['name'],
        'price' => $product['price'],
        'image' => $product['image'],
        'size' => $sizeValue,
        'quantity' => $cartItem['quantity'],
        'total' => $itemTotal,
        'product_type_id' => $product['product_type_id'],
        'product_subtype_id' => $product['product_subtype_id']
    ];
}

$cartProductTypeIds = array_keys($cartProductTypeIds);
$cartProductSubtypeIds = array_keys($cartProductSubtypeIds);

// Felhasználó elérhető kuponjai (aktivált, nem használt, érvényes)
// Szűrés: általános VAGY megfelelő főkategória VAGY megfelelő alkategória
$userCoupons = [];
$stmt = $pdo->prepare("
    SELECT c.*, pt.name as product_type_name, ps.name as product_subtype_name
    FROM user_coupons uc
    JOIN coupons c ON uc.coupon_id = c.id
    LEFT JOIN product_type pt ON c.product_type_id = pt.product_type_id
    LEFT JOIN product_subtype ps ON c.product_subtype_id = ps.product_subtype_id
    WHERE uc.user_id = ?
      AND uc.used_at IS NULL
      AND c.is_active = 1
      AND CURDATE() BETWEEN c.start_date AND c.end_date
    ORDER BY c.amount DESC
");
$stmt->execute([$_SESSION['user_id']]);
$allCoupons = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Szűrjük a kuponokat a kosár tartalma alapján
foreach ($allCoupons as $coupon) {
    // Általános kupon (nincs kategória megkötés)
    if (empty($coupon['product_type_id']) && empty($coupon['product_subtype_id'])) {
        $userCoupons[] = $coupon;
        continue;
    }
    // Alkategória kupon
    if (!empty($coupon['product_subtype_id'])) {
        if (in_array($coupon['product_subtype_id'], $cartProductSubtypeIds)) {
            $userCoupons[] = $coupon;
        }
        continue;
    }
    // Főkategória kupon
    if (!empty($coupon['product_type_id'])) {
        if (in_array($coupon['product_type_id'], $cartProductTypeIds)) {
            $userCoupons[] = $coupon;
        }
    }
}

// Típus nevek magyarul
$typeNames = [
    'Accessory' => 'Kiegészítők',
    'Clothe' => 'Ruházat',
    'Shoe' => 'Cipők'
];

$subtypeNames = [
    'bag' => 'Táska',
    'cap' => 'Sapka',
    'hat' => 'Kalap',
    'hoodie' => 'Kapucnis pulcsi',
    'jacket' => 'Dzseki',
    'jeans' => 'Farmer',
    'leggings' => 'Leggings',
    'sweater' => 'Pulóver',
    't-shirt' => 'Póló',
    'winter coat' => 'Télikabát',
    'sandals' => 'Szandál',
    'shoes' => 'Cipő'
];

$shippingCost = $subtotal >= 15000 ? 0 : 1490;
$total = $subtotal + $shippingCost;

// Közterület típusok betöltése
$streetTypes = [];
try {
    $stmt = $pdo->query("SELECT * FROM street_type ORDER BY CASE WHEN name = 'utca' THEN 0 WHEN name = 'út' THEN 1 ELSE 2 END, name");
    $streetTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}
?>

<div class="max-w-6xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold mb-8">
        <i class="las la-credit-card mr-2"></i>Pénztár
    </h1>
    
    <form method="post" action="/webshop/index.php" id="checkoutForm">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="place_order">
        
        <div class="grid lg:grid-cols-3 gap-8">
            <!-- BAL OLDAL - ŰRLAP -->
            <div class="lg:col-span-2 space-y-6">
                
                <!-- SZÁLLÍTÁSI MÓD -->
                <div class="bg-white border rounded-xl p-6">
                    <h2 class="text-lg font-semibold mb-4">
                        <i class="las la-truck mr-2 text-gray-500"></i>Szállítási mód
                    </h2>
                    
                    <div class="space-y-3">
                        <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:border-black transition delivery-option" data-type="delivery">
                            <input type="radio" name="delivery_method_id" value="2" class="w-5 h-5 text-black" required>
                            <div class="ml-4 flex-1">
                                <span class="font-medium">Házhoz szállítás</span>
                                <p class="text-sm text-gray-500">GLS futárszolgálat, 1-3 munkanap</p>
                            </div>
                            <span class="font-medium"><?= $subtotal >= 15000 ? 'Ingyenes' : '1 490 Ft' ?></span>
                        </label>
                        
                        <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:border-black transition delivery-option" data-type="foxpost">
                            <input type="radio" name="delivery_method_id" value="3" class="w-5 h-5 text-black">
                            <div class="ml-4 flex-1">
                                <span class="font-medium">FoxPost csomagautomata</span>
                                <p class="text-sm text-gray-500">Válassz az 1400+ automata közül</p>
                            </div>
                            <span class="font-medium"><?= $subtotal >= 15000 ? 'Ingyenes' : '990 Ft' ?></span>
                        </label>
                    </div>
                </div>
                
                <!-- HÁZHOZ SZÁLLÍTÁS ADATOK -->
                <div id="deliveryFields" class="bg-white border rounded-xl p-6 hidden">
                    <h2 class="text-lg font-semibold mb-4">
                        <i class="las la-map-marker mr-2 text-gray-500"></i>Szállítási cím
                    </h2>
                    
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Név *</label>
                            <input type="text" name="shipping_name" 
                                   value="<?= htmlspecialchars($user['username'] ?? '') ?>"
                                   class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-black">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Telefonszám *</label>
                            <input type="tel" name="shipping_phone" 
                                   value="<?= htmlspecialchars($user['phone'] ?? '') ?>"
                                   placeholder="+36 30 123 4567"
                                   class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-black">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Irányítószám *</label>
                            <input type="text" name="shipping_postcode" id="shipping_postcode"
                                   value="<?= htmlspecialchars($user['shipping_postcode'] ?? '') ?>"
                                   maxlength="4" pattern="[0-9]{4}"
                                   class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-black">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Város *</label>
                            <input type="text" name="shipping_city" id="shipping_city"
                                   value="<?= htmlspecialchars($user['shipping_city'] ?? '') ?>"
                                   readonly
                                   class="w-full border rounded-lg px-4 py-3 bg-gray-50 focus:outline-none">
                            <p class="text-xs text-gray-500 mt-1">Automatikusan kitöltődik az irányítószám alapján</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Közterület neve *</label>
                            <input type="text" name="shipping_street_name" id="shipping_street_name"
                                   value="<?= htmlspecialchars($user['shipping_street_name'] ?? '') ?>"
                                   placeholder="Példa"
                                   class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-black">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Közterület típusa *</label>
                            <select name="shipping_street_type" id="shipping_street_type"
                                    class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-black bg-white">
                                <option value="">Válassz...</option>
                                <?php foreach ($streetTypes as $type): ?>
                                    <option value="<?= htmlspecialchars($type['name']) ?>"
                                        <?= ($user['shipping_street_type'] ?? '') === $type['name'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($type['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Házszám *</label>
                            <input type="text" name="shipping_house_number" id="shipping_house_number"
                                   value="<?= htmlspecialchars($user['shipping_house_number'] ?? '') ?>"
                                   placeholder="12/A"
                                   class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-black">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Emelet, ajtó (opcionális)</label>
                            <input type="text" name="shipping_floor_door" id="shipping_floor_door"
                                   value="<?= htmlspecialchars($user['shipping_floor_door'] ?? '') ?>"
                                   placeholder="3. em. 4."
                                   class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-black">
                        </div>
                    </div>
                    <!-- Teljes cím hidden mező a rendszer számára -->
                    <input type="hidden" name="shipping_address" id="shipping_address_combined">
                </div>
                
                <!-- FOXPOST CSOMAGPONT VÁLASZTÓ -->
                <div id="foxpostFields" class="bg-white border rounded-xl p-6 hidden">
                    <h2 class="text-lg font-semibold mb-4">
                        <i class="las la-box mr-2 text-gray-500"></i>FoxPost csomagautomata
                    </h2>
                    
                    <!-- Kiválasztott pont megjelenítése -->
                    <div id="selectedFoxpost" class="hidden mb-4 p-4 bg-green-50 border border-green-200 rounded-lg">
                        <div class="flex items-start gap-3">
                            <i class="las la-check-circle text-2xl text-green-500 mt-1"></i>
                            <div>
                                <p class="font-medium text-green-800" id="foxpostName"></p>
                                <p class="text-sm text-green-600" id="foxpostAddress"></p>
                            </div>
                            <button type="button" onclick="changeFoxpost()" class="ml-auto text-sm text-green-700 hover:underline">Módosítás</button>
                        </div>
                    </div>
                    
                    <!-- FoxPost iframe -->
                    <div id="foxpostIframe" class="rounded-lg overflow-hidden border">
                        <iframe frameborder="0" loading="lazy" 
                                src="https://cdn.foxpost.hu/apt-finder/v1/app/?desktop_height=450&tablet_width=600&tablet_height=350&mobile_width=400&mobile_height=350" 
                                width="100%" height="450"></iframe>
                    </div>
                    
                    <!-- Hidden mezők a FoxPost adatoknak -->
                    <input type="hidden" name="foxpost_point_id" id="foxpost_point_id">
                    <input type="hidden" name="foxpost_point_name" id="foxpost_point_name">
                    <input type="hidden" name="foxpost_point_address" id="foxpost_point_address">
                </div>
                
                <!-- FIZETÉSI MÓD -->
                <div class="bg-white border rounded-xl p-6">
                    <h2 class="text-lg font-semibold mb-4">
                        <i class="las la-wallet mr-2 text-gray-500"></i>Fizetési mód
                    </h2>
                    
                    <div class="space-y-3">
                        <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:border-black transition">
                            <input type="radio" name="payment_method_id" value="1" class="w-5 h-5 text-black" required>
                            <div class="ml-4 flex-1">
                                <span class="font-medium">Bankkártyás fizetés</span>
                                <p class="text-sm text-gray-500">Visa, Mastercard, American Express</p>
                            </div>
                            <i class="lab la-cc-visa text-2xl text-gray-400 mr-2"></i>
                            <i class="lab la-cc-mastercard text-2xl text-gray-400"></i>
                        </label>
                        
                        <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:border-black transition">
                            <input type="radio" name="payment_method_id" value="2" class="w-5 h-5 text-black">
                            <div class="ml-4 flex-1">
                                <span class="font-medium">Utánvét</span>
                                <p class="text-sm text-gray-500">Fizetés a csomag átvételekor</p>
                            </div>
                            <span class="text-sm text-gray-500">+390 Ft</span>
                        </label>
                        
                        <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:border-black transition">
                            <input type="radio" name="payment_method_id" value="3" class="w-5 h-5 text-black">
                            <div class="ml-4 flex-1">
                                <span class="font-medium">Banki átutalás</span>
                                <p class="text-sm text-gray-500">Előre utalás, gyorsabb feldolgozás</p>
                            </div>
                        </label>
                    </div>
                </div>
                
                <!-- KUPONKÓD -->
                <div class="bg-white border rounded-xl p-6">
                    <h2 class="text-lg font-semibold mb-4">
                        <i class="las la-ticket-alt mr-2 text-gray-500"></i>Kuponkód
                    </h2>
                    
                    <?php if (!empty($userCoupons)): ?>
                        <!-- Aktivált kuponok választó -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Aktivált kuponjaim</label>
                            <div class="space-y-2 max-h-48 overflow-y-auto pr-2" id="couponList">
                                <?php foreach ($userCoupons as $uc): ?>
                                    <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:border-purple-300 transition coupon-option"
                                           data-coupon-id="<?= $uc['id'] ?>"
                                           data-coupon-amount="<?= (int)$uc['amount'] ?>"
                                           data-coupon-type="<?= $uc['product_type_id'] ?? 'all' ?>"
                                           data-coupon-subtype="<?= $uc['product_subtype_id'] ?? 'all' ?>">
                                        <input type="checkbox" name="selected_coupons[]" value="<?= $uc['id'] ?>" 
                                               class="w-5 h-5 text-purple-600 rounded coupon-checkbox">
                                        <div class="ml-3 flex-1">
                                            <span class="font-medium text-sm">
                                                <?= htmlspecialchars($uc['name'] ?: $uc['description']) ?>
                                            </span>
                                            <p class="text-xs text-gray-500">
                                                <?php
                                                if (!empty($uc['product_subtype_name'])) {
                                                    echo $subtypeNames[$uc['product_subtype_name']] ?? ucfirst($uc['product_subtype_name']);
                                                } elseif (!empty($uc['product_type_name'])) {
                                                    echo $typeNames[$uc['product_type_name']] ?? $uc['product_type_name'];
                                                } else {
                                                    echo 'Minden termékre';
                                                }
                                                ?>
                                            </p>
                                        </div>
                                        <span class="px-2 py-1 bg-purple-100 text-purple-700 text-sm font-bold rounded">
                                            -<?= (int)$uc['amount'] ?>%
                                        </span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <div class="border-t pt-4">
                            <p class="text-sm text-gray-500 mb-2">Vagy adj meg egy új kuponkódot:</p>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Szabad szöveges kuponkód mező -->
                    <div class="flex gap-2">
                        <input type="text" name="manual_coupon_code" id="manualCouponCode"
                               placeholder="Kuponkód beírása..."
                               class="flex-1 border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-purple-500">
                        <button type="button" id="checkCouponBtn"
                                class="px-4 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition">
                            <i class="las la-check"></i>
                        </button>
                    </div>
                    <div id="couponMessage" class="mt-2 text-sm hidden"></div>
                    
                    <!-- Alkalmazott kedvezmény -->
                    <div id="appliedDiscount" class="mt-4 p-3 bg-green-50 border border-green-200 rounded-lg hidden">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-medium text-green-800">Kupon alkalmazva!</p>
                                <p class="text-sm text-green-600" id="discountInfo"></p>
                            </div>
                            <span class="text-lg font-bold text-green-700" id="discountAmount"></span>
                        </div>
                    </div>
                </div>
                
                <!-- MEGJEGYZÉS -->
                <div class="bg-white border rounded-xl p-6">
                    <h2 class="text-lg font-semibold mb-4">
                        <i class="las la-comment mr-2 text-gray-500"></i>Megjegyzés (opcionális)
                    </h2>
                    <textarea name="note" rows="3" 
                              placeholder="Pl.: Kapucsengő kód, kézbesítési instrukciók..."
                              class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-black resize-none"></textarea>
                </div>
            </div>
            
            <!-- JOBB OLDAL - ÖSSZESÍTŐ -->
            <div class="lg:col-span-1">
                <div class="bg-gray-50 rounded-xl p-6 sticky top-4">
                    <h2 class="text-lg font-semibold mb-4">Rendelés összesítő</h2>
                    
                    <!-- TERMÉKEK -->
                    <div class="space-y-3 mb-4 max-h-64 overflow-y-auto">
                        <?php foreach ($items as $item): ?>
                            <div class="flex gap-3">
                                <?php if (!empty($item['image'])): ?>
                                    <img src="/webshop/<?= htmlspecialchars($item['image']) ?>" 
                                         alt="<?= htmlspecialchars($item['name']) ?>"
                                         class="w-16 h-16 object-cover rounded-lg flex-shrink-0">
                                <?php else: ?>
                                    <div class="w-16 h-16 bg-gray-200 rounded-lg flex items-center justify-center flex-shrink-0">
                                        <i class="las la-image text-gray-400"></i>
                                    </div>
                                <?php endif; ?>
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-sm truncate"><?= htmlspecialchars($item['name']) ?></p>
                                    <p class="text-xs text-gray-500">Méret: <?= htmlspecialchars($item['size']) ?> × <?= $item['quantity'] ?></p>
                                    <p class="text-sm font-medium"><?= number_format($item['total'], 0, ',', ' ') ?> Ft</p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="border-t pt-4 space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Részösszeg:</span>
                            <span id="subtotalDisplay"><?= number_format($subtotal, 0, ',', ' ') ?> Ft</span>
                        </div>
                        <div class="flex justify-between text-sm text-green-600" id="couponDiscountRow" style="display: none;">
                            <span>Kupon kedvezmény:</span>
                            <span id="couponDiscountDisplay">-0 Ft</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Szállítás:</span>
                            <span id="shippingCostDisplay"><?= $shippingCost === 0 ? 'Ingyenes' : number_format($shippingCost, 0, ',', ' ') . ' Ft' ?></span>
                        </div>
                        <div class="flex justify-between text-sm" id="codFeeRow" style="display: none;">
                            <span class="text-gray-600">Utánvét kezelési díj:</span>
                            <span>390 Ft</span>
                        </div>
                    </div>
                    
                    <div class="border-t mt-4 pt-4">
                        <div class="flex justify-between items-center">
                            <span class="text-lg font-bold">Összesen:</span>
                            <span class="text-2xl font-bold" id="totalDisplay"><?= number_format($total, 0, ',', ' ') ?> Ft</span>
                        </div>
                    </div>
                    
                    <input type="hidden" name="subtotal" value="<?= $subtotal ?>">
                    <input type="hidden" name="discount_amount" id="discountAmountHidden" value="0">
                    <input type="hidden" name="applied_coupon_ids" id="appliedCouponIds" value="">
                    
                    <button type="submit" id="submitBtn"
                            class="w-full mt-6 bg-black text-white py-4 rounded-lg font-medium text-lg hover:bg-gray-800 transition flex items-center justify-center gap-2">
                        <i class="las la-lock"></i>
                        Megrendelés elküldése
                    </button>
                    
                    <p class="text-xs text-gray-500 text-center mt-4">
                        A "Megrendelés elküldése" gombra kattintva elfogadod az 
                        <a href="/webshop/aszf" class="underline">ÁSZF</a>-et és az 
                        <a href="/webshop/adatvedelem" class="underline">Adatvédelmi tájékoztatót</a>.
                    </p>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
// Árak
const subtotal = <?= $subtotal ?>;
const freeShippingThreshold = 15000;
const deliveryCost = subtotal >= freeShippingThreshold ? 0 : 1490;
const foxpostCost = subtotal >= freeShippingThreshold ? 0 : 990;
const codFee = 390;

let currentShippingCost = 0;
let currentCodFee = 0;
let currentDiscount = 0;
let appliedCouponIds = [];
let manualCouponData = null;

// Kosár termék típusok, alkategóriák és árak
const cartItems = <?= json_encode(array_map(function($item) {
    return [
        'product_type_id' => $item['product_type_id'],
        'product_subtype_id' => $item['product_subtype_id'],
        'total' => $item['total']
    ];
}, $items)) ?>;

// Szállítási mód váltás
document.querySelectorAll('input[name="delivery_method_id"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const type = this.closest('.delivery-option').dataset.type;
        
        document.getElementById('deliveryFields').classList.toggle('hidden', type !== 'delivery');
        document.getElementById('foxpostFields').classList.toggle('hidden', type !== 'foxpost');
        
        // Szállítási költség frissítése
        if (type === 'delivery') {
            currentShippingCost = deliveryCost;
        } else if (type === 'foxpost') {
            currentShippingCost = foxpostCost;
        }
        
        updateTotal();
        updateRequiredFields(type);
    });
});

// Fizetési mód váltás (utánvét díj)
document.querySelectorAll('input[name="payment_method_id"]').forEach(radio => {
    radio.addEventListener('change', function() {
        currentCodFee = this.value === '2' ? codFee : 0;
        document.getElementById('codFeeRow').style.display = currentCodFee > 0 ? 'flex' : 'none';
        updateTotal();
    });
});

function updateTotal() {
    const total = subtotal - currentDiscount + currentShippingCost + currentCodFee;
    document.getElementById('totalDisplay').textContent = new Intl.NumberFormat('hu-HU').format(total) + ' Ft';
    document.getElementById('shippingCostDisplay').textContent = currentShippingCost === 0 ? 'Ingyenes' : new Intl.NumberFormat('hu-HU').format(currentShippingCost) + ' Ft';
    
    // Kedvezmény sor
    const discountRow = document.getElementById('couponDiscountRow');
    if (currentDiscount > 0) {
        discountRow.style.display = 'flex';
        document.getElementById('couponDiscountDisplay').textContent = '-' + new Intl.NumberFormat('hu-HU').format(currentDiscount) + ' Ft';
    } else {
        discountRow.style.display = 'none';
    }
    
    // Hidden mezők frissítése
    document.getElementById('discountAmountHidden').value = currentDiscount;
    document.getElementById('appliedCouponIds').value = appliedCouponIds.join(',');
}

function updateRequiredFields(type) {
    // Házhoz szállítás mezők required kezelése (kivéve emelet/ajtó)
    const deliveryInputs = document.querySelectorAll('#deliveryFields input');
    deliveryInputs.forEach(input => {
        // Az emelet/ajtó mező nem kötelező
        if (input.name === 'shipping_floor_door') {
            input.required = false;
        } else {
            input.required = (type === 'delivery');
        }
    });
}

// FoxPost pont választás
window.addEventListener('message', function(event) {
    // FoxPost iframe üzenet
    if (event.data && typeof event.data === 'string') {
        try {
            const apt = JSON.parse(event.data);
            if (apt.operator_id && apt.name) {
                document.getElementById('foxpost_point_id').value = apt.operator_id;
                document.getElementById('foxpost_point_name').value = apt.name;
                document.getElementById('foxpost_point_address').value = apt.address;
                
                document.getElementById('foxpostName').textContent = apt.name;
                document.getElementById('foxpostAddress').textContent = apt.address;
                
                document.getElementById('selectedFoxpost').classList.remove('hidden');
                document.getElementById('foxpostIframe').classList.add('hidden');
            }
        } catch (e) {
            // Nem JSON üzenet, ignoráljuk
        }
    }
}, false);

function changeFoxpost() {
    document.getElementById('selectedFoxpost').classList.add('hidden');
    document.getElementById('foxpostIframe').classList.remove('hidden');
    document.getElementById('foxpost_point_id').value = '';
    document.getElementById('foxpost_point_name').value = '';
    document.getElementById('foxpost_point_address').value = '';
}

// Irányítószám - város automatikus kitöltés
const postcodeInput = document.getElementById('shipping_postcode');
const cityInput = document.getElementById('shipping_city');

if (postcodeInput) {
    postcodeInput.addEventListener('input', async function() {
        const zip = this.value.replace(/\D/g, '');
        this.value = zip; // Csak számok
        
        if (zip.length === 4) {
            try {
                const res = await fetch('/webshop/api/postal.php?zip=' + zip);
                const data = await res.json();
                if (data.city && cityInput) {
                    cityInput.value = data.city;
                    cityInput.classList.remove('bg-gray-50');
                    cityInput.classList.add('bg-green-50');
                } else {
                    cityInput.value = '';
                    cityInput.classList.remove('bg-green-50');
                    cityInput.classList.add('bg-gray-50');
                }
            } catch (e) {}
        } else {
            cityInput.value = '';
            cityInput.classList.remove('bg-green-50');
            cityInput.classList.add('bg-gray-50');
        }
    });
    
    // Ha már van irányítószám, töltsük be a várost
    if (postcodeInput.value.length === 4 && !cityInput.value) {
        postcodeInput.dispatchEvent(new Event('input'));
    }
}

// Cím összeállítása a hidden mezőbe
function buildFullAddress() {
    const streetName = document.getElementById('shipping_street_name')?.value || '';
    const streetType = document.getElementById('shipping_street_type')?.value || '';
    const houseNumber = document.getElementById('shipping_house_number')?.value || '';
    const floorDoor = document.getElementById('shipping_floor_door')?.value || '';
    
    let address = streetName;
    if (streetType) address += ' ' + streetType;
    if (houseNumber) address += ' ' + houseNumber;
    if (floorDoor) address += ' ' + floorDoor;
    
    const combinedField = document.getElementById('shipping_address_combined');
    if (combinedField) {
        combinedField.value = address.trim();
    }
}

// Cím mezők változásakor frissítsük a hidden mezőt
['shipping_street_name', 'shipping_street_type', 'shipping_house_number', 'shipping_floor_door'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.addEventListener('input', buildFullAddress);
    if (el) el.addEventListener('change', buildFullAddress);
});

// ===============================
// KUPON KEZELÉS
// ===============================

// Kupon kedvezmény számítása
function calculateDiscount() {
    currentDiscount = 0;
    appliedCouponIds = [];
    
    // Aktivált kuponok feldolgozása (checkboxok)
    document.querySelectorAll('.coupon-checkbox:checked').forEach(checkbox => {
        const option = checkbox.closest('.coupon-option');
        const couponId = parseInt(option.dataset.couponId);
        const amount = parseInt(option.dataset.couponAmount);
        const typeId = option.dataset.couponType;
        const subtypeId = option.dataset.couponSubtype;
        
        appliedCouponIds.push(couponId);
        
        // Kedvezmény számítása a megfelelő terméktípusra/alkategóriára
        cartItems.forEach(item => {
            let matches = false;
            // Általános kupon
            if ((typeId === 'all' || typeId === '') && (subtypeId === 'all' || subtypeId === '')) {
                matches = true;
            }
            // Alkategória kupon
            else if (subtypeId && subtypeId !== 'all' && subtypeId !== '') {
                matches = (subtypeId == item.product_subtype_id);
            }
            // Főkategória kupon
            else if (typeId && typeId !== 'all' && typeId !== '') {
                matches = (typeId == item.product_type_id);
            }
            
            if (matches) {
                currentDiscount += Math.round(item.total * (amount / 100));
            }
        });
    });
    
    // Manuális kupon hozzáadása (ha van)
    if (manualCouponData && !appliedCouponIds.includes(manualCouponData.id)) {
        appliedCouponIds.push(manualCouponData.id);
        cartItems.forEach(item => {
            let matches = false;
            // Általános kupon
            if (!manualCouponData.product_type_id && !manualCouponData.product_subtype_id) {
                matches = true;
            }
            // Alkategória kupon
            else if (manualCouponData.product_subtype_id) {
                matches = (manualCouponData.product_subtype_id == item.product_subtype_id);
            }
            // Főkategória kupon
            else if (manualCouponData.product_type_id) {
                matches = (manualCouponData.product_type_id == item.product_type_id);
            }
            
            if (matches) {
                currentDiscount += Math.round(item.total * (manualCouponData.amount / 100));
            }
        });
    }
    
    updateTotal();
    updateAppliedDiscountUI();
}

function updateAppliedDiscountUI() {
    const appliedDiv = document.getElementById('appliedDiscount');
    if (currentDiscount > 0) {
        appliedDiv.classList.remove('hidden');
        document.getElementById('discountInfo').textContent = appliedCouponIds.length + ' kupon alkalmazva';
        document.getElementById('discountAmount').textContent = '-' + new Intl.NumberFormat('hu-HU').format(currentDiscount) + ' Ft';
    } else {
        appliedDiv.classList.add('hidden');
    }
}

// Checkbox kuponok eseménykezelő
document.querySelectorAll('.coupon-checkbox').forEach(checkbox => {
    checkbox.addEventListener('change', calculateDiscount);
});

// Manuális kuponkód ellenőrzés
const checkCouponBtn = document.getElementById('checkCouponBtn');
const couponMessage = document.getElementById('couponMessage');
const manualCouponInput = document.getElementById('manualCouponCode');

if (checkCouponBtn) {
    checkCouponBtn.addEventListener('click', async function() {
        const code = manualCouponInput.value.trim();
        if (!code) {
            showCouponMessage('Kérlek adj meg egy kuponkódot!', 'error');
            return;
        }
        
        checkCouponBtn.disabled = true;
        checkCouponBtn.innerHTML = '<i class="las la-spinner la-spin"></i>';
        
        try {
            const res = await fetch('/webshop/api/check-coupon.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    code: code, 
                    cart_type_ids: cartItems.map(i => i.product_type_id),
                    cart_subtype_ids: cartItems.map(i => i.product_subtype_id)
                })
            });
            const data = await res.json();
            
            if (data.success) {
                manualCouponData = data.coupon;
                showCouponMessage(data.message, 'success');
                manualCouponInput.value = '';
                manualCouponInput.disabled = true;
                calculateDiscount();
            } else {
                showCouponMessage(data.message, 'error');
            }
        } catch (e) {
            showCouponMessage('Hiba történt a kupon ellenőrzése közben.', 'error');
        }
        
        checkCouponBtn.disabled = false;
        checkCouponBtn.innerHTML = '<i class="las la-check"></i>';
    });
}

function showCouponMessage(msg, type) {
    couponMessage.classList.remove('hidden', 'text-green-600', 'text-red-600');
    couponMessage.classList.add(type === 'success' ? 'text-green-600' : 'text-red-600');
    couponMessage.innerHTML = '<i class="las ' + (type === 'success' ? 'la-check-circle' : 'la-times-circle') + ' mr-1"></i>' + msg;
}

// Form validáció
document.getElementById('checkoutForm').addEventListener('submit', function(e) {
    const deliveryMethod = document.querySelector('input[name="delivery_method_id"]:checked');
    
    if (!deliveryMethod) {
        e.preventDefault();
        alert('Kérlek válassz szállítási módot!');
        return;
    }
    
    // Házhoz szállítás ellenőrzés
    if (deliveryMethod.value === '2') {
        const postcode = document.getElementById('shipping_postcode')?.value;
        const city = document.getElementById('shipping_city')?.value;
        const streetName = document.getElementById('shipping_street_name')?.value;
        const streetType = document.getElementById('shipping_street_type')?.value;
        const houseNumber = document.getElementById('shipping_house_number')?.value;
        
        if (!postcode || postcode.length !== 4) {
            e.preventDefault();
            alert('Kérlek adj meg érvényes irányítószámot!');
            return;
        }
        if (!city) {
            e.preventDefault();
            alert('Érvénytelen irányítószám - város nem található!');
            return;
        }
        if (!streetName) {
            e.preventDefault();
            alert('Kérlek add meg a közterület nevét!');
            return;
        }
        if (!streetType) {
            e.preventDefault();
            alert('Kérlek válaszd ki a közterület típusát!');
            return;
        }
        if (!houseNumber) {
            e.preventDefault();
            alert('Kérlek add meg a házszámot!');
            return;
        }
        
        // Teljes cím összerakása
        buildFullAddress();
    }
    
    // FoxPost ellenőrzés
    if (deliveryMethod.value === '3') {
        const foxpostId = document.getElementById('foxpost_point_id').value;
        if (!foxpostId) {
            e.preventDefault();
            alert('Kérlek válassz FoxPost csomagautomatát!');
            return;
        }
    }
    
    const paymentMethod = document.querySelector('input[name="payment_method_id"]:checked');
    if (!paymentMethod) {
        e.preventDefault();
        alert('Kérlek válassz fizetési módot!');
        return;
    }
});
</script>
