<?php






error_reporting(E_ALL);
ini_set('display_errors', 1);


define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', __DIR__);
define('BASE_URL', '/webshop');
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('STORAGE_PATH', ROOT_PATH . '/storage');


date_default_timezone_set('Europe/Budapest');


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


$configPath = file_exists(APP_PATH . '/Config/database.php') 
    ? APP_PATH . '/Config/database.php' 
    : APP_PATH . '/config/database.php';
$dbConfig = require $configPath;


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


require_once APP_PATH . '/Helpers/functions.php';


spl_autoload_register(function ($class) {
    
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
