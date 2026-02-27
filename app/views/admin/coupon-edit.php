<?php
// Terméktípusok lekérdezése
$productTypes = $pdo->query("SELECT * FROM product_type")->fetchAll(PDO::FETCH_ASSOC);

// Alkategóriák lekérdezése (csoportosítva típus szerint)
$subtypes = $pdo->query("
    SELECT ps.*, pt.name as type_name 
    FROM product_subtype ps 
    JOIN product_type pt ON ps.product_type_id = pt.product_type_id 
    ORDER BY pt.product_type_id, ps.name
")->fetchAll(PDO::FETCH_ASSOC);

$typeNames = [
    'Accessory' => 'Kiegészítők',
    'Clothe' => 'Ruházat',
    'Shoe' => 'Cipők'
];

$subtypeNames = [
    'bag' => 'Táska',
    'cap' => 'Sapka',
    'hat' => 'Kalap',
    'hoodie' => 'Kapucnis pulcsi',
    'jacket' => 'Dzseki',
    'jeans' => 'Farmer',
    'leggings' => 'Leggings',
    'sweater' => 'Pulóver',
    't-shirt' => 'Póló',
    'winter coat' => 'Télikabát',
    'sandals' => 'Szandál',
    'shoes' => 'Cipő'
];
?>

<div class="max-w-2xl">
    <form method="post" action="/webshop/yw-admin" class="bg-white rounded-lg shadow-sm">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save_coupon">
        <?php if (isset($coupon)): ?>
            <input type="hidden" name="coupon_id" value="<?= $coupon['id'] ?>">
        <?php endif; ?>
        
        <div class="p-6 space-y-6">
            <!-- Név -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Kupon neve *</label>
                <input type="text" name="name" required
                       value="<?= htmlspecialchars($coupon['name'] ?? '') ?>"
                       class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-purple-500"
                       placeholder="Pl: Tavaszi akció">
            </div>
            
            <!-- Leírás -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Leírás (opcionális)</label>
                <textarea name="description" rows="2"
                          class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-purple-500"
                          placeholder="Rövid leírás a kuponról..."><?= htmlspecialchars($coupon['description'] ?? '') ?></textarea>
            </div>
            
            <!-- Kuponkód -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Kuponkód *</label>
                <div class="flex gap-2">
                    <input type="text" name="coupon_pass" id="couponCode" required
                           value="<?= htmlspecialchars($coupon['coupon_pass'] ?? '') ?>"
                           class="flex-1 border rounded-lg px-4 py-3 font-mono uppercase focus:outline-none focus:ring-2 focus:ring-purple-500"
                           placeholder="TAVASZI20">
                    <button type="button" onclick="generateCode()" 
                            class="px-4 py-3 bg-gray-100 border rounded-lg hover:bg-gray-200 transition">
                        <i class="las la-random"></i> Generálás
                    </button>
                </div>
                <p class="text-xs text-gray-500 mt-1">Egyedi kód, amit a vásárlók megadnak</p>
            </div>
            
            <!-- Kedvezmény százalék -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Kedvezmény (%) *</label>
                <input type="number" name="amount" required min="1" max="100"
                       value="<?= (int)($coupon['amount'] ?? 10) ?>"
                       class="w-32 border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-purple-500">
            </div>
            
            <!-- Kategória választás -->
            <div class="space-y-4">
                <label class="block text-sm font-medium text-gray-700">Érvényes kategória</label>
                
                <!-- Rádió gombok a választás típusához -->
                <div class="flex flex-wrap gap-4">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="category_type" value="all" 
                               <?= empty($coupon['product_type_id']) && empty($coupon['product_subtype_id']) ? 'checked' : '' ?>
                               class="w-4 h-4 text-purple-600" onchange="updateCategorySelects()">
                        <span>Minden termék</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="category_type" value="type" 
                               <?= !empty($coupon['product_type_id']) && empty($coupon['product_subtype_id']) ? 'checked' : '' ?>
                               class="w-4 h-4 text-purple-600" onchange="updateCategorySelects()">
                        <span>Főkategória</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="category_type" value="subtype" 
                               <?= !empty($coupon['product_subtype_id']) ? 'checked' : '' ?>
                               class="w-4 h-4 text-purple-600" onchange="updateCategorySelects()">
                        <span>Alkategória</span>
                    </label>
                </div>
                
                <!-- Főkategória választó -->
                <div id="typeSelect" class="<?= !empty($coupon['product_type_id']) && empty($coupon['product_subtype_id']) ? '' : 'hidden' ?>">
                    <select name="product_type_id" id="product_type_id"
                            class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-purple-500 bg-white">
                        <option value="">Válassz főkategóriát...</option>
                        <?php foreach ($productTypes as $pt): ?>
                            <option value="<?= $pt['product_type_id'] ?>" 
                                <?= ($coupon['product_type_id'] ?? '') == $pt['product_type_id'] && empty($coupon['product_subtype_id']) ? 'selected' : '' ?>>
                                <?= $typeNames[$pt['name']] ?? $pt['name'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Alkategória választó -->
                <div id="subtypeSelect" class="<?= !empty($coupon['product_subtype_id']) ? '' : 'hidden' ?>">
                    <select name="product_subtype_id" id="product_subtype_id"
                            class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-purple-500 bg-white">
                        <option value="">Válassz alkategóriát...</option>
                        <?php 
                        $currentType = null;
                        foreach ($subtypes as $st): 
                            if ($currentType !== $st['type_name']):
                                if ($currentType !== null) echo '</optgroup>';
                                $currentType = $st['type_name'];
                        ?>
                            <optgroup label="<?= $typeNames[$currentType] ?? $currentType ?>">
                        <?php endif; ?>
                            <option value="<?= $st['product_subtype_id'] ?>" 
                                <?= ($coupon['product_subtype_id'] ?? '') == $st['product_subtype_id'] ? 'selected' : '' ?>>
                                <?= $subtypeNames[$st['name']] ?? ucfirst($st['name']) ?>
                            </option>
                        <?php endforeach; ?>
                        <?php if ($currentType !== null) echo '</optgroup>'; ?>
                    </select>
                </div>
            </div>
            
            <!-- Érvényesség -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Kezdő dátum *</label>
                    <input type="date" name="start_date" required
                           value="<?= $coupon['start_date'] ?? date('Y-m-d') ?>"
                           class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-purple-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Befejező dátum *</label>
                    <input type="date" name="end_date" required
                           value="<?= $coupon['end_date'] ?? date('Y-m-d', strtotime('+30 days')) ?>"
                           class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-purple-500">
                </div>
            </div>
            
            <!-- Aktív -->
            <div class="flex items-center gap-3">
                <input type="checkbox" name="is_active" id="is_active" value="1"
                       <?= ($coupon['is_active'] ?? 1) ? 'checked' : '' ?>
                       class="w-5 h-5 text-purple-600 rounded">
                <label for="is_active" class="text-sm font-medium text-gray-700">
                    Kupon aktív (azonnal használható a start_date után)
                </label>
            </div>
            
            <!-- QR kód előnézet (ha létezik) -->
            <?php if (!empty($coupon['qr_code_path'])): ?>
                <div class="border rounded-lg p-4 bg-gray-50">
                    <label class="block text-sm font-medium text-gray-700 mb-3">Aktuális QR kód</label>
                    <div class="flex items-center gap-4">
                        <img src="/webshop/<?= htmlspecialchars($coupon['qr_code_path']) ?>" 
                             alt="QR Code" class="w-32 h-32 object-contain">
                        <div>
                            <p class="text-sm text-gray-600 mb-2">
                                A QR kód a kuponkód alapján generálódik.<br>
                                Ha módosítod a kódot, új QR generálódik.
                            </p>
                            <a href="/webshop/<?= htmlspecialchars($coupon['qr_code_path']) ?>" 
                               download class="text-purple-600 hover:text-purple-800 text-sm">
                                <i class="las la-download mr-1"></i>Letöltés
                            </a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="border rounded-lg p-4 bg-purple-50 border-purple-200">
                    <p class="text-sm text-purple-700">
                        <i class="las la-info-circle mr-1"></i>
                        A mentéskor automatikusan generálódik egy QR kód a kuponhoz.
                    </p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- SUBMIT -->
        <div class="px-6 py-4 bg-gray-50 border-t flex items-center justify-between rounded-b-lg">
            <a href="/webshop/yw-admin/coupons" class="text-gray-600 hover:text-gray-800">
                <i class="las la-arrow-left mr-1"></i>Vissza
            </a>
            <button type="submit" 
                    class="bg-purple-600 text-white px-6 py-3 rounded-lg hover:bg-purple-700 transition font-medium">
                <i class="las la-save mr-1"></i>
                <?= isset($coupon) ? 'Mentés' : 'Kupon létrehozása' ?>
            </button>
        </div>
    </form>
</div>

<script>
function generateCode() {
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    let code = '';
    for (let i = 0; i < 8; i++) {
        code += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    document.getElementById('couponCode').value = code;
}

// Auto uppercase
document.getElementById('couponCode').addEventListener('input', function() {
    this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
});

// Kategória választás kezelése
function updateCategorySelects() {
    const categoryType = document.querySelector('input[name="category_type"]:checked').value;
    const typeSelect = document.getElementById('typeSelect');
    const subtypeSelect = document.getElementById('subtypeSelect');
    
    typeSelect.classList.add('hidden');
    subtypeSelect.classList.add('hidden');
    
    // Mezők törlése
    document.getElementById('product_type_id').value = '';
    document.getElementById('product_subtype_id').value = '';
    
    if (categoryType === 'type') {
        typeSelect.classList.remove('hidden');
    } else if (categoryType === 'subtype') {
        subtypeSelect.classList.remove('hidden');
    }
}
</script>
