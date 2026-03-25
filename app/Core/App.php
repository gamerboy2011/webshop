<?php
/**
 * Application Class
 * Az alkalmazás fő belépési pontja és inicializálása
 */

namespace App\Core;

use PDO;
use PDOException;

class App
{
    private static ?App $instance = null;
    private PDO $db;
    private Router $router;
    private array $config = [];
    
    private function __construct()
    {
        $this->loadConfig();
        $this->initDatabase();
        $this->initSession();
        $this->router = new Router();
    }
    
    /**
     * Singleton instance
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Konfiguráció betöltése
     */
    private function loadConfig(): void
    {
        // Alapértelmezett értékek
        $this->config = [
            'app' => [
                'name' => 'YoursyWear',
                'url' => 'http://localhost/webshop',
                'debug' => true
            ],
            'database' => [],
            'mail' => []
        ];
        
        // Config fájlok betöltése
        $configPath = APP_PATH . '/Config/';
        
        if (file_exists($configPath . 'app.php')) {
            $this->config['app'] = array_merge($this->config['app'], require $configPath . 'app.php');
        }
        if (file_exists($configPath . 'database.php')) {
            $this->config['database'] = require $configPath . 'database.php';
        }
        if (file_exists($configPath . 'mail.php')) {
            $this->config['mail'] = require $configPath . 'mail.php';
        }
    }
    
    /**
     * Adatbázis kapcsolat inicializálása
     */
    private function initDatabase(): void
    {
        $config = $this->config['database'];
        
        $host = $config['host'] ?? 'localhost';
        $dbname = $config['database'] ?? 'yoursywear';
        $username = $config['username'] ?? 'root';
        $password = $config['password'] ?? '';
        $charset = $config['charset'] ?? 'utf8mb4';
        
        $dsn = "mysql:host={$host};dbname={$dbname};charset={$charset}";
        
        try {
            $this->db = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
        } catch (PDOException $e) {
            if ($this->config['app']['debug']) {
                die('Database connection failed: ' . $e->getMessage());
            }
            die('Database connection failed');
        }
    }
    
    /**
     * Session inicializálása
     */
    private function initSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    /**
     * Router elérése
     */
    public function getRouter(): Router
    {
        return $this->router;
    }
    
    /**
     * Database elérése
     */
    public function getDb(): PDO
    {
        return $this->db;
    }
    
    /**
     * Config érték lekérése
     */
    public function config(string $key, $default = null)
    {
        $keys = explode('.', $key);
        $value = $this->config;
        
        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }
        
        return $value;
    }
    
    /**
     * Alkalmazás futtatása
     */
    public function run(): void
    {
        $uri = $_SERVER['REQUEST_URI'];
        $method = $_SERVER['REQUEST_METHOD'];
        
        $this->router->dispatch($uri, $method);
    }
}
