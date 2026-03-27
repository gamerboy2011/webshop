<?php

$requestUri = $_SERVER['REQUEST_URI'];
$scriptName = $_SERVER['SCRIPT_NAME'];
$basePath = rtrim(str_replace('index.php', '', $scriptName), '/') . '/';

if (strpos($requestUri, $basePath) === 0) {
    $requestUri = substr($requestUri, strlen($basePath));
}


$path = parse_url($requestUri, PHP_URL_PATH) ?? '';
$uri = trim($path, '/');
$parts = !empty($uri) ? explode('/', $uri) : [];

$page = 'home';




if (isset($_GET['q']) && $_GET['q'] !== '') {
    $_GET['page'] = 'search';
}






if (!empty($parts[0]) && ($parts[0] === 'noi' || $parts[0] === 'ferfi')) {
    $_GET['page'] = 'category';
    $_GET['gender'] = $parts[0];
    $_GET['category'] = $parts[1] ?? null;
}




if (!empty($parts[0])) {
    switch ($parts[0]) {

        


        case 'yw-admin':
            require __DIR__ . '/admin.php';
            exit;

        


        case 'login':
            $page = 'login';
            break;

        case 'register':
            $page = 'register';
            break;

        case 'email-elkuldve':
            $page = 'email-sent';
            break;

        case 'logout':
            $page = 'logout';
            break;

        


        case 'kosar':
            $page = 'cart';
            break;

        case 'checkout':
            $page = 'checkout';
            break;

        case 'rendeles-sikeres':
            $page = 'order-success';
            break;

        case 'fizetes':
            $page = 'fizetes';
            break;

        case 'fizetes-sikeres':
            require __DIR__ . '/app/api/payment-success.php';
            exit;

        


        case 'profil':
            $page = 'profile';
            break;

        


        case 'akcio':
            $page = 'sale';
            break;

        case 'ujdonsagok':
            $page = 'new';
            break;

        case 'kapcsolat':
            $page = 'contact';
            break;

        case 'szallitas':
            $page = 'shipping';
            break;

        case 'aszf':
            $page = 'aszf';
            break;

        case 'adatvedelem':
            $page = 'privacy';
            break;

        case 'ertekeles':
            require_once __DIR__ . '/app/config/database.php';
            require __DIR__ . '/app/views/pages/ertekeles.php';
            exit;

        


        case 'kuponok':
            if (!empty($parts[1])) {
                $_GET['code'] = $parts[1];
            }
            $page = 'kuponok';
            break;

        



        case 'termek':
            $page = 'product';
            if (!empty($parts[1])) {
                $_GET['id'] = $parts[1];
            }
            break;

        


        case 'activate':
            require_once __DIR__ . '/app/controllers/ActivationController.php';
            $controller = new ActivationController($pdo);
            $controller->activate();
            exit;

        


        case 'favorite-toggle':
            require_once __DIR__ . '/app/controllers/FavouriteController.php';
            $controller = new FavouriteController($pdo);
            $controller->toggle();
            exit;

        


        default:
            $possibleFile = __DIR__ . '/app/views/pages/' . $parts[0] . '.php';
            if (file_exists($possibleFile) && $parts[0] !== 'index') {
                $page = $parts[0];
            } else {
                $page = 'home';
            }
            break;
    }
}


if (!isset($_GET['page'])) {
    $_GET['page'] = $page;
}
