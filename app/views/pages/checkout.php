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

foreach ($cart as $cartItem) {
    $stmt = $pdo->prepare("
        SELECT p.product_id, p.name, p.price,
               (SELECT src FROM product_img WHERE product_id = p.product_id ORDER BY position LIMIT 1) AS image
        FROM product p WHERE p.product_id = ?
    ");
    $stmt->execute([$cartItem['product_id']]);
    $product = $stmt->fetch();
    
    if (!$product) continue;
    
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
        'total' => $itemTotal
    ];
}

$shippingCost = $subtotal >= 15000 ? 0 : 1490;
$total = $subtotal + $shippingCost;
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
                            <input type="text" name="shipping_postcode" 
                                   value="<?= htmlspecialchars($user['shipping_postcode'] ?? '') ?>"
                                   maxlength="4"
                                   class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-black">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Város *</label>
                            <input type="text" name="shipping_city" 
                                   value="<?= htmlspecialchars($user['shipping_city'] ?? '') ?>"
                                   class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-black">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Utca, házszám *</label>
                            <input type="text" name="shipping_address" 
                                   value="<?= htmlspecialchars(trim(($user['shipping_street_name'] ?? '') . ' ' . ($user['shipping_street_type'] ?? '') . ' ' . ($user['shipping_house_number'] ?? ''))) ?>"
                                   placeholder="Példa utca 12. 3. em. 4. ajtó"
                                   class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-black">
                        </div>
                    </div>
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
                            <span><?= number_format($subtotal, 0, ',', ' ') ?> Ft</span>
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
    const total = subtotal + currentShippingCost + currentCodFee;
    document.getElementById('totalDisplay').textContent = new Intl.NumberFormat('hu-HU').format(total) + ' Ft';
    document.getElementById('shippingCostDisplay').textContent = currentShippingCost === 0 ? 'Ingyenes' : new Intl.NumberFormat('hu-HU').format(currentShippingCost) + ' Ft';
}

function updateRequiredFields(type) {
    // Házhoz szállítás mezők required kezelése
    const deliveryInputs = document.querySelectorAll('#deliveryFields input');
    deliveryInputs.forEach(input => {
        input.required = (type === 'delivery');
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
const postcodeInput = document.querySelector('input[name="shipping_postcode"]');
const cityInput = document.querySelector('input[name="shipping_city"]');

if (postcodeInput) {
    postcodeInput.addEventListener('input', async function() {
        const zip = this.value.replace(/\D/g, '');
        if (zip.length === 4) {
            try {
                const res = await fetch('/webshop/api/postal.php?zip=' + zip);
                const data = await res.json();
                if (data.city && cityInput) {
                    cityInput.value = data.city;
                }
            } catch (e) {}
        }
    });
}

if (cityInput) {
    let cityTimeout;
    cityInput.addEventListener('input', function() {
        clearTimeout(cityTimeout);
        const city = this.value.trim();
        if (city.length >= 3) {
            cityTimeout = setTimeout(async () => {
                try {
                    const res = await fetch('/webshop/api/postal.php?city=' + encodeURIComponent(city));
                    const data = await res.json();
                    if (data.zip && postcodeInput && !postcodeInput.value) {
                        postcodeInput.value = data.zip;
                    }
                } catch (e) {}
            }, 500);
        }
    });
}

// Form validáció
document.getElementById('checkoutForm').addEventListener('submit', function(e) {
    const deliveryMethod = document.querySelector('input[name="delivery_method_id"]:checked');
    
    if (!deliveryMethod) {
        e.preventDefault();
        alert('Kérlek válassz szállítási módot!');
        return;
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
