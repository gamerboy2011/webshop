<?php

class AuthController
{
    private User $userModel;

    public function __construct(PDO $pdo)
    {
        $this->userModel = new User($pdo);
    }

    public function handle(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/');
        }

        $action = $_POST['action'] ?? '';

        if ($action === 'login') {
            $this->login();
        } elseif ($action === 'register') {
            $this->register();
        } else {
            redirect('/');
        }
    }

    private function login(): void
    {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            redirect('/login?error=empty');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            redirect('/login?error=invalid_email');
        }

        $user = $this->userModel->findByEmail($email);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            redirect('/login?error=invalid');
        }

        if ((int)$user['is_active'] !== 1) {
            redirect('/login?error=inactive');
        }

        session_regenerate_id(true);

        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['role_id'] = $user['role_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['logged_in'] = true;

        redirect('/?login=success');
    }

    private function register(): void
    {
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';

        if (
            strlen($fullName) < 2 ||
            strlen($fullName) > 50 ||
            !preg_match('/^[A-Za-zÁÉÍÓÖŐÚÜŰáéíóöőúüű\-\' ]+$/', $fullName)
        ) {
            redirect('/register?error=invalid_name');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            redirect('/register?error=invalid_email');
        }

        if ($this->userModel->findByEmail($email)) {
            redirect('/register?error=email_exists');
        }

        if ($password !== $passwordConfirm) {
            redirect('/register?error=password_mismatch');
        }

        if (strlen($password) < 6) {
            redirect('/register?error=password_too_short');
        }

        if (strlen($password) > 13) {
            redirect('/register?error=password_too_long');
        }

        if (
            !preg_match('/[a-z]/', $password) ||
            !preg_match('/[A-Z]/', $password) ||
            !preg_match('/[0-9]/', $password)
        ) {
            redirect('/register?error=password_complexity');
        }

        $token = bin2hex(random_bytes(32));

        $success = $this->userModel->create([
            'username' => $fullName,
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'phone' => null,
            'role_id' => 1,
            'is_active' => 0,
            'activation_token' => $token
        ]);

        if ($success) {

            // 🔵 FEJLESZTŐI MÓD – Tailwind-es aktivációs oldal
            $activationLink = "http://{$_SERVER['HTTP_HOST']}/webshop/activate?token=$token";

            echo "
<!DOCTYPE html>
<html lang='hu'>
<head>
    <meta charset='UTF-8'>
    <title>Fejlesztői aktiváció</title>
    <script src='https://cdn.tailwindcss.com'></script>
</head>

<body class='bg-gray-100 flex items-center justify-center min-h-screen'>

    <div class='bg-white shadow-lg rounded-xl p-10 text-center max-w-lg mx-auto'>
        
        <h2 class='text-2xl font-semibold text-gray-900 mb-4'>
            Fejlesztői aktivációs link
        </h2>

        <p class='text-gray-600 mb-6'>
            E-mail szerver hiányában itt tudod aktiválni a fiókot:
        </p>

        <a href='$activationLink'
           class='inline-block bg-black text-white px-6 py-3 rounded-lg text-lg font-medium hover:bg-gray-800 transition'>
            Aktiválás megnyitása
        </a>

        <p class='text-sm text-gray-400 mt-6'>
            <b>Megjegyzés:</b> éles szerveren ez a rész automatikusan el lesz távolítva.
        </p>
    </div>

</body>
</html>
";
            exit;
        } else {
            redirect('/register?error=database');
        }
    }
}
