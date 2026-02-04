<?php
require_once __DIR__ . "/app/config/database.php";

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST["username"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    $passwordConfirm = $_POST["password_confirm"];

    if (
        strlen($username) > 24 ||
        !preg_match("/^[A-Za-zÁÉÍÓÖŐÚÜŰáéíóöőúüű ]+$/u", $username)
    ) {
        $error = "Name can only contain letters and spaces (max 24 characters).";
    }
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email address.";
    }
    elseif (
        strlen($password) < 6 ||
        strlen($password) > 14 ||
        !preg_match("/[A-Z]/", $password) ||
        !preg_match("/[a-z]/", $password) ||
        !preg_match("/[0-9]/", $password)
    ) {
        $error = "Password must be 6–14 characters and contain uppercase, lowercase and a number.";
    }
    elseif ($password !== $passwordConfirm) {
        $error = "Passwords do not match.";
    }
    else {
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            $error = "Email already registered.";
        } else {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $token = bin2hex(random_bytes(32));

            $stmt = $pdo->prepare("
                INSERT INTO users
                (username, email, password_hash, role_id,
                 billing_city_id, billing_street,
                 shipping_city_id, shipping_street,
                 is_active, activation_token)
                VALUES (?, ?, ?, 1, 1, 'NOT SET', 1, 'NOT SET', 0, ?)
            ");

            $stmt->execute([$username, $email, $passwordHash, $token]);

            $activationLink = "http://localhost/webshop/webshop/activate.php?token=" . $token;

            $success = "Registration successful. Activate your account:<br>
                        <a class='text-blue-600 underline' href='$activationLink'>
                            Activate account
                        </a>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Register</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 flex items-center justify-center min-h-screen">

<div class="bg-white p-8 rounded shadow w-full max-w-md">

    <h2 class="text-2xl font-semibold mb-6 text-center">Register</h2>

    <?php if ($error): ?>
        <div class="bg-red-100 text-red-700 p-3 mb-4 rounded">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="bg-green-100 text-green-700 p-3 mb-4 rounded">
            <?= $success ?>
        </div>
    <?php endif; ?>

    <form method="post" class="space-y-4">
        <input class="w-full border p-2 rounded" name="username" placeholder="Name" required maxlength="24">
        <input class="w-full border p-2 rounded" name="email" type="email" placeholder="Email" required>
        <input class="w-full border p-2 rounded" name="password" type="password" placeholder="Password" required maxlength="14">
        <input class="w-full border p-2 rounded" name="password_confirm" type="password" placeholder="Confirm password" required maxlength="14">
        <button class="w-full bg-black text-white py-2 rounded hover:bg-gray-800">
            Register
        </button>
    </form>

    <p class="text-sm text-center mt-4">
        <a href="login.php" class="text-blue-600 underline">
            If you already have an account
        </a>
    </p>
</div>

</body>
</html>
