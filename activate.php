<?php
require_once __DIR__ . "/app/config/database.php";

$success = false;
$error = "";

$token = $_GET['token'] ?? null;

if (!$token) {
    $error = "Invalid activation link.";
} else {

    $stmt = $pdo->prepare("
        SELECT user_id
        FROM users
        WHERE activation_token = ? AND is_active = 0
    ");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if (!$user) {
        $error = "Activation link is invalid or expired.";
    } else {

        $stmt = $pdo->prepare("
            UPDATE users
            SET is_active = 1,
                activation_token = NULL
            WHERE user_id = ?
        ");
        $stmt->execute([$user['user_id']]);

        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Account activation</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 flex items-center justify-center min-h-screen">

<div class="bg-white p-8 rounded shadow-md w-full max-w-md text-center">

<?php if ($success): ?>
    <h2 class="text-2xl font-semibold mb-4 text-green-600">
        Account activated
    </h2>
    <p class="mb-6">
        Your account has been successfully activated.
    </p>
    <a
        href="login.php"
        class="inline-block bg-black text-white px-6 py-2 rounded hover:bg-gray-800"
    >
        Go to login
    </a>

<?php else: ?>
    <h2 class="text-2xl font-semibold mb-4 text-red-600">
        Activation failed
    </h2>
    <p class="mb-6">
        <?= htmlspecialchars($error) ?>
    </p>
    <a
        href="resend_activation.php"
        class="inline-block bg-black text-white px-6 py-2 rounded hover:bg-gray-800"
    >
        Resend activation email
    </a>
<?php endif; ?>

</div>

</body>
</html>
