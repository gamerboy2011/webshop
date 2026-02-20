<?php
/**
 * ADMIN PANEL - Yoursy Wear
 * Belépési pont: /webshop/yw-admin
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

// Session és alap függvények
require_once __DIR__ . '/app/library/customfunctions.php';
secure_session_start();

// Adatbázis
require_once __DIR__ . '/app/config/database.php';

// Autoloader
spl_autoload_register(function ($class) {
    $dirs = ['app/controllers', 'app/models'];
    foreach ($dirs as $dir) {
        $file = __DIR__ . '/' . $dir . '/' . $class . '.php';
        if (file_exists($file)) { require_once $file; return; }
        $file = __DIR__ . '/' . $dir . '/' . strtolower($class) . '.php';
        if (file_exists($file)) { require_once $file; return; }
    }
});

// Admin controller
$admin = new AdminController($pdo);

// URL feldolgozás
$uri = $_SERVER['REQUEST_URI'];
$uri = str_replace('/webshop/yw-admin', '', $uri);
$uri = trim(parse_url($uri, PHP_URL_PATH), '/');
$parts = $uri ? explode('/', $uri) : [];
$page = $parts[0] ?? 'dashboard';
$action = $parts[1] ?? null;
$id = $parts[2] ?? null;

// POST kérések kezelése
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // CSRF ellenőrzés
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die('CSRF token érvénytelen');
    }
    
    $postAction = $_POST['action'] ?? '';
    
    switch ($postAction) {
        case 'admin_login':
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role_id = 2");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['role_id'] = $user['role_id'];
                $_SESSION['logged_in'] = true;
                header('Location: /webshop/yw-admin');
                exit;
            } else {
                $loginError = 'Hibás email vagy jelszó, vagy nincs admin jogosultságod.';
            }
            break;
            
        case 'save_product':
            $admin->requireAdmin();
            $admin->saveProduct($_POST);
            header('Location: /webshop/yw-admin/products?saved=1');
            exit;
            
        case 'delete_product':
            $admin->requireAdmin();
            $admin->deleteProduct((int)$_POST['product_id']);
            header('Location: /webshop/yw-admin/products?deleted=1');
            exit;
            
        case 'toggle_sale':
            $admin->requireAdmin();
            $admin->toggleSale((int)$_POST['product_id']);
            header('Location: /webshop/yw-admin/products');
            exit;
            
        case 'set_user_role':
            $admin->requireAdmin();
            $admin->setUserRole((int)$_POST['user_id'], (int)$_POST['role_id']);
            header('Location: /webshop/yw-admin/users');
            exit;

        case 'update_stock':
            $admin->requireAdmin();
            if (!empty($_POST['stock'])) {
                $admin->bulkUpdateStock($_POST['stock']);
            }
            $redirect = '/webshop/yw-admin/stock';
            if (!empty($_POST['product_id'])) {
                $redirect .= '?product_id=' . $_POST['product_id'];
            }
            header('Location: ' . $redirect . '&saved=1');
            exit;

        case 'admin_logout':
            session_destroy();
            header('Location: /webshop/yw-admin/login');
            exit;
    }
}

// Login oldal - nem kell admin ellenőrzés
if ($page === 'login') {
    require __DIR__ . '/app/views/admin/login.php';
    exit;
}

// Minden más oldalhoz admin kell
$admin->requireAdmin();

// Adatok betöltése az aktuális oldalhoz
switch ($page) {
    case 'dashboard':
        $stats = $admin->getDashboardStats();
        $recentOrders = $admin->getRecentOrders(5);
        break;
        
    case 'products':
        $search = $_GET['q'] ?? null;
        $products = $admin->getProducts($search);
        break;
        
    case 'product-edit':
        $productId = (int)($parts[1] ?? 0);
        $product = $productId ? $admin->getProduct($productId) : null;
        $vendors = $admin->getVendors();
        $colors = $admin->getColors();
        $genders = $admin->getGenders();
        $subtypes = $admin->getSubtypes();
        break;
        
    case 'orders':
        $orders = $admin->getOrders();
        break;
        
    case 'users':
        $users = $admin->getUsers();
        break;
        
    case 'stock':
        $productId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : null;
        $search = $_GET['q'] ?? null;
        $stockItems = $admin->getStock($productId, $search);
        if ($productId) {
            $currentProduct = $admin->getProduct($productId);
        }
        break;
}

// View renderelés
$viewFile = __DIR__ . '/app/views/admin/' . $page . '.php';
if (!file_exists($viewFile)) {
    $viewFile = __DIR__ . '/app/views/admin/dashboard.php';
}

require __DIR__ . '/app/views/admin/layout.php';
