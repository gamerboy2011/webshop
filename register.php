<?php
require_once __DIR__ . "/app/config/database.php";

$success = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST["username"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    $passwordConfirm = $_POST["password_confirm"];

    /* NÉV: BETŰ + SZÓKÖZ + MAGYAR ÉKEZET */
    if (!preg_match("/^[A-Za-zÁÉÍÓÖŐÚÜŰáéíóöőúüű ]+$/u", $username)) {
    $error = "Name can only contain letters and spaces.";
    }   


    /* EMAIL */
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email address.";
    }

    /* JELSZÓ SZABÁLY */
    elseif (
        strlen($password) < 6 ||
        !preg_match("/[A-Z]/", $password) ||
        !preg_match("/[a-z]/", $password) ||
        !preg_match("/[0-9]/", $password)
    ) {
        $error = "Password must contain at least 1 uppercase, 1 lowercase letter and 1 number.";
    }

    /* JELSZÓ EGYEZÉS */
    elseif ($password !== $passwordConfirm) {
        $error = "Passwords do not match.";
    }

    else {
        /* EMAIL ELLENŐRZÉS */
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            $error = "Email already registered.";
        } else {

            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            /* IDEIGLENES ADATOK */
            $roleId = 1;
            $billingCityId = 1;
            $shippingCityId = 1;
            $billingStreet = "NOT SET";
            $shippingStreet = "NOT SET";

            $stmt = $pdo->prepare("
                INSERT INTO users
                (username, email, password_hash, role_id,
                 billing_city_id, billing_street,
                 shipping_city_id, shipping_street)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $username,
                $email,
                $passwordHash,
                $roleId,
                $billingCityId,
                $billingStreet,
                $shippingCityId,
                $shippingStreet
            ]);

            $success = "Succesfully registered";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register</title>
</head>
<body>

<h2>Register</h2>

<?php if ($error): ?>
    <p style="color:red"><?= $error ?></p>
<?php endif; ?>

<?php if ($success): ?>
    <p style="color:green"><?= $success ?></p>
<?php endif; ?>

<form method="post">
    <input type="text" name="username" placeholder="Name" required><br><br>
    <input type="email" name="email" placeholder="Email" required><br><br>
    <input type="password" name="password" placeholder="Password" required><br><br>
    <input type="password" name="password_confirm" placeholder="Confirm Password" required><br><br>

    <button type="submit">Register</button>
</form>

<p>
    <a href="login.php">If you already have an account</a>
</p>

</body>
</html>
