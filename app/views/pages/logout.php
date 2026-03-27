<?php





if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); 
    die('Csak POST kérés engedélyezett.');
}


if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    die('CSRF token érvénytelen.');
}


$_SESSION = [];


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


session_destroy();


header("Location: /?logout=success");
exit;