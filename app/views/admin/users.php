<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Felhasználó</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Regisztráció</th>
                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Státusz</th>
                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Jogosultság</th>
                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Művelet</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            <?php foreach ($users as $u): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 text-sm text-gray-500">#<?= $u['user_id'] ?></td>
                    <td class="px-6 py-4">
                        <p class="font-medium text-gray-900"><?= htmlspecialchars($u['username']) ?></p>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">
                        <?= htmlspecialchars($u['email']) ?>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">
                        <?= date('Y.m.d', strtotime($u['created_at'])) ?>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <?php if ($u['is_active']): ?>
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                Aktív
                            </span>
                        <?php else: ?>
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700">
                                Inaktív
                            </span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <form method="post" class="inline">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="set_user_role">
                            <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
                            <select name="role_id" onchange="this.form.submit()"
                                    class="text-sm border rounded px-2 py-1 <?= $u['role_id'] == 2 ? 'bg-purple-100 text-purple-700 border-purple-300' : 'bg-gray-100' ?>">
                                <option value="1" <?= $u['role_id'] == 1 ? 'selected' : '' ?>>Felhasználó</option>
                                <option value="2" <?= $u['role_id'] == 2 ? 'selected' : '' ?>>Admin</option>
                            </select>
                        </form>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <div class="flex items-center justify-center gap-2">
                            <?php if (!$u['is_active']): ?>
                                <form method="post" class="inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="activate_user">
                                    <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
                                    <button type="submit" 
                                            class="text-green-500 hover:text-green-700 transition" 
                                            title="Aktiválás">
                                        <i class="las la-check-circle text-lg"></i>
                                    </button>
                                </form>
                            <?php endif; ?>
                            
                            <?php if ($u['user_id'] != $_SESSION['user_id']): ?>
                                <button type="button" 
                                        onclick="confirmDeleteUser(<?= $u['user_id'] ?>, '<?= htmlspecialchars($u['username']) ?>')"
                                        class="text-red-500 hover:text-red-700 transition" 
                                        title="Törlés">
                                    <i class="las la-trash text-lg"></i>
                                </button>
                            <?php else: ?>
                                <span class="text-gray-300" title="Nem törölheted önmagad">
                                    <i class="las la-trash text-lg"></i>
                                </span>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <?php if (empty($users)): ?>
        <div class="p-8 text-center text-gray-500">
            <i class="las la-users text-4xl mb-2"></i>
            <p>Nincsenek felhasználók</p>
        </div>
    <?php endif; ?>
</div>

<?php if (isset($_GET['deleted'])): ?>
    <div class="mt-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
        <i class="las la-check-circle mr-2"></i> Felhasználó sikeresen törölve!
    </div>
<?php endif; ?>

<?php if (isset($_GET['activated'])): ?>
    <div class="mt-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
        <i class="las la-check-circle mr-2"></i> Felhasználó sikeresen aktiválva!
    </div>
<?php endif; ?>

<div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
    <p class="text-sm text-blue-700">
        <i class="las la-info-circle mr-2"></i>
        <strong>Tipp:</strong> Admin jogosultságot adni bármelyik felhasználónak a legördülő menüből tudsz.
    </p>
</div>

<!-- TÖRLÉS MEGERŐSÍTŐ MODAL -->
<div id="deleteUserModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50" onclick="closeDeleteUserModal()"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-2xl max-w-sm w-full p-6 text-center">
            <div class="w-16 h-16 mx-auto mb-4 bg-red-100 rounded-full flex items-center justify-center">
                <i class="las la-exclamation-triangle text-3xl text-red-500"></i>
            </div>
            <h3 class="text-lg font-bold mb-2">Felhasználó törlése</h3>
            <p class="text-gray-500 mb-4">
                Biztosan törölni szeretnéd <strong id="deleteUserName"></strong> felhasználót?
            </p>
            <div class="flex gap-3">
                <button onclick="closeDeleteUserModal()" 
                        class="flex-1 border py-2 rounded-lg hover:bg-gray-50 transition">
                    Mégsem
                </button>
                <form method="post" action="/webshop/yw-admin" class="flex-1" id="deleteUserForm">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete_user">
                    <input type="hidden" name="user_id" id="deleteUserId" value="">
                    <button type="submit" class="w-full bg-red-500 text-white py-2 rounded-lg hover:bg-red-600 transition">
                        Törlés
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDeleteUser(userId, userName) {
    document.getElementById('deleteUserId').value = userId;
    document.getElementById('deleteUserName').textContent = userName;
    document.getElementById('deleteUserModal').classList.remove('hidden');
}

function closeDeleteUserModal() {
    document.getElementById('deleteUserModal').classList.add('hidden');
}
</script>
