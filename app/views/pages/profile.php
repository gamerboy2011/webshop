<?php
if (!isset($_SESSION['user_id'])) {
    header("Location: /webshop/login");
    exit;
}

require_once __DIR__ . "/../../library/config.php";

$userId  = $_SESSION['user_id'];

// Közterület típusok betöltése
$streetTypes = [];
try {
    $stmt = $pdo->query("SELECT * FROM street_type ORDER BY CASE WHEN name = 'utca' THEN 0 WHEN name = 'út' THEN 1 ELSE 2 END, name");
    $streetTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}
$section = $_GET['section'] ?? 'favorites';

/* ===== KEDVENCEK BETÖLTÉSE ===== */
$favorites = [];
if ($section === 'favorites') {
    $favModel = new FavouriteModel($pdo);
    $favorites = $favModel->getUserFavorites($userId);
}

/* ===== RENDELÉSEK BETÖLTÉSE ===== */
$orders = [];
if ($section === 'orders') {
    $stmt = $pdo->prepare("
        SELECT o.*, 
               dm.name as delivery_method_name,
               pm.name as payment_method_name
        FROM orders o
        LEFT JOIN delivery_method dm ON o.delivery_method_id = dm.delivery_method_id
        LEFT JOIN payment_method pm ON o.payment_method_id = pm.payment_method_id
        WHERE o.user_id = ?
        ORDER BY o.created_at DESC
    ");
    $stmt->execute([$userId]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Rendelés tételek betöltése
    foreach ($orders as &$order) {
        $stmt = $pdo->prepare("
            SELECT oi.*, s.product_id, p.name as product_name, p.price, p.is_sale,
                   sz.size_value,
                   (SELECT pi.src FROM product_img pi WHERE pi.product_id = p.product_id ORDER BY pi.position LIMIT 1) as image
            FROM order_item oi
            JOIN stock s ON oi.stock_id = s.stock_id
            JOIN product p ON s.product_id = p.product_id
            LEFT JOIN size sz ON s.size_id = sz.size_id
            WHERE oi.order_id = ?
        ");
        $stmt->execute([$order['order_id']]);
        $order['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Összeg számítása
        $order['total'] = 0;
        foreach ($order['items'] as $item) {
            $price = $item['is_sale'] ? ($item['price'] * 0.8) : $item['price']; // 20% kedvezmény
            $order['total'] += $price * $item['quantity'];
        }
        
        // Van-e már visszaküldés ehhez?
        $stmt = $pdo->prepare("SELECT * FROM returns WHERE order_id = ? AND user_id = ?");
        $stmt->execute([$order['order_id'], $userId]);
        $order['return'] = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

/* ===== VISSZAKÜLDÉSEK BETÖLTÉSE ===== */
$returns = [];
if ($section === 'returns') {
    $stmt = $pdo->prepare("
        SELECT r.*, o.created_at as order_date
        FROM returns r
        JOIN orders o ON r.order_id = o.order_id
        WHERE r.user_id = ?
        ORDER BY r.created_at DESC
    ");
    $stmt->execute([$userId]);
    $returns = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$success = "";
$error   = "";

/* ===== MENTÉS ===== */
if ($section === 'security' && $_SERVER["REQUEST_METHOD"] === "POST") {

    // Szállítási cím (mindig kötelező)
    $shipping_postcode      = trim($_POST['shipping_postcode'] ?? '');
    $shipping_city          = trim($_POST['shipping_city'] ?? '');
    $shipping_street_name   = trim($_POST['shipping_street_name'] ?? '');
    $shipping_street_type   = trim($_POST['shipping_street_type'] ?? '');
    $shipping_house_number  = trim($_POST['shipping_house_number'] ?? '');
    $shipping_floor_door    = trim($_POST['shipping_floor_door'] ?? '');

    // Számlázási cím (pipálható)
    $sameBilling = isset($_POST['sameBilling']);

    if ($sameBilling) {
        $billing_postcode      = $shipping_postcode;
        $billing_city          = $shipping_city;
        $billing_street_name   = $shipping_street_name;
        $billing_street_type   = $shipping_street_type;
        $billing_house_number  = $shipping_house_number;
        $billing_floor_door    = $shipping_floor_door;
    } else {
        $billing_postcode      = trim($_POST['billing_postcode'] ?? '');
        $billing_city          = trim($_POST['billing_city'] ?? '');
        $billing_street_name   = trim($_POST['billing_street_name'] ?? '');
        $billing_street_type   = trim($_POST['billing_street_type'] ?? '');
        $billing_house_number  = trim($_POST['billing_house_number'] ?? '');
        $billing_floor_door    = trim($_POST['billing_floor_door'] ?? '');
    }

    $phone = trim($_POST['phone'] ?? '');

    $stmt = $pdo->prepare("
        UPDATE users SET
            shipping_postcode = ?,
            shipping_city = ?,
            shipping_street_name = ?,
            shipping_street_type = ?,
            shipping_house_number = ?,
            shipping_floor_door = ?,

            billing_postcode = ?,
            billing_city = ?,
            billing_street_name = ?,
            billing_street_type = ?,
            billing_house_number = ?,
            billing_floor_door = ?,

            phone = ?
        WHERE user_id = ?
    ");

    $stmt->execute([
        $shipping_postcode,
        $shipping_city,
        $shipping_street_name,
        $shipping_street_type,
        $shipping_house_number,
        $shipping_floor_door,

        $billing_postcode,
        $billing_city,
        $billing_street_name,
        $billing_street_type,
        $billing_house_number,
        $billing_floor_door,

        $phone,
        $userId
    ]);

    $success = "Profil adatok sikeresen mentve.";
}

/* ===== FELHASZNÁLÓ ADATOK ===== */
$stmt = $pdo->prepare("
    SELECT
        username,
        email,

        shipping_postcode,
        shipping_city,
        shipping_street_name,
        shipping_street_type,
        shipping_house_number,
        shipping_floor_door,

        billing_postcode,
        billing_city,
        billing_street_name,
        billing_street_type,
        billing_house_number,
        billing_floor_door,

        phone
    FROM users
    WHERE user_id = ?
");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<div class="max-w-6xl mx-auto mt-12 grid grid-cols-1 md:grid-cols-4 gap-8">

    <aside class="bg-white p-6 rounded-xl shadow-md h-fit">
        <nav class="space-y-3 text-sm">

            <a href="profil?section=favorites"
                class="block px-4 py-2 rounded-lg font-medium 
               <?= $section === 'favorites' ? 'bg-black text-white' : 'hover:bg-gray-100' ?>">
                Kedvencek
            </a>

            <a href="profil?section=orders"
                class="block px-4 py-2 rounded-lg font-medium 
               <?= $section === 'orders' ? 'bg-black text-white' : 'hover:bg-gray-100' ?>">
                Rendeléseid
            </a>

            <a href="profil?section=security"
                class="block px-4 py-2 rounded-lg font-medium 
               <?= $section === 'security' ? 'bg-black text-white' : 'hover:bg-gray-100' ?>">
                Profil &amp; Biztonság
            </a>

            <a href="profil?section=settings"
                class="block px-4 py-2 rounded-lg font-medium 
               <?= $section === 'settings' ? 'bg-black text-white' : 'hover:bg-gray-100' ?>">
                Beállítások
            </a>

            <a href="profil?section=returns"
                class="block px-4 py-2 rounded-lg font-medium 
               <?= $section === 'returns' ? 'bg-black text-white' : 'hover:bg-gray-100' ?>">
                Visszaküldött termékek
            </a>

        </nav>
    </aside>

    <main class="md:col-span-3 bg-white p-8 rounded-xl shadow-md">

        <?php if ($section === 'favorites'): ?>

            <h2 class="text-2xl font-semibold mb-6">
                <i class="lar la-heart text-red-500 mr-2"></i>
                Kedvenceim
            </h2>

            <?php if (empty($favorites)): ?>
                <div class="text-center py-12">
                    <i class="lar la-heart text-gray-300 text-6xl mb-4"></i>
                    <p class="text-gray-500 text-lg mb-2">Még nincs kedvenc terméked</p>
                    <p class="text-gray-400 text-sm mb-6">Böngészd a termékeket és kattints a szív ikonra!</p>
                    <a href="/webshop/" class="inline-block bg-black text-white px-6 py-3 rounded-lg hover:bg-gray-800 transition">
                        Termékek böngészése
                    </a>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                    <?php foreach ($favorites as $product): ?>
                        <div class="group relative bg-white border rounded-lg overflow-hidden hover:shadow-lg transition-shadow">
                            
                            <!-- TÖRLÉS GOMB -->
                            <button onclick="removeFavorite(<?= $product['product_id'] ?>, this)"
                                    class="absolute top-2 right-2 z-10 w-8 h-8 bg-white rounded-full shadow flex items-center justify-center text-red-500 hover:bg-red-500 hover:text-white transition">
                                <i class="las la-times"></i>
                            </button>
                            
                            <a href="/webshop/termek/<?= $product['product_id'] ?>" class="block">
                                <div class="aspect-[3/4] bg-gray-100 overflow-hidden relative">
                                    <?php if (!empty($product['is_sale'])): ?>
                                        <span class="absolute top-2 left-2 bg-red-500 text-white text-xs font-bold px-2 py-1 rounded">
                                            -20%
                                        </span>
                                    <?php endif; ?>
                                    <?php if (!empty($product['image'])): ?>
                                        <img src="/webshop/<?= htmlspecialchars($product['image']) ?>" 
                                             alt="<?= htmlspecialchars($product['name']) ?>"
                                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
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
                                    <h3 class="font-medium text-gray-900 group-hover:text-gray-600 transition-colors line-clamp-2 text-sm">
                                        <?= htmlspecialchars($product['name']) ?>
                                    </h3>
                                    <?php if (!empty($product['is_sale'])): ?>
                                        <div class="mt-2 flex items-center gap-2">
                                            <span class="text-gray-400 line-through text-xs">
                                                <?= number_format($product['price'], 0, ',', ' ') ?> Ft
                                            </span>
                                            <span class="text-red-600 font-bold text-sm">
                                                <?= number_format($product['price'] * 0.8, 0, ',', ' ') ?> Ft
                                            </span>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-gray-900 font-bold mt-2 text-sm">
                                            <?= number_format($product['price'], 0, ',', ' ') ?> Ft
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <p class="text-center text-gray-400 text-sm mt-6">
                    <?= count($favorites) ?> termék a kedvenceid között
                </p>
            <?php endif; ?>

        <?php elseif ($section === 'orders'): ?>

            <h2 class="text-2xl font-semibold mb-6">
                <i class="las la-shopping-bag mr-2"></i>
                Rendeléseim
            </h2>

            <?php if (empty($orders)): ?>
                <div class="text-center py-12">
                    <i class="las la-shopping-bag text-gray-300 text-6xl mb-4"></i>
                    <p class="text-gray-500 text-lg mb-2">Még nincs rendelésed</p>
                    <a href="/webshop/" class="inline-block bg-black text-white px-6 py-3 rounded-lg hover:bg-gray-800 transition">
                        Vásárlás
                    </a>
                </div>
            <?php else: ?>
                <div class="space-y-6">
                    <?php foreach ($orders as $order): ?>
                        <div class="border rounded-lg overflow-hidden">
                            <!-- Rendelés fejléc -->
                            <div class="bg-gray-50 p-4 flex flex-wrap justify-between items-center gap-4">
                                <div>
                                    <span class="font-semibold">Rendelés #<?= $order['order_id'] ?></span>
                                    <span class="text-gray-500 text-sm ml-2">
                                        <?= date('Y.m.d H:i', strtotime($order['created_at'])) ?>
                                    </span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <?php
                                    $statusColors = [
                                        'pending' => 'bg-yellow-100 text-yellow-800',
                                        'confirmed' => 'bg-blue-100 text-blue-800',
                                        'shipped' => 'bg-purple-100 text-purple-800',
                                        'delivered' => 'bg-green-100 text-green-800'
                                    ];
                                    $statusTexts = [
                                        'pending' => 'Feldolgozás alatt',
                                        'confirmed' => 'Visszaigazolva',
                                        'shipped' => 'Kiszállítás alatt',
                                        'delivered' => 'Kézbesítve'
                                    ];
                                    $status = $order['status'] ?? 'pending';
                                    ?>
                                    <span class="px-3 py-1 rounded-full text-xs font-medium <?= $statusColors[$status] ?>">
                                        <?= $statusTexts[$status] ?>
                                    </span>
                                    <span class="font-bold">
                                        <?= number_format($order['total'], 0, ',', ' ') ?> Ft
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Termékek -->
                            <div class="p-4">
                                <div class="space-y-3">
                                    <?php foreach ($order['items'] as $item): ?>
                                        <div class="flex items-center gap-4">
                                            <div class="w-16 h-16 bg-gray-100 rounded overflow-hidden flex-shrink-0">
                                                <?php if ($item['image']): ?>
                                                    <img src="/webshop/<?= htmlspecialchars($item['image']) ?>" 
                                                         class="w-full h-full object-cover">
                                                <?php endif; ?>
                                            </div>
                                            <div class="flex-1">
                                                <p class="font-medium"><?= htmlspecialchars($item['product_name']) ?></p>
                                                <p class="text-sm text-gray-500">
                                                    Méret: <?= htmlspecialchars($item['size_value'] ?? '-') ?> | 
                                                    <?= $item['quantity'] ?> db
                                                </p>
                                            </div>
                                            <div class="text-right">
                                                <?php $price = $item['is_sale'] ? ($item['price'] * 0.8) : $item['price']; ?>
                                                <p class="font-medium"><?= number_format($price * $item['quantity'], 0, ',', ' ') ?> Ft</p>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <!-- Szállítási adatok -->
                                <div class="mt-4 pt-4 border-t text-sm text-gray-600">
                                    <p><strong>Szállítás:</strong> <?= htmlspecialchars($order['delivery_method_name'] ?? '-') ?></p>
                                    <?php if ($order['foxpost_point_name']): ?>
                                        <p><?= htmlspecialchars($order['foxpost_point_name']) ?></p>
                                    <?php elseif ($order['shipping_address']): ?>
                                        <p><?= htmlspecialchars($order['shipping_postcode'] . ' ' . $order['shipping_city'] . ', ' . $order['shipping_address']) ?></p>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Visszaküldés gomb -->
                                <div class="mt-4 pt-4 border-t">
                                    <?php if ($order['return']): ?>
                                        <?php
                                        $returnStatusColors = [
                                            'pending' => 'bg-yellow-100 text-yellow-800',
                                            'approved' => 'bg-green-100 text-green-800',
                                            'rejected' => 'bg-red-100 text-red-800',
                                            'completed' => 'bg-gray-100 text-gray-800'
                                        ];
                                        $returnStatusTexts = [
                                            'pending' => 'Visszaküldés elbírálás alatt',
                                            'approved' => 'Visszaküldés jóváhagyva',
                                            'rejected' => 'Visszaküldés elutasítva',
                                            'completed' => 'Visszaküldés lezárva'
                                        ];
                                        ?>
                                        <span class="px-3 py-1 rounded-full text-xs font-medium <?= $returnStatusColors[$order['return']['status']] ?>">
                                            <?= $returnStatusTexts[$order['return']['status']] ?>
                                        </span>
                                    <?php else: ?>
                                        <button onclick="openReturnModal(<?= $order['order_id'] ?>)" 
                                                class="text-sm text-red-600 hover:text-red-800 font-medium">
                                            <i class="las la-undo-alt mr-1"></i> Visszaküldés kérése
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        <?php elseif ($section === 'returns'): ?>

            <h2 class="text-2xl font-semibold mb-6">
                <i class="las la-undo-alt mr-2"></i>
                Visszaküldött termékek
            </h2>

            <?php if (empty($returns)): ?>
                <div class="text-center py-12">
                    <i class="las la-undo-alt text-gray-300 text-6xl mb-4"></i>
                    <p class="text-gray-500 text-lg">Nincs visszaküldési kérelmed</p>
                </div>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($returns as $return): ?>
                        <?php
                        $returnStatusColors = [
                            'pending' => 'bg-yellow-100 text-yellow-800',
                            'approved' => 'bg-green-100 text-green-800',
                            'rejected' => 'bg-red-100 text-red-800',
                            'completed' => 'bg-gray-100 text-gray-800'
                        ];
                        $returnStatusTexts = [
                            'pending' => 'Elbírálás alatt',
                            'approved' => 'Jóváhagyva',
                            'rejected' => 'Elutasítva',
                            'completed' => 'Lezárva'
                        ];
                        ?>
                        <div class="border rounded-lg p-4">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="font-semibold">Rendelés #<?= $return['order_id'] ?></p>
                                    <p class="text-sm text-gray-500">Kérelem: <?= date('Y.m.d H:i', strtotime($return['created_at'])) ?></p>
                                    <p class="text-sm mt-2"><strong>Indoklás:</strong> <?= htmlspecialchars($return['reason']) ?></p>
                                    <?php if ($return['admin_note']): ?>
                                        <p class="text-sm mt-2 text-blue-600"><strong>Admin válasz:</strong> <?= htmlspecialchars($return['admin_note']) ?></p>
                                    <?php endif; ?>
                                </div>
                                <span class="px-3 py-1 rounded-full text-xs font-medium <?= $returnStatusColors[$return['status']] ?>">
                                    <?= $returnStatusTexts[$return['status']] ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        <?php elseif ($section === 'security'): ?>

            <h2 class="text-2xl font-semibold mb-6">Profil &amp; Biztonság</h2>

            <?php if ($success): ?>
                <div class="bg-green-100 text-green-700 p-4 rounded mb-6 text-sm">
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <form method="post" action="/webshop/profil?section=security" class="space-y-10">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="profile_save">

                <!-- SZÁLLÍTÁSI CÍM -->
                <div>
                    <h3 class="text-lg font-medium mb-4">Szállítási cím</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <!-- 1. sor -->
                        <input class="border p-2 rounded"
                            name="shipping_postcode"
                            placeholder="Irányítószám"
                            value="<?= htmlspecialchars($user['shipping_postcode'] ?? '') ?>">

                        <input class="border p-2 rounded bg-gray-50"
                            name="shipping_city"
                            id="shipping_city"
                            placeholder="Város"
                            readonly
                            value="<?= htmlspecialchars($user['shipping_city'] ?? '') ?>">

                        <!-- 2. sor -->
                        <input class="border p-2 rounded"
                            name="shipping_street_name"
                            placeholder="Utca neve"
                            value="<?= htmlspecialchars($user['shipping_street_name'] ?? '') ?>">

                        <select class="border p-2 rounded bg-white"
                            name="shipping_street_type"
                            id="shipping_street_type">
                            <option value="">Közterület típusa...</option>
                            <?php foreach ($streetTypes as $type): ?>
                                <option value="<?= htmlspecialchars($type['name']) ?>"
                                    <?= ($user['shipping_street_type'] ?? '') === $type['name'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($type['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <!-- 3. sor -->
                        <input class="border p-2 rounded"
                            name="shipping_house_number"
                            placeholder="Házszám"
                            value="<?= htmlspecialchars($user['shipping_house_number'] ?? '') ?>">

                        <input class="border p-2 rounded"
                            name="shipping_floor_door"
                            placeholder="Emelet, ajtó (opcionális)"
                            value="<?= htmlspecialchars($user['shipping_floor_door'] ?? '') ?>">
                    </div>
                </div>

                <!-- SZÁMLÁZÁSI CÍM -->
                <div>
                    <h3 class="text-lg font-medium mb-4">Számlázási cím</h3>

                    <label class="flex items-center gap-2 mb-3 text-sm">
                        <input type="checkbox" id="sameBilling" name="sameBilling">
                        <span>A számlázási cím megegyezik a szállítási címmel</span>
                    </label>

                    <div id="billingFields" class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <!-- 1. sor -->
                        <input class="border p-2 rounded"
                            name="billing_postcode"
                            placeholder="Irányítószám"
                            value="<?= htmlspecialchars($user['billing_postcode'] ?? '') ?>">

                        <input class="border p-2 rounded bg-gray-50"
                            name="billing_city"
                            id="billing_city"
                            placeholder="Város"
                            readonly
                            value="<?= htmlspecialchars($user['billing_city'] ?? '') ?>">

                        <!-- 2. sor -->
                        <input class="border p-2 rounded"
                            name="billing_street_name"
                            placeholder="Utca neve"
                            value="<?= htmlspecialchars($user['billing_street_name'] ?? '') ?>">

                        <select class="border p-2 rounded bg-white"
                            name="billing_street_type"
                            id="billing_street_type">
                            <option value="">Közterület típusa...</option>
                            <?php foreach ($streetTypes as $type): ?>
                                <option value="<?= htmlspecialchars($type['name']) ?>"
                                    <?= ($user['billing_street_type'] ?? '') === $type['name'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($type['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <!-- 3. sor -->
                        <input class="border p-2 rounded"
                            name="billing_house_number"
                            placeholder="Házszám"
                            value="<?= htmlspecialchars($user['billing_house_number'] ?? '') ?>">

                        <input class="border p-2 rounded"
                            name="billing_floor_door"
                            placeholder="Emelet, ajtó (opcionális)"
                            value="<?= htmlspecialchars($user['billing_floor_door'] ?? '') ?>">
                    </div>
                </div>

                <!-- TELEFON -->
                <div>
                    <h3 class="text-lg font-medium mb-4">Kapcsolat</h3>
                    <input class="border p-2 rounded w-full"
                        name="phone"
                        placeholder="Telefonszám"
                        value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                </div>

                <button class="bg-black text-white px-8 py-2 rounded hover:bg-gray-800 text-sm">
                    Változtatások mentése
                </button>

            </form>

        <?php endif; ?>

    </main>
</div>

<!-- VISSZAKÜLDÉS MODAL -->
<div id="returnModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-xl p-6 max-w-lg w-full mx-4">
        <h3 class="text-xl font-semibold mb-4">
            <i class="las la-undo-alt mr-2"></i>Visszaküldés kérése
        </h3>
        <form id="returnForm" method="POST" action="/webshop/api/return-request.php">
            <input type="hidden" name="order_id" id="returnOrderId">
            
            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Mi a probléma a termékkel?</label>
                <select name="problem_type" required class="w-full border rounded-lg p-3 text-sm mb-3">
                    <option value="">Válassz...</option>
                    <option value="damaged">Sérült termék</option>
                    <option value="wrong_size">Nem megfelelő méret</option>
                    <option value="wrong_product">Nem ezt a terméket rendeltem</option>
                    <option value="quality">Minőségi probléma</option>
                    <option value="not_as_described">Nem felel meg a leírásnak</option>
                    <option value="changed_mind">Meggondoltam magam</option>
                    <option value="other">Egyéb</option>
                </select>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Részletes leírás</label>
                <textarea name="reason" rows="4" required
                          class="w-full border rounded-lg p-3 text-sm"
                          placeholder="Kérlek írd le részletesen a problémát..."></textarea>
            </div>
            
            <div class="flex gap-3">
                <button type="button" onclick="closeReturnModal()" 
                        class="flex-1 px-4 py-2 border rounded-lg hover:bg-gray-50">
                    Mégse
                </button>
                <button type="submit" 
                        class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                    <i class="las la-paper-plane mr-1"></i> Kérelem beküldése
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ===== JAVÍTOTT, VÉGLEGES SCRIPT BLOKK ===== -->
<script>

/* ===== AUTOMATIKUS VÁROSKITÖLTÉS (ZIP → CITY) ===== */
function autoFillCity(zipInputName, cityInputId) {
    const zipInput = document.querySelector(`input[name='${zipInputName}']`);
    const cityInput = document.getElementById(cityInputId);

    if (!zipInput || !cityInput) {
        console.log("Hiányzó mező:", zipInputName, cityInputId);
        return;
    }

    zipInput.addEventListener("keyup", function () {
        const zip = this.value.trim();
        console.log("ZIP input:", zip);

        if (zip.length === 4) {
            fetch("/webshop/app/api/getcity.php?zip=" + zip)
                .then(res => res.json())
                .then(data => {
                    console.log("City API válasz:", data);
                    cityInput.value = data.city || "";
                    cityInput.readOnly = true;
                })
                .catch(err => console.error("AJAX hiba:", err));
        } else {
            cityInput.value = "";
            cityInput.readOnly = false;
        }
    });
}

/* ===== AUTOMATIKUS IRÁNYÍTÓSZÁM KITÖLTÉS (CITY → ZIP) ===== */
function autoFillZip(cityInputName, zipInputName) {
    const cityInput = document.querySelector(`input[name='${cityInputName}']`);
    const zipInput  = document.querySelector(`input[name='${zipInputName}']`);

    if (!cityInput || !zipInput) {
        console.log("Hiányzó mező:", cityInputName, zipInputName);
        return;
    }

    cityInput.addEventListener("keyup", function () {
        const city = this.value.trim();

        // Csak akkor kérdezünk az API-tól, ha legalább 3 karakter van
        if (city.length >= 3) {
            fetch("/webshop/app/api/postcode.php?city=" + city)
                .then(res => res.json())
                .then(data => {
                    if (data.postcode) {
                        zipInput.value = data.postcode;
                    } else {
                        zipInput.value = "";
                    }
                })
                .catch(err => console.error("AJAX hiba:", err));
        } else {
            zipInput.value = "";
        }
    });
}



/* ===== FUNKCIÓK AKTIVÁLÁSA ===== */
autoFillCity("shipping_postcode", "shipping_city");
autoFillCity("billing_postcode", "billing_city");

autoFillZip("shipping_city", "shipping_postcode");
autoFillZip("billing_city", "billing_postcode");

/* ===== ENTER TILTÁSA ===== */
document.querySelectorAll("input").forEach(input => {
    input.addEventListener("keydown", function(e) {
        if (e.key === "Enter") e.preventDefault();
    });
});

/* ===== SZÁMLÁZÁSI CÍM MÁSOLÁSA ===== */
const sameBillingCheckbox = document.getElementById('sameBilling');
if (sameBillingCheckbox) {
    sameBillingCheckbox.addEventListener('change', function() {
        const fields = ['postcode', 'city', 'street_name', 'street_type', 'house_number', 'floor_door'];

        fields.forEach(f => {
            const ship = document.querySelector(`[name='shipping_${f}']`);
            const bill = document.querySelector(`[name='billing_${f}']`);

            if (ship && bill) {
                if (this.checked) {
                    bill.value = ship.value;
                    // Select elem esetén disabled, input esetén readOnly
                    if (bill.tagName === 'SELECT') {
                        bill.disabled = true;
                    } else if (f !== 'city') { // A város mező mindig readonly marad
                        bill.readOnly = true;
                    }
                } else {
                    if (bill.tagName === 'SELECT') {
                        bill.disabled = false;
                    } else if (f !== 'city') {
                        bill.readOnly = false;
                    }
                }
            }
        });
    });
}

/* ===== KEDVENC ELTÁVOLÍTÁSA ===== */
function removeFavorite(productId, btn) {
    fetch('/webshop/favorite-toggle', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'product_id=' + productId
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const card = btn.closest('.group');
            card.style.transition = 'opacity 0.3s, transform 0.3s';
            card.style.opacity = '0';
            card.style.transform = 'scale(0.9)';
            setTimeout(() => {
                card.remove();
                const remaining = document.querySelectorAll('.group.relative');
                if (remaining.length === 0) {
                    location.reload();
                }
            }, 300);
        }
    })
    .catch(err => console.error('Hiba:', err));
}

/* ===== VISSZAKÜLDÉS MODAL ===== */
function openReturnModal(orderId) {
    document.getElementById('returnOrderId').value = orderId;
    document.getElementById('returnModal').classList.remove('hidden');
    document.getElementById('returnModal').classList.add('flex');
}

function closeReturnModal() {
    document.getElementById('returnModal').classList.add('hidden');
    document.getElementById('returnModal').classList.remove('flex');
}

document.getElementById('returnForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch('/webshop/api/return-request.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('Visszaküldési kérelem sikeresen beküldve!');
            location.reload();
        } else {
            alert('Hiba: ' + data.error);
        }
    })
    .catch(err => {
        console.error('Hiba:', err);
        alert('Hiba történt a kérelem beküldésekor.');
    });
});

</script>

