<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rendelés #</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vásárló</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Dátum</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cím</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Összeg</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            <?php foreach ($orders as $o): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4">
                        <span class="font-medium text-gray-900">#<?= $o['order_id'] ?></span>
                    </td>
                    <td class="px-6 py-4">
                        <p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($o['username'] ?? 'Vendég') ?></p>
                        <p class="text-xs text-gray-500"><?= htmlspecialchars($o['email'] ?? '') ?></p>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">
                        <?= date('Y.m.d H:i', strtotime($o['order_date'])) ?>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">
                        <?= htmlspecialchars(($o['shipping_postcode'] ?? '') . ' ' . ($o['shipping_city'] ?? '')) ?>
                        <span class="block text-xs text-gray-400">
                            <?= htmlspecialchars(($o['shipping_street'] ?? '') . ' ' . ($o['shipping_house_number'] ?? '')) ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <span class="font-bold text-gray-900">
                            <?= number_format($o['total_price'] ?? 0, 0, ',', ' ') ?> Ft
                        </span>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <?php if (empty($orders)): ?>
        <div class="p-8 text-center text-gray-500">
            <i class="las la-shopping-cart text-4xl mb-2"></i>
            <p>Még nincsenek rendelések</p>
        </div>
    <?php endif; ?>
</div>
