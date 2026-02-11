<?php
/**
 * Kijelentkezés kezelése (POST kérésre)
 */

// Csak POST kérés engedélyezett
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    die('Csak POST kérés engedélyezett.');
}

// CSRF token ellenőrzése
if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    die('CSRF token érvénytelen.');
}

// Session megsemmisítése
$_SESSION = [];

// Session cookie törlése
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Session megsemmisítése
session_destroy();

// Átirányítás a főoldalra
header("Location: /?logout=success");
exit;