<?php





namespace App\Core;

class Router
{
    private array $routes = [];
    private string $notFoundHandler = '';
    
    


    public function get(string $path, string $handler): self
    {
        $this->addRoute('GET', $path, $handler);
        return $this;
    }
    
    


    public function post(string $path, string $handler): self
    {
        $this->addRoute('POST', $path, $handler);
        return $this;
    }
    
    


    public function put(string $path, string $handler): self
    {
        $this->addRoute('PUT', $path, $handler);
        return $this;
    }
    
    


    public function delete(string $path, string $handler): self
    {
        $this->addRoute('DELETE', $path, $handler);
        return $this;
    }
    
    


    private function addRoute(string $method, string $path, string $handler): void
    {
        
        $pattern = preg_replace('/\{([a-zA-Z_]+)\}/', '(?P<$1>[^/]+)', $path);
        $pattern = '#^' . $pattern . '$#';
        
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'pattern' => $pattern,
            'handler' => $handler
        ];
    }
    
    


    public function notFound(string $handler): self
    {
        $this->notFoundHandler = $handler;
        return $this;
    }
    
    


    public function dispatch(string $uri, string $method): void
    {
        
        $uri = parse_url($uri, PHP_URL_PATH);
        $uri = '/' . trim($uri, '/');
        
        
        $basePath = '/webshop';
        if (strpos($uri, $basePath) === 0) {
            $uri = substr($uri, strlen($basePath));
        }
        $uri = '/' . trim($uri, '/');
        if ($uri === '/') {
            $uri = '/';
        }
        
        
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            
            if (preg_match($route['pattern'], $uri, $matches)) {
                
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                
                $this->callHandler($route['handler'], $params);
                return;
            }
        }
        
        
        if ($this->notFoundHandler) {
            $this->callHandler($this->notFoundHandler, []);
        } else {
            http_response_code(404);
            echo '404 Not Found';
        }
    }
    
    


    private function callHandler(string $handler, array $params): void
    {
        global $pdo;
        
        
        if (strpos($handler, '@') !== false) {
            [$controllerName, $method] = explode('@', $handler);
            
            $controllerClass = "App\\Controllers\\{$controllerName}";
            
            
            $controllerFile = APP_PATH . '/Controllers/' . $controllerName . '.php';
            if (file_exists($controllerFile)) {
                require_once $controllerFile;
            }
            
            
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
            
            call_user_func_array($handler, $params);
        }
    }
}
