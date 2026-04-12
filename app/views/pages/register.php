<?php

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header("Location: /webshop/");
    exit;
}

$error = $_GET['error'] ?? null;
$oldInput = [
    'full_name' => $_POST['full_name'] ?? '',
    'email' => $_POST['email'] ?? ''
];

$fieldErrors = [
    'invalid_name' => 'full_name',
    'invalid_email' => 'email',
    'email_exists' => 'email',
    'password_mismatch' => 'password_confirm',
    'password_too_short' => 'password',
    'password_complexity' => 'password',
];
$errorField = $error ? ($fieldErrors[$error] ?? null) : null;
$errorMessages = [
    'invalid_name' => 'Csak betűk, szóköz, kötőjel és aposztrf (2-50 karakter)',
    'invalid_email' => 'Helytelen email formátum',
    'email_exists' => 'Ez az email már regisztrálva van',
    'password_mismatch' => 'A két jelszó nem egyezik',
    'password_too_short' => 'Legalább 6 karakter szükséges',
    'password_complexity' => 'Kis- és nagybetű, valamint szám szükséges',
    'database' => 'Szerverhiba történt'
];
?>

<div class="max-w-md mx-auto mt-16 p-8 border rounded-lg shadow-md bg-white">
    <h2 class="text-2xl font-semibold mb-6 text-center">Regisztráció</h2>

    <form id="register-form" method="POST" action="" novalidate>
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
                   class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-black focus:border-transparent <?= $errorField === 'full_name' ? 'border-red-500' : '' ?>"
                   value="<?php echo htmlspecialchars($oldInput['full_name']); ?>"
                   required>
            <?php if ($errorField === 'full_name'): ?>
                <p class="text-red-500 text-xs mt-1 flex items-center gap-1">
                    <i class="las la-exclamation-circle"></i>
                    <?= $errorMessages[$error] ?>
                </p>
            <?php endif; ?>
        </div>

        <div class="mb-4">
            <label for="email" class="block text-sm font-medium mb-1">Email cím *</label>
            <input type="email"
                   id="email"
                   name="email"
                   placeholder="pelda@email.hu"
                   class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-black focus:border-transparent <?= $errorField === 'email' ? 'border-red-500' : '' ?>"
                   value="<?php echo htmlspecialchars($oldInput['email']); ?>"
                   required>
            <?php if ($errorField === 'email'): ?>
                <p class="text-red-500 text-xs mt-1 flex items-center gap-1">
                    <i class="las la-exclamation-circle"></i>
                    <?= $errorMessages[$error] ?>
                </p>
            <?php endif; ?>
        </div>

        <!-- Jelszó -->
        <div class="mb-4">
            <label for="password" class="block text-sm font-medium mb-1">Jelszó *</label>

            <div class="relative">
                <input type="password"
                       id="reg_password"
                       name="password"
                       placeholder="Legalább 6 karakter, kis- és nagybetű, szám"
                       minlength="6"
                       class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-black focus:border-transparent pr-10 <?= $errorField === 'password' ? 'border-red-500' : '' ?>"
                       required>

                <button type="button"
                        onclick="togglePassword('reg_password', this)"
                        class="absolute inset-y-0 right-3 flex items-center text-gray-500 hover:text-gray-700">
                    <svg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24'
                         stroke-width='1.5' stroke='currentColor'
                         class='w-5 h-5 eye-icon'>
                        <path stroke-linecap='round' stroke-linejoin='round'
                              d='M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z'/>
                        <path stroke-linecap='round' stroke-linejoin='round'
                              d='M15 12a3 3 0 11-6 0 3 3 0 016 0z'/>
                    </svg>
                    <svg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24'
                         stroke-width='1.5' stroke='currentColor'
                         class='w-5 h-5 hidden eye-off-icon'>
                        <path stroke-linecap='round' stroke-linejoin='round'
                              d='M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c1.676 0 3.27-.33 4.712-.928M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.5a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228L21 21'/>
                    </svg>
                </button>
            </div>
            <?php if ($errorField === 'password'): ?>
                <p class="text-red-500 text-xs mt-1 flex items-center gap-1">
                    <i class="las la-exclamation-circle"></i>
                    <?= $errorMessages[$error] ?>
                </p>
            <?php endif; ?>
        </div>

        <!-- Jelszó megerősítése -->
        <div class="mb-6">
            <label for="password_confirm" class="block text-sm font-medium mb-1">Jelszó megerősítése *</label>

            <div class="relative">
                <input type="password"
                       id="reg_password_confirm"
                       name="password_confirm"
                       placeholder="Ismételd meg a jelszót"
                       class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-black focus:border-transparent pr-10 <?= $errorField === 'password_confirm' ? 'border-red-500' : '' ?>"
                       required>

                <button type="button"
                        onclick="togglePassword('reg_password_confirm', this)"
                        class="absolute inset-y-0 right-3 flex items-center text-gray-500 hover:text-gray-700">
                    <svg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24'
                         stroke-width='1.5' stroke='currentColor'
                         class='w-5 h-5 eye-icon'>
                        <path stroke-linecap='round' stroke-linejoin='round'
                              d='M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z'/>
                        <path stroke-linecap='round' stroke-linejoin='round'
                              d='M15 12a3 3 0 11-6 0 3 3 0 016 0z'/>
                    </svg>
                    <svg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24'
                         stroke-width='1.5' stroke='currentColor'
                         class='w-5 h-5 hidden eye-off-icon'>
                        <path stroke-linecap='round' stroke-linejoin='round'
                              d='M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c1.676 0 3.27-.33 4.712-.928M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.5a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228L21 21'/>
                    </svg>
                </button>
            </div>
            <?php if ($errorField === 'password_confirm'): ?>
                <p class="text-red-500 text-xs mt-1 flex items-center gap-1">
                    <i class="las la-exclamation-circle"></i>
                    <?= $errorMessages[$error] ?>
                </p>
            <?php endif; ?>
        </div>

        <button type="submit"
                class="w-full bg-black text-white py-3 rounded-lg font-medium hover:bg-gray-800 transition">
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

<script>
function togglePassword(id, btn) {
    const input = document.getElementById(id);
    const eye = btn.querySelector('.eye-icon');
    const eyeOff = btn.querySelector('.eye-off-icon');

    if (input.type === "password") {
        input.type = "text";
        eye.classList.add("hidden");
        eyeOff.classList.remove("hidden");
    } else {
        input.type = "password";
        eye.classList.remove("hidden");
        eyeOff.classList.add("hidden");
    }
}
</script>
