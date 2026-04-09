<?php






require_once __DIR__ . '/app/bootstrap.php';


require_once __DIR__ . '/router.php';



$hideHero = false;


$currentPage = $_GET['page'] ?? 'home';
if (in_array($currentPage, ['login', 'register', 'cart', 'checkout', 'profile', 'logout', 'order-success', 'email-sent', 'ertekeles'])) {
    $hideHero = true;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        // CSRF hiba - új token generálása és vissza a form-ra
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $action = $_POST['action'] ?? '';
        if ($action === 'login') {
            header('Location: /webshop/login?error=session');
        } elseif ($action === 'register') {
            header('Location: /webshop/register?error=session');
        } elseif ($action === 'place_order' || $action === 'checkout') {
            header('Location: /webshop/checkout?error=session');
        } else {
            header('Location: /webshop/');
        }
        exit;
    }
    
    
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
            
            break;
            
        case 'logout':
            
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

<?php
// Definiáljuk az oldalt korán
$page = $_GET['page'] ?? 'home';

// Ellenőrizzük, hogy a főoldalon vagyunk-e (hero megjelenítéséhez)
$isHomePage = $page === 'home' && 
              empty($_GET['gender']) && 
              empty($_GET['type']) && 
              empty($_GET['sale']) && 
              empty($_GET['new']) &&
              empty($_GET['q']);
?>
<body class="min-h-screen bg-white text-gray-900 overflow-x-hidden">

<?php if ($isHomePage): ?>
    <!-- FŐOLDAL: Mobilon normál menü, desktopon overlay -->
    <div class="md:hidden">
        <?php require __DIR__ . '/app/views/layouts/menu.php'; ?>
    </div>
    <div class="hidden md:block">
        <?php require __DIR__ . '/app/views/layouts/menu-hero.php'; ?>
    </div>
<?php else: ?>
    <!-- TÖBBI OLDAL: Normál menü -->
    <?php require __DIR__ . '/app/views/layouts/menu.php'; ?>
<?php endif; ?>

<main class="w-full">
   
    
    <!-- TARTALOM -->
    <?php
    $viewPath = __DIR__ . '/app/views/pages/' . $page . '.php';
    
    
    $userFavoriteIds = [];
    if (!empty($_SESSION['user_id'])) {
        $favModel = new FavouriteModel($pdo);
        $userFavs = $favModel->getUserFavorites($_SESSION['user_id']);
        $userFavoriteIds = array_column($userFavs, 'product_id');
    }
    
    
    if ($page === 'home') {
        $productModel = new ProductModel($pdo);
        $products = $productModel->getAllWithVariants();
    }
    
    
    if ($page === 'category') {
        $gender = $_GET['gender'] ?? null;
        $category = $_GET['category'] ?? null;
        
        
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
    
    
    if ($page === 'search') {
        $q = trim($_GET['q'] ?? '');
        $productModel = new ProductModel($pdo);
        $products = $productModel->search($q);
        $searchQuery = $q;
    }
    
    
    if ($page === 'sale') {
        $productModel = new ProductModel($pdo);
        $products = $productModel->getSaleProducts();
    }
    
    
    if ($page === 'new') {
        $productModel = new ProductModel($pdo);
        $products = $productModel->getNewProducts();
    }
    
    if (file_exists($viewPath)) {
        require $viewPath;
    } else {
        
        http_response_code(404);
        require __DIR__ . '/app/views/components/404.php';
    }
    ?>
</main>

<?php require __DIR__ . '/app/views/layouts/footer.php'; ?>

</body>
</html>