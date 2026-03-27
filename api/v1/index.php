<?php






header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');


if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}


require_once __DIR__ . '/../../app/bootstrap.php';
require_once __DIR__ . '/ApiResponse.php';


$method = $_SERVER['REQUEST_METHOD'];
$requestUri = $_SERVER['REQUEST_URI'];


$basePath = '/webshop/api/v1';
$path = parse_url($requestUri, PHP_URL_PATH);
$path = str_replace($basePath, '', $path);
$path = trim($path, '/');


$segments = $path ? explode('/', $path) : [];
$resource = $segments[0] ?? null;


$input = [];
if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
    $rawInput = file_get_contents('php://input');
    if ($rawInput) {
        $input = json_decode($rawInput, true) ?? [];
    }
    
    if (empty($input) && !empty($_POST)) {
        $input = $_POST;
    }
}


$queryParams = $_GET;


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
