<?php
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
   RESET KOSÁR (TESZTHEZ)
   ========================= */
if (isset($_GET['reset_cart'])) {
    unset($_SESSION['cart']);
    echo "KOSÁR TÖRÖLVE";
    exit;
}

/* =========================
   DB KAPCSOLAT
   ========================= */
require_once __DIR__ . "/app/config/database.php";

/* =========================
   AUTOLOAD
   ========================= */
spl_autoload_register(function ($class) {
    foreach (['app/controllers', 'app/models'] as $dir) {
        $file = __DIR__ . "/$dir/$class.php";
        if (file_exists($file)) {
            require_once $file;
        }
    }
});

/* =========================
   ROUTER LOGIKA
   ========================= */
$page   = $_GET['page'] ?? 'home';
$method = $_SERVER['REQUEST_METHOD'];

/* =========================
   POST AKCIÓK (NINCS HTML)
   ========================= */
if ($page === 'checkout' && $method === 'POST') {
    (new OrderController())->checkout();
    exit;
}

if ($page === 'cart_add' && $method === 'POST') {
    (new CartController())->add();
    exit;
}

if ($page === 'cart_update' && $method === 'POST') {
    (new CartController())->update();
    exit;
}

if ($page === 'cart_remove' && $method === 'POST') {
    (new CartController())->remove();
    exit;
}
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <?php require_once __DIR__ . "/app/views/layouts/head.php"; ?>
</head>

<body class="min-h-screen overflow-x-hidden bg-white text-gray-900">

<?php require_once __DIR__ . "/app/views/layouts/menu.php"; ?>

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
    (new CartController())->index();
    break;

case 'cart_add':
    (new CartController())->add();
    break;

case 'cart_remove':
    (new CartController())->remove();
    break;

case 'checkout':
    (new OrderController())->checkout();
    break;
    default:
        (new ProductController())->index();
        break;
}
?>

</main>

<?php require_once __DIR__ . "/app/views/layouts/footer.php"; ?>

</body>
</html>