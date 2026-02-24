<!-- FEJLÉC -->
<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
    
    <!-- Keresés -->
    <form method="get" class="flex gap-2">
        <input type="text" name="q" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>" 
               placeholder="Keresés termék név alapján..."
               class="w-80 px-4 py-2 border rounded-lg focus:ring-2 focus:ring-gray-900 focus:outline-none">
        <button type="submit" class="px-4 py-2 bg-gray-200 rounded-lg hover:bg-gray-300 transition">
            <i class="las la-search"></i>
        </button>
        <?php if (!empty($_GET['q']) || !empty($_GET['product_id'])): ?>
            <a href="/webshop/yw-admin/stock" class="px-4 py-2 text-gray-500 hover:text-gray-700">
                <i class="las la-times"></i> Szűrő törlése
            </a>
        <?php endif; ?>
    </form>
    
    <?php if (!empty($currentProduct)): ?>
        <div class="bg-blue-50 text-blue-700 px-4 py-2 rounded-lg">
            <i class="las la-filter mr-2"></i>
            Szűrés: <strong><?= htmlspecialchars($currentProduct['name']) ?></strong>
        </div>
    <?php endif; ?>
</div>

<!-- Üzenetek -->
<?php if (isset($_GET['saved'])): ?>
    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6">
        <i class="las la-check-circle mr-2"></i> Készlet sikeresen frissítve!
    </div>
<?php endif; ?>

<!-- KÉSZLET FORM -->
<form method="post" action="/webshop/yw-admin">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="update_stock">
    <?php if (!empty($_GET['product_id'])): ?>
        <input type="hidden" name="product_id" value="<?= (int)$_GET['product_id'] ?>">
    <?php endif; ?>
    
    <?php
    // Termékek csoportosítása
    $groupedStock = [];
    foreach ($stockItems as $item) {
        $pid = $item['product_id'];
        if (!isset($groupedStock[$pid])) {
            $groupedStock[$pid] = [
                'product_id' => $pid,
                'product_name' => $item['product_name'],
                'vendor_name' => $item['vendor_name'] ?? '-',
                'image' => $item['image'],
                'sizes' => [],
                'has_low' => false,
                'has_out' => false
            ];
        }
        $groupedStock[$pid]['sizes'][] = [
            'stock_id' => $item['stock_id'],
            'size_value' => $item['size_value'],
            'quantity' => $item['quantity']
        ];
        if ($item['quantity'] == 0) $groupedStock[$pid]['has_out'] = true;
        elseif ($item['quantity'] <= 3) $groupedStock[$pid]['has_low'] = true;
    }
    ?>
    
    <div class="space-y-3">
        <?php foreach ($groupedStock as $product): ?>
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <!-- Termék fejléc (kattintható) -->
                <div class="flex items-center justify-between p-4 cursor-pointer hover:bg-gray-50 transition"
                     onclick="toggleProduct(<?= $product['product_id'] ?>)">
                    <div class="flex items-center gap-4">
                        <!-- Nyíl -->
                        <i class="las la-chevron-right text-gray-400 text-xl transition-transform" 
                           id="arrow-<?= $product['product_id'] ?>"></i>
                        
                        <!-- Kép -->
                        <?php if ($product['image']): ?>
                            <img src="/webshop/<?= htmlspecialchars($product['image']) ?>" 
                                 class="w-12 h-12 object-cover rounded">
                        <?php else: ?>
                            <div class="w-12 h-12 bg-gray-200 rounded flex items-center justify-center">
                                <i class="las la-image text-gray-400"></i>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Név és márka -->
                        <div>
                            <p class="font-medium text-gray-900"><?= htmlspecialchars($product['product_name']) ?></p>
                            <p class="text-sm text-gray-500"><?= htmlspecialchars($product['vendor_name']) ?></p>
                        </div>
                    </div>
                    
                    <!-- Figyelmeztetések -->
                    <div class="flex items-center gap-2">
                        <?php if ($product['has_out']): ?>
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700">
                                <i class="las la-exclamation-circle mr-1"></i> Elfogyott
                            </span>
                        <?php endif; ?>
                        <?php if ($product['has_low']): ?>
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-700">
                                <i class="las la-exclamation-triangle mr-1"></i> Alacsony
                            </span>
                        <?php endif; ?>
                        <span class="text-sm text-gray-400">
                            <?= count($product['sizes']) ?> méret
                        </span>
                    </div>
                </div>
                
                <!-- Méretek (rejtett alapállapotban) -->
                <div id="sizes-<?= $product['product_id'] ?>" class="hidden border-t">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-2 text-left text-xs font-medium text-gray-500 uppercase">Méret</th>
                                <th class="px-6 py-2 text-center text-xs font-medium text-gray-500 uppercase">Készlet</th>
                                <th class="px-6 py-2 text-center text-xs font-medium text-gray-500 uppercase">Státusz</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <?php foreach ($product['sizes'] as $size): ?>
                                <tr class="<?= $size['quantity'] == 0 ? 'bg-red-50' : ($size['quantity'] <= 3 ? 'bg-orange-50' : '') ?>">
                                    <td class="px-6 py-3">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100">
                                            <?= htmlspecialchars($size['size_value']) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-3 text-center">
                                        <input type="number" 
                                               name="stock[<?= $size['stock_id'] ?>]" 
                                               value="<?= $size['quantity'] ?>"
                                               min="0"
                                               class="w-20 px-3 py-2 border rounded-lg text-center focus:ring-2 focus:ring-gray-900 focus:outline-none
                                                      <?= $size['quantity'] == 0 ? 'border-red-300' : '' ?>
                                                      <?= $size['quantity'] <= 3 && $size['quantity'] > 0 ? 'border-orange-300' : '' ?>">
                                    </td>
                                    <td class="px-6 py-3 text-center">
                                        <?php if ($size['quantity'] == 0): ?>
                                            <span class="text-red-600 font-medium">
                                                <i class="las la-exclamation-circle"></i> Elfogyott
                                            </span>
                                        <?php elseif ($size['quantity'] <= 3): ?>
                                            <span class="text-orange-600 font-medium">
                                                <i class="las la-exclamation-triangle"></i> Alacsony!
                                            </span>
                                        <?php else: ?>
                                            <span class="text-green-600">
                                                <i class="las la-check-circle"></i> OK
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <?php if (empty($stockItems)): ?>
        <div class="bg-white rounded-xl p-8 text-center text-gray-500">
            <i class="las la-warehouse text-4xl mb-2"></i>
            <p>Nincs találat</p>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($stockItems)): ?>
        <!-- Mentés gomb -->
        <div class="mt-6 flex items-center gap-4">
            <button type="submit" 
                    class="bg-gray-900 text-white px-8 py-3 rounded-lg font-medium hover:bg-gray-800 transition">
                <i class="las la-save mr-2"></i>
                Készlet mentése
            </button>
            <button type="button" onclick="expandAll()" class="px-4 py-2 text-gray-600 hover:text-gray-800">
                <i class="las la-expand-arrows-alt mr-1"></i> Mind kinyit
            </button>
            <button type="button" onclick="collapseAll()" class="px-4 py-2 text-gray-600 hover:text-gray-800">
                <i class="las la-compress-arrows-alt mr-1"></i> Mind becsuk
            </button>
        </div>
    <?php endif; ?>
