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
            <div class="relative">
                <input type="text"
                       id="full_name"
                       name="full_name"
                       placeholder="Kovács János"
                       minlength="2"
                       maxlength="50"
                       class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-black focus:border-transparent pr-10 <?= $errorField === 'full_name' ? 'border-red-500' : '' ?>"
                       value="<?php echo htmlspecialchars($oldInput['full_name']); ?>"
                       required>
                <?php if ($errorField === 'full_name'): ?>
                    <div class="absolute inset-y-0 right-3 flex items-center group">
                        <i class="las la-exclamation-circle text-red-500 text-xl cursor-help"></i>
                        <div class="absolute right-8 top-1/2 -translate-y-1/2 bg-red-500 text-white text-xs px-2 py-1 rounded whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity">
                            <?= $errorMessages[$error] ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="mb-4">
            <label for="email" class="block text-sm font-medium mb-1">Email cím *</label>
            <div class="relative">
                <input type="email"
                       id="email"
                       name="email"
                       placeholder="pelda@email.hu"
                       class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-black focus:border-transparent pr-10 <?= $errorField === 'email' ? 'border-red-500' : '' ?>"
                       value="<?php echo htmlspecialchars($oldInput['email']); ?>"
                       required>
                <?php if ($errorField === 'email'): ?>
                    <div class="absolute inset-y-0 right-3 flex items-center group">
                        <i class="las la-exclamation-circle text-red-500 text-xl cursor-help"></i>
                        <div class="absolute right-8 top-1/2 -translate-y-1/2 bg-red-500 text-white text-xs px-2 py-1 rounded whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity">
                            <?= $errorMessages[$error] ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
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
                       class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-black focus:border-transparent pr-16 <?= $errorField === 'password' ? 'border-red-500' : '' ?>"
                       required>

                <?php if ($errorField === 'password'): ?>
                    <div class="absolute inset-y-0 right-10 flex items-center group">
                        <i class="las la-exclamation-circle text-red-500 text-xl cursor-help"></i>
                        <div class="absolute right-8 top-1/2 -translate-y-1/2 bg-red-500 text-white text-xs px-2 py-1 rounded whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity z-10">
                            <?= $errorMessages[$error] ?>
                        </div>
                    </div>
                <?php endif; ?>

                <button type="button"
                        onclick="togglePassword('reg_password', this)"
                        class="absolute inset-y-0 right-3 flex items-center text-gray-500 hover:text-gray-700">
                    <i class="las la-eye text-lg eye-icon"></i>
                    <i class="las la-eye-slash text-lg hidden eye-off-icon"></i>
                </button>
            </div>
        </div>

        <!-- Jelszó megerősítése -->
        <div class="mb-6">
            <label for="password_confirm" class="block text-sm font-medium mb-1">Jelszó megerősítése *</label>

            <div class="relative">
                <input type="password"
                       id="reg_password_confirm"
                       name="password_confirm"
                       placeholder="Ismételd meg a jelszót"
                       class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-black focus:border-transparent pr-16 <?= $errorField === 'password_confirm' ? 'border-red-500' : '' ?>"
                       required>

                <?php if ($errorField === 'password_confirm'): ?>
                    <div class="absolute inset-y-0 right-10 flex items-center group">
                        <i class="las la-exclamation-circle text-red-500 text-xl cursor-help"></i>
                        <div class="absolute right-8 top-1/2 -translate-y-1/2 bg-red-500 text-white text-xs px-2 py-1 rounded whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity z-10">
                            <?= $errorMessages[$error] ?>
                        </div>
                    </div>
                <?php endif; ?>

                <button type="button"
                        onclick="togglePassword('reg_password_confirm', this)"
                        class="absolute inset-y-0 right-3 flex items-center text-gray-500 hover:text-gray-700">
                    <i class="las la-eye text-lg eye-icon"></i>
                    <i class="las la-eye-slash text-lg hidden eye-off-icon"></i>
                </button>
            </div>
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
