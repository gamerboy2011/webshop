<?php
/**
 * Auth API
 * POST /api/v1/auth/login - Bejelentkezés
 * POST /api/v1/auth/register - Regisztráció
 * POST /api/v1/auth/logout - Kijelentkezés
 * GET /api/v1/auth/me - Aktuális felhasználó
 */

global $pdo;
$action = $segments[1] ?? null;

switch ($method) {
    case 'GET':
        if ($action === 'me') {
            // GET /api/v1/auth/me
            $userId = $_SESSION['user_id'] ?? null;
            
            if (!$userId) {
                ApiResponse::unauthorized('Nincs bejelentkezve');
            }
            
            $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                ApiResponse::notFound('Felhasználó nem található');
            }
            
            // Jelszó eltávolítása
            unset($user['password']);
            
            ApiResponse::success($user);
        } else {
            ApiResponse::methodNotAllowed(['POST']);
        }
        break;
        
    case 'POST':
        switch ($action) {
            case 'login':
                // POST /api/v1/auth/login
                $email = $input['email'] ?? null;
                $password = $input['password'] ?? null;
                
                if (!$email || !$password) {
                    ApiResponse::badRequest('Hiányzó paraméterek: email, password');
                }
                
                // Email validálás
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    ApiResponse::badRequest('Érvénytelen email cím');
                }
                
                // Felhasználó keresése
                $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$user) {
                    ApiResponse::unauthorized('Hibás email vagy jelszó');
                }
                
                // Jelszó ellenőrzés
                if (!password_verify($password, $user['password'])) {
                    ApiResponse::unauthorized('Hibás email vagy jelszó');
                }
                
                // Session beállítás
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_name'] = $user['username'] ?? '';
                $_SESSION['is_admin'] = $user['is_admin'] ?? 0;
                
                // Jelszó eltávolítása a válaszból
                unset($user['password']);
                
                ApiResponse::success([
                    'user' => $user,
                    'message' => 'Sikeres bejelentkezés'
                ]);
                break;
                
            case 'register':
                // POST /api/v1/auth/register
                $requiredFields = ['email', 'password', 'username'];
                $errors = [];
                
                foreach ($requiredFields as $field) {
                    if (empty($input[$field])) {
                        $errors[$field] = 'A mező kitöltése kötelező';
                    }
                }
                
                if (!empty($errors)) {
                    ApiResponse::validationError('Hiányzó kötelező mezők', $errors);
                }
                
                // Email validálás
                if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
                    ApiResponse::badRequest('Érvénytelen email cím');
                }
                
                // Jelszó hossz ellenőrzés
                if (strlen($input['password']) < 6) {
                    ApiResponse::badRequest('A jelszónak legalább 6 karakter hosszúnak kell lennie');
                }
                
                // Email egyediség ellenőrzés
                $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
                $stmt->execute([$input['email']]);
                if ($stmt->fetch()) {
                    ApiResponse::badRequest('Ez az email cím már regisztrálva van');
                }
                
                // Felhasználó létrehozása
                $stmt = $pdo->prepare("
                    INSERT INTO users (email, password, username, created_at)
                    VALUES (?, ?, ?, NOW())
                ");
                $result = $stmt->execute([
                    $input['email'],
                    password_hash($input['password'], PASSWORD_DEFAULT),
                    $input['username']
                ]);
                
                if ($result) {
                    $userId = $pdo->lastInsertId();
                    
                    // Automatikus bejelentkezés
                    $_SESSION['user_id'] = $userId;
                    $_SESSION['user_email'] = $input['email'];
                    $_SESSION['user_name'] = $input['username'];
                    
                    ApiResponse::created([
                        'user_id' => $userId,
                        'message' => 'Sikeres regisztráció'
                    ]);
                } else {
                    ApiResponse::serverError('Nem sikerült létrehozni a felhasználót');
                }
                break;
                
            case 'logout':
                // POST /api/v1/auth/logout
                session_unset();
                session_destroy();
                
                ApiResponse::success(['message' => 'Sikeres kijelentkezés']);
                break;
                
            default:
                ApiResponse::notFound('Ismeretlen auth művelet');
        }
        break;
        
    default:
        ApiResponse::methodNotAllowed(['GET', 'POST']);
}
