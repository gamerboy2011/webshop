<?php
session_start();

/* HIBAKIÍRÁS FEJLESZTÉSKOR */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/* ADATBÁZIS */
require_once __DIR__ . "/app/config/database.php";

/* OLDAL MEGHATÁROZÁS */
$page = $_GET['page'] ?? 'home';
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>Yoursy Wear</title>

    <!-- TAILWIND -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- FONT AWESOME -->
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
    >
</head>
<body class="bg-gray-100">

<?php
/* MENÜ */
require_once __DIR__ . "/app/views/layouts/menu.php";
?>

<main class="min-h-screen">

<?php
/* ROUTER */
switch ($page) {

    case 'home':
        require_once __DIR__ . "/app/views/pages/home.php";
        break;

    case 'cart':
        require_once __DIR__ . "/app/views/pages/cart.php";
        break;

    case 'profile':
        require_once __DIR__ . "/app/views/pages/profile.php";
        break;

    case 'checkout':
        require_once __DIR__ . "/app/views/pages/checkout.php";
        break;

    default:
        require_once __DIR__ . "/app/views/pages/home.php";
        break;
}
?>

</main>

<?php
/* FOOTER – ha van */
if (file_exists(__DIR__ . "/app/views/layouts/footer.php")) {
    require_once __DIR__ . "/app/views/layouts/footer.php";
}
?>

</body>
</html>
