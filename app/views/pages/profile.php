<?php
if (!isset($_SESSION['user_id'])) {
    header("Location: /webshop/login");
    exit;
}

$userId  = $_SESSION['user_id'];


$streetTypes = [];
try {
    $stmt = $pdo->query("SELECT * FROM street_type ORDER BY CASE WHEN name = 'utca' THEN 0 WHEN name = 'út' THEN 1 ELSE 2 END, name");
    $streetTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}
$section = $_GET['section'] ?? 'favorites';


$favorites = [];
if ($section === 'favorites') {
    $favModel = new FavouriteModel($pdo);
    $favorites = $favModel->getUserFavorites($userId);
}


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
        
        // Ellenőrizzük, hogy van-e már értékelés
        $stmt = $pdo->prepare("SELECT * FROM order_ratings WHERE order_id = ?");
        $stmt->execute([$order['order_id']]);
        $order['rating'] = $stmt->fetch(PDO::FETCH_ASSOC);
        
        
        $order['total'] = 0;
        foreach ($order['items'] as $item) {
            $price = $item['is_sale'] ? ($item['price'] * 0.8) : $item['price']; 
            $order['total'] += $price * $item['quantity'];
        }
        
        
        $stmt = $pdo->prepare("SELECT * FROM returns WHERE order_id = ? AND user_id = ?");
        $stmt->execute([$order['order_id'], $userId]);
        $order['return'] = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}


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

