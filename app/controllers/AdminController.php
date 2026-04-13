<?php

class AdminController
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    


    public function isAdmin(): bool
    {
        return !empty($_SESSION['user_id']) && !empty($_SESSION['role_id']) && $_SESSION['role_id'] == 2;
    }

    


    public function requireAdmin(): void
    {
        if (!$this->isAdmin()) {
            header('Location: /webshop/yw-admin/login');
            exit;
        }
    }

    


    public function getDashboardStats(): array
    {
        $stats = [];

        
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM product WHERE is_active = 1");
        $stats['products'] = $stmt->fetchColumn();

        
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM product WHERE is_active = 1 AND is_sale = 1");
        $stats['sale_products'] = $stmt->fetchColumn();

        
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM users");
        $stats['users'] = $stmt->fetchColumn();

        
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM orders");
        $stats['orders'] = $stmt->fetchColumn();

        
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()");
        $stats['orders_today'] = $stmt->fetchColumn();

        
        $stmt = $this->pdo->query("
            SELECT COALESCE(SUM(p.price * oi.quantity), 0) 
            FROM order_item oi
            JOIN stock s ON oi.stock_id = s.stock_id
            JOIN product p ON s.product_id = p.product_id
        ");
        $stats['revenue'] = $stmt->fetchColumn();

        return $stats;
    }

    


    public function getRecentOrders(int $limit = 10): array
    {
        $stmt = $this->pdo->prepare("
            SELECT o.*, u.username, u.email,
                   o.created_at AS order_date,
                   o.shipping_name, o.shipping_phone, 
                   COALESCE(NULLIF(o.shipping_postcode, ''), u.shipping_postcode) AS shipping_postcode,
                   COALESCE(NULLIF(o.shipping_city, ''), u.shipping_city, c.name) AS shipping_city,
                   o.shipping_address,
                   o.foxpost_point_id, o.foxpost_point_name, o.foxpost_point_address,
                   o.status,
                   (
                       SELECT COALESCE(SUM(p.price * oi.quantity), 0)
                       FROM order_item oi 
                       JOIN stock s ON oi.stock_id = s.stock_id
                       JOIN product p ON s.product_id = p.product_id
                       WHERE oi.order_id = o.order_id
                   ) AS total_price
            FROM orders o
            LEFT JOIN users u ON o.user_id = u.user_id
            LEFT JOIN city c ON u.shipping_city_id = c.city_id
            ORDER BY o.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    


    public function getProducts(?string $search = null, ?int $limit = 500): array
    {
        $sql = "
            SELECT 
                p.*,
                v.name AS vendor_name,
                c.name AS color_name,
                g.gender,
                pt.name AS type_name,
                ps.name AS subtype_name,
                (SELECT src FROM product_img WHERE product_id = p.product_id ORDER BY position LIMIT 1) AS image
            FROM product p
            LEFT JOIN vendor v ON p.vendor_id = v.vendor_id
            LEFT JOIN color c ON p.color_id = c.color_id
            LEFT JOIN gender g ON p.gender_id = g.gender_id
            LEFT JOIN product_subtype ps ON p.subtype_id = ps.product_subtype_id
            LEFT JOIN product_type pt ON ps.product_type_id = pt.product_type_id
        ";

        $params = [];
        if ($search) {
            $sql .= " WHERE p.name LIKE ? OR v.name LIKE ?";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $sql .= " ORDER BY p.product_id DESC LIMIT ?";
        $params[] = $limit;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    


    public function getProduct(int $id): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT p.*, v.name AS vendor_name, c.name AS color_name
            FROM product p
            LEFT JOIN vendor v ON p.vendor_id = v.vendor_id
            LEFT JOIN color c ON p.color_id = c.color_id
            WHERE p.product_id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    


    public function saveProduct(array $data): bool
    {
        // Színek kezelése
        $colorIds = $data['color_ids'] ?? [];
        if (!is_array($colorIds)) {
            $colorIds = [$colorIds];
        }
        // Első szín a régi color_id mezőbe (backward compatibility)
        $primaryColorId = !empty($colorIds) ? (int)$colorIds[0] : ($data['color_id'] ?? null);
        
        if (!empty($data['product_id'])) {
            // UPDATE
            $stmt = $this->pdo->prepare("
                UPDATE product SET
                    name = ?,
                    description = ?,
                    price = ?,
                    is_sale = ?,
                    vendor_id = ?,
                    color_id = ?,
                    gender_id = ?,
                    subtype_id = ?,
                    is_active = ?
                WHERE product_id = ?
            ");
            $result = $stmt->execute([
                $data['name'],
                $data['description'] ?? '',
                $data['price'],
                $data['is_sale'] ?? 0,
                $data['vendor_id'],
                $primaryColorId,
                $data['gender_id'],
                $data['subtype_id'],
                $data['is_active'] ?? 1,
                $data['product_id']
            ]);
            
            // Színek mentése a product_colors táblába
            $productId = $data['product_id'];
        } else {
            // INSERT
            $stmt = $this->pdo->prepare("
                INSERT INTO product (name, description, price, is_sale, vendor_id, color_id, gender_id, subtype_id, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $result = $stmt->execute([
                $data['name'],
                $data['description'] ?? '',
                $data['price'],
                $data['is_sale'] ?? 0,
                $data['vendor_id'],
                $primaryColorId,
                $data['gender_id'],
                $data['subtype_id'],
                $data['is_active'] ?? 1
            ]);
            $productId = $this->pdo->lastInsertId();
        }
        
        // Színek frissítése a product_colors táblában
        if (!empty($productId) && !empty($colorIds)) {
            // Régi színek törlése
            $stmt = $this->pdo->prepare("DELETE FROM product_colors WHERE product_id = ?");
            $stmt->execute([$productId]);
            
            // Új színek beszúrása
            foreach ($colorIds as $colorId) {
                if ($colorId) {
                    $stmt = $this->pdo->prepare("
                        INSERT INTO product_colors (product_id, color_id)
                        VALUES (?, ?)
                    ");
                    $stmt->execute([$productId, (int)$colorId]);
                }
            }
        }
        
        return $result;
    }

    


    public function deleteProduct(int $id): bool
    {
        $stmt = $this->pdo->prepare("UPDATE product SET is_active = 0 WHERE product_id = ?");
        return $stmt->execute([$id]);
    }

    


    public function toggleSale(int $id): bool
    {
        $stmt = $this->pdo->prepare("UPDATE product SET is_sale = NOT is_sale WHERE product_id = ?");
        return $stmt->execute([$id]);
    }

    


    public function getUsers(): array
    {
        $stmt = $this->pdo->query("
            SELECT u.*, r.name AS role_name
            FROM users u
            LEFT JOIN user_role r ON u.role_id = r.user_role_id
            ORDER BY u.user_id DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    


    public function setUserRole(int $userId, int $roleId): bool
    {
        $stmt = $this->pdo->prepare("UPDATE users SET role_id = ? WHERE user_id = ?");
        return $stmt->execute([$roleId, $userId]);
    }

    


    public function deleteUser(int $userId): bool
    {
        
        if ($userId == $_SESSION['user_id']) {
            return false;
        }
        
        
        $stmt = $this->pdo->prepare("DELETE FROM favorites WHERE user_id = ?");
        $stmt->execute([$userId]);
        
        
        $stmt = $this->pdo->prepare("DELETE FROM users WHERE user_id = ?");
        return $stmt->execute([$userId]);
    }
    
    


    public function activateUser(int $userId): bool
    {
        $stmt = $this->pdo->prepare("UPDATE users SET is_active = 1, activation_token = NULL WHERE user_id = ?");
        return $stmt->execute([$userId]);
    }

    


    public function getOrders(): array
    {
        $stmt = $this->pdo->query("
            SELECT o.*, u.username, u.email,
                   o.created_at AS order_date,
                   (
                       SELECT SUM(p.price * oi.quantity) 
                       FROM order_item oi 
                       JOIN stock s ON oi.stock_id = s.stock_id
                       JOIN product p ON s.product_id = p.product_id
                       WHERE oi.order_id = o.order_id
                   ) AS total_price
            FROM orders o
            LEFT JOIN users u ON o.user_id = u.user_id
            ORDER BY o.created_at DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    


    public function getOrderDetails(int $orderId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT oi.*, p.name AS product_name, p.price, sz.size_value
            FROM order_item oi
            JOIN stock s ON oi.stock_id = s.stock_id
            JOIN product p ON s.product_id = p.product_id
            LEFT JOIN size sz ON s.size_id = sz.size_id
            WHERE oi.order_id = ?
        ");
        $stmt->execute([$orderId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    


    public function getVendors(): array
    {
        return $this->pdo->query("SELECT * FROM vendor ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getColors(): array
    {
        return $this->pdo->query("SELECT * FROM color ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getGenders(): array
    {
        return $this->pdo->query("SELECT * FROM gender")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getSubtypes(): array
    {
        return $this->pdo->query("
            SELECT ps.*, pt.name AS type_name 
            FROM product_subtype ps 
            JOIN product_type pt ON ps.product_type_id = pt.product_type_id
            ORDER BY pt.name, ps.name
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    


    public function getStock(?int $productId = null, ?string $search = null): array
    {
        $sql = "
            SELECT s.*, p.name AS product_name, p.product_id, sz.size_value,
                   v.name AS vendor_name,
                   (SELECT src FROM product_img WHERE product_id = p.product_id ORDER BY position LIMIT 1) AS image
            FROM stock s
            JOIN product p ON s.product_id = p.product_id
            JOIN size sz ON s.size_id = sz.size_id
            LEFT JOIN vendor v ON p.vendor_id = v.vendor_id
            WHERE 1=1
        ";
        
        $params = [];
        
        if ($productId) {
            $sql .= " AND s.product_id = ?";
            $params[] = $productId;
        }
        
        if ($search) {
            $sql .= " AND (p.name LIKE ? OR v.name LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        $sql .= " ORDER BY p.name, sz.size_id";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    


    public function updateStock(int $stockId, int $quantity): bool
    {
        $stmt = $this->pdo->prepare("UPDATE stock SET quantity = ? WHERE stock_id = ?");
        return $stmt->execute([$quantity, $stockId]);
    }

    


    public function bulkUpdateStock(array $stockData): bool
    {
        $stmt = $this->pdo->prepare("UPDATE stock SET quantity = ? WHERE stock_id = ?");
        foreach ($stockData as $stockId => $quantity) {
            $stmt->execute([(int)$quantity, (int)$stockId]);
        }
        return true;
    }

    


    public function getProductImages(int $productId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM product_img 
            WHERE product_id = ? 
            ORDER BY position ASC
        ");
        $stmt->execute([$productId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    


    public function uploadProductImage(int $productId, array $file): array
    {
        $uploadDir = __DIR__ . '/../../storage/uploads/products';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        if (!in_array($file['type'], $allowedTypes)) {
            return ['success' => false, 'error' => 'Nem támogatott fájltípus'];
        }

        if ($file['size'] > 5 * 1024 * 1024) {
            return ['success' => false, 'error' => 'Max 5MB méret engedélyezett'];
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'product_' . $productId . '_' . uniqid() . '.' . $ext;
        $filepath = $uploadDir . '/' . $filename;
        $relativePath = 'storage/uploads/products/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            return ['success' => false, 'error' => 'Feltöltés sikertelen'];
        }

        
        $stmt = $this->pdo->prepare("SELECT COALESCE(MAX(position), 0) + 1 FROM product_img WHERE product_id = ?");
        $stmt->execute([$productId]);
        $position = $stmt->fetchColumn();

        
        $stmt = $this->pdo->prepare("INSERT INTO product_img (product_id, src, position) VALUES (?, ?, ?)");
        $stmt->execute([$productId, $relativePath, $position]);
        $imageId = $this->pdo->lastInsertId();

        return [
            'success' => true,
            'image' => [
                'product_img_id' => $imageId,
                'src' => $relativePath,
                'position' => $position
            ]
        ];
    }

    


    public function deleteProductImage(int $imageId): bool
    {
        $stmt = $this->pdo->prepare("SELECT * FROM product_img WHERE product_img_id = ?");
        $stmt->execute([$imageId]);
        $image = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$image) return false;

        $webshopRoot = dirname(__DIR__, 2);
        $filepath = $webshopRoot . '/' . $image['src'];
        
        if (file_exists($filepath) && is_writable($filepath)) {
            unlink($filepath);
        }

        $stmt = $this->pdo->prepare("DELETE FROM product_img WHERE product_img_id = ?");
        $stmt->execute([$imageId]);

        // Pozíciók újraszámozása
        $stmt = $this->pdo->prepare("
            SELECT product_img_id FROM product_img 
            WHERE product_id = ? ORDER BY position
        ");
        $stmt->execute([$image['product_id']]);
        $images = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $updateStmt = $this->pdo->prepare("UPDATE product_img SET position = ? WHERE product_img_id = ?");
        foreach ($images as $pos => $imgId) {
            $updateStmt->execute([$pos + 1, $imgId]);
        }

        return true;
    }

    


    public function reorderProductImages(array $imageIds): bool
    {
        $stmt = $this->pdo->prepare("UPDATE product_img SET position = ? WHERE product_img_id = ?");
        foreach ($imageIds as $position => $imageId) {
            $stmt->execute([$position + 1, (int)$imageId]);
        }
        return true;
    }
}
