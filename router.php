<?php


$alias = $_GET['alias'] ?? '';

switch ($alias) {
    case 'login':
        $content = __DIR__ . '/app/views/pages/login.php';
        break;

    case 'regisztracio':
        $content = __DIR__ . '/app/views/pages/registration.php';
        break;

    case 'termekek':
        $content = __DIR__ . '/app/views/pages/product.php';
        break;

    case 'kosar':
        $content = __DIR__ . '/app/views/pages/cart.php';
        break;

    case 'checkout':
        $content = __DIR__ . '/app/views/pages/checkout.php';
        break;

    default:
        $content = __DIR__ . '/app/views/pages/main.php';
}