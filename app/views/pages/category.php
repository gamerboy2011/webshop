<?php
// Változók az index.php-ból
$gender = $gender ?? $_GET['gender'] ?? null;
$category = $category ?? $_GET['category'] ?? null;
$products = $products ?? [];
$filterOptions = $filterOptions ?? ['brands' => [], 'colors' => [], 'sizes' => [], 'price_min' => 0, 'price_max' => 100000];
$activeFilters = $activeFilters ?? ['sale' => false, 'brands' => [], 'colors' => [], 'sizes' => [], 'min_price' => null, 'max_price' => null, 'sort' => 'newest'];

// Aktuális URL alap (szűrők nélkül)
$baseUrl = "/webshop/" . ($gender ?? '') . ($category ? "/$category" : '');

// Segédfüggvény: szűrő URL generálás
function buildFilterUrl($baseUrl, $filters, $exclude = null, $excludeValue = null) {
    $params = [];
    
    if (!empty($filters['sale']) && $exclude !== 'sale') {
        $params['sale'] = '1';
    }
    
    if (!empty($filters['brands'])) {
        $brands = $exclude === 'brands' && $excludeValue !== null 
            ? array_diff($filters['brands'], [$excludeValue])
            : $filters['brands'];
        foreach ($brands as $b) {
            $params['brands[]'] = $b;
        }
    }
    
    if (!empty($filters['colors'])) {
        $colors = $exclude === 'colors' && $excludeValue !== null 
            ? array_diff($filters['colors'], [$excludeValue])
            : $filters['colors'];
        foreach ($colors as $c) {
            $params['colors[]'] = $c;
        }
    }
    
    if (!empty($filters['sizes'])) {
        $sizes = $exclude === 'sizes' && $excludeValue !== null 
            ? array_diff($filters['sizes'], [$excludeValue])
            : $filters['sizes'];
        foreach ($sizes as $s) {
            $params['sizes[]'] = $s;
        }
    }
    
    if (!empty($filters['min_price']) && $exclude !== 'price') {
        $params['min_price'] = $filters['min_price'];
    }
    if (!empty($filters['max_price']) && $exclude !== 'price') {
        $params['max_price'] = $filters['max_price'];
    }
    if (!empty($filters['sort']) && $filters['sort'] !== 'newest') {
        $params['sort'] = $filters['sort'];
    }
    
    $query = http_build_query($params);
    $query = preg_replace('/%5B\d*%5D=/', '[]=', $query);
    return $baseUrl . ($query ? '?' . $query : '');
}

// Aktív szűrők száma
$activeFilterCount = 0;
if ($activeFilters['sale']) $activeFilterCount++;
$activeFilterCount += count($activeFilters['brands']);
$activeFilterCount += count($activeFilters['colors']);
$activeFilterCount += count($activeFilters['sizes']);
if ($activeFilters['min_price'] || $activeFilters['max_price']) $activeFilterCount++;

// Szín kódok
$colorCodes = [
    'Red' => '#EF4444', 'Blue' => '#3B82F6', 'Green' => '#22C55E', 'Brown' => '#92400E',
    'Yellow' => '#EAB308', 'Orange' => '#F97316', 'White' => '#FFFFFF', 'Black' => '#000000',
    'Gray' => '#6B7280', 'Pink' => '#EC4899', 'Purple' => '#A855F7', 'Beige' => '#D4C4A8',
    'Navy' => '#1E3A5F', 'Cream' => '#FFFDD0'
];
?>

