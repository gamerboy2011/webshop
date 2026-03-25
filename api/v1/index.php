<?php
/**
 * RESTful API Router v1
 * Központi belépési pont az összes API kéréshez
 */

// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// OPTIONS preflight request kezelése
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Bootstrap betöltése (session, db, autoload)
require_once __DIR__ . '/../../app/bootstrap.php';
require_once __DIR__ . '/ApiResponse.php';

// Request adatok
$method = $_SERVER['REQUEST_METHOD'];
$requestUri = $_SERVER['REQUEST_URI'];

// API base path eltávolítása
$basePath = '/webshop/api/v1';
$path = parse_url($requestUri, PHP_URL_PATH);
$path = str_replace($basePath, '', $path);
$path = trim($path, '/');

// Path elemekre bontása
$segments = $path ? explode('/', $path) : [];
$resource = $segments[0] ?? null;

// JSON body beolvasása POST/PUT kéréseknél
$input = [];
if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
    $rawInput = file_get_contents('php://input');
    if ($rawInput) {
        $input = json_decode($rawInput, true) ?? [];
    }
    // Form data is fallback
    if (empty($input) && !empty($_POST)) {
        $input = $_POST;
    }
}

// Query paraméterek
$queryParams = $_GET;

// Resource routing
switch ($resource) {
    case 'products':
        require_once __DIR__ . '/resources/products.php';
        break;
        
    case 'cart':
        require_once __DIR__ . '/resources/cart.php';
        break;
        
    case 'favorites':
        require_once __DIR__ . '/resources/favorites.php';
        break;
        
    case 'coupons':
        require_once __DIR__ . '/resources/coupons.php';
        break;
        
    case 'orders':
        require_once __DIR__ . '/resources/orders.php';
        break;
        
    case 'returns':
        require_once __DIR__ . '/resources/returns.php';
        break;
        
    case 'cities':
        require_once __DIR__ . '/resources/cities.php';
        break;
        
    case 'auth':
        require_once __DIR__ . '/resources/auth.php';
        break;
        
    case '':
        // API info endpoint
        ApiResponse::success([
            'name' => 'YoursyWear API',
            'version' => '1.0',
            'endpoints' => [
                'GET /api/v1/products' => 'Termékek listázása',
                'GET /api/v1/products/{id}' => 'Termék részletei',
                'GET /api/v1/cart' => 'Kosár tartalma',
                'POST /api/v1/cart' => 'Termék hozzáadása a kosárhoz',
                'PUT /api/v1/cart/{id}' => 'Kosár elem módosítása',
                'DELETE /api/v1/cart/{id}' => 'Kosár elem törlése',
                'GET /api/v1/favorites' => 'Kedvencek listázása',
                'POST /api/v1/favorites' => 'Kedvencekhez adás',
                'DELETE /api/v1/favorites/{id}' => 'Kedvencekből törlés',
                'GET /api/v1/coupons/{code}/validate' => 'Kupon ellenőrzése',
                'POST /api/v1/orders' => 'Rendelés leadása',
                'GET /api/v1/orders' => 'Rendelések listázása',
                'POST /api/v1/returns' => 'Visszáru kérelem',
                'GET /api/v1/cities?postcode={code}' => 'Város keresése irányítószám alapján',
                'POST /api/v1/auth/login' => 'Bejelentkezés',
                'POST /api/v1/auth/register' => 'Regisztráció',
                'POST /api/v1/auth/logout' => 'Kijelentkezés'
            ]
        ]);
        break;
        
    default:
        ApiResponse::notFound('Ismeretlen API végpont: ' . $resource);
}
