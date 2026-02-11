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

<div class="max-w-md mx-auto mt-16 p-8 border rounded-lg shadow-md">
    <h2 class="text-2xl font-semibold mb-6 text-center">Bejelentkezés</h2>

    <?php if ($error === 'invalid'): ?>
        <div class="mb-4 p-3 bg-red-100 text-red-700 rounded">
            Hibás email cím vagy jelszó.
        </div>
    <?php elseif ($error === 'invalid_email'): ?>
        <div class="mb-4 p-3 bg-red-100 text-red-700 rounded">
            Helytelen email formátum.
        </div>
    <?php elseif ($error === 'inactive'): ?>
        <div class="mb-4 p-3 bg-red-100 text-red-700 rounded">
            A fiók nincs aktiválva. Kérjük, lépj kapcsolatba az ügyfélszolgálattal.
        </div>
    <?php elseif ($error === 'empty'): ?>
        <div class="mb-4 p-3 bg-red-100 text-red-700 rounded">
            Kérjük, töltsd ki mindkét mezőt.
        </div>
    <?php endif; ?>

    <?php if ($success === 'registered'): ?>
        <div class="mb-4 p-3 bg-green-100 text-green-700 rounded">
            Sikeres regisztráció! Most már bejelentkezhetsz.
        </div>
    <?php endif; ?>

    <form method="POST" action="">
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

        <div class="mb-6">
            <label for="password" class="block text-sm font-medium mb-1">Jelszó</label>
            <input type="password"
                   id="password"
                   name="password"
                   placeholder="••••••••"
                   class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-black focus:border-transparent"
                   required>
        </div>

        <button type="submit" class="w-full bg-black text-white py-3 rounded-lg font-medium hover:bg-gray-800 transition">
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