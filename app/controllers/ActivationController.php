<?php

class ActivationController
{
    private User $userModel;

    public function __construct(PDO $pdo)
    {
        $this->userModel = new User($pdo);
    }

    public function activate(): void
    {
        $token = $_GET['token'] ?? '';

        if (empty($token)) {
            die("Érvénytelen aktivációs link.");
        }

        $user = $this->userModel->findByToken($token);

        if (!$user) {
            die("Érvénytelen vagy lejárt aktivációs token.");
        }

        $this->userModel->activateUser($user['user_id']);

        // Tailwind + automatikus átirányítás
        echo "
<!DOCTYPE html>
<html lang='hu'>
<head>
    <meta charset='UTF-8'>
    <title>Fiók aktiválva</title>
    <script src='https://cdn.tailwindcss.com'></script>

    <script>
        // 3 másodperc után átirányítás
        setTimeout(function() {
            window.location.href = '/webshop/login';
        }, 3000);
    </script>
</head>

<body class='bg-gray-100 flex items-center justify-center min-h-screen'>

    <div class='bg-white shadow-lg rounded-xl p-10 text-center max-w-md mx-auto'>
        
        <h1 class='text-3xl font-semibold text-gray-900 mb-4'>
            A fiókod sikeresen aktiválva!
        </h1>

        <p class='text-gray-600 mb-6'>
            Most már bejelentkezhetsz a Yoursy Wear webshopba.
        </p>

        <a href='/webshop/login'
           class='inline-block bg-black text-white px-6 py-3 rounded-lg text-lg font-medium hover:bg-gray-800 transition'>
            Bejelentkezés
        </a>

        <p class='text-sm text-gray-400 mt-6'>
            Automatikus átirányítás 3 másodperc múlva...
        </p>
    </div>

</body>
</html>
        ";
    }
}
