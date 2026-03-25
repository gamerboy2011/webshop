<?php
/**
 * Router Class
 * URL routing kezelése
 */

namespace App\Core;

class Router
{
    private array $routes = [];
    private string $notFoundHandler = '';
    
    /**
     * GET route hozzáadása
     */
    public function get(string $path, string $handler): self
    {
        $this->addRoute('GET', $path, $handler);
        return $this;
    }
    
    /**
     * POST route hozzáadása
     */
    public function post(string $path, string $handler): self
    {
        $this->addRoute('POST', $path, $handler);
        return $this;
    }
    
    /**
     * PUT route hozzáadása
     */
    public function put(string $path, string $handler): self
    {
        $this->addRoute('PUT', $path, $handler);
        return $this;
    }
    
    /**
     * DELETE route hozzáadása
     */
    public function delete(string $path, string $handler): self
    {
        $this->addRoute('DELETE', $path, $handler);
        return $this;
    }
    
    /**
     * Route hozzáadása
     */
    private function addRoute(string $method, string $path, string $handler): void
    {
        // Konvertáljuk a :param formátumot regex-re
        $pattern = preg_replace('/\{([a-zA-Z_]+)\}/', '(?P<$1>[^/]+)', $path);
        $pattern = '#^' . $pattern . '$#';
        
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'pattern' => $pattern,
            'handler' => $handler
        ];
    }
    
    /**
     * 404 handler beállítása
     */
    public function notFound(string $handler): self
    {
        $this->notFoundHandler = $handler;
        return $this;
    }
    
    /**
     * Request feldolgozása
     */
    public function dispatch(string $uri, string $method): void
    {
        // Query string eltávolítása
        $uri = parse_url($uri, PHP_URL_PATH);
        $uri = '/' . trim($uri, '/');
        
        // Base path eltávolítása
        $basePath = '/webshop';
        if (strpos($uri, $basePath) === 0) {
            $uri = substr($uri, strlen($basePath));
        }
        $uri = '/' . trim($uri, '/');
        if ($uri === '/') {
            $uri = '/';
        }
        
        // Route keresése
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            
            if (preg_match($route['pattern'], $uri, $matches)) {
                // Paraméterek kinyerése
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                
                $this->callHandler($route['handler'], $params);
                return;
            }
        }
        
        // 404
        if ($this->notFoundHandler) {
            $this->callHandler($this->notFoundHandler, []);
        } else {
            http_response_code(404);
            echo '404 Not Found';
        }
    }
    
    /**
     * Handler meghívása
     */
    private function callHandler(string $handler, array $params): void
    {
        global $pdo;
        
        // Controller@method formátum
        if (strpos($handler, '@') !== false) {
            [$controllerName, $method] = explode('@', $handler);
            
            $controllerClass = "App\\Controllers\\{$controllerName}";
            
            // Régi stílusú controller betöltés (ha nincs namespace)
            $controllerFile = APP_PATH . '/Controllers/' . $controllerName . '.php';
            if (file_exists($controllerFile)) {
                require_once $controllerFile;
            }
            
            // Controller példányosítás
            if (class_exists($controllerClass)) {
                $controller = new $controllerClass($pdo);
            } elseif (class_exists($controllerName)) {
                $controller = new $controllerName($pdo);
            } else {
                throw new \Exception("Controller not found: {$controllerName}");
            }
            
            if (!method_exists($controller, $method)) {
                throw new \Exception("Method not found: {$controllerName}@{$method}");
            }
            
            call_user_func_array([$controller, $method], $params);
        } else {
            // Closure vagy függvény
            call_user_func_array($handler, $params);
        }
    }
}
