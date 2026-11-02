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
        $varStmt = $pdo->prepare("SELECT * FROM variants WHERE product_id = ? AND active = 1 ORDER BY price_paise ASC");
        foreach ($products as &$p) {
            $varStmt->execute([$p['id']]);
            $p['variants'] = $varStmt->fetchAll();
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

    // --- Shipping Settings ---
    public static function getShippingSettings(): array {
        $pdo = Database::getConnection();
        $row = $pdo->query("SELECT * FROM shipping_settings WHERE id = 1 LIMIT 1")->fetch();
        if (!$row) {
            return [
                'flat_charge_paise' => 4900,
                'free_threshold_paise' => 99900,
                'active' => 1,
            ];
        }
        return $row;
    }

    // --- Orders ---
    public static function createOrder(array $data, array $items): string {
        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare("
                INSERT INTO orders (
                    id, order_number, tracking_token, payment_status, fulfillment_status,
                    customer_name, customer_email, customer_phone, shipping_address,
                    subtotal_paise, discount_paise, shipping_paise, total_paise,
                    coupon_code, cashfree_order_id, payment_session_id
                ) VALUES (
                    ?, ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?
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
                $data['cashfree_order_id'] ?? null,
                $data['payment_session_id'] ?? null,
            ]);

            // Insert order items
            $itemStmt = $pdo->prepare("
                INSERT INTO order_items (id, order_id, variant_id, product_name, variant_label, unit_price_paise, quantity)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            $isSqlite = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite';
            $stockSql = $isSqlite 
                ? "UPDATE variants SET stock = MAX(0, stock - ?) WHERE id = ?"
                : "UPDATE variants SET stock = GREATEST(0, stock - ?) WHERE id = ?";
            $stockStmt = $pdo->prepare($stockSql);

            foreach ($items as $item) {
                $unitPrice = (int)($item['unit_price_paise'] ?? $item['price_paise'] ?? 0);
                $itemStmt->execute([
                    'item_' . bin2hex(random_bytes(10)),
                    $data['id'],
                    $item['variant_id'] ?? null,
                    $item['product_name'],
                    $item['variant_label'],
                    $unitPrice,
                    $item['quantity'],
                ]);

                // Reduce inventory stock
                if (!empty($item['variant_id'])) {
                    $stockStmt->execute([(int)$item['quantity'], (string)$item['variant_id']]);
                }
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
        $stmt = $pdo->prepare("
            UPDATE orders 
            SET payment_status = ?, fulfillment_status = ? 
            WHERE order_number = ? OR cashfree_order_id = ?
        ");
        $stmt->execute([$status, $fulfillment, $orderNumber, $cfOrderId]);
    }

    public static function updateOrderCourier(string $orderId, string $courier, string $trackingNumber, string $fulfillmentStatus): void {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            UPDATE orders 
            SET courier = ?, tracking_number = ?, fulfillment_status = ? 
            WHERE id = ?
        ");
        $stmt->execute([$courier, $trackingNumber, $fulfillmentStatus, $orderId]);
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
