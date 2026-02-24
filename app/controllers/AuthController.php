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
            $activationLink = "http://{$_SERVER['HTTP_HOST']}/webshop/activate?token=$token";
            
            // Email küldése
            $emailSent = $this->sendActivationEmail($email, $fullName, $activationLink);
            
            // Session-be mentjük az adatokat az email-sent oldalhoz
            $_SESSION['registration_email'] = $email;
            $_SESSION['registration_name'] = $fullName;
            
            // Fejlesztői mód: ha nincs email szerver, mentjük az aktivációs linket is
            if (!$emailSent) {
                $_SESSION['dev_activation_link'] = $activationLink;
            }
            
            redirect('/email-elkuldve');
        } else {
            redirect('/register?error=database');
        }
    }
    
    private function sendActivationEmail(string $email, string $name, string $activationLink): bool
    {
        $subject = "YoursyWear - Fiók aktiválás";
        
        $htmlBody = "
        <!DOCTYPE html>
        <html>
        <head><meta charset='UTF-8'></head>
        <body style='font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px;'>
            <div style='max-width: 600px; margin: 0 auto; background: white; border-radius: 10px; overflow: hidden;'>
                <div style='background: #000; color: white; padding: 30px; text-align: center;'>
                    <h1 style='margin: 0;'>YoursyWear</h1>
                </div>
                
                <div style='padding: 30px;'>
                    <h2 style='color: #333;'>Üdvözöljük, {$name}!</h2>
                    <p style='color: #666;'>Köszönjük, hogy regisztráltál a YoursyWear webshopban!</p>
                    <p style='color: #666;'>A fiókod aktiválásához kattints az alábbi gombra:</p>
                    
                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='{$activationLink}' 
                           style='display: inline-block; background: #000; color: white; padding: 15px 30px; 
                                  text-decoration: none; border-radius: 8px; font-weight: bold;'>
                            Fiók aktiválása
                        </a>
                    </div>
                    
                    <p style='color: #999; font-size: 14px;'>
                        Ha nem te regisztráltál, hagyd figyelmen kívül ezt az emailt.
                    </p>
                    
                    <p style='color: #999; font-size: 12px; margin-top: 20px;'>
                        Ha a gomb nem működik, másold be ezt a linket a böngésződbe:<br>
                        <a href='{$activationLink}' style='color: #666;'>{$activationLink}</a>
                    </p>
                </div>
                
                <div style='background: #f5f5f5; padding: 20px; text-align: center; font-size: 12px; color: #999;'>
                    © " . date('Y') . " YoursyWear. Minden jog fenntartva.
                </div>
            </div>
        </body>
        </html>";
        
        require_once __DIR__ . '/../helpers/Mail.php';
        $result = Mail::send($email, $subject, $htmlBody, $name);
        return $result['success'];
    }
}