</form>

<script>
function toggleProduct(id) {
    const sizes = document.getElementById('sizes-' + id);
    const arrow = document.getElementById('arrow-' + id);
    
    if (sizes.classList.contains('hidden')) {
        sizes.classList.remove('hidden');
        arrow.style.transform = 'rotate(90deg)';
    } else {
        sizes.classList.add('hidden');
        arrow.style.transform = 'rotate(0deg)';
    }
}

function expandAll() {
    document.querySelectorAll('[id^="sizes-"]').forEach(el => el.classList.remove('hidden'));
    document.querySelectorAll('[id^="arrow-"]').forEach(el => el.style.transform = 'rotate(90deg)');
}

function collapseAll() {
    document.querySelectorAll('[id^="sizes-"]').forEach(el => el.classList.add('hidden'));
    document.querySelectorAll('[id^="arrow-"]').forEach(el => el.style.transform = 'rotate(0deg)');
}
</script>

<!-- Összesítés -->
<?php if (!empty($stockItems)): ?>
    <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-4">
        <?php
        $totalItems = count($stockItems);
        $outOfStock = count(array_filter($stockItems, fn($i) => $i['quantity'] == 0));
        $lowStock = count(array_filter($stockItems, fn($i) => $i['quantity'] > 0 && $i['quantity'] <= 3));
        ?>
        <div class="bg-white rounded-lg p-4 shadow-sm">
            <p class="text-sm text-gray-500">Összes tétel</p>
            <p class="text-2xl font-bold"><?= $totalItems ?></p>
        </div>
        <div class="bg-orange-50 rounded-lg p-4">
            <p class="text-sm text-orange-600">Alacsony készlet</p>
            <p class="text-2xl font-bold text-orange-700"><?= $lowStock ?></p>
        </div>
        <div class="bg-red-50 rounded-lg p-4">
            <p class="text-sm text-red-600">Elfogyott</p>
            <p class="text-2xl font-bold text-red-700"><?= $outOfStock ?></p>
        </div>
    </div>
<?php endif; ?>
