<?php
// Csak belépve
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . "/../../config/database.php";

$userId = $_SESSION['user_id'];
$success = "";
$error = "";

/* ===== REGEXEK ===== */
$regexCountry  = "/^[A-Za-zÁÉÍÓÖŐÚÜŰáéíóöőúüű ]{2,50}$/u";
$regexCity     = "/^[A-Za-zÁÉÍÓÖŐÚÜŰáéíóöőúüű \\-]{2,50}$/u";
$regexPostcode = "/^[0-9]{4,6}$/";
$regexStreet   = "/^[A-Za-zÁÉÍÓÖŐÚÜŰáéíóöőúüű0-9 .\\-\\/]{3,100}$/u";
$regexPhone    = "/^\\+[0-9]{9,15}$/";

/* ===== ADATOK LEKÉRÉSE ===== */
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
$user = $stmt->fetch();

/* ===== MENTÉS ===== */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $billingCountry  = trim($_POST['billing_country']);
    $billingCity     = trim($_POST['billing_city']);
    $billingPostcode = trim($_POST['billing_postcode']);
    $billingStreet   = trim($_POST['billing_street']);

    $shippingCountry  = trim($_POST['shipping_country']);
    $shippingCity     = trim($_POST['shipping_city']);
    $shippingPostcode = trim($_POST['shipping_postcode']);
    $shippingStreet   = trim($_POST['shipping_street']);

    $phone = trim($_POST['phone']);

    if (
        !preg_match($regexCountry, $billingCountry) ||
        !preg_match($regexCity, $billingCity) ||
        !preg_match($regexPostcode, $billingPostcode) ||
        !preg_match($regexStreet, $billingStreet) ||

        !preg_match($regexCountry, $shippingCountry) ||
        !preg_match($regexCity, $shippingCity) ||
        !preg_match($regexPostcode, $shippingPostcode) ||
        !preg_match($regexStreet, $shippingStreet) ||

        !preg_match($regexPhone, $phone)
    ) {
        $error = "Please provide valid address and phone number data.";
    } else {

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

        $success = "Profile data successfully saved.";
    }
}
?>

<div class="max-w-4xl mx-auto py-10">

    <h1 class="text-2xl font-semibold mb-8">My profile</h1>

    <?php if ($success): ?>
        <div class="bg-green-100 text-green-700 p-4 rounded mb-6">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="bg-red-100 text-red-700 p-4 rounded mb-6">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="post" class="space-y-10">

        <!-- ===== BILLING ===== -->
        <div>
            <h2 class="text-lg font-medium mb-4">Billing address</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <input class="border p-2 rounded" name="billing_country" placeholder="Country" required maxlength="50"
                       value="<?= htmlspecialchars($user['billing_country'] ?? '') ?>">
                <input class="border p-2 rounded" name="billing_city" placeholder="City name" required maxlength="50"
                       value="<?= htmlspecialchars($user['billing_city'] ?? '') ?>">
                <input class="border p-2 rounded" name="billing_postcode" placeholder="Postcode" required pattern="[0-9]{4,6}"
                       value="<?= htmlspecialchars($user['billing_postcode'] ?? '') ?>">
                <input class="border p-2 rounded md:col-span-2" name="billing_street" placeholder="Street address" required maxlength="100"
                       value="<?= htmlspecialchars($user['billing_street'] ?? '') ?>">
            </div>
        </div>

        <!-- ===== SHIPPING ===== -->
        <div>
            <h2 class="text-lg font-medium mb-4">Shipping address</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <input class="border p-2 rounded" name="shipping_country" placeholder="Country" required maxlength="50"
                       value="<?= htmlspecialchars($user['shipping_country'] ?? '') ?>">
                <input class="border p-2 rounded" name="shipping_city" placeholder="City name" required maxlength="50"
                       value="<?= htmlspecialchars($user['shipping_city'] ?? '') ?>">
                <input class="border p-2 rounded" name="shipping_postcode" placeholder="Postcode" required pattern="[0-9]{4,6}"
                       value="<?= htmlspecialchars($user['shipping_postcode'] ?? '') ?>">
                <input class="border p-2 rounded md:col-span-2" name="shipping_street" placeholder="Street address" required maxlength="100"
                       value="<?= htmlspecialchars($user['shipping_street'] ?? '') ?>">
            </div>
        </div>

        <!-- ===== PHONE ===== -->
        <div>
            <h2 class="text-lg font-medium mb-4">Contact</h2>

            <input class="border p-2 rounded w-full" name="phone" placeholder="+36301234567" required
                   value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
        </div>

        <button class="bg-black text-white px-8 py-2 rounded hover:bg-gray-800">
            Save changes
        </button>
    </form>

</div>
