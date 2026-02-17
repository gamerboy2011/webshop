<?php
// Fejlesztési mód - VIZSGA ELŐTT EZEKET KIKAPCSOLNI!
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 1. CUSTOM FUNCTIONS BIZTONSÁGOS BETÖLTÉSE - ÚJ, JAVÍTOTT
$customFunctionsPath = __DIR__ . '/app/library/customfunctions.php';

if (!file_exists($customFunctionsPath)) {
    // Ha nem létezik, hozzuk létre automatikusan
    $libraryDir = dirname($customFunctionsPath);
    if (!is_dir($libraryDir)) {
        mkdir($libraryDir, 0755, true);
    }
    
    $content = '<?php
// CSRF token generálása
function generate_csrf_token(): string {
    if (empty($_SESSION["csrf_token"])) {
        $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
    }
    return $_SESSION["csrf_token"];
}

// CSRF token ellenőrzése
function verify_csrf_token($token): bool {
    return !empty($_SESSION["csrf_token"]) && hash_equals($_SESSION["csrf_token"], $token);
}

// Form mező generálása CSRF token számára
function csrf_field(): string {
    return "<input type=\"hidden\" name=\"csrf_token\" value=\"" . generate_csrf_token() . "\">";
}

// Session biztonságos indítása
function secure_session_start(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
        if (empty($_SESSION["last_regeneration"])) {
            session_regenerate_id(true);
            $_SESSION["last_regeneration"] = time();
        }
    }
}

// Redirect helper
function redirect($path) {
    $basePath = rtrim(dirname($_SERVER["SCRIPT_NAME"]), "/") . "/";
    if ($basePath === "//") $basePath = "/";
    header("Location: " . $basePath . ltrim($path, "/"));
    exit;
}
';
    
    file_put_contents($customFunctionsPath, $content);
}

require_once $customFunctionsPath;

// 2. SESSION INDÍTÁS
secure_session_start();

// 3. ADATBÁZIS KAPCSOLAT
require_once __DIR__ . '/app/config/database.php';

// 4. AUTOLOADER - KIJAVÍTVA
spl_autoload_register(function ($class) {
    $directories = ['app/controllers', 'app/models'];
    
    foreach ($directories as $dir) {
        // Először próbálkozzunk az eredeti névvel
        $file = __DIR__ . '/' . $dir . '/' . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
        
        // Ha nem található, próbálkozzunk kisbetűs névvel
        $file = __DIR__ . '/' . $dir . '/' . strtolower($class) . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
    
    // Ha nem találtuk, logoljuk (csak fejlesztés közben)
    // error_log("Autoloader: Nem található osztály: $class");
});

// 5. ROUTING
require_once __DIR__ . '/router.php';

// 6. HERO SECTION BEÁLLÍTÁSA
// Alapértelmezett: nem rejtjük el a hero-t
$hideHero = false;

// Ha bejelentkezési vagy regisztrációs oldalon vagyunk, elrejtjük
$currentPage = $_GET['page'] ?? 'home';
if (in_array($currentPage, ['login', 'register', 'cart', 'checkout', 'profile', 'logout'])) {
    $hideHero = true;
}

// 7. POST KÉRÉSEK KEZELÉSE
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // CSRF token ellenőrzés MINDEN POST kérésnél
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        die('<h1>403 - CSRF token érvénytelen</h1><p>Kérjük, frissítsd az oldalt és próbáld újra.</p>');
    }
    
    // Action alapján vezérlés
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'login':
        case 'register':
            if (class_exists('AuthController')) {
                (new AuthController($pdo))->handle();
            } else {
                die('AuthController nem található!');
            }
            exit;
            
        case 'cart_add':
        case 'cart_update':
        case 'cart_remove':
            if (class_exists('CartController')) {
                $controller = new CartController();
                if ($action === 'cart_add' && method_exists($controller, 'add')) {
                    $controller->add();
                } elseif ($action === 'cart_update' && method_exists($controller, 'update')) {
                    $controller->update();
                } elseif ($action === 'cart_remove' && method_exists($controller, 'remove')) {
                    $controller->remove();
                }
            }
            exit;
            
        case 'checkout':
            if (class_exists('OrderController')) {
                (new OrderController())->checkout();
            }
            exit;
            
        case 'logout':
            // Kijelentkezés POST kérésként
            session_destroy();
            redirect('/?logout=success');
            exit;
    }
}
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <?php require __DIR__ . '/app/views/layouts/head.php'; ?>
</head>

<body class="min-h-screen bg-white text-gray-900">

<?php require __DIR__ . '/app/views/layouts/menu.php'; ?>

<main class="w-full">
   
    
    <!-- TARTALOM -->
    <?php
    $page = $_GET['page'] ?? 'home';
    $viewPath = __DIR__ . '/app/views/pages/' . $page . '.php';
    
    // Főoldal esetén termékek betöltése
    if ($page === 'home') {
        $productModel = new ProductModel($pdo);
        $products = $productModel->getAll();
    }
    
    // Kategória oldal esetén termékek betöltése
    if ($page === 'category') {
        $gender = $_GET['gender'] ?? null;
        $category = $_GET['category'] ?? null;
        $filters = [
            'brand' => $_GET['brand'] ?? null,
            'color' => $_GET['color'] ?? null,
            'size'  => $_GET['size'] ?? null,
            'min'   => $_GET['min'] ?? null,
            'max'   => $_GET['max'] ?? null,
        ];
        $productModel = new ProductModel($pdo);
        $products = $productModel->filter($gender, $category, $filters);
    }
    
    // Keresés oldal esetén
    if ($page === 'search') {
        $q = trim($_GET['q'] ?? '');
        $productModel = new ProductModel($pdo);
        $products = $productModel->search($q);
        $searchQuery = $q;
    }
    
    // Akciós termékek
    if ($page === 'sale') {
        $productModel = new ProductModel($pdo);
        $products = $productModel->getSaleProducts();
    }
    
    // Újdonságok
    if ($page === 'new') {
        $productModel = new ProductModel($pdo);
        $products = $productModel->getNewProducts();
    }
    
    if (file_exists($viewPath)) {
        require $viewPath;
    } else {
        // 404 - oldal nem található
        http_response_code(404);
        require __DIR__ . '/app/views/components/404.php';
    }
    ?>
</main>

<?php require __DIR__ . '/app/views/layouts/footer.php'; ?>

</body>
</html>