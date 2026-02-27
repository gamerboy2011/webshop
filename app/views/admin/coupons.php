<?php
// Kuponok lekérdezése
$statusFilter = $_GET['status'] ?? '';
$dateFilter = $_GET['date'] ?? '';
$search = $_GET['q'] ?? '';

$query = "
    SELECT c.*, 
           pt.name as product_type_name,
           ps.name as product_subtype_name,
           (SELECT COUNT(*) FROM user_coupons uc WHERE uc.coupon_id = c.id AND uc.used_at IS NOT NULL) as used_count,
           (SELECT COUNT(*) FROM user_coupons uc WHERE uc.coupon_id = c.id) as activated_count
    FROM coupons c
    LEFT JOIN product_type pt ON c.product_type_id = pt.product_type_id
    LEFT JOIN product_subtype ps ON c.product_subtype_id = ps.product_subtype_id
    WHERE 1=1
";
$params = [];

// Keresés
if ($search) {
    $query .= " AND (c.name LIKE ? OR c.coupon_pass LIKE ? OR c.description LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

// Státusz szűrés
if ($statusFilter === 'active') {
    $query .= " AND c.is_active = 1 AND CURDATE() BETWEEN c.start_date AND c.end_date";
} elseif ($statusFilter === 'inactive') {
    $query .= " AND c.is_active = 0";
} elseif ($statusFilter === 'expired') {
    $query .= " AND c.end_date < CURDATE()";
} elseif ($statusFilter === 'future') {
    $query .= " AND c.start_date > CURDATE()";
}

// Dátum szűrés
if ($dateFilter) {
    $query .= " AND ? BETWEEN c.start_date AND c.end_date";
    $params[] = $dateFilter;
}

$query .= " ORDER BY c.id DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Terméktípusok
$productTypes = $pdo->query("SELECT * FROM product_type")->fetchAll(PDO::FETCH_ASSOC);
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

<!-- SIKERES MŰVELETEK -->
<?php if (isset($_GET['saved'])): ?>
    <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6">
        <p class="text-green-700"><i class="las la-check-circle mr-2"></i>Kupon sikeresen mentve!</p>
    </div>
<?php endif; ?>

<?php if (isset($_GET['deleted'])): ?>
    <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
        <p class="text-red-700"><i class="las la-trash mr-2"></i>Kupon törölve!</p>
    </div>
<?php endif; ?>

<!-- SZŰRŐK ÉS Új KUPON -->
<div class="bg-white rounded-lg shadow-sm p-4 md:p-6 mb-6">
    <div class="flex flex-col gap-4">
        <!-- Szűrők -->
        <form method="get" class="flex flex-col sm:flex-row flex-wrap gap-3">
            <div>
                <label class="block text-xs text-gray-500 mb-1">Keresés</label>
                <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" 
                       placeholder="Név, kód..."
                       class="border rounded px-3 py-2 w-48">
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Státusz</label>
                <select name="status" class="border rounded px-3 py-2">
                    <option value="">Mind</option>
                    <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Aktív</option>
                    <option value="inactive" <?= $statusFilter === 'inactive' ? 'selected' : '' ?>>Inaktív</option>
                    <option value="expired" <?= $statusFilter === 'expired' ? 'selected' : '' ?>>Lejárt</option>
                    <option value="future" <?= $statusFilter === 'future' ? 'selected' : '' ?>>Jövőbeli</option>
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Érvényes ezen a napon</label>
                <input type="date" name="date" value="<?= htmlspecialchars($dateFilter) ?>"
                       class="border rounded px-3 py-2">
            </div>
            <div class="flex items-end gap-2 w-full sm:w-auto">
                <button type="submit" class="flex-1 sm:flex-initial bg-gray-800 text-white px-4 py-2 rounded hover:bg-gray-700">
                    <i class="las la-search"></i> Szűrés
                </button>
                <a href="/webshop/yw-admin/coupons" class="text-gray-500 px-4 py-2 hover:text-gray-700">
                    <i class="las la-times"></i>
                </a>
            </div>
        </form>
        
        <!-- Új kupon gomb -->
        <a href="/webshop/yw-admin/coupon-edit" 
           class="w-full sm:w-auto text-center bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700 flex items-center justify-center gap-2">
            <i class="las la-plus"></i>
            Új kupon
        </a>
    </div>
</div>

<!-- KUPONOK TÁBLÁZAT -->
<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">QR</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Név / Kód</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kedvezmény</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kategória</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Érvényesség</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Státusz</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Használat</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Műveletek</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <?php if (empty($coupons)): ?>
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                            <i class="las la-ticket-alt text-4xl text-gray-300 mb-2"></i>
                            <p>Nincs találat</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($coupons as $c): ?>
                        <?php
                        $today = date('Y-m-d');
                        $isExpired = $today > $c['end_date'];
                        $isFuture = $today < $c['start_date'];
                        $isActive = $c['is_active'] && !$isExpired && !$isFuture;
                        ?>
                        <tr class="hover:bg-gray-50 <?= !$isActive ? 'opacity-60' : '' ?>">
                            <!-- QR kód -->
                            <td class="px-4 py-3">
                                <?php if ($c['qr_code_path']): ?>
                                    <img src="/webshop/<?= htmlspecialchars($c['qr_code_path']) ?>" 
                                         alt="QR" class="w-12 h-12 object-contain cursor-pointer hover:scale-150 transition"
                                         onclick="showQR('/webshop/<?= htmlspecialchars($c['qr_code_path']) ?>', '<?= htmlspecialchars($c['coupon_pass']) ?>')">
                                <?php else: ?>
                                    <span class="text-gray-400 text-xs">Nincs</span>
                                <?php endif; ?>
                            </td>
                            
                            <!-- Név / Kód -->
                            <td class="px-4 py-3">
                                <p class="font-medium text-gray-900"><?= htmlspecialchars($c['name'] ?: $c['description']) ?></p>
                                <p class="text-xs text-gray-500 font-mono"><?= htmlspecialchars($c['coupon_pass']) ?></p>
                            </td>
                            
                            <!-- Kedvezmény -->
                            <td class="px-4 py-3">
                                <span class="text-lg font-bold text-purple-600">-<?= (int)$c['amount'] ?>%</span>
                            </td>
                            
                            <!-- Kategória -->
                            <td class="px-4 py-3 text-sm">
                                <?php if (!empty($c['product_subtype_name'])): ?>
                                    <span class="px-2 py-1 bg-purple-100 text-purple-700 rounded text-xs">
                                        <?= $subtypeNames[$c['product_subtype_name']] ?? ucfirst($c['product_subtype_name']) ?>
                                    </span>
                                <?php elseif (!empty($c['product_type_name'])): ?>
                                    <span class="px-2 py-1 bg-gray-100 rounded text-xs">
                                        <?= $typeNames[$c['product_type_name']] ?? $c['product_type_name'] ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-gray-400">Minden</span>
                                <?php endif; ?>
                            </td>
                            
                            <!-- Érvényesség -->
                            <td class="px-4 py-3 text-sm">
                                <?= date('Y.m.d', strtotime($c['start_date'])) ?><br>
                                <span class="text-gray-400">—</span>
                                <?= date('Y.m.d', strtotime($c['end_date'])) ?>
                            </td>
                            
                            <!-- Státusz -->
                            <td class="px-4 py-3">
                                <?php if ($isExpired): ?>
                                    <span class="px-2 py-1 bg-red-100 text-red-700 text-xs font-medium rounded">Lejárt</span>
                                <?php elseif ($isFuture): ?>
                                    <span class="px-2 py-1 bg-yellow-100 text-yellow-700 text-xs font-medium rounded">Jövőbeli</span>
                                <?php elseif (!$c['is_active']): ?>
                                    <span class="px-2 py-1 bg-gray-100 text-gray-700 text-xs font-medium rounded">Inaktív</span>
                                <?php else: ?>
                                    <span class="px-2 py-1 bg-green-100 text-green-700 text-xs font-medium rounded">Aktív</span>
                                <?php endif; ?>
                            </td>
                            
                            <!-- Használat -->
                            <td class="px-4 py-3 text-sm">
                                <span class="text-gray-600"><?= (int)$c['used_count'] ?></span>
                                <span class="text-gray-400">/</span>
                                <span class="text-gray-500"><?= (int)$c['activated_count'] ?></span>
                                <span class="text-xs text-gray-400">aktiv.</span>
                            </td>
                            
                            <!-- Műveletek -->
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="/webshop/yw-admin/coupon-edit/<?= $c['id'] ?>" 
                                       class="text-blue-600 hover:text-blue-800" title="Szerkesztés">
                                        <i class="las la-edit text-xl"></i>
                                    </a>
                                    
                                    <form method="post" action="/webshop/yw-admin" class="inline"
                                          onsubmit="return confirm('Biztosan törlöd ezt a kupont?')">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete_coupon">
                                        <input type="hidden" name="coupon_id" value="<?= $c['id'] ?>">
                                        <button type="submit" class="text-red-500 hover:text-red-700" title="Törlés">
                                            <i class="las la-trash text-xl"></i>
                                        </button>
                                    </form>
                                    
                                    <form method="post" action="/webshop/yw-admin" class="inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="toggle_coupon">
                                        <input type="hidden" name="coupon_id" value="<?= $c['id'] ?>">
                                        <button type="submit" class="<?= $c['is_active'] ? 'text-yellow-500 hover:text-yellow-700' : 'text-green-500 hover:text-green-700' ?>" 
                                                title="<?= $c['is_active'] ? 'Deaktiválás' : 'Aktiválás' ?>">
                                            <i class="las <?= $c['is_active'] ? 'la-toggle-on' : 'la-toggle-off' ?> text-xl"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- QR Modal -->
<div id="qrModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-xl p-8 max-w-sm text-center">
        <img id="qrImage" src="" alt="QR Code" class="w-64 h-64 mx-auto mb-4">
        <p id="qrCode" class="font-mono text-lg font-bold mb-4"></p>
        <div class="flex gap-2 justify-center">
            <a id="qrDownload" href="" download class="bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700">
                <i class="las la-download mr-1"></i>Letöltés
            </a>
            <button onclick="closeQR()" class="bg-gray-200 text-gray-700 px-4 py-2 rounded hover:bg-gray-300">
                Bezárás
            </button>
        </div>
    </div>
</div>

<script>
function showQR(src, code) {
    document.getElementById('qrImage').src = src;
    document.getElementById('qrCode').textContent = code;
    document.getElementById('qrDownload').href = src;
    document.getElementById('qrModal').classList.remove('hidden');
    document.getElementById('qrModal').classList.add('flex');
}

function closeQR() {
    document.getElementById('qrModal').classList.add('hidden');
    document.getElementById('qrModal').classList.remove('flex');
}

document.getElementById('qrModal').addEventListener('click', function(e) {
    if (e.target === this) closeQR();
});
</script>
