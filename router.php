<?php

// Az URL-t lebontjuk és szétválasztjuk
$uri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$uri = str_replace('webshop/', '', $uri);  // ha a webshop mappában van
$parts = explode('/', $uri);

// Alapértelmezett page
$page = 'home';
$params = [];

if (!empty($parts[0])) {

    switch ($parts[0]) {

        // Kosár oldal
        case 'kosar':
            $page = 'cart';
            break;

        // Checkout oldal
        case 'checkout':
            $page = 'checkout';
            break;

        // Termék oldal
        case 'termek':
            $page = 'product';
            $params['id'] = $parts[1] ?? null;  // Termék ID
            break;

        // Alapértelmezett oldalak, ha nincs találat
        case 'noi':
            $params['gender'] = 'female';
            $page = 'home';
            break;

        case 'ferfi':
            $params['gender'] = 'male';
            $page = 'home';
            break;

        default:
            $page = '404';  // Ha nem található az oldal
            break;
    }
}

// A GET paramétereket dinamikusan beállítjuk
$_GET['page'] = $page;
$_GET = array_merge($_GET, $params);