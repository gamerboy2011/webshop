<?php
// csak belépve
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . "/../../config/database.php";

$userId = $_SESSION['user_id'];
$success = "";
$error = "";

/* ADATOK LEKÉRÉSE */
$stmt = $pdo->prepare("
    SELECT
        billing_city_id,
        billing_street,
        shipping_city_id,
        shipping_street
    FROM users
    WHERE user_id = ?
");
$stmt->execute([$userId]);
$user = $stmt->fetch();

/* MENTÉS */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $billingCity   = trim($_POST['billing_city_id']);
    $billingStreet = trim($_POST['billing_street']);
    $shippingCity  = trim($_POST['shipping_city_id']);
    $shippingStreet= trim($_POST['shipping_street']);

    if (
        empty($billingCity) || empty($billingStreet) ||
        empty($shippingCity) || empty($shippingStreet)
    ) {
        $error = "All address fields are required.";
    } else {
        $stmt = $pdo->prepare("
            UPDATE users
            SET
                billing_city_id = ?,
                billing_street = ?,
                shipping_city_id = ?,
                shipping_street = ?
            WHERE user_id = ?
        ");
        $stmt->execute([
            $billingCity,
            $billingStreet,
            $shippingCity,
            $shippingStreet,
            $userId
        ]);

        $success = "Address data successfully saved.";
    }
}
?>

<div class="max-w-3xl mx-auto py-10">

    <h1 class="text-2xl font-semibold mb-6">My profile</h1>

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

    <form method="post" class="space-y-8">

        <!-- SZÁMLÁZÁSI CÍM -->
        <div>
            <h2 class="text-lg font-medium mb-3">Billing address</h2>

            <input
                class="w-full border p-2 rounded mb-3"
                name="billing_city_id"
                placeholder="Billing city"
                value="<?= htmlspecialchars($user['billing_city_id'] ?? '') ?>"
                required
            >

            <input
                class="w-full border p-2 rounded"
                name="billing_street"
                placeholder="Billing street"
                value="<?= htmlspecialchars($user['billing_street'] ?? '') ?>"
                required
            >
        </div>

        <!-- SZÁLLÍTÁSI CÍM -->
        <div>
            <h2 class="text-lg font-medium mb-3">Shipping address</h2>

            <input
                class="w-full border p-2 rounded mb-3"
                name="shipping_city_id"
                placeholder="Shipping city"
                value="<?= htmlspecialchars($user['shipping_city_id'] ?? '') ?>"
                required
            >

            <input
                class="w-full border p-2 rounded"
                name="shipping_street"
                placeholder="Shipping street"
                value="<?= htmlspecialchars($user['shipping_street'] ?? '') ?>"
                required
            >
        </div>

        <button
            class="bg-black text-white px-6 py-2 rounded hover:bg-gray-800"
        >
            Save changes
        </button>
    </form>

</div>
