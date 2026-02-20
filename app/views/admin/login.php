<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Bejelentkezés - Yoursy Wear</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://maxst.icons8.com/vue-static/landings/line-awesome/line-awesome/1.3.0/css/line-awesome.min.css">
    <link rel="icon" type="image/svg+xml" href="/webshop/favicon.svg">
</head>
<body class="bg-gray-900 min-h-screen flex items-center justify-center">
    
    <div class="w-full max-w-md">
        <div class="bg-white rounded-2xl shadow-2xl p-8">
            
            <!-- Logo -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-gray-900 rounded-xl mb-4">
                    <i class="las la-lock text-3xl text-white"></i>
                </div>
                <h1 class="text-2xl font-bold text-gray-900">Admin Panel</h1>
                <p class="text-gray-500 text-sm mt-1">Yoursy Wear</p>
            </div>
            
            <!-- Hibaüzenet -->
            <?php if (!empty($loginError)): ?>
                <div class="bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-lg mb-6 text-sm">
                    <i class="las la-exclamation-circle mr-2"></i>
                    <?= htmlspecialchars($loginError) ?>
                </div>
            <?php endif; ?>
            
            <!-- Form -->
            <form method="post" action="/webshop/yw-admin/login">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="admin_login">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                    <div class="relative">
                        <input type="email" name="email" required
                               class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-transparent"
                               placeholder="admin@example.com">
                        <i class="las la-envelope absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xl"></i>
                    </div>
                </div>
                
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Jelszó</label>
                    <div class="relative">
                        <input type="password" name="password" required
                               class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-transparent"
                               placeholder="••••••••">
                        <i class="las la-key absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xl"></i>
                    </div>
                </div>
                
                <button type="submit" 
                        class="w-full bg-gray-900 text-white py-3 rounded-lg font-medium hover:bg-gray-800 transition">
                    <i class="las la-sign-in-alt mr-2"></i>
                    Bejelentkezés
                </button>
            </form>
            
            <!-- Vissza a shophoz -->
            <div class="mt-6 text-center">
                <a href="/webshop/" class="text-sm text-gray-500 hover:text-gray-700">
                    <i class="las la-arrow-left mr-1"></i>
                    Vissza a webshophoz
                </a>
            </div>
            
        </div>
    </div>
    
</body>
</html>
