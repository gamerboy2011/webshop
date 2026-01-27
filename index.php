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
   DB KAPCSOLAT
   ========================= */
require_once __DIR__ . "/app/config/database.php";

/* =========================
   AUTOLOAD (CONTROLLER + MODEL)
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
   ROUTER
   ========================= */
$page = $_GET['page'] ?? 'home';
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <?php require_once __DIR__ . "/app/views/layouts/head.php"; ?>
</head>

<body class="min-h-screen overflow-x-hidden bg-white text-gray-900">

<?php
/* =========================
   MENÜ
   ========================= */
require_once __DIR__ . "/app/views/layouts/menu.php";
?>

<main class="w-full">
<?php
switch ($page) {

    case 'product':
        (new ProductController())->show();
        break;

    default:
        (new ProductController())->index();
        break;
}
?>
</main>

<?php
/* =========================
   FOOTER
   ========================= */
require_once __DIR__ . "/app/views/layouts/footer.php";
?>

</body>
</html>