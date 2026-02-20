<div class="max-w-4xl">
    <form method="post" action="/webshop/yw-admin" class="bg-white rounded-xl shadow-sm p-8">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save_product">
        <?php if (!empty($product)): ?>
            <input type="hidden" name="product_id" value="<?= $product['product_id'] ?>">
        <?php endif; ?>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            
            <!-- Név -->
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Termék neve *</label>
                <input type="text" name="name" required
                       value="<?= htmlspecialchars($product['name'] ?? '') ?>"
                       class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-gray-900 focus:outline-none">
            </div>
            
            <!-- Leírás -->
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Leírás</label>
                <textarea name="description" rows="4"
                          class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-gray-900 focus:outline-none"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
            </div>
            
            <!-- Ár -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Ár (Ft) *</label>
                <input type="number" name="price" required min="0"
                       value="<?= htmlspecialchars($product['price'] ?? '') ?>"
                       class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-gray-900 focus:outline-none">
            </div>
            
            <!-- Márka -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Márka *</label>
                <select name="vendor_id" required
                        class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-gray-900 focus:outline-none">
                    <option value="">Válassz...</option>
                    <?php foreach ($vendors as $v): ?>
                        <option value="<?= $v['vendor_id'] ?>" <?= ($product['vendor_id'] ?? '') == $v['vendor_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($v['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Szín -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Szín *</label>
                <select name="color_id" required
                        class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-gray-900 focus:outline-none">
                    <option value="">Válassz...</option>
                    <?php foreach ($colors as $c): ?>
                        <option value="<?= $c['color_id'] ?>" <?= ($product['color_id'] ?? '') == $c['color_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Nem -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Nem *</label>
                <select name="gender_id" required
                        class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-gray-900 focus:outline-none">
                    <option value="">Válassz...</option>
                    <?php foreach ($genders as $g): ?>
                        <option value="<?= $g['gender_id'] ?>" <?= ($product['gender_id'] ?? '') == $g['gender_id'] ? 'selected' : '' ?>>
                            <?= $g['gender'] === 'm' ? 'Férfi' : ($g['gender'] === 'f' ? 'Női' : 'Uniszex') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Kategória -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Kategória *</label>
                <select name="subtype_id" required
                        class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-gray-900 focus:outline-none">
                    <option value="">Válassz...</option>
                    <?php 
                    $currentType = '';
                    foreach ($subtypes as $st): 
                        if ($currentType !== $st['type_name']):
                            if ($currentType !== '') echo '</optgroup>';
                            $currentType = $st['type_name'];
                            echo '<optgroup label="' . htmlspecialchars($currentType) . '">';
                        endif;
                    ?>
                        <option value="<?= $st['product_subtype_id'] ?>" <?= ($product['subtype_id'] ?? '') == $st['product_subtype_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($st['name']) ?>
                        </option>
                    <?php endforeach; ?>
                    <?php if ($currentType !== '') echo '</optgroup>'; ?>
                </select>
            </div>
            
            <!-- Akciós -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Akció</label>
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" name="is_sale" value="1" 
                           <?= !empty($product['is_sale']) ? 'checked' : '' ?>
                           class="w-5 h-5 rounded border-gray-300 text-red-500 focus:ring-red-500">
                    <span class="text-sm text-gray-600">Akciós termék (-20%)</span>
                </label>
            </div>
            
            <!-- Aktív -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Státusz</label>
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" 
                           <?= !isset($product) || !empty($product['is_active']) ? 'checked' : '' ?>
                           class="w-5 h-5 rounded border-gray-300 text-green-500 focus:ring-green-500">
                    <span class="text-sm text-gray-600">Aktív (látható a webshopban)</span>
                </label>
            </div>
            
        </div>
        
        <!-- Gombok -->
        <div class="flex items-center gap-4 mt-8 pt-6 border-t">
            <button type="submit" 
                    class="bg-gray-900 text-white px-8 py-3 rounded-lg font-medium hover:bg-gray-800 transition">
                <i class="las la-save mr-2"></i>
                Mentés
            </button>
            <a href="/webshop/yw-admin/products" 
               class="px-8 py-3 border rounded-lg text-gray-600 hover:bg-gray-50 transition">
                Mégse
            </a>
        </div>
        
    </form>
</div>
