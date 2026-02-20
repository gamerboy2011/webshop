<!-- FEJLÉC -->
<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
    
    <!-- Keresés -->
    <form method="get" class="flex gap-2">
        <input type="text" name="q" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>" 
               placeholder="Keresés név vagy márka alapján..."
               class="w-80 px-4 py-2 border rounded-lg focus:ring-2 focus:ring-gray-900 focus:outline-none">
        <button type="submit" class="px-4 py-2 bg-gray-200 rounded-lg hover:bg-gray-300 transition">
            <i class="las la-search"></i>
        </button>
    </form>
    
    <!-- Új termék gomb -->
    <a href="/webshop/yw-admin/product-edit" 
       class="inline-flex items-center gap-2 bg-gray-900 text-white px-6 py-2 rounded-lg hover:bg-gray-800 transition">
        <i class="las la-plus"></i>
        Új termék
    </a>
</div>

<!-- Üzenetek -->
<?php if (isset($_GET['saved'])): ?>
    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6">
        <i class="las la-check-circle mr-2"></i> Termék sikeresen mentve!
    </div>
<?php endif; ?>

<?php if (isset($_GET['deleted'])): ?>
    <div class="bg-yellow-50 border border-yellow-200 text-yellow-700 px-4 py-3 rounded-lg mb-6">
        <i class="las la-trash mr-2"></i> Termék törölve (inaktiválva)!
    </div>
<?php endif; ?>

<!-- TERMÉK TÁBLA -->
<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kép</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Termék</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Márka</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ár</th>
                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Akció</th>
                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Státusz</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Műveletek</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            <?php foreach ($products as $p): ?>
                <tr class="hover:bg-gray-50 <?= !$p['is_active'] ? 'opacity-50' : '' ?>">
                    <!-- Kép -->
                    <td class="px-4 py-3">
                        <?php if ($p['image']): ?>
                            <img src="/webshop/<?= htmlspecialchars($p['image']) ?>" 
                                 class="w-12 h-12 object-cover rounded">
                        <?php else: ?>
                            <div class="w-12 h-12 bg-gray-200 rounded flex items-center justify-center">
                                <i class="las la-image text-gray-400"></i>
                            </div>
                        <?php endif; ?>
                    </td>
                    
                    <!-- Név -->
                    <td class="px-4 py-3">
                        <p class="font-medium text-gray-900"><?= htmlspecialchars($p['name']) ?></p>
                        <p class="text-xs text-gray-500"><?= htmlspecialchars($p['type_name'] ?? '') ?> / <?= htmlspecialchars($p['subtype_name'] ?? '') ?></p>
                    </td>
                    
                    <!-- Márka -->
                    <td class="px-4 py-3 text-sm text-gray-600">
                        <?= htmlspecialchars($p['vendor_name'] ?? '-') ?>
                    </td>
                    
                    <!-- Ár -->
                    <td class="px-4 py-3">
                        <?php if ($p['is_sale']): ?>
                            <span class="text-gray-400 line-through text-sm"><?= number_format($p['price'], 0, ',', ' ') ?> Ft</span>
                            <span class="text-red-600 font-medium block"><?= number_format($p['price'] * 0.8, 0, ',', ' ') ?> Ft</span>
                        <?php else: ?>
                            <span class="font-medium"><?= number_format($p['price'], 0, ',', ' ') ?> Ft</span>
                        <?php endif; ?>
                    </td>
                    
                    <!-- Akció toggle -->
                    <td class="px-4 py-3 text-center">
                        <form method="post" class="inline">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="toggle_sale">
                            <input type="hidden" name="product_id" value="<?= $p['product_id'] ?>">
                            <button type="submit" 
                                    class="w-10 h-6 rounded-full transition-colors <?= $p['is_sale'] ? 'bg-red-500' : 'bg-gray-300' ?> relative">
                                <span class="absolute w-4 h-4 bg-white rounded-full top-1 transition-all <?= $p['is_sale'] ? 'right-1' : 'left-1' ?>"></span>
                            </button>
                        </form>
                    </td>
                    
                    <!-- Státusz -->
                    <td class="px-4 py-3 text-center">
                        <?php if ($p['is_active']): ?>
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                Aktív
                            </span>
                        <?php else: ?>
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                Inaktív
                            </span>
                        <?php endif; ?>
                    </td>
                    
                    <!-- Műveletek -->
                    <td class="px-4 py-3 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <a href="/webshop/termek/<?= $p['product_id'] ?>" target="_blank"
                               class="p-2 text-gray-400 hover:text-gray-600" title="Megtekintetés">
                                <i class="las la-external-link-alt"></i>
                            </a>
                            <a href="/webshop/yw-admin/stock?product_id=<?= $p['product_id'] ?>" 
                               class="p-2 text-green-600 hover:text-green-800" title="Készlet">
                                <i class="las la-warehouse"></i>
                            </a>
                            <a href="/webshop/yw-admin/product-edit/<?= $p['product_id'] ?>" 
                               class="p-2 text-blue-600 hover:text-blue-800" title="Szerkesztés">
                                <i class="las la-edit"></i>
                            </a>
                            <form method="post" class="inline" onsubmit="return confirm('Biztosan törlöd ezt a terméket?')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete_product">
                                <input type="hidden" name="product_id" value="<?= $p['product_id'] ?>">
                                <button type="submit" class="p-2 text-red-600 hover:text-red-800" title="Törlés">
                                    <i class="las la-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <?php if (empty($products)): ?>
        <div class="p-8 text-center text-gray-500">
            <i class="las la-box-open text-4xl mb-2"></i>
            <p>Nincsenek termékek</p>
        </div>
    <?php endif; ?>
</div>
