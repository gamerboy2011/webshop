<?php
/**
 * ADMIN PANEL - Yoursy Wear
 * Belépési pont: /webshop/yw-admin
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

// Session és alap függvények
require_once __DIR__ . '/app/library/customfunctions.php';
secure_session_start();

// Adatbázis
require_once __DIR__ . '/app/config/database.php';

// Autoloader
spl_autoload_register(function ($class) {
    $dirs = ['app/controllers', 'app/models'];
    foreach ($dirs as $dir) {
        $file = __DIR__ . '/' . $dir . '/' . $class . '.php';
        if (file_exists($file)) { require_once $file; return; }
        $file = __DIR__ . '/' . $dir . '/' . strtolower($class) . '.php';
        if (file_exists($file)) { require_once $file; return; }
    }
});

// Admin controller
$admin = new AdminController($pdo);

// URL feldolgozás
$uri = $_SERVER['REQUEST_URI'];
$uri = str_replace('/webshop/yw-admin', '', $uri);
$uri = trim(parse_url($uri, PHP_URL_PATH), '/');
$parts = $uri ? explode('/', $uri) : [];
$page = $parts[0] ?? 'dashboard';
$action = $parts[1] ?? null;
$id = $parts[2] ?? null;

// POST kérések kezelése
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // CSRF ellenőrzés
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die('CSRF token érvénytelen');
    }
    
    $postAction = $_POST['action'] ?? '';
    
    switch ($postAction) {
        case 'admin_login':
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role_id = 2");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['role_id'] = $user['role_id'];
                $_SESSION['logged_in'] = true;
                header('Location: /webshop/yw-admin');
                exit;
            } else {
                $loginError = 'Hibás email vagy jelszó, vagy nincs admin jogosultságod.';
            }
            break;
            
        case 'save_product':
            $admin->requireAdmin();
            $admin->saveProduct($_POST);
            header('Location: /webshop/yw-admin/products?saved=1');
            exit;
            
        case 'delete_product':
            $admin->requireAdmin();
            $admin->deleteProduct((int)$_POST['product_id']);
            header('Location: /webshop/yw-admin/products?deleted=1');
            exit;
            
        case 'toggle_sale':
            $admin->requireAdmin();
            $admin->toggleSale((int)$_POST['product_id']);
            header('Location: /webshop/yw-admin/products');
            exit;
            
        case 'set_user_role':
            $admin->requireAdmin();
            $admin->setUserRole((int)$_POST['user_id'], (int)$_POST['role_id']);
            header('Location: /webshop/yw-admin/users');
            exit;

        case 'delete_user':
            $admin->requireAdmin();
            $admin->deleteUser((int)$_POST['user_id']);
            header('Location: /webshop/yw-admin/users?deleted=1');
            exit;

        case 'activate_user':
            $admin->requireAdmin();
            $admin->activateUser((int)$_POST['user_id']);
            header('Location: /webshop/yw-admin/users?activated=1');
            exit;

        case 'update_stock':
            $admin->requireAdmin();
            if (!empty($_POST['stock'])) {
                $admin->bulkUpdateStock($_POST['stock']);
            }
            $redirect = '/webshop/yw-admin/stock';
            if (!empty($_POST['product_id'])) {
                $redirect .= '?product_id=' . $_POST['product_id'];
            }
            header('Location: ' . $redirect . '&saved=1');
            exit;

        case 'update_order_status':
            $admin->requireAdmin();
            $orderId = (int)$_POST['order_id'];
            $newStatus = $_POST['new_status'] ?? '';
            
            if ($orderId && in_array($newStatus, ['confirmed', 'shipped', 'delivered'])) {
                // Státusz frissítése
                $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
                $stmt->execute([$newStatus, $orderId]);
                
                // Felhasználó adatai
                $stmt = $pdo->prepare("
                    SELECT o.*, u.username, u.email 
                    FROM orders o 
                    JOIN users u ON o.user_id = u.user_id 
                    WHERE o.order_id = ?
                ");
                $stmt->execute([$orderId]);
                $order = $stmt->fetch();
                
                if ($order) {
                    require_once __DIR__ . '/app/helpers/Mail.php';
                    
                    // Email küldése státusz alapján
                    if ($newStatus === 'shipped') {
                        // FELADÁS EMAIL
                        $subject = "YoursyWear - Csomagod feladva! #$orderId";
                        $htmlBody = "
                        <!DOCTYPE html>
                        <html>
                        <head><meta charset='UTF-8'></head>
                        <body style='font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px;'>
                            <div style='max-width: 600px; margin: 0 auto; background: white; border-radius: 10px; overflow: hidden;'>
                                <div style='background: #000; color: white; padding: 30px; text-align: center;'>
                                    <h1 style='margin: 0;'>YoursyWear</h1>
                                </div>
                                <div style='padding: 30px;'>
                                    <h2 style='color: #333;'>Kedves {$order['username']}!</h2>
                                    
                                    <div style='background: #e8f5e9; border-left: 4px solid #4caf50; padding: 20px; margin: 20px 0;'>
                                        <p style='margin: 0; font-size: 18px;'>
                                            <strong>\xf0\x9f\x93\xa6 Csomagod úton van!</strong>
                                        </p>
                                    </div>
                                    
                                    <p style='color: #666;'>A <strong>#{$orderId}</strong> számú rendelésedet feladtuk!</p>
                                    <p style='color: #666;'>Hamarosan megérkezik hozzád a csomag.</p>
                                    
                                    <div style='background: #f5f5f5; padding: 15px; border-radius: 8px; margin: 20px 0;'>
                                        <p style='margin: 0 0 5px 0;'><strong>Szállítási cím:</strong></p>
                                        <p style='margin: 0; color: #666;'>" . 
                                        ($order['foxpost_point_name'] ?: ($order['shipping_postcode'] . ' ' . $order['shipping_city'] . ', ' . $order['shipping_address'])) . 
                                        "</p>
                                    </div>
                                    
                                    <p style='color: #999; font-size: 14px;'>Kérdés esetén: <a href='mailto:info@yoursywear.hu'>info@yoursywear.hu</a></p>
                                </div>
                                <div style='background: #f5f5f5; padding: 20px; text-align: center; font-size: 12px; color: #999;'>
                                    © " . date('Y') . " YoursyWear
                                </div>
                            </div>
                        </body>
                        </html>";
                        $result = Mail::send($order['email'], $subject, $htmlBody, $order['username']);
                        if (!$result['success']) {
                            error_log("Shipped email error: " . $result['error']);
                        }
                        
                    } elseif ($newStatus === 'delivered') {
                        // KÉZBESÍTÉS + ELÉGEDETTSÉGI EMAIL
                        $subject = "YoursyWear - Csomagod megérkezett! #$orderId";
                        $feedbackUrl = "http://{$_SERVER['HTTP_HOST']}/webshop/ertekeles?order=$orderId";
                        $htmlBody = "
                        <!DOCTYPE html>
                        <html>
                        <head><meta charset='UTF-8'></head>
                        <body style='font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px;'>
                            <div style='max-width: 600px; margin: 0 auto; background: white; border-radius: 10px; overflow: hidden;'>
                                <div style='background: #000; color: white; padding: 30px; text-align: center;'>
                                    <h1 style='margin: 0;'>YoursyWear</h1>
                                </div>
                                <div style='padding: 30px;'>
                                    <h2 style='color: #333;'>Kedves {$order['username']}!</h2>
                                    
                                    <div style='background: #e8f5e9; border-left: 4px solid #4caf50; padding: 20px; margin: 20px 0;'>
                                        <p style='margin: 0; font-size: 18px;'>
                                            <strong>\xe2\x9c\x85 Csomagod kézbesítve!</strong>
                                        </p>
                                    </div>
                                    
                                    <p style='color: #666;'>A <strong>#{$orderId}</strong> számú rendelésed sikeresen kézbesítettük.</p>
                                    <p style='color: #666;'>Reméljük, elégedett vagy a termékekkel!</p>
                                    
                                    <div style='background: #fff3e0; border: 2px solid #ff9800; padding: 25px; border-radius: 8px; margin: 25px 0; text-align: center;'>
                                        <p style='margin: 0 0 15px 0; font-size: 16px;'><strong>\xf0\x9f\x8c\x9f Mennyire voltál elégedett?</strong></p>
                                        <p style='margin: 0 0 20px 0; color: #666;'>Véleményed sokat segít nekünk!</p>
                                        <a href='{$feedbackUrl}' style='display: inline-block; background: #000; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: bold;'>
                                            \xe2\xad\x90 Értékelés írása
                                        </a>
                                    </div>
                                    
                                    <p style='color: #666;'>Köszönjük, hogy nálunk vásároltál!</p>
                                    
                                    <p style='color: #999; font-size: 14px; margin-top: 20px;'>
                                        Probléma esetén: <a href='mailto:info@yoursywear.hu'>info@yoursywear.hu</a>
                                    </p>
                                </div>
                                <div style='background: #f5f5f5; padding: 20px; text-align: center; font-size: 12px; color: #999;'>
                                    © " . date('Y') . " YoursyWear
                                </div>
                            </div>
                        </body>
                        </html>";
                        $result = Mail::send($order['email'], $subject, $htmlBody, $order['username']);
                        if (!$result['success']) {
                            error_log("Delivered email error: " . $result['error']);
                        }
                    }
                }
            }
            header('Location: /webshop/yw-admin/orders?status_updated=1');
            exit;

        case 'update_return':
            $admin->requireAdmin();
            $returnId = (int)$_POST['return_id'];
            $status = $_POST['status'] ?? '';
            if ($returnId && in_array($status, ['approved', 'rejected', 'completed'])) {
                $stmt = $pdo->prepare("UPDATE returns SET status = ? WHERE return_id = ?");
                $stmt->execute([$status, $returnId]);
                
                // Email küldése jóváhagyás esetén
                if ($status === 'approved') {
                    require_once __DIR__ . '/app/helpers/Mail.php';
                    
                    // Visszaküldés és felhasználó adatai
                    $stmt = $pdo->prepare("
                        SELECT r.*, u.username, u.email, o.order_id
                        FROM returns r
                        JOIN users u ON r.user_id = u.user_id
                        JOIN orders o ON r.order_id = o.order_id
                        WHERE r.return_id = ?
                    ");
                    $stmt->execute([$returnId]);
                    $returnData = $stmt->fetch();
                    
                    if ($returnData) {
                        $subject = "YoursyWear - Visszaküldés jóváhagyva #" . $returnData['order_id'];
                        $htmlBody = "
                        <!DOCTYPE html>
                        <html>
                        <head><meta charset='UTF-8'></head>
                        <body style='font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px;'>
                            <div style='max-width: 600px; margin: 0 auto; background: white; border-radius: 10px; overflow: hidden;'>
                                <div style='background: #000; color: white; padding: 30px; text-align: center;'>
                                    <h1 style='margin: 0;'>YoursyWear</h1>
                                </div>
                                
                                <div style='padding: 30px;'>
                                    <h2 style='color: #333;'>Kedves {$returnData['username']}!</h2>
                                    <p style='color: #666;'>Jó hír! Visszaküldési kérelmed <strong>jóváhagyták</strong>.</p>
                                    
                                    <div style='background: #d4edda; border: 1px solid #28a745; padding: 15px; border-radius: 8px; margin: 20px 0;'>
                                        <p style='margin: 0; font-weight: bold; color: #155724;'>
                                            <span style='font-size: 20px;'>\xe2\x9c\x93</span> Rendelés #{$returnData['order_id']} visszaküldése elfogadva
                                        </p>
                                    </div>
                                    
                                    <h3 style='color: #333; margin-top: 30px;'>Hogyan küldd vissza a csomagot?</h3>
                                    
                                    <div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 15px 0;'>
                                        <p style='margin: 0 0 15px 0;'><strong>1.</strong> Csomagold be biztonságosan a terméket</p>
                                        <p style='margin: 0 0 15px 0;'><strong>2.</strong> Írd rá a csomagra a visszaküldési számot: <strong>RET-{$returnData['return_id']}</strong></p>
                                        <p style='margin: 0;'><strong>3.</strong> Add fel a csomagot postán vagy csomagpontnál</p>
                                    </div>
                                    
                                    <div style='background: #e7f3ff; border: 2px solid #0066cc; padding: 20px; border-radius: 8px; margin: 20px 0;'>
                                        <p style='margin: 0 0 10px 0; font-weight: bold; color: #0066cc;'>Szállítási cím:</p>
                                        <p style='margin: 0; font-size: 16px;'>
                                            <strong>YoursyWear Raktár</strong><br>
                                            1234 Budapest<br>
                                            Példa utca 123.<br>
                                            Magyarország
                                        </p>
                                    </div>
                                    
                                    <p style='color: #666;'>A csomag beérkezése után feldolgozzuk a visszaküldést és visszatérítjük a vételárat.</p>
                                    
                                    <p style='color: #999; font-size: 14px; margin-top: 20px;'>
                                        Kérdés esetén írj nekünk: <a href='mailto:info@yoursywear.hu'>info@yoursywear.hu</a>
                                    </p>
                                </div>
                                
                                <div style='background: #f5f5f5; padding: 20px; text-align: center; font-size: 12px; color: #999;'>
                                    © " . date('Y') . " YoursyWear. Minden jog fenntartva.
                                </div>
                            </div>
                        </body>
                        </html>";
                        
                        Mail::send($returnData['email'], $subject, $htmlBody, $returnData['username']);
                    }
                }
            }
            header('Location: /webshop/yw-admin/returns?success=1');
            exit;

        case 'admin_logout':
            session_destroy();
            header('Location: /webshop/yw-admin/login');
            exit;
            
        case 'save_coupon':
            $admin->requireAdmin();
            
            $couponId = (int)($_POST['coupon_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $couponPass = strtoupper(trim($_POST['coupon_pass'] ?? ''));
            $amount = (int)($_POST['amount'] ?? 0);
            $categoryType = $_POST['category_type'] ?? 'all';
            $productTypeId = null;
            $productSubtypeId = null;
            
            // Kategória típus alapján állítjuk be
            if ($categoryType === 'type' && !empty($_POST['product_type_id'])) {
                $productTypeId = (int)$_POST['product_type_id'];
            } elseif ($categoryType === 'subtype' && !empty($_POST['product_subtype_id'])) {
                $productSubtypeId = (int)$_POST['product_subtype_id'];
            }
            
            $startDate = $_POST['start_date'] ?? date('Y-m-d');
            $endDate = $_POST['end_date'] ?? date('Y-m-d', strtotime('+30 days'));
            $isActive = isset($_POST['is_active']) ? 1 : 0;
            
            // QR kód generálás
            $qrCodePath = null;
            if ($couponPass) {
                $qrUrl = "https://" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "/webshop/kuponok/$couponPass";
                
                // goqr.me API használata cURL-lel
                $qrApiUrl = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($qrUrl);
                
                $qrDir = __DIR__ . '/uploads/qrcodes';
                if (!is_dir($qrDir)) {
                    mkdir($qrDir, 0755, true);
                }
                
                $qrFileName = 'qr_' . $couponPass . '_' . time() . '.png';
                $qrFilePath = $qrDir . '/' . $qrFileName;
                
                // QR kép letöltése cURL-lel
                $ch = curl_init($qrApiUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 15);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                $qrImage = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                if ($qrImage !== false && $httpCode === 200 && strlen($qrImage) > 100) {
                    file_put_contents($qrFilePath, $qrImage);
                    $qrCodePath = 'uploads/qrcodes/' . $qrFileName;
                }
            }
            
            if ($couponId > 0) {
                // Update
                $sql = "UPDATE coupons SET name = ?, description = ?, coupon_pass = ?, amount = ?, 
                        product_type_id = ?, product_subtype_id = ?, start_date = ?, end_date = ?, is_active = ?";
                $params = [$name, $description, $couponPass, $amount, $productTypeId, $productSubtypeId, $startDate, $endDate, $isActive];
                
                if ($qrCodePath) {
                    $sql .= ", qr_code_path = ?";
                    $params[] = $qrCodePath;
                }
                $sql .= " WHERE id = ?";
                $params[] = $couponId;
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
            } else {
                // Insert
                $stmt = $pdo->prepare("
                    INSERT INTO coupons (name, description, coupon_pass, amount, product_type_id, product_subtype_id, start_date, end_date, is_active, qr_code_path)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$name, $description, $couponPass, $amount, $productTypeId, $productSubtypeId, $startDate, $endDate, $isActive, $qrCodePath]);
            }
            
            header('Location: /webshop/yw-admin/coupons?saved=1');
            exit;
            
        case 'delete_coupon':
            $admin->requireAdmin();
            $couponId = (int)$_POST['coupon_id'];
            if ($couponId > 0) {
                // QR kép törlése
                $stmt = $pdo->prepare("SELECT qr_code_path FROM coupons WHERE id = ?");
                $stmt->execute([$couponId]);
                $qrPath = $stmt->fetchColumn();
                if ($qrPath && file_exists(__DIR__ . '/' . $qrPath)) {
                    unlink(__DIR__ . '/' . $qrPath);
                }
                
                // Kupon törlése
                $stmt = $pdo->prepare("DELETE FROM user_coupons WHERE coupon_id = ?");
                $stmt->execute([$couponId]);
                $stmt = $pdo->prepare("DELETE FROM coupons WHERE id = ?");
                $stmt->execute([$couponId]);
            }
            header('Location: /webshop/yw-admin/coupons?deleted=1');
            exit;
            
        case 'toggle_coupon':
            $admin->requireAdmin();
            $couponId = (int)$_POST['coupon_id'];
            if ($couponId > 0) {
                $stmt = $pdo->prepare("UPDATE coupons SET is_active = NOT is_active WHERE id = ?");
                $stmt->execute([$couponId]);
            }
            header('Location: /webshop/yw-admin/coupons');
            exit;
            
        case 'upload_product_image':
            $admin->requireAdmin();
            header('Content-Type: application/json');
            
            $productId = (int)($_POST['product_id'] ?? 0);
            if ($productId <= 0 || empty($_FILES['image'])) {
                echo json_encode(['success' => false, 'error' => 'Hiányzó adatok']);
                exit;
            }
            
            $result = $admin->uploadProductImage($productId, $_FILES['image']);
            echo json_encode($result);
            exit;
            
        case 'delete_product_image':
            $admin->requireAdmin();
            header('Content-Type: application/json');
            
            $imageId = (int)($_POST['image_id'] ?? 0);
            if ($imageId <= 0) {
                echo json_encode(['success' => false, 'error' => 'Hiányzó kép ID']);
                exit;
            }
            
            $result = $admin->deleteProductImage($imageId);
            echo json_encode(['success' => $result]);
            exit;
            
        case 'reorder_product_images':
            $admin->requireAdmin();
            header('Content-Type: application/json');
            
            $imageIds = $_POST['image_ids'] ?? [];
            if (empty($imageIds)) {
                echo json_encode(['success' => false, 'error' => 'Hiányzó kép lista']);
                exit;
            }
            
            $result = $admin->reorderProductImages($imageIds);
            echo json_encode(['success' => $result]);
            exit;
    }
}

// Login oldal - nem kell admin ellenőrzés
if ($page === 'login') {
    require __DIR__ . '/app/views/admin/login.php';
    exit;
}

// Minden más oldalhoz admin kell
$admin->requireAdmin();

// Adatok betöltése az aktuális oldalhoz
switch ($page) {
    case 'dashboard':
        $stats = $admin->getDashboardStats();
        $recentOrders = $admin->getRecentOrders(5);
        break;
        
    case 'products':
        $search = $_GET['q'] ?? null;
        $products = $admin->getProducts($search);
        break;
        
    case 'product-edit':
        $productId = (int)($parts[1] ?? 0);
        $product = $productId ? $admin->getProduct($productId) : null;
        $productImages = $productId ? $admin->getProductImages($productId) : [];
        $vendors = $admin->getVendors();
        $colors = $admin->getColors();
        $genders = $admin->getGenders();
        $subtypes = $admin->getSubtypes();
        break;
        
    case 'orders':
        $orders = $admin->getOrders();
        break;
        
    case 'users':
        $users = $admin->getUsers();
        break;
        
    case 'stock':
        $productId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : null;
        $search = $_GET['q'] ?? null;
        $stockItems = $admin->getStock($productId, $search);
        if ($productId) {
            $currentProduct = $admin->getProduct($productId);
        }
        break;
        
    case 'returns':
        // A returns.php maga betölti az adatokat
        break;
        
    case 'ratings':
        // A ratings.php maga betölti az adatokat
        break;
        
    case 'coupons':
        // A coupons.php maga betölti az adatokat
        break;
        
    case 'coupon-edit':
        $couponId = (int)($parts[1] ?? 0);
        if ($couponId > 0) {
            $stmt = $pdo->prepare("SELECT * FROM coupons WHERE id = ?");
            $stmt->execute([$couponId]);
            $coupon = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        break;
}

// View renderelés
$viewFile = __DIR__ . '/app/views/admin/' . $page . '.php';
if (!file_exists($viewFile)) {
    $viewFile = __DIR__ . '/app/views/admin/dashboard.php';
}

require __DIR__ . '/app/views/admin/layout.php';