// Kuponok lekérdezése
$userCoupons = [];
$availableCoupons = [];
if ($section === 'coupons') {
    // Felhasználó aktivált kuponjai
    $stmt = $pdo->prepare("
        SELECT uc.*, c.name, c.description, c.amount, c.coupon_pass, c.start_date, c.end_date,
               CASE WHEN uc.used_at IS NOT NULL THEN 'used' 
                    WHEN c.end_date < CURDATE() THEN 'expired'
                    ELSE 'active' END as status
        FROM user_coupons uc
        JOIN coupons c ON uc.coupon_id = c.id
        WHERE uc.user_id = ?
        ORDER BY uc.activated_at DESC
    ");
    $stmt->execute([$userId]);
    $userCoupons = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Aktiválható kuponok (amiket még nem aktivált)
    $stmt = $pdo->prepare("
        SELECT c.*
        FROM coupons c
        WHERE c.is_active = 1 
          AND c.start_date <= CURDATE() 
          AND c.end_date >= CURDATE()
          AND c.id NOT IN (SELECT coupon_id FROM user_coupons WHERE user_id = ?)
        ORDER BY c.end_date ASC
    ");
    $stmt->execute([$userId]);
    $availableCoupons = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$success = "";
$error   = "";


if ($section === 'security' && $_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST['form_action'] ?? 'address';
    
    
    if ($action === 'change_email') {
        $newEmail = trim($_POST['new_email'] ?? '');
        $password = $_POST['email_password'] ?? '';
        
        
        $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE user_id = ?");
        $stmt->execute([$userId]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!password_verify($password, $userData['password_hash'])) {
            $error = "Hibás jelszó!";
        } elseif (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            $error = "Érvénytelen email cím formátum!";
        } else {
            
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
            $stmt->execute([$newEmail, $userId]);
            if ($stmt->fetch()) {
                $error = "Ez az email cím már foglalt!";
            } else {
                $stmt = $pdo->prepare("UPDATE users SET email = ? WHERE user_id = ?");
                $stmt->execute([$newEmail, $userId]);
                $success = "Email cím sikeresen megváltoztatva.";
            }
        }
    }
    
    elseif ($action === 'change_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        
        $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE user_id = ?");
        $stmt->execute([$userId]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!password_verify($currentPassword, $userData['password_hash'])) {
            $error = "A jelenlegi jelszó helytelen!";
        } elseif (strlen($newPassword) < 6) {
            $error = "Az új jelszónak legalább 6 karakter hosszúnak kell lennie!";
        } elseif (!preg_match('/[a-z]/', $newPassword) || !preg_match('/[A-Z]/', $newPassword) || !preg_match('/[0-9]/', $newPassword)) {
            $error = "A jelszónak tartalmaznia kell kis- és nagybetűt, valamint számot!";
        } elseif (password_verify($newPassword, $userData['password_hash'])) {
            $error = "Az új jelszó nem egyezhet a jelenlegi jelszóval!";
        } elseif ($newPassword !== $confirmPassword) {
            $error = "A két jelszó nem egyezik!";
        } else {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
            $stmt->execute([$hashedPassword, $userId]);
            $success = "Jelszó sikeresen megváltoztatva.";
        }
    }
    
    else {
        
        $shipping_postcode         = trim($_POST['shipping_postcode'] ?? '');
        $shipping_city             = trim($_POST['shipping_city'] ?? '');
        $shipping_street_name      = trim($_POST['shipping_street_name'] ?? '');
        $shipping_street_type_id   = !empty($_POST['shipping_street_type_id']) ? (int)$_POST['shipping_street_type_id'] : null;
        $shipping_house_number     = trim($_POST['shipping_house_number'] ?? '');
        $shipping_floor_door       = trim($_POST['shipping_floor_door'] ?? '');

        // City ID keresése postcode alapján
        $shipping_city_id = null;
        if ($shipping_postcode) {
            $stmt = $pdo->prepare("SELECT city_id FROM city WHERE postcode = ?");
            $stmt->execute([(int)$shipping_postcode]);
            $shipping_city_id = $stmt->fetchColumn() ?: null;
        }

        $sameBilling = isset($_POST['sameBilling']);

        if ($sameBilling) {
            $billing_postcode         = $shipping_postcode;
            $billing_city             = $shipping_city;
            $billing_city_id          = $shipping_city_id;
            $billing_street_name      = $shipping_street_name;
            $billing_street_type_id   = $shipping_street_type_id;
            $billing_house_number     = $shipping_house_number;
            $billing_floor_door       = $shipping_floor_door;
        } else {
            $billing_postcode         = trim($_POST['billing_postcode'] ?? '');
            $billing_city             = trim($_POST['billing_city'] ?? '');
            $billing_street_name      = trim($_POST['billing_street_name'] ?? '');
            $billing_street_type_id   = !empty($_POST['billing_street_type_id']) ? (int)$_POST['billing_street_type_id'] : null;
            $billing_house_number     = trim($_POST['billing_house_number'] ?? '');
            $billing_floor_door       = trim($_POST['billing_floor_door'] ?? '');
            
            $billing_city_id = null;
            if ($billing_postcode) {
                $stmt = $pdo->prepare("SELECT city_id FROM city WHERE postcode = ?");
                $stmt->execute([(int)$billing_postcode]);
                $billing_city_id = $stmt->fetchColumn() ?: null;
            }
        }

        $phone = trim($_POST['phone'] ?? '');

        $stmt = $pdo->prepare("
            UPDATE users SET
                shipping_postcode = ?,
                shipping_city = ?,
                shipping_city_id = ?,
                shipping_street_name = ?,
                shipping_street_type_id = ?,
                shipping_house_number = ?,
                shipping_floor_door = ?,

                billing_postcode = ?,
                billing_city = ?,
                billing_city_id = ?,
                billing_street_name = ?,
                billing_street_type_id = ?,
                billing_house_number = ?,
                billing_floor_door = ?,

                phone = ?
            WHERE user_id = ?
        ");

        $stmt->execute([
            $shipping_postcode,
            $shipping_city,
            $shipping_city_id,
            $shipping_street_name,
            $shipping_street_type_id,
            $shipping_house_number,
            $shipping_floor_door,

            $billing_postcode,
            $billing_city,
            $billing_city_id,
            $billing_street_name,
            $billing_street_type_id,
            $billing_house_number,
            $billing_floor_door,

            $phone,
            $userId
        ]);

        $success = "Profil adatok sikeresen mentve.";
    }
}


$stmt = $pdo->prepare("
    SELECT
        username,
        email,

        shipping_postcode,
        shipping_city,
        shipping_city_id,
        shipping_street_name,
        shipping_street_type_id,
        shipping_house_number,
        shipping_floor_door,

        billing_postcode,
        billing_city,
        billing_city_id,
        billing_street_name,
        billing_street_type_id,
        billing_house_number,
        billing_floor_door,

        phone
    FROM users
    WHERE user_id = ?
");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<div class="max-w-6xl mx-auto mt-6 md:mt-12 px-4 md:px-0">

    <!-- Profil fejléc -->
    <div class="mb-6">
        <h1 class="text-2xl md:text-3xl font-bold text-gray-900">
            <i class="las la-user-circle mr-2"></i>Profilom
        </h1>
        <p class="text-gray-500 mt-1">Üdvözlünk, <span class="font-medium text-gray-700"><?= htmlspecialchars($user['username']) ?></span>!</p>
    </div>

    <!-- MOBIL: Horizontális menü -->
    <div class="md:hidden mb-4 -mx-4 px-4">
        <div class="flex gap-2 overflow-x-auto pb-2 scrollbar-hide">
            <a href="/webshop/profil/kivansaglista"
               class="flex-shrink-0 px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap
                      <?= $section === 'favorites' ? 'bg-black text-white' : 'bg-gray-100 text-gray-700' ?>">
                <i class="lar la-heart mr-1"></i>Kívánságlistám
            </a>
            <a href="/webshop/profil/rendelesek"
               class="flex-shrink-0 px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap
                      <?= $section === 'orders' ? 'bg-black text-white' : 'bg-gray-100 text-gray-700' ?>">
                <i class="las la-shopping-bag mr-1"></i>Rendeléseim
            </a>
            <a href="/webshop/profil/kuponjaim"
               class="flex-shrink-0 px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap
                      <?= $section === 'coupons' ? 'bg-black text-white' : 'bg-gray-100 text-gray-700' ?>">
                <i class="las la-ticket-alt mr-1"></i>Kuponjaim
            </a>
            <a href="/webshop/profil/biztonsag"
               class="flex-shrink-0 px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap
                      <?= $section === 'security' ? 'bg-black text-white' : 'bg-gray-100 text-gray-700' ?>">
                <i class="las la-user-shield mr-1"></i>Profil
            </a>
            <a href="/webshop/profil/visszakuldott"
               class="flex-shrink-0 px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap
                      <?= $section === 'returns' ? 'bg-black text-white' : 'bg-gray-100 text-gray-700' ?>">
                <i class="las la-undo-alt mr-1"></i>Visszaküldések
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-8">

        <!-- DESKTOP: Sidebar -->
        <aside class="hidden md:block bg-white p-6 rounded-xl shadow-md h-fit">
            <nav class="space-y-3 text-sm">
                <a href="/webshop/profil/kivansaglista"
                    class="block px-4 py-2 rounded-lg font-medium 
                   <?= $section === 'favorites' ? 'bg-black text-white' : 'hover:bg-gray-100' ?>">
                    Kívánságlistám
                </a>
                <a href="/webshop/profil/rendelesek"
                    class="block px-4 py-2 rounded-lg font-medium 
                   <?= $section === 'orders' ? 'bg-black text-white' : 'hover:bg-gray-100' ?>">
                    Rendeléseim
                </a>
                <a href="/webshop/profil/kuponjaim"
                    class="block px-4 py-2 rounded-lg font-medium 
                   <?= $section === 'coupons' ? 'bg-black text-white' : 'hover:bg-gray-100' ?>">
                    Kuponjaim
                </a>
                <a href="/webshop/profil/biztonsag"
                    class="block px-4 py-2 rounded-lg font-medium 
                   <?= $section === 'security' ? 'bg-black text-white' : 'hover:bg-gray-100' ?>">
                    Profil &amp; Biztonság
                </a>
                <a href="/webshop/profil/visszakuldott"
                    class="block px-4 py-2 rounded-lg font-medium 
                   <?= $section === 'returns' ? 'bg-black text-white' : 'hover:bg-gray-100' ?>">
                    Visszaküldött termékek
                </a>
            </nav>
        </aside>

        <main class="md:col-span-3 bg-white p-4 md:p-8 rounded-xl shadow-md">

        <?php if ($section === 'favorites'): ?>

            <h2 class="text-2xl font-semibold mb-6">
                <i class="lar la-heart text-red-500 mr-2"></i>
                Kívánságlistám
            </h2>

            <?php if (empty($favorites)): ?>
                <div class="text-center py-12">
                    <i class="lar la-heart text-gray-300 text-6xl mb-4"></i>
                    <p class="text-gray-500 text-lg mb-2">A kívánságlistád még üres</p>
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
                                <div class="aspect-[3/4] bg-white overflow-hidden relative flex items-center justify-center border-b">
                                    <?php if (!empty($product['is_sale'])): ?>
                                        <span class="absolute top-2 left-2 bg-red-500 text-white text-xs font-bold px-2 py-1 rounded">
                                            -20%
                                        </span>
                                    <?php endif; ?>
                                    <?php if (!empty($product['image'])): ?>
                                        <img src="/webshop/<?= htmlspecialchars($product['image']) ?>" 
                                             alt="<?= htmlspecialchars($product['name']) ?>"
                                             class="max-w-full max-h-full object-contain group-hover:scale-105 transition-transform duration-300">
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
                    <?= count($favorites) ?> termék a kívánságlistádon
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
                                
                                <!-- Visszaküldés és Értékelés -->
                                <div class="mt-4 pt-4 border-t flex flex-wrap items-center gap-4">
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
                                    
                                    <!-- Értékelés -->
                                    <?php if ($order['rating']): ?>
                                        <div class="flex items-center gap-2 text-sm">
                                            <span class="text-yellow-500">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <?= $i <= $order['rating']['rating'] ? '★' : '☆' ?>
                                                <?php endfor; ?>
                                            </span>
                                            <span class="text-gray-500">Már értékelted</span>
                                        </div>
                                    <?php else: ?>
                                        <button onclick="openRatingModal(<?= $order['order_id'] ?>)" 
                                                class="text-sm text-yellow-600 hover:text-yellow-700 font-medium">
                                            <i class="las la-star mr-1"></i> Értékelés
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

        <?php elseif ($section === 'coupons'): ?>

            <h2 class="text-2xl font-semibold mb-6">
                <i class="las la-ticket-alt mr-2"></i>
                Kuponjaim
            </h2>

            <!-- KUPON AKTIVÁLÁS -->
            <div class="mb-8">
                <form id="activateCouponForm" class="flex gap-2">
                    <input type="text" name="coupon_code" id="couponCodeInput"
                           placeholder="Kuponkód megadása..."
                           class="flex-1 border rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-black">
                    <button type="submit" class="bg-black text-white px-6 py-2 rounded-lg text-sm font-medium hover:bg-gray-800 transition">
                        <i class="las la-plus mr-1"></i>Aktiválás
                    </button>
                </form>
                <p id="couponMessage" class="text-sm mt-2 hidden"></p>
            </div>

            <!-- AKTIVÁLHATÓ KUPONOK -->
            <?php if (!empty($availableCoupons)): ?>
            <div class="mb-8">
                <h3 class="text-lg font-medium mb-4 text-green-600">
                    <i class="las la-gift mr-1"></i>Elérhető kuponok
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php foreach ($availableCoupons as $coupon): ?>
                        <div class="border-2 border-dashed border-green-300 rounded-lg p-4 bg-green-50">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="font-bold text-lg text-green-700"><?= htmlspecialchars($coupon['name']) ?></p>
                                    <p class="text-sm text-gray-600 mt-1"><?= htmlspecialchars($coupon['description']) ?></p>
                                    <p class="text-2xl font-bold text-green-600 mt-2">-<?= number_format($coupon['amount'], 0, ',', ' ') ?>%</p>
                                    <p class="text-xs text-gray-500 mt-2">
                                        Érvényes: <?= date('Y.m.d', strtotime($coupon['end_date'])) ?>-ig
                                    </p>
                                </div>
                                <button onclick="activateCoupon('<?= htmlspecialchars($coupon['coupon_pass']) ?>')" 
                                        class="bg-green-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-green-700 transition">
                                    <i class="las la-check mr-1"></i>Aktiválás
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- AKTIVÁLT KUPONOK -->
            <h3 class="text-lg font-medium mb-4">
                <i class="las la-tags mr-1"></i>Aktivált kuponjaim
            </h3>

            <?php if (empty($userCoupons)): ?>
                <div class="text-center py-12">
                    <i class="las la-ticket-alt text-gray-300 text-6xl mb-4"></i>
                    <p class="text-gray-500 text-lg mb-2">Még nincs aktivált kuponod</p>
                    <p class="text-gray-400 text-sm">Add meg a kuponkódot fent, vagy aktiváld az elérhető kuponokat!</p>
                </div>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($userCoupons as $coupon): ?>
                        <?php
                        $statusClasses = [
                            'active' => 'border-green-300 bg-green-50',
                            'used' => 'border-gray-300 bg-gray-50 opacity-60',
                            'expired' => 'border-red-300 bg-red-50 opacity-60'
                        ];
                        $statusTexts = [
                            'active' => '<span class="text-green-600 font-medium"><i class="las la-check-circle mr-1"></i>Aktív</span>',
                            'used' => '<span class="text-gray-500"><i class="las la-shopping-cart mr-1"></i>Felhasználva</span>',
                            'expired' => '<span class="text-red-500"><i class="las la-clock mr-1"></i>Lejárt</span>'
                        ];
                        ?>
                        <div class="border-2 border-dashed <?= $statusClasses[$coupon['status']] ?> rounded-lg p-4">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="font-bold text-lg"><?= htmlspecialchars($coupon['name']) ?></p>
                                    <p class="text-sm text-gray-600 mt-1"><?= htmlspecialchars($coupon['description']) ?></p>
                                    <p class="text-2xl font-bold mt-2 <?= $coupon['status'] === 'active' ? 'text-green-600' : 'text-gray-400' ?>">
                                        -<?= number_format($coupon['amount'], 0, ',', ' ') ?>%
                                    </p>
                                    <div class="flex items-center gap-4 mt-2 text-xs text-gray-500">
                                        <span><i class="las la-calendar-plus mr-1"></i>Aktiválva: <?= date('Y.m.d', strtotime($coupon['activated_at'])) ?></span>
                                        <span><i class="las la-calendar-times mr-1"></i>Lejárat: <?= date('Y.m.d', strtotime($coupon['end_date'])) ?></span>
                                    </div>
                                    <?php if ($coupon['status'] === 'active'): ?>
                                        <p class="mt-3 text-sm">
                                            <span class="bg-gray-100 px-3 py-1 rounded font-mono text-gray-700">
                                                <?= htmlspecialchars($coupon['coupon_pass']) ?>
                                            </span>
                                            <span class="text-gray-400 ml-2">- Használd a pénztárnál</span>
                                        </p>
                                    <?php endif; ?>
                                </div>
                                <div class="text-right">
                                    <?= $statusTexts[$coupon['status']] ?>
                                    <?php if ($coupon['used_at']): ?>
                                        <p class="text-xs text-gray-400 mt-1">
                                            <?= date('Y.m.d', strtotime($coupon['used_at'])) ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        <?php elseif ($section === 'security'): ?>

            <h2 class="text-2xl font-semibold mb-6">Profil &amp; Biztonság</h2>

            <?php if ($success): ?>
                <div class="bg-green-100 text-green-700 p-4 rounded mb-6 text-sm">
                    <i class="las la-check-circle mr-1"></i><?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="bg-red-100 text-red-700 p-4 rounded mb-6 text-sm">
                    <i class="las la-exclamation-circle mr-1"></i><?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <!-- FIÓK ADATOK -->
            <div class="mb-10 pb-10 border-b">
                <h3 class="text-lg font-medium mb-4"><i class="las la-user-circle mr-2"></i>Fiók adatok</h3>
                
                <div class="bg-gray-50 rounded-lg p-4 mb-4">
                    <p class="text-sm text-gray-600">Felhasználónév</p>
                    <p class="font-medium"><?= htmlspecialchars($user['username']) ?></p>
                </div>
                
                <div class="bg-gray-50 rounded-lg p-4 mb-4">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-sm text-gray-600">Email cím</p>
                            <p class="font-medium"><?= htmlspecialchars($user['email']) ?></p>
                        </div>
                        <button type="button" onclick="openEmailModal()" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                            <i class="las la-edit mr-1"></i>Módosítás
                        </button>
                    </div>
                </div>
                
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-sm text-gray-600">Jelszó</p>
                            <p class="font-medium">••••••••</p>
                        </div>
                        <button type="button" onclick="openPasswordModal()" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                            <i class="las la-key mr-1"></i>Módosítás
                        </button>
                    </div>
                </div>
            </div>

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
                            name="shipping_street_type_id"
                            id="shipping_street_type_id">
                            <option value="">Közterület típusa...</option>
                            <?php foreach ($streetTypes as $type): ?>
                                <option value="<?= $type['street_type_id'] ?>"
                                    <?= ($user['shipping_street_type_id'] ?? '') == $type['street_type_id'] ? 'selected' : '' ?>>
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
                            name="billing_street_type_id"
                            id="billing_street_type_id">
                            <option value="">Közterület típusa...</option>
                            <?php foreach ($streetTypes as $type): ?>
                                <option value="<?= $type['street_type_id'] ?>"
                                    <?= ($user['billing_street_type_id'] ?? '') == $type['street_type_id'] ? 'selected' : '' ?>>
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

/* ===== AUTOMATIKUS VÁROSKITÖLTÉS (ZIP → CITY) - RESTful API ===== */
function autoFillCity(zipInputName, cityInputId) {
    const zipInput = document.querySelector(`input[name='${zipInputName}']`);
    const cityInput = document.getElementById(cityInputId);

    if (!zipInput || !cityInput) {
        console.log("Hiányzó mező:", zipInputName, cityInputId);
        return;
    }

    zipInput.addEventListener("keyup", async function () {
        const zip = this.value.trim();

        if (zip.length === 4) {
            try {
                const response = await fetch("/webshop/api/v1/cities?postcode=" + zip);
                const data = await response.json();
                if (data.success && data.data?.city) {
                    cityInput.value = data.data.city;
                    cityInput.readOnly = true;
                } else {
                    cityInput.value = "";
                    cityInput.readOnly = false;
                }
            } catch (err) {
                console.error("API hiba:", err);
            }
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

/* ===== KEDVENC ELTÁVOLÍTÁSA - RESTful API ===== */
async function removeFavorite(productId, btn) {
    try {
        const response = await fetch('/webshop/api/v1/favorites/' + productId, {
            method: 'DELETE'
        });
        
        if (response.ok || response.status === 204) {
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
    } catch (err) {
        console.error('Hiba:', err);
    }
}

/* ===== KUPON AKTIVÁLÁS ===== */
async function activateCoupon(code) {
    const messageEl = document.getElementById('couponMessage');
    
    try {
        const response = await fetch('/webshop/api/v1/coupons', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ code: code })
        });
        
        const data = await response.json();
        
        messageEl.classList.remove('hidden', 'text-green-600', 'text-red-600');
        
        if (response.ok && data.success) {
            messageEl.classList.add('text-green-600');
            messageEl.innerHTML = '<i class="las la-check-circle mr-1"></i>' + data.data.message;
            setTimeout(() => location.reload(), 1500);
        } else {
            messageEl.classList.add('text-red-600');
            messageEl.innerHTML = '<i class="las la-times-circle mr-1"></i>' + (data.message || 'Hiba történt');
        }
    } catch (err) {
        messageEl.classList.remove('hidden');
        messageEl.classList.add('text-red-600');
        messageEl.innerHTML = '<i class="las la-times-circle mr-1"></i>Hiba történt a kupon aktiválásakor.';
    }
}

// Kupon form kezelése
document.getElementById('activateCouponForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const code = document.getElementById('couponCodeInput').value.trim();
    if (code) {
        activateCoupon(code);
    }
});

/* ===== VISSZAÜLDÉS MODAL ===== */
function openReturnModal(orderId) {
    document.getElementById('returnOrderId').value = orderId;
    document.getElementById('returnModal').classList.remove('hidden');
    document.getElementById('returnModal').classList.add('flex');
}

function closeReturnModal() {
    document.getElementById('returnModal').classList.add('hidden');
    document.getElementById('returnModal').classList.remove('flex');
}

document.getElementById('returnForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    try {
        const response = await fetch('/webshop/api/v1/returns', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                order_id: formData.get('order_id'),
                reason: formData.get('problem_type'),
                description: formData.get('reason')
            })
        });
        
        const data = await response.json();
        
        if (response.ok && data.success) {
            alert('Visszaküldési kérelem sikeresen beküldve!');
            location.reload();
        } else {
            alert('Hiba: ' + (data.message || 'Ismeretlen hiba'));
        }
    } catch (err) {
        console.error('Hiba:', err);
        alert('Hiba történt a kérelem beküldésekor.');
    }
});

/* ===== EMAIL MODAL ===== */
function openEmailModal() {
    document.getElementById('emailModal').classList.remove('hidden');
    document.getElementById('emailModal').classList.add('flex');
}

function closeEmailModal() {
    document.getElementById('emailModal').classList.add('hidden');
    document.getElementById('emailModal').classList.remove('flex');
}

/* ===== JELSZÓ MODAL ===== */
function openPasswordModal() {
    document.getElementById('passwordModal').classList.remove('hidden');
    document.getElementById('passwordModal').classList.add('flex');
}

function closePasswordModal() {
    document.getElementById('passwordModal').classList.add('hidden');
    document.getElementById('passwordModal').classList.remove('flex');
}

</script>

<!-- EMAIL MÓDOSÍTÁS MODAL -->
<div id="emailModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-xl p-6 max-w-md w-full mx-4">
        <h3 class="text-xl font-semibold mb-4">
            <i class="las la-envelope mr-2"></i>Email cím módosítása
        </h3>
        <form method="POST" action="/webshop/profil?section=security">
            <?= csrf_field() ?>
            <input type="hidden" name="form_action" value="change_email">
            
            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Új email cím</label>
                <input type="email" name="new_email" required
                       class="w-full border rounded-lg p-3 text-sm focus:ring-2 focus:ring-black focus:outline-none"
                       placeholder="pelda@email.com">
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Jelenlegi jelszó (megerősítéshez)</label>
                <input type="password" name="email_password" required
                       class="w-full border rounded-lg p-3 text-sm focus:ring-2 focus:ring-black focus:outline-none"
                       placeholder="••••••••">
            </div>
            
            <div class="flex gap-3">
                <button type="button" onclick="closeEmailModal()" 
                        class="flex-1 px-4 py-2 border rounded-lg hover:bg-gray-50">
                    Mégse
                </button>
                <button type="submit" 
                        class="flex-1 px-4 py-2 bg-black text-white rounded-lg hover:bg-gray-800">
                    Mentés
                </button>
            </div>
        </form>
    </div>
</div>

<!-- JELSZÓ MÓDOSÍTÁS MODAL -->
<div id="passwordModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-xl p-6 max-w-md w-full mx-4">
        <h3 class="text-xl font-semibold mb-4">
            <i class="las la-key mr-2"></i>Jelszó módosítása
        </h3>
        <form method="POST" action="/webshop/profil?section=security">
            <?= csrf_field() ?>
            <input type="hidden" name="form_action" value="change_password">
            
            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Jelenlegi jelszó</label>
                <div class="relative">
                    <input type="password" name="current_password" id="current_password" required
                           class="w-full border rounded-lg p-3 text-sm focus:ring-2 focus:ring-black focus:outline-none pr-10"
                           placeholder="••••••••">
                    <button type="button" onclick="togglePasswordVisibility('current_password', this)"
                            class="absolute inset-y-0 right-3 flex items-center text-gray-500 hover:text-gray-700">
                        <i class="las la-eye text-lg"></i>
                    </button>
                </div>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Új jelszó</label>
                <div class="relative">
                    <input type="password" name="new_password" id="new_password" required minlength="6"
                           class="w-full border rounded-lg p-3 text-sm focus:ring-2 focus:ring-black focus:outline-none pr-10"
                           placeholder="Legalább 6 karakter, kis- és nagybetű, szám">
                    <button type="button" onclick="togglePasswordVisibility('new_password', this)"
                            class="absolute inset-y-0 right-3 flex items-center text-gray-500 hover:text-gray-700">
                        <i class="las la-eye text-lg"></i>
                    </button>
                </div>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Új jelszó megerősítése</label>
                <div class="relative">
                    <input type="password" name="confirm_password" id="confirm_password" required minlength="6"
                           class="w-full border rounded-lg p-3 text-sm focus:ring-2 focus:ring-black focus:outline-none pr-10"
                           placeholder="Írd be újra">
                    <button type="button" onclick="togglePasswordVisibility('confirm_password', this)"
                            class="absolute inset-y-0 right-3 flex items-center text-gray-500 hover:text-gray-700">
                        <i class="las la-eye text-lg"></i>
                    </button>
                </div>
            </div>
            
            <div class="flex gap-3">
                <button type="button" onclick="closePasswordModal()" 
                        class="flex-1 px-4 py-2 border rounded-lg hover:bg-gray-50">
                    Mégse
                </button>
                <button type="submit" 
                        class="flex-1 px-4 py-2 bg-black text-white rounded-lg hover:bg-gray-800">
                    Jelszó megváltoztatása
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ÉRTÉKELÉS MODAL -->
<div id="ratingModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-xl p-6 max-w-md w-full mx-4">
        <h3 class="text-xl font-semibold mb-4">
            <i class="las la-star text-yellow-500 mr-2"></i>Rendelés értékelése
        </h3>
        <form id="ratingForm">
            <input type="hidden" name="order_id" id="rating_order_id">
            
            <div class="mb-6">
                <label class="block text-sm font-medium mb-3">Mennyire voltál elégedett?</label>
                <div class="flex justify-center gap-2" id="starContainer">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <label class="cursor-pointer">
                            <input type="radio" name="rating" value="<?= $i ?>" class="sr-only" required>
                            <span class="text-4xl text-gray-300 hover:text-yellow-500 transition star-rating" data-star="<?= $i ?>">★</span>
                        </label>
                    <?php endfor; ?>
                </div>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Véleményed (opcionális)</label>
                <textarea name="comment" rows="3"
                          class="w-full border rounded-lg p-3 text-sm focus:ring-2 focus:ring-black focus:outline-none resize-none"
                          placeholder="Írd le tapasztalataidat..."></textarea>
            </div>
            
            <div id="ratingMessage" class="mb-4 text-sm hidden"></div>
            
            <div class="flex gap-3">
                <button type="button" onclick="closeRatingModal()" 
                        class="flex-1 px-4 py-2 border rounded-lg hover:bg-gray-50">
                    Mégse
                </button>
                <button type="submit" id="ratingSubmitBtn"
                        class="flex-1 px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600">
                    Értékelés küldése
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function togglePasswordVisibility(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('la-eye');
        icon.classList.add('la-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('la-eye-slash');
        icon.classList.add('la-eye');
    }
}

// Értékelés modal
function openRatingModal(orderId) {
    document.getElementById('rating_order_id').value = orderId;
    document.getElementById('ratingModal').classList.remove('hidden');
    document.getElementById('ratingModal').classList.add('flex');
    // Reset form
    document.getElementById('ratingForm').reset();
    document.querySelectorAll('.star-rating').forEach(s => {
        s.classList.remove('text-yellow-500');
        s.classList.add('text-gray-300');
    });
    document.getElementById('ratingMessage').classList.add('hidden');
}

function closeRatingModal() {
    document.getElementById('ratingModal').classList.add('hidden');
    document.getElementById('ratingModal').classList.remove('flex');
}

// Csillagok interakció
document.querySelectorAll('.star-rating').forEach(star => {
    star.addEventListener('click', function() {
        const starValue = parseInt(this.dataset.star);
        document.querySelectorAll('.star-rating').forEach(s => {
            const sValue = parseInt(s.dataset.star);
            if (sValue <= starValue) {
                s.classList.remove('text-gray-300');
                s.classList.add('text-yellow-500');
            } else {
                s.classList.remove('text-yellow-500');
                s.classList.add('text-gray-300');
            }
        });
    });
    
    star.addEventListener('mouseenter', function() {
        const starValue = parseInt(this.dataset.star);
        document.querySelectorAll('.star-rating').forEach(s => {
            const sValue = parseInt(s.dataset.star);
            if (sValue <= starValue) {
                s.classList.add('text-yellow-500');
            }
        });
    });
});

document.getElementById('starContainer')?.addEventListener('mouseleave', function() {
    const checkedInput = document.querySelector('#starContainer input:checked');
    const checkedValue = checkedInput ? parseInt(checkedInput.value) : 0;
    document.querySelectorAll('.star-rating').forEach(s => {
        const sValue = parseInt(s.dataset.star);
        if (sValue <= checkedValue) {
            s.classList.remove('text-gray-300');
            s.classList.add('text-yellow-500');
        } else {
            s.classList.remove('text-yellow-500');
            s.classList.add('text-gray-300');
        }
    });
});

// Értékelés beküldése
document.getElementById('ratingForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const messageEl = document.getElementById('ratingMessage');
    const submitBtn = document.getElementById('ratingSubmitBtn');
    
    submitBtn.disabled = true;
    submitBtn.textContent = 'Küldés...';
    
    fetch('/webshop/api/submit-rating.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        messageEl.classList.remove('hidden');
        if (data.success) {
            messageEl.className = 'mb-4 text-sm text-green-600';
            messageEl.textContent = 'Köszönjük az értékelést!';
            setTimeout(() => location.reload(), 1500);
        } else {
            messageEl.className = 'mb-4 text-sm text-red-600';
            messageEl.textContent = data.error || 'Hiba történt.';
            submitBtn.disabled = false;
            submitBtn.textContent = 'Értékelés küldése';
        }
    })
    .catch(err => {
        messageEl.classList.remove('hidden');
        messageEl.className = 'mb-4 text-sm text-red-600';
        messageEl.textContent = 'Hiba történt. Próbáld újra!';
        submitBtn.disabled = false;
        submitBtn.textContent = 'Értékelés küldése';
    });
});

// Modal bezárása kívülre kattintásra
document.getElementById('ratingModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeRatingModal();
});
</script>

