<?php

class AdminController
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Ellenőrzi, hogy a felhasználó admin-e
     */
    public function isAdmin(): bool
    {
        return !empty($_SESSION['user_id']) && !empty($_SESSION['role_id']) && $_SESSION['role_id'] == 2;
    }

    /**
     * Admin belépés ellenőrzés - átirányít ha nem admin
     */
    public function requireAdmin(): void
    {
        if (!$this->isAdmin()) {
            header('Location: /webshop/yw-admin/login');
            exit;
        }
    }

    /**
     * Dashboard statisztikák
     */
    public function getDashboardStats(): array
    {
        $stats = [];

        // Termékek száma
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM product WHERE is_active = 1");
        $stats['products'] = $stmt->fetchColumn();

        // Akciós termékek
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM product WHERE is_active = 1 AND is_sale = 1");
        $stats['sale_products'] = $stmt->fetchColumn();

        // Felhasználók száma
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM users");
        $stats['users'] = $stmt->fetchColumn();

        // Rendelések száma
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM orders");
        $stats['orders'] = $stmt->fetchColumn();

        // Mai rendelések
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()");
        $stats['orders_today'] = $stmt->fetchColumn();

        // Összes bevétel (order_item + product árból)
        $stmt = $this->pdo->query("
            SELECT COALESCE(SUM(p.price * oi.quantity), 0) 
            FROM order_item oi
            JOIN stock s ON oi.stock_id = s.stock_id
            JOIN product p ON s.product_id = p.product_id
        ");
        $stats['revenue'] = $stmt->fetchColumn();

        return $stats;
    }

    /**
     * Legutóbbi rendelések
     */
    public function getRecentOrders(int $limit = 10): array
    {
        $stmt = $this->pdo->prepare("
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
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Összes termék lekérése (admin lista)
     */
    public function getProducts(?string $search = null, ?int $limit = 50): array
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

    /**
     * Egyetlen termék lekérése
     */
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

    /**
     * Termék mentése (új vagy módosítás)
     */
    public function saveProduct(array $data): bool
    {
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
            return $stmt->execute([
                $data['name'],
                $data['description'] ?? '',
                $data['price'],
                $data['is_sale'] ?? 0,
                $data['vendor_id'],
                $data['color_id'],
                $data['gender_id'],
                $data['subtype_id'],
                $data['is_active'] ?? 1,
                $data['product_id']
            ]);
        } else {
            // INSERT
            $stmt = $this->pdo->prepare("
                INSERT INTO product (name, description, price, is_sale, vendor_id, color_id, gender_id, subtype_id, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            return $stmt->execute([
                $data['name'],
                $data['description'] ?? '',
                $data['price'],
                $data['is_sale'] ?? 0,
                $data['vendor_id'],
                $data['color_id'],
                $data['gender_id'],
                $data['subtype_id'],
                $data['is_active'] ?? 1
            ]);
        }
    }

    /**
     * Termék törlése (soft delete)
     */
    public function deleteProduct(int $id): bool
    {
        $stmt = $this->pdo->prepare("UPDATE product SET is_active = 0 WHERE product_id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Akció toggle
     */
    public function toggleSale(int $id): bool
    {
        $stmt = $this->pdo->prepare("UPDATE product SET is_sale = NOT is_sale WHERE product_id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Felhasználók lekérése
     */
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

    /**
     * Felhasználó role módosítása
     */
    public function setUserRole(int $userId, int $roleId): bool
    {
        $stmt = $this->pdo->prepare("UPDATE users SET role_id = ? WHERE user_id = ?");
        return $stmt->execute([$roleId, $userId]);
    }

    /**
     * Összes rendelés
     */
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

    /**
     * Rendelés részletei
     */
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

    /**
     * Dropdown adatok lekérése
     */
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

    /**
     * Készletek lekérése (termékhez)
     */
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

    /**
     * Készlet frissítése
     */
    public function updateStock(int $stockId, int $quantity): bool
    {
        $stmt = $this->pdo->prepare("UPDATE stock SET quantity = ? WHERE stock_id = ?");
        return $stmt->execute([$quantity, $stockId]);
    }

    /**
     * Tömeges készlet frissítés
     */
    public function bulkUpdateStock(array $stockData): bool
    {
        $stmt = $this->pdo->prepare("UPDATE stock SET quantity = ? WHERE stock_id = ?");
        foreach ($stockData as $stockId => $quantity) {
            $stmt->execute([(int)$quantity, (int)$stockId]);
        }
        return true;
    }
}
