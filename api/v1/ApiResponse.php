<?php





class ApiResponse
{
    


    public static function success($data = null, int $statusCode = 200, string $message = null): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        
        $response = [
            'success' => true,
            'status' => $statusCode
        ];
        
        if ($message !== null) {
            $response['message'] = $message;
        }
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    


    public static function error(string $message, int $statusCode = 400, $errors = null): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        
        $response = [
            'success' => false,
            'status' => $statusCode,
            'message' => $message
        ];
        
        if ($errors !== null) {
            $response['errors'] = $errors;
        }
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    


    public static function created($data = null, string $message = 'Sikeresen létrehozva'): void
    {
        self::success($data, 201, $message);
    }
    
    


    public static function noContent(): void
    {
        http_response_code(204);
        exit;
    }
    
    


    public static function badRequest(string $message = 'Hibás kérés', $errors = null): void
    {
        self::error($message, 400, $errors);
    }
    
    


    public static function unauthorized(string $message = 'Bejelentkezés szükséges'): void
    {
        self::error($message, 401);
    }
    
    


    public static function forbidden(string $message = 'Nincs jogosultság'): void
    {
        self::error($message, 403);
    }
    
    


    public static function notFound(string $message = 'Az erőforrás nem található'): void
    {
        self::error($message, 404);
    }
    
    


    public static function methodNotAllowed(array $allowedMethods = []): void
    {
        if (!empty($allowedMethods)) {
            header('Allow: ' . implode(', ', $allowedMethods));
        }
        self::error('A HTTP metódus nem engedélyezett', 405);
    }
    
    


    public static function validationError(string $message = 'Validációs hiba', $errors = null): void
    {
        self::error($message, 422, $errors);
    }
    
    


    public static function serverError(string $message = 'Szerver hiba történt'): void
    {
        self::error($message, 500);
    }
}
