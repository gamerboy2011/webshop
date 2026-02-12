<?php

// Request URI
$requestUri = $_SERVER['REQUEST_URI'];

// A projekt mappája (ahol az index.php van)
$scriptName = $_SERVER['SCRIPT_NAME']; // pl. /webshop/index.php
$basePath = rtrim(str_replace('index.php', '', $scriptName), '/') . '/';

// Távolítsuk el a basePath-et az URI elejéről
if (strpos($requestUri, $basePath) === 0) {
    $requestUri = substr($requestUri, strlen($basePath));
}

// URL feldolgozása – JAVÍTVA (nem dob deprecated hibát)
$path = parse_url($requestUri, PHP_URL_PATH) ?? '';
$uri = trim($path, '/');
$parts = !empty($uri) ? explode('/', $uri) : [];

// Alapértelmezett oldal
$page = 'home';

// Router logika
if (!empty($parts[0])) {
    switch ($parts[0]) {

        case 'login':
            $page = 'login';
            break;

        case 'register':
            $page = 'register';
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

        case 'profil':
            $page = 'profile';
            break;

        case 'termek':
            $page = 'product';
            if (!empty($parts[1])) {
                $_GET['id'] = $parts[1];
            }
            break;

        case 'kategoria':
            $page = 'category';
            if (!empty($parts[1])) {
                $_GET['category'] = $parts[1];
            }
            break;

        case 'noi':
            $page = 'category';
            $_GET['category'] = 'noi';
            break;

        case 'ferfi':
            $page = 'category';
            $_GET['category'] = 'ferfi';
            break;

        case 'home':
            $page = 'home';
            break;

        case 'activate':
            $controller = new ActivationController($pdo);
            $controller->activate();
            exit;

        default:
            // Ha létezik ilyen PHP fájl az app/pages mappában, töltsük be
            $possibleFile = __DIR__ . '/app/pages/' . $parts[0] . '.php';
            if (file_exists($possibleFile) && $parts[0] !== 'index') {
                $page = $parts[0];
            } else {
                $page = 'home'; // vagy 404
            }
            break;
    }
}

// Oldal beállítása GET paraméterben
$_GET['page'] = $page;
