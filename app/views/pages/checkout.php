<?php
// ====== BELÉPÉS ELLENŐRZÉS ======
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?page=login");
    exit;
}

require_once __DIR__ . "/../../config/database.php";

// Ezeket a controller adja
// $items  → kosár tételek
// $total  → végösszeg

$userId = $_SESSION['user_id'];
$error = "";
$success = "";

// ====== REGEXEK ======
$regexCountry  = "/^[A-Za-zÁÉÍÓÖŐÚÜŰáéíóöőúüű ]{2,50}$/u";
$regexCity     = "/^[A-Za-zÁÉÍÓÖŐÚÜŰáéíóöőúüű \\-]{2,50}$/u";
$regexPostcode = "/^[0-9]{4,6}$/";
$regexStreet   = "/^[A-Za-zÁÉÍÓÖŐÚÜŰáéíóöőúüű0-9 .\\-\\/]{3,100}$/u";
$regexPhone    = "/^\\+[0-9]{9,15}$/";

// ====== PROFIL ADATOK ELŐTÖLTÉS ======
$stmt = $pdo->prepare("
    SELECT
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

// alapértékek
$billingCountry  = $user['billing_country']  ?? '';
$billingCity     = $user['billing_city']     ?? '';
$billingPostcode = $user['billing_postcode'] ?? '';
$billingStreet   = $user['billing_street']   ?? '';

$shippingCountry  = $user['shipping_country']  ?? '';
$shippingCity     = $user['shipping_city']     ?? '';
$shippingPostcode = $user['shipping_postcode'] ?? '';
$shippingStreet   = $user['shipping_street']   ?? '';

$phone = $user['phone'] ?? '';

// ====== POST KEZELÉS ======
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $deliveryMethod = $_POST['delivery_method_id'] ?? '1';

    // számlázási cím
    $billingCountry  = trim($_POST['billing_country'] ?? '');
    $billingCity     = trim($_POST['billing_city'] ?? '');
    $billingPostcode = trim($_POST['billing_postcode'] ?? '');
    $billingStreet   = trim($_POST['billing_street'] ?? '');

    // cím egyezés
    $sameAddress = isset($_POST['same_address']);

    if ($sameAddress) {
        $shippingCountry  = $billingCountry;
        $shippingCity     = $billingCity;
        $shippingPostcode = $billingPostcode;
        $shippingStreet   = $billingStreet;
    } else {
        $shippingCountry  = trim($_POST['shipping_country'] ?? '');
        $shippingCity     = trim($_POST['shipping_city'] ?? '');
        $shippingPostcode = trim($_POST['shipping_postcode'] ?? '');
        $shippingStreet   = trim($_POST['shipping_street'] ?? '');
    }

    $phone = trim($_POST['phone'] ?? '');

    // ====== VALIDÁCIÓ ======
    if (
        !preg_match($regexCountry, $billingCountry) ||
        !preg_match($regexCity, $billingCity) ||
        !preg_match($regexPostcode, $billingPostcode) ||
        !preg_match($regexStreet, $billingStreet) ||
        !preg_match($regexPhone, $phone)
    ) {
        $error = "Hibás számlázási cím vagy telefonszám.";
    }

    if ($deliveryMethod === '1' && !$error) {
        if (
            !preg_match($regexCountry, $shippingCountry) ||
            !preg_match($regexCity, $shippingCity) ||
            !preg_match($regexPostcode, $shippingPostcode) ||
            !preg_match($regexStreet, $shippingStreet)
        ) {
            $error = "Hibás szállítási cím.";
        }
    }

    if (!$error) {
        $success = "Adatok ellenőrizve, megrendelés feldolgozásra kész.";
    }
}
?>

<form method="post" action="index.php?page=checkout">

<main class="max-w-7xl mx-auto px-4 lg:px-8 py-8">
<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

<section class="lg:col-span-2 space-y-10">

<?php if ($error): ?>
<div class="bg-red-100 text-red-700 p-4 rounded-xl">
<?= htmlspecialchars($error) ?>
</div>
<?php endif; ?>

<?php if ($success): ?>
<div class="bg-green-100 text-green-700 p-4 rounded-xl">
<?= htmlspecialchars($success) ?>
</div>
<?php endif; ?>

