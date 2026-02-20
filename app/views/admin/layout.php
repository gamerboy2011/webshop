<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Yoursy Wear</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://maxst.icons8.com/vue-static/landings/line-awesome/line-awesome/1.3.0/css/line-awesome.min.css">
    <link rel="icon" href="/webshop/favicon.svg">
</head>
<body class="bg-gray-100">
    <div class="flex min-h-screen">
        
        <!-- SIDEBAR -->
        <aside class="w-64 bg-gray-900 text-white flex-shrink-0">
            <div class="p-6">
                <a href="/webshop/yw-admin" class="text-xl font-bold">
                    <i class="las la-cog mr-2"></i>YW Admin
                </a>
            </div>
            
            <nav class="mt-6">
                <a href="/webshop/yw-admin" 
                   class="flex items-center px-6 py-3 <?= $page === 'dashboard' ? 'bg-gray-800 border-l-4 border-white' : 'hover:bg-gray-800' ?>">
                    <i class="las la-chart-bar text-xl mr-3"></i>
                    Dashboard
                </a>
                
                <a href="/webshop/yw-admin/products" 
                   class="flex items-center px-6 py-3 <?= $page === 'products' || $page === 'product-edit' ? 'bg-gray-800 border-l-4 border-white' : 'hover:bg-gray-800' ?>">
                    <i class="las la-box text-xl mr-3"></i>
                    Termékek
                </a>
                
                <a href="/webshop/yw-admin/stock" 
                   class="flex items-center px-6 py-3 <?= $page === 'stock' ? 'bg-gray-800 border-l-4 border-white' : 'hover:bg-gray-800' ?>">
                    <i class="las la-warehouse text-xl mr-3"></i>
                    Készlet
                </a>
                
                <a href="/webshop/yw-admin/orders"
                   class="flex items-center px-6 py-3 <?= $page === 'orders' ? 'bg-gray-800 border-l-4 border-white' : 'hover:bg-gray-800' ?>">
                    <i class="las la-shopping-cart text-xl mr-3"></i>
                    Rendelések
                </a>
                
                <a href="/webshop/yw-admin/users" 
                   class="flex items-center px-6 py-3 <?= $page === 'users' ? 'bg-gray-800 border-l-4 border-white' : 'hover:bg-gray-800' ?>">
                    <i class="las la-users text-xl mr-3"></i>
                    Felhasználók
                </a>
                
                <div class="border-t border-gray-700 mt-6 pt-6">
                    <a href="/webshop/" target="_blank" 
                       class="flex items-center px-6 py-3 hover:bg-gray-800 text-gray-400">
                        <i class="las la-external-link-alt text-xl mr-3"></i>
                        Webshop megtekintése
                    </a>
                    
                    <form method="post" action="/webshop/yw-admin">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="admin_logout">
                        <button type="submit" class="w-full flex items-center px-6 py-3 hover:bg-gray-800 text-red-400">
                            <i class="las la-sign-out-alt text-xl mr-3"></i>
                            Kijelentkezés
                        </button>
                    </form>
                </div>
            </nav>
        </aside>
        
        <!-- MAIN CONTENT -->
        <main class="flex-1 overflow-x-hidden">
            
            <!-- TOP BAR -->
            <header class="bg-white shadow-sm">
                <div class="flex items-center justify-between px-8 py-4">
                    <h1 class="text-2xl font-semibold text-gray-800">
                        <?php
                        $titles = [
                            'dashboard' => 'Dashboard',
                            'products' => 'Termékek',
                            'product-edit' => isset($product) ? 'Termék szerkesztése' : 'Új termék',
                            'stock' => 'Készletkezelés',
                            'orders' => 'Rendelések',
                            'users' => 'Felhasználók'
                        ];
                        echo $titles[$page] ?? 'Admin';
                        ?>
                    </h1>
                    <div class="flex items-center gap-4">
                        <span class="text-gray-600">
                            <i class="las la-user-shield mr-1"></i>
                            <?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?>
                        </span>
                    </div>
                </div>
            </header>
            
            <!-- PAGE CONTENT -->
            <div class="p-8">
                <?php require $viewFile; ?>
            </div>
            
        </main>
    </div>
</body>
</html>
