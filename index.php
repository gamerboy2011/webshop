<?php
/**
 * YoursyWear - Fő belépési pont
 * MVC architektúra
 */

// Bootstrap betöltése
require_once __DIR__ . '/app/bootstrap.php';

// 5. ROUTING
require_once __DIR__ . '/router.php';

// 6. HERO SECTION BEÁLLÍTÁSA
// Alapértelmezett: nem rejtjük el a hero-t
$hideHero = false;

// Ha bejelentkezési vagy regisztrációs oldalon vagyunk, elrejtjük
$currentPage = $_GET['page'] ?? 'home';
if (in_array($currentPage, ['login', 'register', 'cart', 'checkout', 'profile', 'logout', 'order-success', 'email-sent', 'ertekeles'])) {
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
        case 'cart_clear':
            if (class_exists('CartController')) {
                $controller = new CartController();
                if ($action === 'cart_add' && method_exists($controller, 'add')) {
                    $controller->add();
                } elseif ($action === 'cart_update' && method_exists($controller, 'update')) {
                    $controller->update();
                } elseif ($action === 'cart_remove' && method_exists($controller, 'remove')) {
                    $controller->remove();
                } elseif ($action === 'cart_clear' && method_exists($controller, 'clear')) {
                    $controller->clear();
                }
            }
            exit;
            
        case 'checkout':
            if (class_exists('OrderController')) {
                (new OrderController())->checkout();
            }
            exit;
            
        case 'place_order':
            if (class_exists('OrderController')) {
                (new OrderController())->placeOrder();
            }
            exit;
            
        case 'profile_save':
            // Profile mentés - továbbengedjük, a profile.php kezeli
            break;
            
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
    
    // Kedvencek betöltése (bejelentkezett felhasználónak)
    $userFavoriteIds = [];
    if (!empty($_SESSION['user_id'])) {
        $favModel = new FavouriteModel($pdo);
        $userFavs = $favModel->getUserFavorites($_SESSION['user_id']);
        $userFavoriteIds = array_column($userFavs, 'product_id');
    }
    
    // Főoldal esetén termékek betöltése (színváltozatokkal)
    if ($page === 'home') {
        $productModel = new ProductModel($pdo);
        $products = $productModel->getAllWithVariants();
    }
    
    // Kategória oldal esetén termékek betöltése
    if ($page === 'category') {
        $gender = $_GET['gender'] ?? null;
        $category = $_GET['category'] ?? null;
        
        // Szűrők feldolgozása
        $filters = [
            'sale' => isset($_GET['sale']) ? true : false,
            'brands' => isset($_GET['brands']) ? (array)$_GET['brands'] : [],
            'colors' => isset($_GET['colors']) ? (array)$_GET['colors'] : [],
            'sizes' => isset($_GET['sizes']) ? (array)$_GET['sizes'] : [],
            'min_price' => $_GET['min_price'] ?? null,
            'max_price' => $_GET['max_price'] ?? null,
            'sort' => $_GET['sort'] ?? 'newest',
        ];
        
        $productModel = new ProductModel($pdo);
        $products = $productModel->filterAdvanced($gender, $category, $filters);
        $filterOptions = $productModel->getFilterOptions($gender, $category);
        $activeFilters = $filters;
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