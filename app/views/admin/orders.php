<?php if (isset($_GET['status_updated'])): ?>
    <div class="bg-green-100 text-green-700 p-4 rounded-lg mb-6">
        <i class="las la-check-circle mr-2"></i> Rendelés állapota sikeresen frissítve!
    </div>
<?php endif; ?>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rendelés #</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vásárló</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Dátum</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Szállítás</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Állapot</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Összeg</th>
                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Műveletek</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            <?php foreach ($orders as $o): 
                $status = $o['status'] ?? 'pending';
                $statusColors = [
                    'pending' => 'bg-yellow-100 text-yellow-800',
                    'confirmed' => 'bg-blue-100 text-blue-800',
                    'shipped' => 'bg-purple-100 text-purple-800',
                    'delivered' => 'bg-green-100 text-green-800'
                ];
                $statusTexts = [
                    'pending' => 'Függőben',
                    'confirmed' => 'Megerősítve',
                    'shipped' => 'Feladás alatt',
                    'delivered' => 'Kézbesítve'
                ];
            ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-4">
                        <span class="font-medium text-gray-900">#<?= $o['order_id'] ?></span>
                    </td>
                    <td class="px-4 py-4">
                        <p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($o['username'] ?? 'Vendég') ?></p>
                        <p class="text-xs text-gray-500"><?= htmlspecialchars($o['email'] ?? '') ?></p>
                    </td>
                    <td class="px-4 py-4 text-sm text-gray-500">
                        <?= date('Y.m.d H:i', strtotime($o['order_date'])) ?>
                    </td>
                    <td class="px-4 py-4 text-sm text-gray-600">
                        <?php if (!empty($o['foxpost_point_name'])): ?>
                            <span class="inline-flex items-center gap-1 text-orange-600">
                                <i class="las la-box"></i> FoxPost
                            </span>
                            <span class="block text-xs text-gray-400 truncate max-w-[150px]"><?= htmlspecialchars($o['foxpost_point_name']) ?></span>
                        <?php else: ?>
                            <span class="inline-flex items-center gap-1">
                                <i class="las la-truck"></i> Házhoz
                            </span>
                            <span class="block text-xs text-gray-400">
                                <?= htmlspecialchars(($o['shipping_postcode'] ?? '') . ' ' . ($o['shipping_city'] ?? '')) ?>
                            </span>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-4">
                        <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full <?= $statusColors[$status] ?? 'bg-gray-100 text-gray-800' ?>">
                            <?= $statusTexts[$status] ?? $status ?>
                        </span>
                    </td>
                    <td class="px-4 py-4 text-right">
                        <span class="font-bold text-gray-900">
                            <?= number_format($o['total_price'] ?? 0, 0, ',', ' ') ?> Ft
                        </span>
                    </td>
                    <td class="px-4 py-4">
                        <div class="flex justify-center gap-1">
                            <?php if ($status === 'pending'): ?>
                                <form method="POST" class="inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="update_order_status">
                                    <input type="hidden" name="order_id" value="<?= $o['order_id'] ?>">
                                    <input type="hidden" name="new_status" value="confirmed">
                                    <button type="submit" class="px-2 py-1 text-xs bg-blue-100 text-blue-700 rounded hover:bg-blue-200" title="Megerősítés">
                                        <i class="las la-check"></i> Megerősít
                                    </button>
                                </form>
                            <?php elseif ($status === 'confirmed'): ?>
                                <form method="POST" class="inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="update_order_status">
                                    <input type="hidden" name="order_id" value="<?= $o['order_id'] ?>">
                                    <input type="hidden" name="new_status" value="shipped">
                                    <button type="submit" class="px-2 py-1 text-xs bg-purple-100 text-purple-700 rounded hover:bg-purple-200" title="Feladva">
                                        <i class="las la-shipping-fast"></i> Feladva
                                    </button>
                                </form>
                            <?php elseif ($status === 'shipped'): ?>
                                <form method="POST" class="inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="update_order_status">
                                    <input type="hidden" name="order_id" value="<?= $o['order_id'] ?>">
                                    <input type="hidden" name="new_status" value="delivered">
                                    <button type="submit" class="px-2 py-1 text-xs bg-green-100 text-green-700 rounded hover:bg-green-200" title="Kézbesítve">
                                        <i class="las la-check-double"></i> Kézbesítve
                                    </button>
                                </form>
                            <?php else: ?>
                                <span class="text-xs text-gray-400">-</span>
                            <?php endif; ?>
                        </div>
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
