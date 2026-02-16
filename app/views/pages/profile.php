<?php
if (!isset($_SESSION['user_id'])) {
    header("Location: /webshop/login");
    exit;
}

require_once __DIR__ . "/../../library/config.php";

$userId  = $_SESSION['user_id'];
$section = $_GET['section'] ?? 'favorites';

$success = "";
$error   = "";

/* ===== FELHASZNÁLÓ ADATOK ===== */
$stmt = $pdo->prepare("
    SELECT
        username,
        email,
        billing_country,
        billing_city,
        billing_postcode,
        billing_street,
        shipping_country,
        shipping_city,
        shipping_postcode,
        shipping_street,
        phone
    FROM users
    WHERE user_id = ?
");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

/* ===== MENTÉS ===== */
if ($section === 'security' && $_SERVER["REQUEST_METHOD"] === "POST") {

    $billingCountry  = trim($_POST['billing_country'] ?? '');
    $billingCity     = trim($_POST['billing_city'] ?? '');
    $billingPostcode = trim($_POST['billing_postcode'] ?? '');
    $billingStreet   = trim($_POST['billing_street'] ?? '');

    $shippingCountry  = trim($_POST['shipping_country'] ?? '');
    $shippingCity     = trim($_POST['shipping_city'] ?? '');
    $shippingPostcode = trim($_POST['shipping_postcode'] ?? '');
    $shippingStreet   = trim($_POST['shipping_street'] ?? '');

    $phone = trim($_POST['phone'] ?? '');

    $stmt = $pdo->prepare("
        UPDATE users SET
            billing_country = ?,
            billing_city = ?,
            billing_postcode = ?,
            billing_street = ?,
            shipping_country = ?,
            shipping_city = ?,
            shipping_postcode = ?,
            shipping_street = ?,
            phone = ?
        WHERE user_id = ?
    ");

    $stmt->execute([
        $billingCountry,
        $billingCity,
        $billingPostcode,
        $billingStreet,
        $shippingCountry,
        $shippingCity,
        $shippingPostcode,
        $shippingStreet,
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

    <h2 class="text-2xl font-semibold mb-6">Kedvenceid</h2>

    <?php
    $stmt = $pdo->prepare("
        SELECT 
            p.product_id,
            p.name,
            p.price,
            (
                SELECT src 
                FROM product_img 
                WHERE product_id = p.product_id 
                ORDER BY position ASC 
                LIMIT 1
            ) AS image
        FROM favorites f
        JOIN product p ON p.product_id = f.product_id
        WHERE f.user_id = :uid
        ORDER BY f.created_at DESC
    ");
    $stmt->execute(['uid' => $userId]);
    $favorites = $stmt->fetchAll(PDO::FETCH_ASSOC);
    ?>

    <?php if (empty($favorites)): ?>
        <p class="text-gray-600">Még nincs kedvenc terméked.</p>
    <?php else: ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($favorites as $product): ?>
                <a href="/webshop/termek/<?= $product['product_id'] ?>" class="block border rounded-lg p-4 shadow-sm hover:shadow-md transition">
                    <?php if (!empty($product['image'])): ?>
                        <img src="<?= htmlspecialchars($product['image']) ?>" class="w-full mb-3 rounded">
                    <?php endif; ?>
                    <p class="font-medium mb-1"><?= htmlspecialchars($product['name']) ?></p>
                    <p class="text-sm text-gray-700">
                        <?= number_format($product['price'], 0, ',', ' ') ?> Ft
                    </p>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

<?php elseif ($section === 'orders'): ?>

    <h2 class="text-2xl font-semibold mb-6">Rendeléseid</h2>
    <p class="text-gray-600">Itt fognak megjelenni a rendeléseid.</p>

<?php elseif ($section === 'security'): ?>

    <h2 class="text-2xl font-semibold mb-6">Profil &amp; Biztonság</h2>

    <?php if ($success): ?>
        <div class="bg-green-100 text-green-700 p-4 rounded mb-6 text-sm">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <form method="post" class="space-y-10">

        <div>
            <h3 class="text-lg font-medium mb-4">Személyes adatok</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm mb-1">Felhasználónév</label>
                    <input class="border p-2 rounded w-full bg-gray-50"
                           value="<?= htmlspecialchars($user['username'] ?? '') ?>" disabled>
                </div>
                <div>
                    <label class="block text-sm mb-1">Email cím</label>
                    <input class="border p-2 rounded w-full bg-gray-50"
                           value="<?= htmlspecialchars($user['email'] ?? '') ?>" disabled>
                </div>
            </div>
        </div>

        <div>
            <h3 class="text-lg font-medium mb-4">Számlázási cím</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <input class="border p-2 rounded" name="billing_country" value="<?= htmlspecialchars($user['billing_country'] ?? '') ?>">
                <input class="border p-2 rounded" name="billing_city" value="<?= htmlspecialchars($user['billing_city'] ?? '') ?>">
                <input class="border p-2 rounded" name="billing_postcode" value="<?= htmlspecialchars($user['billing_postcode'] ?? '') ?>">
                <input class="border p-2 rounded md:col-span-2" name="billing_street" value="<?= htmlspecialchars($user['billing_street'] ?? '') ?>">
            </div>
        </div>

        <div>
            <h3 class="text-lg font-medium mb-4">Szállítási cím</h3>

            <label class="flex items-center gap-2 mb-3 text-sm">
                <input type="checkbox" id="sameAddress" checked>
                <span>Megegyezik a számlázási címmel</span>
            </label>

            <div id="shippingFields" class="grid grid-cols-1 md:grid-cols-2 gap-4 opacity-50 pointer-events-none">
                <input class="border p-2 rounded" name="shipping_country" value="<?= htmlspecialchars($user['shipping_country'] ?? '') ?>">
                <input class="border p-2 rounded" name="shipping_city" value="<?= htmlspecialchars($user['shipping_city'] ?? '') ?>">
                <input class="border p-2 rounded" name="shipping_postcode" value="<?= htmlspecialchars($user['shipping_postcode'] ?? '') ?>">
                <input class="border p-2 rounded md:col-span-2" name="shipping_street" value="<?= htmlspecialchars($user['shipping_street'] ?? '') ?>">
            </div>
        </div>

        <div>
            <h3 class="text-lg font-medium mb-4">Kapcsolat</h3>
            <input class="border p-2 rounded w-full" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
        </div>

        <button class="bg-black text-white px-8 py-2 rounded hover:bg-gray-800 text-sm">
            Változtatások mentése
        </button>

    </form>

    <script>
    const sameAddress = document.getElementById('sameAddress');
    const shippingFields = document.getElementById('shippingFields');

    sameAddress.addEventListener('change', () => {
        if (sameAddress.checked) {
            shippingFields.classList.add('opacity-50', 'pointer-events-none');
        } else {
            shippingFields.classList.remove('opacity-50', 'pointer-events-none');
        }
    });
    </script>

<?php elseif ($section === 'settings'): ?>

    <h2 class="text-2xl font-semibold mb-6">Beállítások</h2>
    <p class="text-gray-600">Később ide kerülnek a fiókbeállítások.</p>

<?php elseif ($section === 'returns'): ?>

    <h2 class="text-2xl font-semibold mb-6">Visszaküldött termékek</h2>
    <p class="text-gray-600">Még nem küldtél vissza terméket.</p>

<?php endif; ?>

    </main>
</div>
