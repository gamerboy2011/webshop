<?php
// Ha már be van jelentkezve, irányítsuk a főoldalra
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header("Location: /");
    exit;
}

require_once __DIR__ . "/../../library/customfunctions.php";

$error = $_GET['error'] ?? null;
$oldInput = [
    'full_name' => $_POST['full_name'] ?? '',
    'email' => $_POST['email'] ?? ''
];
?>

<div class="max-w-md mx-auto mt-16 p-8 border rounded-lg shadow-md">
    <h2 class="text-2xl font-semibold mb-6 text-center">Regisztráció</h2>

    <?php if ($error === 'invalid_name'): ?>
        <div class="mb-4 p-3 bg-red-100 text-red-700 rounded">
            A név csak betűket, szóközt, kötőjelet és aposztrófot tartalmazhat (2-50 karakter).
        </div>
    <?php elseif ($error === 'invalid_email'): ?>
        <div class="mb-4 p-3 bg-red-100 text-red-700 rounded">
            Helytelen email cím formátum.
        </div>
    <?php elseif ($error === 'email_exists'): ?>
        <div class="mb-4 p-3 bg-red-100 text-red-700 rounded">
            Ez az email cím már regisztrálva van.
        </div>
    <?php elseif ($error === 'password_mismatch'): ?>
        <div class="mb-4 p-3 bg-red-100 text-red-700 rounded">
            A két jelszó nem egyezik.
        </div>
    <?php elseif ($error === 'password_too_short'): ?>
        <div class="mb-4 p-3 bg-red-100 text-red-700 rounded">
            A jelszónak legalább 8 karakter hosszúnak kell lennie.
        </div>
    <?php elseif ($error === 'password_too_long'): ?>
        <div class="mb-4 p-3 bg-red-100 text-red-700 rounded">
            A jelszó túl hosszú (max 72 karakter).
        </div>
    <?php elseif ($error === 'password_complexity'): ?>
        <div class="mb-4 p-3 bg-red-100 text-red-700 rounded">
            A jelszónak tartalmaznia kell kis- és nagybetűt, valamint számot.
        </div>
    <?php elseif ($error === 'database'): ?>
        <div class="mb-4 p-3 bg-red-100 text-red-700 rounded">
            Szerverhiba történt. Kérjük, próbáld újra később.
        </div>
    <?php endif; ?>

    <form id="register-form" method="POST" action="">
        <input type="hidden" name="action" value="register">
        <?php echo csrf_field(); ?>

        <div class="mb-4">
            <label for="full_name" class="block text-sm font-medium mb-1">Teljes név *</label>
            <input type="text"
                   id="full_name"
                   name="full_name"
                   placeholder="Kovács János"
                   minlength="2"
                   maxlength="50"
                   class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-black focus:border-transparent"
                   value="<?php echo htmlspecialchars($oldInput['full_name']); ?>"
                   required>
            <p class="text-xs text-gray-500 mt-1">2-50 karakter, csak betűk, szóköz, kötőjel</p>
        </div>
        
        <div class="mb-4">
            <label for="email" class="block text-sm font-medium mb-1">Email cím *</label>
            <input type="email"
                   id="email"
                   name="email"
                   placeholder="pelda@email.hu"
                   class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-black focus:border-transparent"
                   value="<?php echo htmlspecialchars($oldInput['email']); ?>"
                   required>
        </div>
        
        <div class="mb-4">
            <label for="password" class="block text-sm font-medium mb-1">Jelszó *</label>
            <input type="password"
                   id="password"
                   name="password"
                   placeholder="Legalább 8 karakter"
                   minlength="8"
                   class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-black focus:border-transparent"
                   required>
            <p class="text-xs text-gray-500 mt-1">Minimum 8 karakter, tartalmaznia kell kis- és nagybetűt, számot</p>
        </div>
        
        <div class="mb-6">
            <label for="password_confirm" class="block text-sm font-medium mb-1">Jelszó megerősítése *</label>
            <input type="password"
                   id="password_confirm"
                   name="password_confirm"
                   placeholder="Ismételd meg a jelszót"
                   class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-black focus:border-transparent"
                   required>
        </div>
        
        <button type="submit" class="w-full bg-black text-white py-3 rounded-lg font-medium hover:bg-gray-800 transition">
            Regisztráció
        </button>

        <div class="mt-4 text-center text-sm">
            <p>Már van fiókod? 
                <a href="/webshop/login" class="text-blue-600 hover:underline font-medium">
                    Jelentkezz be itt
                </a>
            </p>
        </div>
    </form>
</div>

<!-- Kliensoldali validáció (opcionális, de UX javítása) -->
<script>
document.getElementById('register-form').addEventListener('submit', function (event) {
    const password = document.getElementById('password');
    const passwordConfirm = document.getElementById('password_confirm');
    let isValid = true;
    
    // Jelszó egyezés ellenőrzése
    if (password.value !== passwordConfirm.value) {
        passwordConfirm.style.borderColor = 'red';
        isValid = false;
    } else {
        passwordConfirm.style.borderColor = '';
    }
    
    // Jelszó hossz ellenőrzése
    if (password.value.length < 8) {
        password.style.borderColor = 'red';
        isValid = false;
    } else {
        password.style.borderColor = '';
    }
    
    if (!isValid) {
        event.preventDefault();
        alert('Kérlek, ellenőrizd a megadott adatokat!');
    }
});
</script>