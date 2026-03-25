<?php
/**
 * Helper Functions
 * Globálisan elérhető segédfüggvények
 */

/**
 * CSRF token mező generálása
 */
function csrf_field(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return '<input type="hidden" name="csrf_token" value="' . $_SESSION['csrf_token'] . '">';
}

/**
 * CSRF token lekérése
 */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * CSRF token validálása
 */
function verify_csrf_token(): bool
{
    $token = $_POST['csrf_token'] ?? '';
    return !empty($token) && hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

/**
 * URL generálás
 */
function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

/**
 * Asset URL generálás
 */
function asset(string $path): string
{
    return BASE_URL . '/public/' . ltrim($path, '/');
}

/**
 * Átirányítás
 */
function redirect(string $url): void
{
    header('Location: ' . url($url));
    exit;
}

/**
 * Input sanitizálás
 */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Bejelentkezett felhasználó ID
 */
function user_id(): ?int
{
    return $_SESSION['user_id'] ?? null;
}

/**
 * Bejelentkezett-e a felhasználó
 */
function is_logged_in(): bool
{
    return !empty($_SESSION['user_id']);
}

/**
 * Admin-e a felhasználó
 */
function is_admin(): bool
{
    return !empty($_SESSION['is_admin']);
}

/**
 * Flash üzenet beállítása
 */
function flash(string $key, string $message): void
{
    $_SESSION['flash'][$key] = $message;
}

/**
 * Flash üzenet lekérése és törlése
 */
function get_flash(string $key): ?string
{
    $message = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $message;
}

/**
 * Pénzformázás
 */
function format_price(int|float $amount): string
{
    return number_format($amount, 0, ',', ' ') . ' Ft';
}

/**
 * Dátum formázás
 */
function format_date(string $date, string $format = 'Y.m.d H:i'): string
{
    return date($format, strtotime($date));
}

/**
 * Debug dump
 */
function dd(...$vars): void
{
    echo '<pre style="background:#1e1e1e;color:#fff;padding:15px;margin:10px;border-radius:5px;">';
    foreach ($vars as $var) {
        var_dump($var);
        echo "\n---\n";
    }
    echo '</pre>';
    die();
}

/**
 * Oldalcím beállítás
 */
function set_page_title(string $title): void
{
    $GLOBALS['page_title'] = $title;
}

/**
 * Oldalcím lekérés
 */
function get_page_title(): string
{
    return $GLOBALS['page_title'] ?? 'YoursyWear';
}

/**
 * View beillesztése
 */
function view(string $name, array $data = []): void
{
    extract($data);
    $path = APP_PATH . '/Views/' . $name . '.php';
    if (file_exists($path)) {
        require $path;
    }
}
