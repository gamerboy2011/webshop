<?php
$page = $_GET['page'] ?? 'home';
/* =========================
   HIBAKIÍRÁS (FEJLESZTÉSKOR)
   ========================= */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/* =========================
   SESSION
   ========================= */
session_start();

/* =========================
   DB KAPCSOLAT
   ========================= */
require_once __DIR__ . "/app/config/database.php";

/* =========================
   AUTOLOAD (MVC)
   ========================= */
spl_autoload_register(function ($class) {
    foreach (['app/controllers', 'app/models'] as $dir) {
        $file = __DIR__ . "/$dir/$class.php";
        if (file_exists($file)) {
            require_once $file;
        }
    }
});

// Ha van 'gender' és 'type' paraméter az URL-ben, elrejtjük a hero szakaszt
$hideHero = isset($_GET['gender']) || isset($_GET['type']);

/* =========================
   TESZT: KOSÁR RESET
   ========================= */
if (isset($_GET['reset_cart'])) {
    unset($_SESSION['cart']);
    header("Location: index.php");
    exit;
}

/* =========================
   ROUTER ALAP
   ========================= */
$page   = $_GET['page'] ?? 'home';
$method = $_SERVER['REQUEST_METHOD'];

/* =========================
   POST AKCIÓK (NINCS HTML!)
   ========================= */
if ($method === 'POST') {

    switch ($page) {

        case 'cart_add':
            (new CartController())->add();
            exit;

        case 'cart_update':
            (new CartController())->update();
            exit;

        case 'cart_remove':
            (new CartController())->remove();
            exit;

        case 'checkout':
            (new OrderController())->checkout();
            exit;
    }
}

/* =========================
   HTML KEZDÉS
   ========================= */
?>
<!DOCTYPE html>
<html lang="hu">

<head>
    <?php require __DIR__ . "/app/views/layouts/head.php"; ?>
</head>

<body class="min-h-screen overflow-x-hidden bg-white text-gray-900">

    <?php require __DIR__ . "/app/views/layouts/menu.php"; ?>

    <main class="w-full">

        <?php
        /* =========================
   GET OLDALAK (VIEW)
   ========================= */
        switch ($page) {

            case 'product':
                (new ProductController())->show();
                break;


            case 'cart':
                require_once __DIR__ . '/app/views/pages/cart.php';
                break;

            case 'checkout':
                require __DIR__ . "/app/views/pages/checkout.php";
                break;

            case 'home':
            default:
                (new ProductController())->index();
                break;
            case 'terms':
                require __DIR__ . "/app/views/pages/terms.php";
                break;

            case 'privacy':
                require __DIR__ . "/app/views/pages/privacy.php";
                break;

            case 'shipping':
                require __DIR__ . "/app/views/pages/shipping.php";
                break;

            case 'contact':
                require __DIR__ . "/app/views/pages/contact.php";
                break;
            case '404':
                require __DIR__ . "/app/views/components/404.php";
                break;

            case '500':
                require __DIR__ . "/app/views/components/500.php";
                break;
        }
        ?>

    </main>

    <?php require __DIR__ . "/app/views/layouts/footer.php"; ?>

</body>

</html>