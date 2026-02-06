<?php

$uri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$uri = str_replace('webshop/', '', $uri);
$parts = explode('/', $uri);

$page   = 'home';
$params = [];

/*
URL minták:
/
ferfi
noi
ferfi/ruhazat
noi/cipok
termek/12
kosar
checkout
*/

if (!empty($parts[0])) {

    /* ===== KÉT SZINT (gender + type) ===== */
    if (count($parts) >= 2) {

        // gender
        if ($parts[0] === 'ferfi') {
            $params['gender'] = 'male';
        }

        if ($parts[0] === 'noi') {
            $params['gender'] = 'female';
        }

        // típus
        switch ($parts[1]) {
            case 'ruhazat':
                $params['type'] = 'clothe';
                break;

            case 'cipok':
                $params['type'] = 'shoe';
                break;

            case 'kiegeszitok':
                $params['type'] = 'accessory';
                break;
        }

        $page = 'home';
    }

    /* ===== EGY SZINT ===== */
    else {

        switch ($parts[0]) {

            case 'ferfi':
                $params['gender'] = 'male';
                $page = 'home';
                break;

            case 'noi':
                $params['gender'] = 'female';
                $page = 'home';
                break;

            case 'kosar':
                $page = 'cart';
                break;

            case 'checkout':
                $page = 'checkout';
                break;

            case 'akcio':
                $params['sale'] = 1;
                $page = 'home';
                break;

            case 'ujdonsagok':
                $params['new'] = 1;
                $page = 'home';
                break;

            case 'termek':
                $page = 'product';
                $params['id'] = $parts[1] ?? null;
                break;
        }
    }
}

/* ===== PARAMÉTEREK BETÖLTÉSE ===== */
$_GET = array_merge($_GET, $params);
$_GET['page'] = $page;