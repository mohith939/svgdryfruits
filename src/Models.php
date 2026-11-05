<?php
/**
 * SVG Dry Fruits - Data Models & Database Operations
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

class Models {
    // --- Categories ---
    public static function getCategories(bool $activeOnly = true): array {
        $pdo = Database::getConnection();
        $sql = "SELECT * FROM categories";
        if ($activeOnly) {
            $sql .= " WHERE active = 1";
        }
        $sql .= " ORDER BY sort_order ASC, name ASC";
        return $pdo->query($sql)->fetchAll();
    }

    public static function getCategoryBySlug(string $slug): ?array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM categories WHERE slug = ? LIMIT 1");
        $stmt->execute([$slug]);
        return $stmt->fetch() ?: null;
    }

    // --- Products & Variants ---
    public static function getProducts(?string $categorySlug = null, bool $activeOnly = true, ?string $search = null): array {
        $pdo = Database::getConnection();
        $where = [];
        $params = [];

        if ($activeOnly) {
            $where[] = "p.active = 1";
        }

        if ($categorySlug) {
            $where[] = "c.slug = ?";
            $params[] = $categorySlug;
        }

        if ($search) {
            $where[] = "(p.name LIKE ? OR p.description LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

        $sql = "
            SELECT 
                p.*,
                c.name AS category_name,
                c.slug AS category_slug,
                v.id AS default_variant_id,
                v.sku AS default_sku,
                v.label AS default_label,
                v.price_paise,
                v.compare_price_paise,
                v.stock
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN variants v ON v.id = (
                SELECT id FROM variants 
                WHERE product_id = p.id AND active = 1 
                ORDER BY price_paise ASC LIMIT 1
            )
            {$whereClause}
            ORDER BY p.created_at DESC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function getProductsWithVariants(?string $categorySlug = null, bool $activeOnly = true, ?string $search = null): array {
        $products = self::getProducts($categorySlug, $activeOnly, $search);
        if (empty($products)) {
            return [];
        }
        $pdo = Database::getConnection();
        $prodIds = array_column($products, 'id');
        $placeholders = implode(',', array_fill(0, count($prodIds), '?'));
        $varStmt = $pdo->prepare("SELECT * FROM variants WHERE product_id IN ($placeholders) AND active = 1 ORDER BY price_paise ASC");
        $varStmt->execute($prodIds);
        $allVariants = $varStmt->fetchAll();

        $variantMap = [];
        foreach ($allVariants as $v) {
            $variantMap[$v['product_id']][] = $v;
        }

        foreach ($products as &$p) {
            $p['variants'] = $variantMap[$p['id']] ?? [];
        }
        unset($p);
        return $products;
    }



    public static function getProductBySlug(string $slug): ?array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT p.*, c.name AS category_name, c.slug AS category_slug 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE p.slug = ? LIMIT 1
        ");
        $stmt->execute([$slug]);
        $product = $stmt->fetch();
        if (!$product) {
            return null;
        }

        // Fetch variants for this product
        $varStmt = $pdo->prepare("
            SELECT * FROM variants 
            WHERE product_id = ? AND active = 1 
            ORDER BY price_paise ASC
        ");
        $varStmt->execute([$product['id']]);
        $product['variants'] = $varStmt->fetchAll();

        return $product;
    }

    public static function getVariantById(string|int $variantId): ?array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT v.*, p.name AS product_name, p.slug AS product_slug, p.image_url AS product_image
            FROM variants v
            JOIN products p ON v.product_id = p.id
            WHERE v.id = ? LIMIT 1
        ");
        $stmt->execute([(string)$variantId]);
        return $stmt->fetch() ?: null;
    }

    // --- Coupons ---
    public static function getCouponByCode(string $code): ?array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM coupons WHERE UPPER(code) = UPPER(?) AND active = 1 LIMIT 1");
        $stmt->execute([trim($code)]);
        return $stmt->fetch() ?: null;
    }

    public static function incrementCouponUsage(string $code): void {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("UPDATE coupons SET times_used = times_used + 1 WHERE UPPER(code) = UPPER(?)");
        $stmt->execute([trim($code)]);
    }

    // --- Schema Self-Healing & Upgrade ---
    private static bool $schemaChecked = false;
    public static function ensureSchema(): void {
        if (self::$schemaChecked) return;
        self::$schemaChecked = true;
        $pdo = Database::getConnection();
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        // 1. Ensure shipping_options table exists
        if ($driver === 'sqlite') {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS shipping_options (
                    id TEXT PRIMARY KEY,
                    name TEXT NOT NULL,
                    description TEXT,
                    estimated_days TEXT,
                    rate_paise INTEGER NOT NULL DEFAULT 4900,
                    free_above_paise INTEGER,
                    min_subtotal_paise INTEGER NOT NULL DEFAULT 0,
                    active INTEGER NOT NULL DEFAULT 1,
                    sort_order INTEGER NOT NULL DEFAULT 0,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            ");
        } else {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS `shipping_options` (
                    `id` VARCHAR(64) NOT NULL,
                    `name` VARCHAR(128) NOT NULL,
                    `description` TEXT NULL,
                    `estimated_days` VARCHAR(64) NULL,
                    `rate_paise` INT NOT NULL DEFAULT 4900,
                    `free_above_paise` INT NULL DEFAULT 99900,
                    `min_subtotal_paise` INT NOT NULL DEFAULT 0,
                    `active` TINYINT(1) NOT NULL DEFAULT 1,
                    `sort_order` INT NOT NULL DEFAULT 0,
                    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        // Seed default options if empty
        $count = (int)$pdo->query("SELECT COUNT(*) FROM shipping_options")->fetchColumn();
        if ($count === 0) {
            $pdo->exec("
                INSERT INTO shipping_options (id, name, description, estimated_days, rate_paise, free_above_paise, min_subtotal_paise, active, sort_order)
                VALUES 
                ('ship_standard', 'Standard Delivery', 'Carefully packed & shipped via Bluedart / Delhivery / DTDC surface express.', '3 – 5 Business Days', 4900, 99900, 0, 1, 1),
                ('ship_express', 'Priority Air Express', 'Faster delivery via dedicated air cargo for urgent gifting & festivities.', '1 – 2 Business Days', 12900, 199900, 0, 1, 2)
            ");
        }

        // 2. Ensure contact columns in shipping_settings
        $contactCols = [
            'contact_phone' => "VARCHAR(32) NOT NULL DEFAULT '919059123456'",
            'contact_email' => "VARCHAR(128) NOT NULL DEFAULT 'svgdryfruits@gmail.com'",
            'whatsapp_number' => "VARCHAR(32) NOT NULL DEFAULT '919059123456'",
            'store_address' => "TEXT NULL",
        ];
        foreach ($contactCols as $col => $def) {
            try {
                $pdo->exec("ALTER TABLE shipping_settings ADD COLUMN `{$col}` {$def}");
            } catch (Throwable $e) {
                // Column already exists or driver syntax difference
            }
        }

        // 3. Ensure shipping_option and stock_restored columns in orders
        $orderCols = [
            'shipping_option_id' => "VARCHAR(64) NULL",
            'shipping_option_name' => "VARCHAR(128) NULL",
            'stock_restored' => ($driver === 'sqlite' ? "INTEGER DEFAULT 0" : "TINYINT(1) NOT NULL DEFAULT 0"),
        ];
        foreach ($orderCols as $col => $def) {
            try {
                $pdo->exec("ALTER TABLE orders ADD COLUMN {$col} {$def}");
            } catch (Throwable $e) {
                // Column already exists
            }
        }

        // 4. Ensure stock_restored in order_items
        try {
            $colDef = ($driver === 'sqlite') ? "INTEGER DEFAULT 0" : "TINYINT(1) NOT NULL DEFAULT 0";
            $pdo->exec("ALTER TABLE order_items ADD COLUMN stock_restored {$colDef}");
        } catch (Throwable $e) {
            // Column already exists
        }

        // 5. Ensure refunds table exists
        try {
            if ($driver === 'sqlite') {
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS refunds (
                        id TEXT PRIMARY KEY,
                        order_id TEXT NOT NULL,
                        amount_paise INTEGER NOT NULL,
                        note TEXT,
                        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
                    )
                ");
            } else {
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS `refunds` (
                        `id` VARCHAR(64) NOT NULL,
                        `order_id` VARCHAR(64) NOT NULL,
                        `amount_paise` INT NOT NULL,
                        `note` TEXT NULL,
                        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        PRIMARY KEY (`id`),
                        KEY `idx_refunds_order_id` (`order_id`),
                        CONSTRAINT `fk_refunds_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ");
            }
        } catch (Throwable $e) {}
    }

    // --- Store & Contact Settings ---
    public static function getStoreSettings(): array {
        self::ensureSchema();
        $pdo = Database::getConnection();
        $row = $pdo->query("SELECT * FROM shipping_settings WHERE id = 1 LIMIT 1")->fetch();
        if (!$row) {
            return [
                'flat_charge_paise' => 4900,
                'free_threshold_paise' => 99900,
                'active' => 1,
                'contact_phone' => WHATSAPP_NUMBER,
                'contact_email' => CONTACT_EMAIL,
                'whatsapp_number' => WHATSAPP_NUMBER,
                'store_address' => 'SVG Dry Fruits, Specialist in Premium Dry Fruits, Hyderabad, Telangana, India',
            ];
        }

        return [
            'flat_charge_paise' => (int)($row['flat_charge_paise'] ?? 4900),
            'free_threshold_paise' => isset($row['free_threshold_paise']) ? (int)$row['free_threshold_paise'] : 99900,
            'active' => (int)($row['active'] ?? 1),
            'contact_phone' => !empty($row['contact_phone']) ? $row['contact_phone'] : WHATSAPP_NUMBER,
            'contact_email' => !empty($row['contact_email']) ? $row['contact_email'] : CONTACT_EMAIL,
            'whatsapp_number' => !empty($row['whatsapp_number']) ? $row['whatsapp_number'] : WHATSAPP_NUMBER,
            'store_address' => !empty($row['store_address']) ? $row['store_address'] : 'SVG Dry Fruits, Specialist in Premium Dry Fruits, Hyderabad, Telangana, India',
        ];
    }

    public static function saveStoreSettings(array $data): void {
        self::ensureSchema();
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            UPDATE shipping_settings 
            SET contact_phone = ?, contact_email = ?, whatsapp_number = ?, store_address = ?
            WHERE id = 1
        ");
        $stmt->execute([
            trim($data['contact_phone'] ?? WHATSAPP_NUMBER),
            trim($data['contact_email'] ?? CONTACT_EMAIL),
            trim($data['whatsapp_number'] ?? WHATSAPP_NUMBER),
            trim($data['store_address'] ?? ''),
        ]);
    }

    // --- Shipping Settings & Options ---
    public static function getShippingSettings(): array {
        return self::getStoreSettings();
    }

    public static function getShippingOptions(bool $activeOnly = false): array {
        self::ensureSchema();
        $pdo = Database::getConnection();
        $sql = "SELECT * FROM shipping_options";
        if ($activeOnly) {
            $sql .= " WHERE active = 1";
        }
        $sql .= " ORDER BY sort_order ASC, rate_paise ASC";
        return $pdo->query($sql)->fetchAll();
    }

    public static function getShippingOptionById(string $id): ?array {
        self::ensureSchema();
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM shipping_options WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function saveShippingOption(array $data): void {
        self::ensureSchema();
        $pdo = Database::getConnection();
        $id = $data['id'] ?? ('ship_' . bin2hex(random_bytes(6)));
        $name = trim($data['name']);
        $description = trim($data['description'] ?? '');
        $estimatedDays = trim($data['estimated_days'] ?? '');
        $ratePaise = (int)($data['rate_paise'] ?? 4900);
        $freeAbovePaise = isset($data['free_above_paise']) && $data['free_above_paise'] !== '' ? (int)$data['free_above_paise'] : null;
        $minSubtotalPaise = (int)($data['min_subtotal_paise'] ?? 0);
        $active = !empty($data['active']) ? 1 : 0;
        $sortOrder = (int)($data['sort_order'] ?? 0);

        $exists = self::getShippingOptionById($id);
        if ($exists) {
            $stmt = $pdo->prepare("
                UPDATE shipping_options 
                SET name = ?, description = ?, estimated_days = ?, rate_paise = ?, free_above_paise = ?, min_subtotal_paise = ?, active = ?, sort_order = ?
                WHERE id = ?
            ");
            $stmt->execute([$name, $description, $estimatedDays, $ratePaise, $freeAbovePaise, $minSubtotalPaise, $active, $sortOrder, $id]);
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO shipping_options (id, name, description, estimated_days, rate_paise, free_above_paise, min_subtotal_paise, active, sort_order)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$id, $name, $description, $estimatedDays, $ratePaise, $freeAbovePaise, $minSubtotalPaise, $active, $sortOrder]);
        }
    }

    public static function deleteShippingOption(string $id): void {
        self::ensureSchema();
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("DELETE FROM shipping_options WHERE id = ?");
        $stmt->execute([$id]);
    }

    public static function toggleShippingOption(string $id): void {
        self::ensureSchema();
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("UPDATE shipping_options SET active = 1 - active WHERE id = ?");
        $stmt->execute([$id]);
    }

    // --- Orders ---
    public static function createOrder(array $data, array $items): string {
        self::ensureSchema();
        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            $data['id'] = $data['id'] ?? ('ord_' . bin2hex(random_bytes(10)));
            $data['order_number'] = $data['order_number'] ?? ('SVG-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3))));
            $data['tracking_token'] = $data['tracking_token'] ?? bin2hex(random_bytes(16));
            $stmt = $pdo->prepare("
                INSERT INTO orders (
                    id, order_number, tracking_token, payment_status, fulfillment_status,
                    customer_name, customer_email, customer_phone, shipping_address,
                    subtotal_paise, discount_paise, shipping_paise, total_paise,
                    coupon_code, shipping_option_id, shipping_option_name,
                    cashfree_order_id, payment_session_id
                ) VALUES (
                    ?, ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?,
                    ?, ?
                )
            ");

            $stmt->execute([
                $data['id'],
                $data['order_number'],
                $data['tracking_token'],
                $data['payment_status'] ?? 'pending',
                $data['fulfillment_status'] ?? 'awaiting_payment',
                $data['customer_name'],
                $data['customer_email'] ?? null,
                $data['customer_phone'],
                json_encode($data['shipping_address']),
                $data['subtotal_paise'],
                $data['discount_paise'] ?? 0,
                $data['shipping_paise'] ?? 0,
                $data['total_paise'],
                $data['coupon_code'] ?? null,
                $data['shipping_option_id'] ?? null,
                $data['shipping_option_name'] ?? null,
                $data['cashfree_order_id'] ?? null,
                $data['payment_session_id'] ?? null,
            ]);

            // Insert order items
            $itemStmt = $pdo->prepare("
                INSERT INTO order_items (id, order_id, variant_id, product_name, variant_label, unit_price_paise, quantity)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            // Atomic conditional stock reduction to prevent concurrency race conditions & overselling
            $stockStmt = $pdo->prepare("
                UPDATE variants 
                SET stock = stock - ? 
                WHERE id = ? AND stock >= ?
            ");

            foreach ($items as $item) {
                $unitPrice = (int)($item['unit_price_paise'] ?? $item['price_paise'] ?? 0);
                $qty = (int)$item['quantity'];

                // Enforce atomic stock decrement
                if (!empty($item['variant_id'])) {
                    $stockStmt->execute([$qty, (string)$item['variant_id'], $qty]);
                    if ($stockStmt->rowCount() === 0) {
                        throw new RuntimeException("Insufficient stock for item: {$item['product_name']} ({$item['variant_label']}). Please review your bag.");
                    }
                }

                $itemStmt->execute([
                    'item_' . bin2hex(random_bytes(10)),
                    $data['id'],
                    $item['variant_id'] ?? null,
                    $item['product_name'],
                    $item['variant_label'],
                    $unitPrice,
                    $qty,
                ]);
            }

            if (!empty($data['coupon_code'])) {
                self::incrementCouponUsage($data['coupon_code']);
            }

            $pdo->commit();
            return $data['id'];
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function getOrderById(string $id): ?array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $order = $stmt->fetch();
        if (!$order) return null;

        $order['shipping_address'] = json_decode($order['shipping_address'], true);

        $itemStmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
        $itemStmt->execute([$order['id']]);
        $order['items'] = $itemStmt->fetchAll();

        return $order;
    }

    public static function getOrderByNumber(string $orderNumber): ?array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_number = ? LIMIT 1");
        $stmt->execute([$orderNumber]);
        $order = $stmt->fetch();
        if (!$order) return null;

        $order['shipping_address'] = json_decode($order['shipping_address'], true);

        $itemStmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
        $itemStmt->execute([$order['id']]);
        $order['items'] = $itemStmt->fetchAll();

        return $order;
    }

    public static function getOrderByToken(string $token): ?array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE tracking_token = ? LIMIT 1");
        $stmt->execute([$token]);
        $order = $stmt->fetch();
        if (!$order) return null;

        $order['shipping_address'] = json_decode($order['shipping_address'], true);

        $itemStmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
        $itemStmt->execute([$order['id']]);
        $order['items'] = $itemStmt->fetchAll();

        return $order;
    }

    public static function updateOrderPayment(string $orderNumber, string $status, ?string $cfOrderId = null): void {
        $pdo = Database::getConnection();
        $fulfillment = ($status === 'paid') ? 'processing' : 'awaiting_payment';
        if ($status === 'failed') {
            $fulfillment = 'cancelled';
        }
        $stmt = $pdo->prepare("
            UPDATE orders 
            SET payment_status = ?, fulfillment_status = ? 
            WHERE order_number = ? OR cashfree_order_id = ?
        ");
        $stmt->execute([$status, $fulfillment, $orderNumber, $cfOrderId]);

        // If payment explicitly failed, restore reserved inventory
        if ($status === 'failed') {
            self::restoreOrderStock($orderNumber);
        }
    }

    public static function updateOrderCourier(string $orderId, string $courier, string $trackingNumber, string $fulfillmentStatus): void {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            UPDATE orders 
            SET courier = ?, tracking_number = ?, fulfillment_status = ? 
            WHERE id = ?
        ");
        $stmt->execute([$courier, $trackingNumber, $fulfillmentStatus, $orderId]);

        if ($fulfillmentStatus === 'cancelled') {
            self::restoreOrderStock($orderId);
        }
    }

    /**
     * Restore variant inventory for cancelled or failed orders (idempotent)
     */
    public static function restoreOrderStock(string $orderIdOrNumber): bool {
        self::ensureSchema();
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT id, payment_status, fulfillment_status, stock_restored FROM orders WHERE id = ? OR order_number = ? LIMIT 1");
        $stmt->execute([$orderIdOrNumber, $orderIdOrNumber]);
        $order = $stmt->fetch();
        if (!$order) {
            return false;
        }

        // Check if order already has stock restored
        if (!empty($order['stock_restored'])) {
            return false; // Already restored, prevent double-restoration
        }

        $itemsStmt = $pdo->prepare("SELECT id, variant_id, quantity FROM order_items WHERE order_id = ?");
        $itemsStmt->execute([$order['id']]);
        $items = $itemsStmt->fetchAll();

        $upStock = $pdo->prepare("UPDATE variants SET stock = stock + ? WHERE id = ?");
        foreach ($items as $item) {
            if (!empty($item['variant_id']) && $item['quantity'] > 0) {
                $upStock->execute([(int)$item['quantity'], (string)$item['variant_id']]);
            }
        }

        $pdo->prepare("UPDATE orders SET stock_restored = 1 WHERE id = ?")->execute([$order['id']]);
        try {
            $pdo->prepare("UPDATE order_items SET stock_restored = 1 WHERE order_id = ?")->execute([$order['id']]);
        } catch (Throwable $e) {}

        return true;
    }

    /**
     * In-place update of product variants without destroying existing variant IDs
     */
    public static function saveProductVariants(string $productId, array $variantsList): void {
        $pdo = Database::getConnection();

        // 1. Fetch current variant IDs for this product
        $currStmt = $pdo->prepare("SELECT id FROM variants WHERE product_id = ?");
        $currStmt->execute([$productId]);
        $existingIds = $currStmt->fetchAll(PDO::FETCH_COLUMN);

        $submittedIds = [];
        $upStmt = $pdo->prepare("
            UPDATE variants 
            SET label = ?, sku = ?, price_paise = ?, compare_price_paise = ?, stock = ?, low_stock_threshold = ?, active = 1
            WHERE id = ? AND product_id = ?
        ");
        $insStmt = $pdo->prepare("
            INSERT INTO variants (id, product_id, sku, label, price_paise, compare_price_paise, stock, low_stock_threshold, active)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)
        ");

        foreach ($variantsList as $v) {
            $varId = !empty($v['id']) ? trim((string)$v['id']) : null;
            $label = trim((string)($v['label'] ?? ''));
            $sku = trim((string)($v['sku'] ?? ''));
            $pricePaise = (int)($v['price_paise'] ?? 0);
            $comparePaise = !empty($v['compare_price_paise']) ? (int)$v['compare_price_paise'] : null;
            $stock = max(0, (int)($v['stock'] ?? 0));
            $threshold = max(0, (int)($v['low_stock_threshold'] ?? 5));

            if (!$label || $pricePaise <= 0) {
                continue;
            }

            if ($varId && in_array($varId, $existingIds, true)) {
                $upStmt->execute([$label, $sku, $pricePaise, $comparePaise, $stock, $threshold, $varId, $productId]);
                $submittedIds[] = $varId;
            } else {
                $newId = $varId ?: ('var_' . bin2hex(random_bytes(8)));
                $insStmt->execute([$newId, $productId, $sku, $label, $pricePaise, $comparePaise, $stock, $threshold]);
                $submittedIds[] = $newId;
            }
        }

        // 2. Remove or deactivate variants removed by the admin
        $toDelete = array_diff($existingIds, $submittedIds);
        if (!empty($toDelete)) {
            $delStmt = $pdo->prepare("DELETE FROM variants WHERE id = ? AND product_id = ?");
            foreach ($toDelete as $delId) {
                try {
                    $delStmt->execute([$delId, $productId]);
                } catch (Throwable $e) {
                    // Soft-deactivate if foreign key constraint exists
                    $pdo->prepare("UPDATE variants SET active = 0 WHERE id = ?")->execute([$delId]);
                }
            }
        }
    }

    // --- Admin Dashboard Metrics ---
    public static function getAdminMetrics(): array {
        $pdo = Database::getConnection();

        $revStmt = $pdo->query("SELECT COALESCE(SUM(total_paise), 0) AS total_revenue FROM orders WHERE payment_status = 'paid'");
        $totalRev = (int)$revStmt->fetchColumn();

        $countStmt = $pdo->query("SELECT COUNT(*) FROM orders");
        $totalOrders = (int)$countStmt->fetchColumn();

        $pendingStmt = $pdo->query("SELECT COUNT(*) FROM orders WHERE fulfillment_status IN ('processing', 'awaiting_payment')");
        $pendingOrders = (int)$pendingStmt->fetchColumn();

        $lowStockStmt = $pdo->query("SELECT COUNT(*) FROM variants WHERE stock <= low_stock_threshold AND active = 1");
        $lowStockCount = (int)$lowStockStmt->fetchColumn();

        return [
            'total_revenue_paise' => $totalRev,
            'total_orders' => $totalOrders,
            'pending_orders' => $pendingOrders,
            'low_stock_count' => $lowStockCount,
        ];
    }
}

