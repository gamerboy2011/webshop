<?php

class AuthController
{
    private User $userModel;

    public function __construct(PDO $pdo)
    {
        // Session már elindult az index.php-ben
        $this->userModel = new User($pdo);
    }

    public function handle(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/');
        }

        $action = $_POST['action'] ?? '';

        // CSRF token ellenőrzése már az index.php-ben történt

        if ($action === 'login') {
            $this->login();
        } elseif ($action === 'register') {
            $this->register();
        } else {
            $this->redirect('/');
        }
    }

    private function login(): void
    {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        // Validáció
        if (empty($email) || empty($password)) {
            $this->redirect('/login?error=empty');
        }

        // Email formátum ellenőrzés
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->redirect('/login?error=invalid_email');
        }

        $user = $this->userModel->findByEmail($email);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $this->redirect('/login?error=invalid');
        }

        if ((int)$user['is_active'] !== 1) {
            $this->redirect('/login?error=inactive');
        }

        // SESSION FIXATION védelem - új session ID
        session_regenerate_id(true);

        // Felhasználói adatok sessionben
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['role_id'] = $user['role_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();

        // Sikeres bejelentkezés
        $this->redirect('/?login=success');
    }

    private function register(): void
    {
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';

        // Teljes név validáció (bővítve ékezetekkel és kötőjellel)
        if (
            strlen($fullName) < 2 ||
            strlen($fullName) > 50 ||
            !preg_match('/^[A-Za-zÁÉÍÓÖŐÚÜŰáéíóöőúüű\-\' ]+$/', $fullName)
        ) {
            $this->redirect('/register?error=invalid_name');
        }

        // Email validáció
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->redirect('/register?error=invalid_email');
        }

        // Email egyediség ellenőrzés
        if ($this->userModel->findByEmail($email)) {
            $this->redirect('/register?error=email_exists');
        }

        // Jelszó validáció (lazított szabályok)
        if ($password !== $passwordConfirm) {
            $this->redirect('/register?error=password_mismatch');
        }

        if (strlen($password) < 8) {
            $this->redirect('/register?error=password_too_short');
        }

        if (strlen($password) > 72) { // Bcrypt limit
            $this->redirect('/register?error=password_too_long');
        }

        // Jelszó komplexitás (opcionális, de ajánlott)
        if (
            !preg_match('/[a-z]/', $password) ||
            !preg_match('/[A-Z]/', $password) ||
            !preg_match('/[0-9]/', $password)
        ) {
            $this->redirect('/register?error=password_complexity');
        }

        // Felhasználó létrehozása
        $success = $this->userModel->create([
            'username' => $fullName,
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'phone' => null,
            'role_id' => 1, // Alapértelmezett felhasználó
            'is_active' => 1,
            'activation_token' => null
        ]);

        if ($success) {
            // Automatikus bejelentkezés az új felhasználóval
            $user = $this->userModel->findByEmail($email);
            
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['role_id'] = $user['role_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['logged_in'] = true;
            
            $this->redirect('/?register=success');
        } else {
            $this->redirect('/register?error=database');
        }
    }

    /**
     * Átirányítás helper függvény
     */
    private function redirect(string $path): void
    {
        // Dinamikus alapútvonal használata
        $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/';
        if ($basePath === '//') $basePath = '/';
        
        header("Location: " . $basePath . ltrim($path, '/'));
        exit;
    }
}