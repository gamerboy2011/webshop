<?php
session_start();
require_once __DIR__ . "/app/config/database.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    $stmt = $pdo->prepare("
        SELECT user_id, username, password_hash, is_active, role_id
        FROM users
        WHERE email = ?
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (
        !$user ||
        !password_verify($password, $user['password_hash'])
    ) {
        $error = "Invalid email or password.";
    }
    elseif (!$user['is_active']) {
        $error = "Please activate your account first.";
    }
    else {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role_id'] = $user['role_id'];

        header("Location: index.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Login</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 flex items-center justify-center min-h-screen">

<div class="bg-white p-8 rounded shadow w-full max-w-md">

    <h2 class="text-2xl font-semibold mb-6 text-center">Login</h2>

    <?php if ($error): ?>
        <div class="bg-red-100 text-red-700 p-3 mb-4 rounded">
            <?= htmlspecialchars($error) ?>

            <?php if ($error === "Please activate your account first."): ?>
                <div class="mt-2">
                    <a href="resend_activation.php" class="text-blue-600 underline text-sm">
                        Resend activation email
                    </a>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <form method="post" class="space-y-4">
        <input class="w-full border p-2 rounded" name="email" type="email" placeholder="Email" required>
        <input class="w-full border p-2 rounded" name="password" type="password" placeholder="Password" required>
        <button class="w-full bg-black text-white py-2 rounded hover:bg-gray-800">
            Login
        </button>
    </form>

    <p class="text-sm text-center mt-4">
        <a href="register.php" class="text-blue-600 underline">
            If you don't have an account
        </a>
    </p>
</div>

</body>
</html>