<!-- SZÁLLÍTÁSI MÓD -->
<div class="border rounded-xl p-6 space-y-4">
<h2 class="text-lg font-semibold">Szállítási mód</h2>

<label class="flex items-center gap-3">
<input type="radio" name="delivery_method_id" value="1" checked>
<span>Házhozszállítás</span>
</label>

<label class="flex items-center gap-3">
<input type="radio" name="delivery_method_id" value="2">
<span>Csomagautomata</span>
</label>
</div>

<!-- SZÁMLÁZÁSI CÍM -->
<div class="border rounded-xl p-6 space-y-4">
<h2 class="text-lg font-semibold">Számlázási cím</h2>

<input name="billing_country" class="w-full border rounded p-3"
placeholder="Ország" required value="<?= htmlspecialchars($billingCountry) ?>">

<input name="billing_city" class="w-full border rounded p-3"
placeholder="Város" required value="<?= htmlspecialchars($billingCity) ?>">

<input name="billing_postcode" class="w-full border rounded p-3"
placeholder="Irányítószám" pattern="[0-9]{4,6}" required
value="<?= htmlspecialchars($billingPostcode) ?>">

<input name="billing_street" class="w-full border rounded p-3"
placeholder="Utca, házszám" required value="<?= htmlspecialchars($billingStreet) ?>">
</div>

<!-- SZÁLLÍTÁSI CÍM -->
<div class="border rounded-xl p-6 space-y-4">
<h2 class="text-lg font-semibold">Szállítási cím</h2>

<label class="flex items-center gap-2 text-sm">
<input type="checkbox" name="same_address" checked>
Megegyezik a számlázási címmel
</label>

<div class="space-y-3">
<input name="shipping_country" class="w-full border rounded p-3"
placeholder="Ország" value="<?= htmlspecialchars($shippingCountry) ?>">

<input name="shipping_city" class="w-full border rounded p-3"
placeholder="Város" value="<?= htmlspecialchars($shippingCity) ?>">

<input name="shipping_postcode" class="w-full border rounded p-3"
placeholder="Irányítószám" pattern="[0-9]{4,6}"
value="<?= htmlspecialchars($shippingPostcode) ?>">

<input name="shipping_street" class="w-full border rounded p-3"
placeholder="Utca, házszám" value="<?= htmlspecialchars($shippingStreet) ?>">
</div>
</div>

<!-- KAPCSOLAT -->
<div class="border rounded-xl p-6 space-y-4">
<h2 class="text-lg font-semibold">Kapcsolat</h2>

<input name="phone" class="w-full border rounded p-3"
placeholder="+36301234567" required value="<?= htmlspecialchars($phone) ?>">
</div>

<label class="flex gap-2 text-sm">
<input type="checkbox" required>
Elfogadom az ÁSZF-et és az adatkezelési tájékoztatót
</label>

</section>

<aside class="border rounded-xl p-6 space-y-6 sticky top-24">
<h3 class="text-lg font-semibold">Rendelési összegzés</h3>

<?php foreach ($items as $item): ?>
<div class="flex gap-4">
<img src="/uploads/<?= htmlspecialchars($item['image']) ?>"
class="w-20 h-24 object-cover rounded" alt="">
<div class="text-sm flex-1">
<div class="font-medium"><?= htmlspecialchars($item['name']) ?></div>
<div class="text-gray-500">
Méret: <?= htmlspecialchars($item['size']) ?> ·
Mennyiség: <?= (int)$item['quantity'] ?>
</div>
<div class="font-semibold mt-1">
<?= number_format($item['line_total'], 0, ',', ' ') ?> Ft
</div>
</div>
</div>
<?php endforeach; ?>

<div class="border-t pt-4 space-y-2 text-sm">
<div class="flex justify-between">
<span>Rendelési érték</span>
<span><?= number_format($total, 0, ',', ' ') ?> Ft</span>
</div>
<div class="flex justify-between">
<span>Szállítás</span>
<span>Ingyenes</span>
</div>
</div>

<div class="flex justify-between font-bold text-lg pt-4 border-t">
<span>Fizetendő</span>
<span><?= number_format($total, 0, ',', ' ') ?> Ft</span>
</div>

<button class="w-full bg-black text-white py-4 rounded-xl font-semibold">
Megrendelés leadása
</button>

</aside>

</div>
</main>
</form>
