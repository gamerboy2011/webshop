<!-- STATISZTIKÁK -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    
    <!-- Termékek -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Összes termék</p>
                <p class="text-3xl font-bold text-gray-900"><?= $stats['products'] ?? 0 ?></p>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                <i class="las la-box text-2xl text-blue-600"></i>
            </div>
        </div>
        <a href="/webshop/yw-admin/products" class="text-sm text-blue-600 mt-4 inline-block hover:underline">
            Termékek kezelése →
        </a>
    </div>
    
    <!-- Akciós termékek -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Akciós termékek</p>
                <p class="text-3xl font-bold text-red-600"><?= $stats['sale_products'] ?? 0 ?></p>
            </div>
            <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                <i class="las la-percent text-2xl text-red-600"></i>
            </div>
        </div>
    </div>
    
    <!-- Felhasználók -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Felhasználók</p>
                <p class="text-3xl font-bold text-gray-900"><?= $stats['users'] ?? 0 ?></p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                <i class="las la-users text-2xl text-green-600"></i>
            </div>
        </div>
        <a href="/webshop/yw-admin/users" class="text-sm text-green-600 mt-4 inline-block hover:underline">
            Felhasználók kezelése →
        </a>
    </div>
    
    <!-- Rendelések -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Összes rendelés</p>
                <p class="text-3xl font-bold text-gray-900"><?= $stats['orders'] ?? 0 ?></p>
            </div>
            <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                <i class="las la-shopping-cart text-2xl text-purple-600"></i>
            </div>
        </div>
        <p class="text-sm text-gray-500 mt-2">Ma: <?= $stats['orders_today'] ?? 0 ?> db</p>
    </div>
    
</div>

<!-- BEVÉTEL -->
<div class="bg-gradient-to-r from-gray-900 to-gray-700 rounded-xl shadow-lg p-8 mb-8 text-white">
    <div class="flex items-center justify-between">
        <div>
            <p class="text-gray-300 text-sm">Összes bevétel</p>
            <p class="text-4xl font-bold mt-2">
                <?= number_format($stats['revenue'] ?? 0, 0, ',', ' ') ?> Ft
            </p>
        </div>
        <i class="las la-chart-line text-6xl text-gray-600"></i>
    </div>
</div>

<!-- LEGUTÓBBI RENDELÉSEK -->
<div class="bg-white rounded-xl shadow-sm">
    <div class="px-6 py-4 border-b flex items-center justify-between">
        <h2 class="text-lg font-semibold">Legutóbbi rendelések</h2>
        <a href="/webshop/yw-admin/orders" class="text-sm text-blue-600 hover:underline">
            Összes rendelés →
        </a>
    </div>
    
    <?php if (empty($recentOrders)): ?>
        <div class="p-8 text-center text-gray-500">
            <i class="las la-inbox text-4xl mb-2"></i>
            <p>Még nincsenek rendelések</p>
        </div>
    <?php else: ?>
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rendelés #</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vásárló</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Dátum</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Összeg</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <?php foreach ($recentOrders as $order): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm font-medium">#<?= $order['order_id'] ?></td>
                        <td class="px-6 py-4 text-sm">
                            <?= htmlspecialchars($order['username'] ?? 'Vendég') ?>
                            <span class="text-gray-400 text-xs block"><?= htmlspecialchars($order['email'] ?? '') ?></span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            <?= date('Y.m.d H:i', strtotime($order['order_date'])) ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-right font-medium">
                            <?= number_format($order['total_price'] ?? 0, 0, ',', ' ') ?> Ft
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
