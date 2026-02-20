<?php
if (!isset($_SESSION['user_id'])) {
    header("Location: /webshop/login");
    exit;
}

require_once __DIR__ . "/../../library/config.php";

$userId  = $_SESSION['user_id'];
$section = $_GET['section'] ?? 'favorites';

/* ===== KEDVENCEK BETÖLTÉSE ===== */
$favorites = [];
if ($section === 'favorites') {
    $favModel = new FavouriteModel($pdo);
    $favorites = $favModel->getUserFavorites($userId);
}

$success = "";
$error   = "";

/* ===== FELHASZNÁLÓ ADATOK ===== */
$stmt = $pdo->prepare("
    SELECT
        username,
        email,

        shipping_postcode,
        shipping_city,
        shipping_street_name,
        shipping_street_type,
        shipping_house_number,

        billing_postcode,
        billing_city,
        billing_street_name,
        billing_street_type,
        billing_house_number,

        phone
    FROM users
    WHERE user_id = ?
");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

/* ===== MENTÉS ===== */
if ($section === 'security' && $_SERVER["REQUEST_METHOD"] === "POST") {

    // Szállítási cím (mindig kötelező)
    $shipping_postcode      = trim($_POST['shipping_postcode'] ?? '');
    $shipping_city          = trim($_POST['shipping_city'] ?? '');
    $shipping_street_name   = trim($_POST['shipping_street_name'] ?? '');
    $shipping_street_type   = trim($_POST['shipping_street_type'] ?? '');
    $shipping_house_number  = trim($_POST['shipping_house_number'] ?? '');

    // Számlázási cím (pipálható)
    $sameBilling = isset($_POST['sameBilling']);

    if ($sameBilling) {
        $billing_postcode      = $shipping_postcode;
        $billing_city          = $shipping_city;
        $billing_street_name   = $shipping_street_name;
        $billing_street_type   = $shipping_street_type;
        $billing_house_number  = $shipping_house_number;
    } else {
        $billing_postcode      = trim($_POST['billing_postcode'] ?? '');
        $billing_city          = trim($_POST['billing_city'] ?? '');
        $billing_street_name   = trim($_POST['billing_street_name'] ?? '');
        $billing_street_type   = trim($_POST['billing_street_type'] ?? '');
        $billing_house_number  = trim($_POST['billing_house_number'] ?? '');
    }

    $phone = trim($_POST['phone'] ?? '');

    $stmt = $pdo->prepare("
        UPDATE users SET
            shipping_postcode = ?,
            shipping_city = ?,
            shipping_street_name = ?,
            shipping_street_type = ?,
            shipping_house_number = ?,

            billing_postcode = ?,
            billing_city = ?,
            billing_street_name = ?,
            billing_street_type = ?,
            billing_house_number = ?,

            phone = ?
        WHERE user_id = ?
    ");

    $stmt->execute([
        $shipping_postcode,
        $shipping_city,
        $shipping_street_name,
        $shipping_street_type,
        $shipping_house_number,

        $billing_postcode,
        $billing_city,
        $billing_street_name,
        $billing_street_type,
        $billing_house_number,

        $phone,
        $userId
    ]);

    $success = "Profil adatok sikeresen mentve.";
}
?>

<div class="max-w-6xl mx-auto mt-12 grid grid-cols-1 md:grid-cols-4 gap-8">

    <aside class="bg-white p-6 rounded-xl shadow-md h-fit">
        <nav class="space-y-3 text-sm">

            <a href="profil?section=favorites"
                class="block px-4 py-2 rounded-lg font-medium 
               <?= $section === 'favorites' ? 'bg-black text-white' : 'hover:bg-gray-100' ?>">
                Kedvencek
            </a>

            <a href="profil?section=orders"
                class="block px-4 py-2 rounded-lg font-medium 
               <?= $section === 'orders' ? 'bg-black text-white' : 'hover:bg-gray-100' ?>">
                Rendeléseid
            </a>

            <a href="profil?section=security"
                class="block px-4 py-2 rounded-lg font-medium 
               <?= $section === 'security' ? 'bg-black text-white' : 'hover:bg-gray-100' ?>">
                Profil &amp; Biztonság
            </a>

            <a href="profil?section=settings"
                class="block px-4 py-2 rounded-lg font-medium 
               <?= $section === 'settings' ? 'bg-black text-white' : 'hover:bg-gray-100' ?>">
                Beállítások
            </a>

            <a href="profil?section=returns"
                class="block px-4 py-2 rounded-lg font-medium 
               <?= $section === 'returns' ? 'bg-black text-white' : 'hover:bg-gray-100' ?>">
                Visszaküldött termékek
            </a>

        </nav>
    </aside>

    <main class="md:col-span-3 bg-white p-8 rounded-xl shadow-md">

        <?php if ($section === 'favorites'): ?>

            <h2 class="text-2xl font-semibold mb-6">
                <i class="lar la-heart text-red-500 mr-2"></i>
                Kedvenceim
            </h2>

            <?php if (empty($favorites)): ?>
                <div class="text-center py-12">
                    <i class="lar la-heart text-gray-300 text-6xl mb-4"></i>
                    <p class="text-gray-500 text-lg mb-2">Még nincs kedvenc terméked</p>
                    <p class="text-gray-400 text-sm mb-6">Böngészd a termékeket és kattints a szív ikonra!</p>
                    <a href="/webshop/" class="inline-block bg-black text-white px-6 py-3 rounded-lg hover:bg-gray-800 transition">
                        Termékek böngészése
                    </a>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                    <?php foreach ($favorites as $product): ?>
                        <div class="group relative bg-white border rounded-lg overflow-hidden hover:shadow-lg transition-shadow">
                            
                            <!-- TÖRLÉS GOMB -->
                            <button onclick="removeFavorite(<?= $product['product_id'] ?>, this)"
                                    class="absolute top-2 right-2 z-10 w-8 h-8 bg-white rounded-full shadow flex items-center justify-center text-red-500 hover:bg-red-500 hover:text-white transition">
                                <i class="las la-times"></i>
                            </button>
                            
                            <a href="/webshop/termek/<?= $product['product_id'] ?>" class="block">
                                <div class="aspect-[3/4] bg-gray-100 overflow-hidden relative">
                                    <?php if (!empty($product['is_sale'])): ?>
                                        <span class="absolute top-2 left-2 bg-red-500 text-white text-xs font-bold px-2 py-1 rounded">
                                            -20%
                                        </span>
                                    <?php endif; ?>
                                    <?php if (!empty($product['image'])): ?>
                                        <img src="/webshop/<?= htmlspecialchars($product['image']) ?>" 
                                             alt="<?= htmlspecialchars($product['name']) ?>"
                                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                    <?php else: ?>
                                        <div class="w-full h-full flex items-center justify-center text-gray-400">
                                            <i class="las la-image text-4xl"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="p-3">
                                    <?php if (!empty($product['vendor_name'])): ?>
                                        <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">
                                            <?= htmlspecialchars($product['vendor_name']) ?>
                                        </p>
                                    <?php endif; ?>
                                    <h3 class="font-medium text-gray-900 group-hover:text-gray-600 transition-colors line-clamp-2 text-sm">
                                        <?= htmlspecialchars($product['name']) ?>
                                    </h3>
                                    <?php if (!empty($product['is_sale'])): ?>
                                        <div class="mt-2 flex items-center gap-2">
                                            <span class="text-gray-400 line-through text-xs">
                                                <?= number_format($product['price'], 0, ',', ' ') ?> Ft
                                            </span>
                                            <span class="text-red-600 font-bold text-sm">
                                                <?= number_format($product['sale_price'], 0, ',', ' ') ?> Ft
                                            </span>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-gray-900 font-bold mt-2 text-sm">
                                            <?= number_format($product['price'], 0, ',', ' ') ?> Ft
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <p class="text-center text-gray-400 text-sm mt-6">
                    <?= count($favorites) ?> termék a kedvenceid között
                </p>
            <?php endif; ?>

        <?php elseif ($section === 'security'): ?>

            <h2 class="text-2xl font-semibold mb-6">Profil &amp; Biztonság</h2>

            <?php if ($success): ?>
                <div class="bg-green-100 text-green-700 p-4 rounded mb-6 text-sm">
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <form method="post" class="space-y-10">

                <!-- SZÁLLÍTÁSI CÍM -->
                <div>
                    <h3 class="text-lg font-medium mb-4">Szállítási cím</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <!-- 1. sor -->
                        <input class="border p-2 rounded"
                            name="shipping_postcode"
                            placeholder="Irányítószám"
                            value="<?= htmlspecialchars($user['shipping_postcode'] ?? '') ?>">

                        <input class="border p-2 rounded"
                            name="shipping_city"
                            id="shipping_city"
                            placeholder="Város"
                            value="<?= htmlspecialchars($user['shipping_city'] ?? '') ?>">

                        <!-- 2. sor -->
                        <input class="border p-2 rounded"
                            name="shipping_street_name"
                            placeholder="Utca neve"
                            value="<?= htmlspecialchars($user['shipping_street_name'] ?? '') ?>">

                        <input class="border p-2 rounded"
                            name="shipping_street_type"
                            placeholder="Utca típusa (utca, út, tér...)"
                            value="<?= htmlspecialchars($user['shipping_street_type'] ?? '') ?>">

                        <!-- 3. sor -->
                        <input class="border p-2 rounded"
                            name="shipping_house_number"
                            placeholder="Házszám"
                            value="<?= htmlspecialchars($user['shipping_house_number'] ?? '') ?>">

                        <div></div>
                    </div>
                </div>

                <!-- SZÁMLÁZÁSI CÍM -->
                <div>
                    <h3 class="text-lg font-medium mb-4">Számlázási cím</h3>

                    <label class="flex items-center gap-2 mb-3 text-sm">
                        <input type="checkbox" id="sameBilling" name="sameBilling">
                        <span>A számlázási cím megegyezik a szállítási címmel</span>
                    </label>

                    <div id="billingFields" class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <!-- 1. sor -->
                        <input class="border p-2 rounded"
                            name="billing_postcode"
                            placeholder="Irányítószám"
                            value="<?= htmlspecialchars($user['billing_postcode'] ?? '') ?>">

                        <input class="border p-2 rounded"
                            name="billing_city"
                            id="billing_city"
                            placeholder="Város"
                            value="<?= htmlspecialchars($user['billing_city'] ?? '') ?>">

                        <!-- 2. sor -->
                        <input class="border p-2 rounded"
                            name="billing_street_name"
                            placeholder="Utca neve"
                            value="<?= htmlspecialchars($user['billing_street_name'] ?? '') ?>">

                        <input class="border p-2 rounded"
                            name="billing_street_type"
                            placeholder="Utca típusa (utca, út, tér...)"
                            value="<?= htmlspecialchars($user['billing_street_type'] ?? '') ?>">

                        <!-- 3. sor -->
                        <input class="border p-2 rounded"
                            name="billing_house_number"
                            placeholder="Házszám"
                            value="<?= htmlspecialchars($user['billing_house_number'] ?? '') ?>">

                        <div></div>
                    </div>
                </div>

                <!-- TELEFON -->
                <div>
                    <h3 class="text-lg font-medium mb-4">Kapcsolat</h3>
                    <input class="border p-2 rounded w-full"
                        name="phone"
                        placeholder="Telefonszám"
                        value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                </div>

                <button class="bg-black text-white px-8 py-2 rounded hover:bg-gray-800 text-sm">
                    Változtatások mentése
                </button>

            </form>

        <?php endif; ?>

    </main>
</div>

<!-- ===== JAVÍTOTT, VÉGLEGES SCRIPT BLOKK ===== -->
<script>

/* ===== AUTOMATIKUS VÁROSKITÖLTÉS (ZIP → CITY) ===== */
function autoFillCity(zipInputName, cityInputId) {
    const zipInput = document.querySelector(`input[name='${zipInputName}']`);
    const cityInput = document.getElementById(cityInputId);

    if (!zipInput || !cityInput) {
        console.log("Hiányzó mező:", zipInputName, cityInputId);
        return;
    }

    zipInput.addEventListener("keyup", function () {
        const zip = this.value.trim();
        console.log("ZIP input:", zip);

        if (zip.length === 4) {
            fetch("/webshop/app/api/getcity.php?zip=" + zip)
                .then(res => res.json())
                .then(data => {
                    console.log("City API válasz:", data);
                    cityInput.value = data.city || "";
                    cityInput.readOnly = true;
                })
                .catch(err => console.error("AJAX hiba:", err));
        } else {
            cityInput.value = "";
            cityInput.readOnly = false;
        }
    });
}

/* ===== AUTOMATIKUS IRÁNYÍTÓSZÁM KITÖLTÉS (CITY → ZIP) ===== */
function autoFillZip(cityInputName, zipInputName) {
    const cityInput = document.querySelector(`input[name='${cityInputName}']`);
    const zipInput  = document.querySelector(`input[name='${zipInputName}']`);

    if (!cityInput || !zipInput) {
        console.log("Hiányzó mező:", cityInputName, zipInputName);
        return;
    }

    cityInput.addEventListener("keyup", function () {
        const city = this.value.trim();

        // Csak akkor kérdezünk az API-tól, ha legalább 3 karakter van
        if (city.length >= 3) {
            fetch("/webshop/app/api/postcode.php?city=" + city)
                .then(res => res.json())
                .then(data => {
                    if (data.postcode) {
                        zipInput.value = data.postcode;
                    } else {
                        zipInput.value = "";
                    }
                })
                .catch(err => console.error("AJAX hiba:", err));
        } else {
            zipInput.value = "";
        }
    });
}



/* ===== FUNKCIÓK AKTIVÁLÁSA ===== */
autoFillCity("shipping_postcode", "shipping_city");
autoFillCity("billing_postcode", "billing_city");

autoFillZip("shipping_city", "shipping_postcode");
autoFillZip("billing_city", "billing_postcode");

/* ===== ENTER TILTÁSA ===== */
document.querySelectorAll("input").forEach(input => {
    input.addEventListener("keydown", function(e) {
        if (e.key === "Enter") e.preventDefault();
    });
});

/* ===== SZÁMLÁZÁSI CÍM MÁSOLÁSA ===== */
const sameBillingCheckbox = document.getElementById('sameBilling');
if (sameBillingCheckbox) {
    sameBillingCheckbox.addEventListener('change', function() {
        const fields = ['postcode', 'city', 'street_name', 'street_type', 'house_number'];

        fields.forEach(f => {
            const ship = document.querySelector(`[name='shipping_${f}']`);
            const bill = document.querySelector(`[name='billing_${f}']`);

            if (ship && bill) {
                if (this.checked) {
                    bill.value = ship.value;
                    bill.readOnly = true;
                } else {
                    bill.readOnly = false;
                }
            }
        });
    });
}

/* ===== KEDVENC ELTÁVOLÍTÁSA ===== */
function removeFavorite(productId, btn) {
    fetch('/webshop/favorite-toggle', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'product_id=' + productId
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            // Eltávolítjuk a kártyát animációval
            const card = btn.closest('.group');
            card.style.transition = 'opacity 0.3s, transform 0.3s';
            card.style.opacity = '0';
            card.style.transform = 'scale(0.9)';
            setTimeout(() => {
                card.remove();
                // Ha nincs több kedvenc, frissítsük az oldalt
                const remaining = document.querySelectorAll('.group.relative');
                if (remaining.length === 0) {
                    location.reload();
                }
            }, 300);
        }
    })
    .catch(err => console.error('Hiba:', err));
}

</script>

