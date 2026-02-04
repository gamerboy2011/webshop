    <?php
require_once __DIR__ . "/app/config/database.php";

$message = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"]);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email address.";
    } else {

        $stmt = $pdo->prepare("
            SELECT user_id, username, is_active
            FROM users
            WHERE email = ?
        ");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            $error = "No account found with this email.";
        }
        elseif ($user['is_active']) {
            $error = "This account is already activated.";
        }
        else {
            // új token
            $token = bin2hex(random_bytes(32));

            $stmt = $pdo->prepare("
                UPDATE users
                SET activation_token = ?
                WHERE user_id = ?
            ");
            $stmt->execute([$token, $user['user_id']]);

            // email
            $activationLink = "http://localhost/webshop/webshop/activate.php?token=" . $token;

            $subject = "Resend activation - Yoursy Wear";
            $messageMail =
                "Hello {$user['username']},\n\n"
              . "Click the link below to activate your account:\n\n"
              . $activationLink;

            $headers = "From: noreply@yoursywear.hu";

            mail($email, $subject, $messageMail, $headers);

            $message = "Activation email sent successfully. Please check your inbox.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Resend activation</title>
</head>
<body>

<h2>Resend activation email</h2>

<?php if ($error): ?>
    <p style="color:red"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<?php if ($message): ?>
    <p style="color:green"><?= htmlspecialchars($message) ?></p>
<?php endif; ?>

<form method="post">
    <input type="email" name="email" placeholder="Email" required><br><br>
    <button type="submit">Resend activation</button>
</form>

<p>
    <a href="login.php">Back to login</a>
</p>

</body>
</html>
