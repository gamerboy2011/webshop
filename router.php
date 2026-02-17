<?php

$requestUri = $_SERVER['REQUEST_URI'];
$scriptName = $_SERVER['SCRIPT_NAME'];
$basePath = rtrim(str_replace('index.php', '', $scriptName), '/') . '/';

if (strpos($requestUri, $basePath) === 0) {
    $requestUri = substr($requestUri, strlen($basePath));
}

// URL feldolgozása
$path = parse_url($requestUri, PHP_URL_PATH) ?? '';
$uri = trim($path, '/');
$parts = !empty($uri) ? explode('/', $uri) : [];

$page = 'home';

/* =========================
   1) KERESŐ KEZELÉSE
   ========================= */
if (isset($_GET['q']) && $_GET['q'] !== '') {
    $_GET['page'] = 'search';
}

/* =========================
   2) KATEGÓRIA + GENDER KEZELÉSE
   /noi/ruhazat
   /ferfi/cipok
   ========================= */
if (!empty($parts[0]) && ($parts[0] === 'noi' || $parts[0] === 'ferfi')) {
    $_GET['page'] = 'category';
    $_GET['gender'] = $parts[0];
    $_GET['category'] = $parts[1] ?? null;
}

/* =========================
   3) ALAP ROUTING
   ========================= */
if (!empty($parts[0])) {
    switch ($parts[0]) {

        /* ============================
           AUTH
        ============================ */
        case 'login':
            $page = 'login';
            break;

        case 'register':
            $page = 'register';
            break;

        case 'logout':
            $page = 'logout';
            break;

        /* ============================
           KOSÁR / CHECKOUT
        ============================ */
        case 'kosar':
            $page = 'cart';
            break;

        case 'checkout':
            $page = 'checkout';
            break;

        /* ============================
           PROFIL
        ============================ */
        case 'profil':
            $page = 'profile';
            break;

        /* ============================
           AKCIÓ / ÚDONSÁGOK
        ============================ */
        case 'akcio':
            $page = 'sale';
            break;

        case 'ujdonsagok':
            $page = 'new';
            break;

        /* ============================
           TERMÉK OLDAL
           /termek/123
        ============================ */
        case 'termek':
            $page = 'product';
            if (!empty($parts[1])) {
                $_GET['id'] = $parts[1];
            }
            break;

        /* ============================
           FIÓK AKTIVÁLÁS
        ============================ */
        case 'activate':
            require_once __DIR__ . '/app/controllers/ActivationController.php';
            $controller = new ActivationController($pdo);
            $controller->activate();
            exit;

        /* ============================
           KEDVENCEK TOGGLE (AJAX)
        ============================ */
        case 'favorite-toggle':
            require_once __DIR__ . '/app/controllers/FavouriteController.php';
            $controller = new FavouriteController($pdo);
            $controller->toggle();
            exit;

        /* ============================
           ALAPÉRTELMEZETT
        ============================ */
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

// Csak akkor állítjuk be, ha még nincs beállítva (pl. search, category, sale, new)
if (!isset($_GET['page'])) {
    $_GET['page'] = $page;
}
