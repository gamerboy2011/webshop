<?php





namespace App\Core;

use PDO;

abstract class Controller
{
    protected PDO $db;
    protected array $data = [];
    
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }
    
    


    protected function view(string $view, array $data = []): void
    {
        
        extract(array_merge($this->data, $data));
        
        $viewPath = APP_PATH . '/Views/' . $view . '.php';
        
        if (file_exists($viewPath)) {
            require $viewPath;
        } else {
            throw new \Exception("View not found: {$view}");
        }
    }
    
    


    protected function render(string $view, array $data = [], string $layout = 'main'): void
    {
        $this->data = array_merge($this->data, $data);
        $this->data['content'] = $view;
        
        $layoutPath = APP_PATH . '/Views/layouts/' . $layout . '.php';
        
        if (file_exists($layoutPath)) {
            extract($this->data);
            require $layoutPath;
        } else {
            $this->view($view, $data);
        }
    }
    
    


    protected function json(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    


    protected function redirect(string $url): void
    {
        header('Location: ' . BASE_URL . '/' . ltrim($url, '/'));
        exit;
    }
    
    


    protected function requireAuth(): void
    {
        if (empty($_SESSION['user_id'])) {
            $this->redirect('login');
        }
    }
    
    


    protected function requireAdmin(): void
    {
        if (empty($_SESSION['user_id']) || empty($_SESSION['is_admin'])) {
            $this->redirect('login');
        }
    }
    
    


    protected function generateCsrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    


    protected function validateCsrfToken(): bool
    {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        return !empty($token) && hash_equals($_SESSION['csrf_token'] ?? '', $token);
    }
    
    


    protected function sanitize($input): string
    {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    


    protected function post(string $key, $default = null)
    {
        return $_POST[$key] ?? $default;
    }
    
    


    protected function get(string $key, $default = null)
    {
        return $_GET[$key] ?? $default;
    }
    
    


    protected function isPost(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }
    
    


    protected function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}
