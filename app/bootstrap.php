<?php
/**
 * Bootstrap - Alkalmazás inicializálás
 * Ez a fájl tölti be az összes szükséges konfigurációt és függőséget
 */

// Hibajelentés bekapcsolása (production-ben ki kell kapcsolni)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Útvonal konstansok
define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', __DIR__);
define('BASE_URL', '/webshop');
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('STORAGE_PATH', ROOT_PATH . '/storage');

// Időzóna beállítás
date_default_timezone_set('Europe/Budapest');

// Session indítás
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Config betöltés (mindkét névvel kompatibilis)
$configPath = file_exists(APP_PATH . '/Config/database.php') 
    ? APP_PATH . '/Config/database.php' 
    : APP_PATH . '/config/database.php';
$dbConfig = require $configPath;

// Adatbázis kapcsolat
$dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['database']};charset={$dbConfig['charset']}";
try {
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], $dbConfig['options'] ?? [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
} catch (PDOException $e) {
    die('Adatbázis kapcsolódási hiba: ' . $e->getMessage());
}

// Helper függvények
require_once APP_PATH . '/Helpers/functions.php';

// Modellek autoload
spl_autoload_register(function ($class) {
    // Namespace eltávolítása
    $class = str_replace('App\\', '', $class);
    $class = str_replace('\\', '/', $class);
    
    $paths = [
        APP_PATH . '/' . $class . '.php',
        APP_PATH . '/Models/' . $class . '.php',
        APP_PATH . '/Controllers/' . $class . '.php',
        APP_PATH . '/Core/' . $class . '.php',
    ];
    
    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
    
    // Lowercase verzió próba
    $lowercasePaths = [
        APP_PATH . '/Models/' . strtolower($class) . '.php',
        APP_PATH . '/Controllers/' . strtolower($class) . '.php',
    ];
    
    foreach ($lowercasePaths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});
