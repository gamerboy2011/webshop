<?php
// CSRF token generálása
function generate_csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// CSRF token ellenőrzése
function verify_csrf_token($token): bool {
    return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Form mező generálása CSRF token számára
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . generate_csrf_token() . '">';
}

// Session biztonságos indítása
function secure_session_start(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
        if (empty($_SESSION['last_regeneration'])) {
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
    }
}

// Redirect helper
function redirect($path) {
    $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/';
    if ($basePath === '//') $basePath = '/';
    header('Location: ' . $basePath . ltrim($path, '/'));
    exit;
}
?>