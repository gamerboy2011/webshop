<?php
// Visszaküldések lekérdezése
$stmt = $pdo->query("
    SELECT r.*, 
           o.created_at as order_date,
           u.username, u.email,
           o.shipping_name, o.shipping_phone
    FROM returns r
    JOIN orders o ON r.order_id = o.order_id
    JOIN users u ON r.user_id = u.user_id
    ORDER BY r.created_at DESC
");
$returns = $stmt->fetchAll(PDO::FETCH_ASSOC);

$statusColors = [
    'pending' => 'bg-yellow-100 text-yellow-800',
    'approved' => 'bg-green-100 text-green-800',
    'rejected' => 'bg-red-100 text-red-800',
    'completed' => 'bg-gray-100 text-gray-800'
];
$statusTexts = [
    'pending' => 'Elbírálás alatt',
    'approved' => 'Jóváhagyva',
    'rejected' => 'Elutasítva',
    'completed' => 'Lezárva'
];
?>

<?php if (isset($_GET['success'])): ?>
    <div class="bg-green-100 text-green-700 p-4 rounded-lg mb-6">
        Visszaküldés státusza sikeresen frissítve!
    </div>
<?php endif; ?>

<?php if (empty($returns)): ?>
    <div class="bg-white rounded-lg shadow p-12 text-center">
        <i class="las la-undo-alt text-gray-300 text-6xl mb-4"></i>
        <p class="text-gray-500 text-lg">Nincs visszaküldési kérelem</p>
    </div>
<?php else: ?>
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rendelés</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Felhasználó</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Indoklás</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dátum</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Státusz</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Műveletek</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($returns as $return): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            #<?= $return['return_id'] ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <a href="/webshop/yw-admin/orders?view=<?= $return['order_id'] ?>" 
                               class="text-blue-600 hover:text-blue-800 font-medium">
                                Rendelés #<?= $return['order_id'] ?>
                            </a>
                            <p class="text-xs text-gray-500">
                                <?= date('Y.m.d', strtotime($return['order_date'])) ?>
                            </p>
                        </td>
                        <td class="px-6 py-4">
                            <p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($return['username']) ?></p>
                            <p class="text-xs text-gray-500"><?= htmlspecialchars($return['email']) ?></p>
                        </td>
                        <td class="px-6 py-4">
                            <div class="max-w-[200px]">
                                <p class="text-sm text-gray-900 truncate" id="reason-short-<?= $return['return_id'] ?>">
                                    <?= htmlspecialchars(mb_substr($return['reason'], 0, 40)) ?><?= mb_strlen($return['reason']) > 40 ? '...' : '' ?>
                                </p>
                                <?php if (mb_strlen($return['reason']) > 40): ?>
                                    <button onclick="toggleReason(<?= $return['return_id'] ?>)" 
                                            class="text-xs text-blue-600 hover:text-blue-800 mt-1" 
                                            id="reason-btn-<?= $return['return_id'] ?>">
                                        Részletek ▼
                                    </button>
                                    <div id="reason-full-<?= $return['return_id'] ?>" class="hidden mt-2 p-2 bg-gray-50 rounded text-sm text-gray-700">
                                        <?= nl2br(htmlspecialchars($return['reason'])) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?= date('Y.m.d H:i', strtotime($return['created_at'])) ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-3 py-1 rounded-full text-xs font-medium <?= $statusColors[$return['status']] ?>">
                                <?= $statusTexts[$return['status']] ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <?php if ($return['status'] === 'pending'): ?>
                                <div class="flex gap-2">
                                    <form method="POST" class="inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="update_return">
                                        <input type="hidden" name="return_id" value="<?= $return['return_id'] ?>">
                                        <input type="hidden" name="status" value="approved">
                                        <button type="submit" class="text-green-600 hover:text-green-800" title="Jóváhagyás">
                                            <i class="las la-check-circle text-xl"></i>
                                        </button>
                                    </form>
                                    <form method="POST" class="inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="update_return">
                                        <input type="hidden" name="return_id" value="<?= $return['return_id'] ?>">
                                        <input type="hidden" name="status" value="rejected">
                                        <button type="submit" class="text-red-600 hover:text-red-800" title="Elutasítás">
                                            <i class="las la-times-circle text-xl"></i>
                                        </button>
                                    </form>
                                </div>
                            <?php elseif ($return['status'] === 'approved'): ?>
                                <form method="POST" class="inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="update_return">
                                    <input type="hidden" name="return_id" value="<?= $return['return_id'] ?>">
                                    <input type="hidden" name="status" value="completed">
                                    <button type="submit" class="text-gray-600 hover:text-gray-800" title="Lezárás">
                                        <i class="las la-check-double text-xl"></i> Lezárás
                                    </button>
                                </form>
                            <?php else: ?>
                                <span class="text-gray-400">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <script>
    function toggleReason(id) {
        const fullDiv = document.getElementById('reason-full-' + id);
        const btn = document.getElementById('reason-btn-' + id);
        
        if (fullDiv.classList.contains('hidden')) {
            fullDiv.classList.remove('hidden');
            btn.innerHTML = 'Bezárás ▲';
        } else {
            fullDiv.classList.add('hidden');
            btn.innerHTML = 'Részletek ▼';
        }
    }
    </script>
<?php endif; ?>