<div class="max-w-7xl mx-auto px-4 py-8">
    
    <!-- FEJLÉC + RENDEZÉS -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold">
                <?= $gender === 'ferfi' ? 'Férfi' : ($gender === 'noi' ? 'Női' : 'Termékek') ?>
                <?= $category ? ' – ' . ucfirst(str_replace('-', ' ', $category)) : '' ?>
            </h1>
            <p class="text-gray-500 text-sm mt-1"><?= count($products) ?> termék</p>
        </div>
        
        <!-- RENDEZÉS -->
        <div class="flex items-center gap-4">
            <label class="text-sm text-gray-600">Rendezés:</label>
            <select onchange="window.location.href=this.value" 
                    class="border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-black">
                <option value="<?= buildFilterUrl($baseUrl, array_merge($activeFilters, ['sort' => 'newest'])) ?>" 
                        <?= $activeFilters['sort'] === 'newest' ? 'selected' : '' ?>>Legújabb</option>
                <option value="<?= buildFilterUrl($baseUrl, array_merge($activeFilters, ['sort' => 'price_asc'])) ?>"
                        <?= $activeFilters['sort'] === 'price_asc' ? 'selected' : '' ?>>Ár: alacsony → magas</option>
                <option value="<?= buildFilterUrl($baseUrl, array_merge($activeFilters, ['sort' => 'price_desc'])) ?>"
                        <?= $activeFilters['sort'] === 'price_desc' ? 'selected' : '' ?>>Ár: magas → alacsony</option>
                <option value="<?= buildFilterUrl($baseUrl, array_merge($activeFilters, ['sort' => 'name_asc'])) ?>"
                        <?= $activeFilters['sort'] === 'name_asc' ? 'selected' : '' ?>>Név: A-Z</option>
            </select>
        </div>
    </div>
    
    <!-- AKTÍV SZŰRŐK CHIP-EK -->
    <?php if ($activeFilterCount > 0): ?>
    <div class="flex flex-wrap gap-2 mb-6">
        <?php if ($activeFilters['sale']): ?>
            <a href="<?= buildFilterUrl($baseUrl, $activeFilters, 'sale') ?>" 
               class="inline-flex items-center gap-1 bg-red-100 text-red-700 px-3 py-1 rounded-full text-sm hover:bg-red-200 transition">
                Akciós
                <i class="las la-times"></i>
            </a>
        <?php endif; ?>
        
        <?php foreach ($activeFilters['brands'] as $brand): ?>
            <a href="<?= buildFilterUrl($baseUrl, $activeFilters, 'brands', $brand) ?>" 
               class="inline-flex items-center gap-1 bg-gray-100 text-gray-700 px-3 py-1 rounded-full text-sm hover:bg-gray-200 transition">
                <?= htmlspecialchars($brand) ?>
                <i class="las la-times"></i>
            </a>
        <?php endforeach; ?>
        
        <?php foreach ($activeFilters['colors'] as $color): ?>
            <a href="<?= buildFilterUrl($baseUrl, $activeFilters, 'colors', $color) ?>" 
               class="inline-flex items-center gap-1 bg-gray-100 text-gray-700 px-3 py-1 rounded-full text-sm hover:bg-gray-200 transition">
                <span class="w-3 h-3 rounded-full border" style="background-color: <?= $colorCodes[$color] ?? '#ccc' ?>"></span>
                <?= htmlspecialchars($color) ?>
                <i class="las la-times"></i>
            </a>
        <?php endforeach; ?>
        
        <?php foreach ($activeFilters['sizes'] as $size): ?>
            <a href="<?= buildFilterUrl($baseUrl, $activeFilters, 'sizes', $size) ?>" 
               class="inline-flex items-center gap-1 bg-gray-100 text-gray-700 px-3 py-1 rounded-full text-sm hover:bg-gray-200 transition">
                Méret: <?= htmlspecialchars($size) ?>
                <i class="las la-times"></i>
            </a>
        <?php endforeach; ?>
        
        <?php if ($activeFilters['min_price'] || $activeFilters['max_price']): ?>
            <a href="<?= buildFilterUrl($baseUrl, $activeFilters, 'price') ?>" 
               class="inline-flex items-center gap-1 bg-gray-100 text-gray-700 px-3 py-1 rounded-full text-sm hover:bg-gray-200 transition">
                <?= number_format($activeFilters['min_price'] ?? $filterOptions['price_min'], 0, ',', ' ') ?> - 
                <?= number_format($activeFilters['max_price'] ?? $filterOptions['price_max'], 0, ',', ' ') ?> Ft
                <i class="las la-times"></i>
            </a>
        <?php endif; ?>
        
        <a href="<?= $baseUrl ?>" class="inline-flex items-center gap-1 text-gray-500 px-3 py-1 text-sm hover:text-black transition">
            <i class="las la-trash-alt"></i> Összes törlése
        </a>
    </div>
    <?php endif; ?>

    <div class="flex gap-8">
        
        <!-- SIDEBAR SZŰRŐK -->
        <aside class="hidden lg:block w-64 flex-shrink-0">
            <form method="get" action="<?= $baseUrl ?>" id="filterForm">
                
                <!-- Akciós termékek -->
                <div class="border-b pb-4 mb-4">
                    <label class="flex items-center gap-3 cursor-pointer group">
                        <input type="checkbox" name="sale" value="1" 
                               <?= $activeFilters['sale'] ? 'checked' : '' ?>
                               onchange="document.getElementById('filterForm').submit()"
                               class="w-5 h-5 rounded border-gray-300 text-red-500 focus:ring-red-500">
                        <span class="text-sm font-medium group-hover:text-red-500 transition">
                            <i class="las la-percent text-red-500 mr-1"></i>
                            Csak akciós termékek
                        </span>
                    </label>
                </div>

                <!-- MÁRKÁK -->
                <details class="border-b pb-4 mb-4" open>
                    <summary class="flex items-center justify-between cursor-pointer list-none font-medium mb-3">
                        Márka
                        <i class="las la-angle-down text-gray-400"></i>
                    </summary>
                    <div class="space-y-2 max-h-48 overflow-y-auto">
                        <?php foreach ($filterOptions['brands'] as $brand): ?>
                            <label class="flex items-center gap-2 cursor-pointer hover:text-black">
                                <input type="checkbox" name="brands[]" value="<?= htmlspecialchars($brand['name']) ?>"
                                       <?= in_array($brand['name'], $activeFilters['brands']) ? 'checked' : '' ?>
                                       onchange="document.getElementById('filterForm').submit()"
                                       class="w-4 h-4 rounded border-gray-300 text-black focus:ring-black">
                                <span class="text-sm text-gray-700"><?= htmlspecialchars($brand['name']) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </details>

                <!-- SZÍNEK -->
                <details class="border-b pb-4 mb-4" open>
                    <summary class="flex items-center justify-between cursor-pointer list-none font-medium mb-3">
                        Szín
                        <i class="las la-angle-down text-gray-400"></i>
                    </summary>
                    <div class="flex flex-wrap gap-2">
                        <?php foreach ($filterOptions['colors'] as $color): ?>
                            <?php $isActive = in_array($color['name'], $activeFilters['colors']); ?>
                            <label class="relative cursor-pointer" title="<?= htmlspecialchars($color['name']) ?>">
                                <input type="checkbox" name="colors[]" value="<?= htmlspecialchars($color['name']) ?>"
                                       <?= $isActive ? 'checked' : '' ?>
                                       onchange="document.getElementById('filterForm').submit()"
                                       class="sr-only peer">
                                <span class="block w-8 h-8 rounded-full border-2 transition
                                             <?= $isActive ? 'border-black ring-2 ring-black ring-offset-2' : 'border-gray-300 hover:border-gray-500' ?>"
                                      style="background-color: <?= $colorCodes[$color['name']] ?? '#ccc' ?>">
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </details>

                <!-- MÉRETEK -->
                <details class="border-b pb-4 mb-4" open>
                    <summary class="flex items-center justify-between cursor-pointer list-none font-medium mb-3">
                        Méret
                        <i class="las la-angle-down text-gray-400"></i>
                    </summary>
                    <div class="flex flex-wrap gap-2">
                        <?php foreach ($filterOptions['sizes'] as $size): ?>
                            <?php $isActive = in_array($size['size_value'], $activeFilters['sizes']); ?>
                            <label class="cursor-pointer">
                                <input type="checkbox" name="sizes[]" value="<?= htmlspecialchars($size['size_value']) ?>"
                                       <?= $isActive ? 'checked' : '' ?>
                                       onchange="document.getElementById('filterForm').submit()"
                                       class="sr-only peer">
                                <span class="block px-3 py-2 border rounded text-sm font-medium transition
                                             <?= $isActive ? 'bg-black text-white border-black' : 'border-gray-300 hover:border-black' ?>">
                                    <?= htmlspecialchars($size['size_value']) ?>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </details>

                <!-- ÁR -->
                <details class="border-b pb-4 mb-4" open>
                    <summary class="flex items-center justify-between cursor-pointer list-none font-medium mb-3">
                        Ár
                        <i class="las la-angle-down text-gray-400"></i>
                    </summary>
                    <div class="space-y-3">
                        <div class="flex gap-2 items-center">
                            <input type="number" name="min_price" placeholder="Min" 
                                   value="<?= $activeFilters['min_price'] ?? '' ?>"
                                   class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-black">
                            <span class="text-gray-400">-</span>
                            <input type="number" name="max_price" placeholder="Max" 
                                   value="<?= $activeFilters['max_price'] ?? '' ?>"
                                   class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-black">
                        </div>
                        <button type="submit" class="w-full bg-gray-100 hover:bg-gray-200 text-sm py-2 rounded transition">
                            Szűrés
                        </button>
                        <p class="text-xs text-gray-400 text-center">
                            <?= number_format($filterOptions['price_min'], 0, ',', ' ') ?> - 
                            <?= number_format($filterOptions['price_max'], 0, ',', ' ') ?> Ft
                        </p>
                    </div>
                </details>
                
                <?php if ($activeFilters['sort'] !== 'newest'): ?>
                    <input type="hidden" name="sort" value="<?= htmlspecialchars($activeFilters['sort']) ?>">
                <?php endif; ?>
                
            </form>
        </aside>

        <!-- MOBIL SZŰRŐ GOMB -->
        <button onclick="document.getElementById('mobileFilters').classList.toggle('hidden')"
                class="lg:hidden fixed bottom-4 left-4 z-50 bg-black text-white px-6 py-3 rounded-full shadow-lg flex items-center gap-2">
            <i class="las la-filter"></i>
            Szűrők
            <?php if ($activeFilterCount > 0): ?>
                <span class="bg-red-500 text-white text-xs w-5 h-5 rounded-full flex items-center justify-center">
                    <?= $activeFilterCount ?>
                </span>
            <?php endif; ?>
        </button>

        <!-- TERMÉKEK GRID -->
        <div class="flex-1">
            <?php if (!empty($products)): ?>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                    <?php foreach ($products as $product): ?>
                        <a href="/webshop/termek/<?= $product['product_id'] ?>" 
                           class="group bg-white rounded-lg shadow-sm hover:shadow-lg transition-shadow overflow-hidden block">
                            
                            <div class="aspect-[3/4] bg-gray-100 overflow-hidden relative">
                                <?php if (!empty($product['is_sale'])): ?>
                                    <span class="absolute top-2 left-2 bg-red-500 text-white text-xs font-bold px-2 py-1 rounded z-10">
                                        -20%
                                    </span>
                                <?php endif; ?>
                                <?php if (!empty($product['image'])): ?>
                                    <img src="/webshop/<?= htmlspecialchars($product['image']) ?>" 
                                         alt="<?= htmlspecialchars($product['name']) ?>"
                                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                         loading="lazy">
                                <?php else: ?>
                                    <div class="w-full h-full flex items-center justify-center text-gray-400">
                                        <i class="las la-image text-4xl"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="p-3">
                                <?php if (!empty($product['vendor_name'])): ?>
                                    <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">
                                        <?= htmlspecialchars($product['vendor_name']) ?>
                                    </p>
                                <?php endif; ?>
                                <h2 class="font-medium text-gray-900 group-hover:text-gray-600 transition-colors line-clamp-2 text-sm">
                                    <?= htmlspecialchars($product['name']) ?>
                                </h2>
                                <?php if (!empty($product['is_sale'])): ?>
                                    <div class="mt-2 flex items-center gap-2">
                                        <span class="text-gray-400 line-through text-xs">
                                            <?= number_format($product['price'], 0, ',', ' ') ?> Ft
                                        </span>
                                        <span class="text-red-600 font-bold text-sm">
                                            <?= number_format($product['sale_price'], 0, ',', ' ') ?> Ft
                                        </span>
                                    </div>
                                <?php else: ?>
                                    <p class="text-gray-900 font-bold mt-2 text-sm">
                                        <?= number_format($product['price'], 0, ',', ' ') ?> Ft
                                    </p>
                                <?php endif; ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-16">
                    <i class="las la-box-open text-gray-300 text-6xl mb-4"></i>
                    <p class="text-gray-500 text-lg mb-2">
                        Nincs találat a megadott szűrőkkel.
                    </p>
                    <p class="text-gray-400 text-sm mb-4">
                        Próbáld meg módosítani a szűrőket.
                    </p>
                    <a href="<?= $baseUrl ?>" class="inline-block bg-black text-white px-6 py-2 rounded-lg hover:bg-gray-800 transition">
                        Szűrők törlése
                    </a>
                </div>
            <?php endif; ?>
        </div>
        
    </div>
