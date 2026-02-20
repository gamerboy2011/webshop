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

<div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
    <p class="text-sm text-blue-700">
        <i class="las la-info-circle mr-2"></i>
        <strong>Tipp:</strong> Admin jogosultságot adni bármelyik felhasználónak a legördülő menüből tudsz.
    </p>
</div>
