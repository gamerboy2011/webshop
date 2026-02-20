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
    
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kép</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Termék</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Márka</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Méret</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Készlet</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Státusz</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <?php 
                $lastProduct = null;
                foreach ($stockItems as $item): 
                    $isNewProduct = $lastProduct !== $item['product_id'];
                    $lastProduct = $item['product_id'];
                ?>
                    <tr class="hover:bg-gray-50 <?= $item['quantity'] == 0 ? 'bg-red-50' : '' ?>">
                        <!-- Kép -->
                        <td class="px-4 py-3">
                            <?php if ($isNewProduct): ?>
                                <?php if ($item['image']): ?>
                                    <img src="/webshop/<?= htmlspecialchars($item['image']) ?>" 
                                         class="w-12 h-12 object-cover rounded">
                                <?php else: ?>
                                    <div class="w-12 h-12 bg-gray-200 rounded flex items-center justify-center">
                                        <i class="las la-image text-gray-400"></i>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                        
                        <!-- Név -->
                        <td class="px-4 py-3">
                            <?php if ($isNewProduct): ?>
                                <a href="/webshop/yw-admin/stock?product_id=<?= $item['product_id'] ?>" 
                                   class="font-medium text-gray-900 hover:text-blue-600">
                                    <?= htmlspecialchars($item['product_name']) ?>
                                </a>
                            <?php endif; ?>
                        </td>
                        
                        <!-- Márka -->
                        <td class="px-4 py-3 text-sm text-gray-600">
                            <?php if ($isNewProduct): ?>
                                <?= htmlspecialchars($item['vendor_name'] ?? '-') ?>
                            <?php endif; ?>
                        </td>
                        
                        <!-- Méret -->
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100">
                                <?= htmlspecialchars($item['size_value']) ?>
                            </span>
                        </td>
                        
                        <!-- Készlet input -->
                        <td class="px-4 py-3 text-center">
                            <input type="number" 
                                   name="stock[<?= $item['stock_id'] ?>]" 
                                   value="<?= $item['quantity'] ?>"
                                   min="0"
                                   class="w-20 px-3 py-2 border rounded-lg text-center focus:ring-2 focus:ring-gray-900 focus:outline-none
                                          <?= $item['quantity'] == 0 ? 'border-red-300 bg-red-50' : '' ?>
                                          <?= $item['quantity'] <= 3 && $item['quantity'] > 0 ? 'border-orange-300 bg-orange-50' : '' ?>">
                        </td>
                        
                        <!-- Státusz -->
                        <td class="px-4 py-3 text-center">
                            <?php if ($item['quantity'] == 0): ?>
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700">
                                    <i class="las la-times-circle mr-1"></i> Elfogyott
                                </span>
                            <?php elseif ($item['quantity'] <= 3): ?>
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-700">
                                    <i class="las la-exclamation-triangle mr-1"></i> Alacsony
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                    <i class="las la-check-circle mr-1"></i> Készleten
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <?php if (empty($stockItems)): ?>
            <div class="p-8 text-center text-gray-500">
                <i class="las la-warehouse text-4xl mb-2"></i>
                <p>Nincs találat</p>
            </div>
        <?php endif; ?>
    </div>
    
    <?php if (!empty($stockItems)): ?>
        <!-- Mentés gomb -->
        <div class="mt-6 flex items-center gap-4">
            <button type="submit" 
                    class="bg-gray-900 text-white px-8 py-3 rounded-lg font-medium hover:bg-gray-800 transition">
                <i class="las la-save mr-2"></i>
                Készlet mentése
            </button>
            <span class="text-sm text-gray-500">
                <i class="las la-info-circle mr-1"></i>
                Módosítsd a mennyiségeket és kattints a mentésre
            </span>
        </div>
    <?php endif; ?>
</form>

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