</div>

<!-- MOBIL SZŰRŐ MODAL -->
<div id="mobileFilters" class="hidden fixed inset-0 z-50 lg:hidden">
    <div class="absolute inset-0 bg-black/50" onclick="document.getElementById('mobileFilters').classList.add('hidden')"></div>
    <div class="absolute right-0 top-0 h-full w-80 bg-white overflow-y-auto">
        <div class="sticky top-0 bg-white border-b px-4 py-3 flex items-center justify-between">
            <h3 class="font-bold text-lg">Szűrők</h3>
            <button onclick="document.getElementById('mobileFilters').classList.add('hidden')" class="text-2xl">
                <i class="las la-times"></i>
            </button>
        </div>
        <div class="p-4">
            <form method="get" action="<?= $baseUrl ?>">
                
                <!-- Akciós -->
                <div class="border-b pb-4 mb-4">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="sale" value="1" <?= $activeFilters['sale'] ? 'checked' : '' ?>
                               class="w-5 h-5 rounded border-gray-300 text-red-500 focus:ring-red-500">
                        <span class="text-sm font-medium">Csak akciós termékek</span>
                    </label>
                </div>
                
                <!-- Márkák -->
                <details class="border-b pb-4 mb-4" open>
                    <summary class="font-medium mb-3 cursor-pointer">Márka</summary>
                    <div class="space-y-2 max-h-40 overflow-y-auto">
                        <?php foreach ($filterOptions['brands'] as $brand): ?>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="brands[]" value="<?= htmlspecialchars($brand['name']) ?>"
                                       <?= in_array($brand['name'], $activeFilters['brands']) ? 'checked' : '' ?>
                                       class="w-4 h-4 rounded">
                                <span class="text-sm"><?= htmlspecialchars($brand['name']) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </details>
                
                <!-- Színek -->
                <details class="border-b pb-4 mb-4" open>
                    <summary class="font-medium mb-3 cursor-pointer">Szín</summary>
                    <div class="flex flex-wrap gap-2">
                        <?php foreach ($filterOptions['colors'] as $color): ?>
                            <label class="cursor-pointer" title="<?= htmlspecialchars($color['name']) ?>">
                                <input type="checkbox" name="colors[]" value="<?= htmlspecialchars($color['name']) ?>"
                                       <?= in_array($color['name'], $activeFilters['colors']) ? 'checked' : '' ?>
                                       class="sr-only peer">
                                <span class="block w-8 h-8 rounded-full border-2 peer-checked:ring-2 peer-checked:ring-black peer-checked:ring-offset-2"
                                      style="background-color: <?= $colorCodes[$color['name']] ?? '#ccc' ?>"></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </details>
                
                <!-- Méretek -->
                <details class="border-b pb-4 mb-4" open>
                    <summary class="font-medium mb-3 cursor-pointer">Méret</summary>
                    <div class="flex flex-wrap gap-2">
                        <?php foreach ($filterOptions['sizes'] as $size): ?>
                            <label class="cursor-pointer">
                                <input type="checkbox" name="sizes[]" value="<?= htmlspecialchars($size['size_value']) ?>"
                                       <?= in_array($size['size_value'], $activeFilters['sizes']) ? 'checked' : '' ?>
                                       class="sr-only peer">
                                <span class="block px-3 py-2 border rounded text-sm peer-checked:bg-black peer-checked:text-white">
                                    <?= htmlspecialchars($size['size_value']) ?>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </details>
                
                <!-- Ár -->
                <details class="border-b pb-4 mb-4" open>
                    <summary class="font-medium mb-3 cursor-pointer">Ár</summary>
                    <div class="flex gap-2">
                        <input type="number" name="min_price" placeholder="Min" value="<?= $activeFilters['min_price'] ?? '' ?>"
                               class="w-full border rounded px-3 py-2 text-sm">
                        <span class="text-gray-400 self-center">-</span>
                        <input type="number" name="max_price" placeholder="Max" value="<?= $activeFilters['max_price'] ?? '' ?>"
                               class="w-full border rounded px-3 py-2 text-sm">
                    </div>
                </details>
                
                <?php if ($activeFilters['sort'] !== 'newest'): ?>
                    <input type="hidden" name="sort" value="<?= htmlspecialchars($activeFilters['sort']) ?>">
                <?php endif; ?>
                
                <div class="flex gap-2 mt-6">
                    <a href="<?= $baseUrl ?>" class="flex-1 text-center border border-black py-3 rounded-lg">
                        Törlés
                    </a>
                    <button type="submit" class="flex-1 bg-black text-white py-3 rounded-lg">
                        Szűrés
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
