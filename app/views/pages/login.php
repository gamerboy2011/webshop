<?php
// Ha már be van jelentkezve, irányítsuk a főoldalra
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header("Location: /");
    exit;
}

require_once __DIR__ . "/../../library/customfunctions.php";

$error = $_GET['error'] ?? null;
$success = $_GET['success'] ?? null;
?>

<div class="max-w-md mx-auto mt-16 p-8 border rounded-lg shadow-md bg-white">
    <h2 class="text-2xl font-semibold mb-6 text-center">Bejelentkezés</h2>

    <?php if ($error === 'invalid'): ?>
        <div class="mb-4 p-3 bg-red-100 text-red-700 rounded">Hibás email cím vagy jelszó.</div>
    <?php elseif ($error === 'invalid_email'): ?>
        <div class="mb-4 p-3 bg-red-100 text-red-700 rounded">Helytelen email formátum.</div>
    <?php elseif ($error === 'inactive'): ?>
        <div class="mb-4 p-3 bg-red-100 text-red-700 rounded">A fiók nincs aktiválva.</div>
    <?php elseif ($error === 'empty'): ?>
        <div class="mb-4 p-3 bg-red-100 text-red-700 rounded">Kérjük, töltsd ki mindkét mezőt.</div>
    <?php endif; ?>

    <?php if ($success === 'registered'): ?>
        <div class="mb-4 p-3 bg-green-100 text-green-700 rounded">
            Sikeres regisztráció! Most már bejelentkezhetsz.
        </div>
    <?php endif; ?>

    <form method="POST" action="/webshop/index.php">
        <input type="hidden" name="action" value="login">
        <?php echo csrf_field(); ?>

        <div class="mb-4">
            <label for="email" class="block text-sm font-medium mb-1">Email cím</label>
            <input type="email"
                   id="email"
                   name="email"
                   placeholder="pelda@email.hu"
                   class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-black focus:border-transparent"
                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                   required>
        </div>

        <!-- Jelszó + ikon -->
        <div class="mb-6">
            <label for="password" class="block text-sm font-medium mb-1">Jelszó</label>

            <div class="relative">
                <input type="password"
                       id="login_password"
                       name="password"
                       placeholder="••••••••"
                       class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-black focus:border-transparent"
                       required>

                <button type="button"
                        onclick="togglePassword('login_password', this)"
                        class="absolute inset-y-0 right-3 flex items-center text-gray-500 hover:text-gray-700">
                    <!-- szem ikon -->
                    <svg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24'
                         stroke-width='1.5' stroke='currentColor'
                         class='w-5 h-5 eye-icon'>
                        <path stroke-linecap='round' stroke-linejoin='round'
                              d='M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z'/>
                        <path stroke-linecap='round' stroke-linejoin='round'
                              d='M15 12a3 3 0 11-6 0 3 3 0 016 0z'/>
                    </svg>

                    <!-- áthúzott szem -->
                    <svg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24'
                         stroke-width='1.5' stroke='currentColor'
                         class='w-5 h-5 hidden eye-off-icon'>
                        <path stroke-linecap='round' stroke-linejoin='round'
                              d='M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c1.676 0 3.27-.33 4.712-.928M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.5a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228L21 21'/>
                    </svg>
                </button>
            </div>
        </div>

        <button type="submit"
                class="w-full bg-black text-white py-3 rounded-lg font-medium hover:bg-gray-800 transition">
            Bejelentkezés
        </button>

        <div class="mt-4 text-center text-sm">
            <p>Nincs még fiókod?
                <a href="/webshop/register" class="text-blue-600 hover:underline font-medium">
                    Regisztrálj itt
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
