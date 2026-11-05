<?php
/**
 * SVG Dry Fruits - Order & Inventory Integrity Verification Tests
 */
declare(strict_types=1);

TestSuite::section('8. Product Variant ID Preservation');

$pdo = Database::getConnection();

// Create a test product
$testProdId = 'prod_test_inv_' . bin2hex(random_bytes(4));
$testProdSlug = 'test-inventory-product-' . bin2hex(random_bytes(4));
$pdo->prepare("
    INSERT INTO products (id, slug, name, description, category_id, active)
    VALUES (?, ?, 'Test Inventory Product', 'Testing preservation', NULL, 1)
")->execute([$testProdId, $testProdSlug]);

// Seed initial variants
$initialVarId1 = 'var_orig_1_' . bin2hex(random_bytes(4));
$initialVarId2 = 'var_orig_2_' . bin2hex(random_bytes(4));
$skuSuffix = bin2hex(random_bytes(4));
$sku1 = 'SKU-T1-' . $skuSuffix;
$sku1Up = 'SKU-T1-UP-' . $skuSuffix;
$sku2 = 'SKU-T2-' . $skuSuffix;
$sku3 = 'SKU-T3-' . $skuSuffix;

$pdo->prepare("
    INSERT INTO variants (id, product_id, sku, label, price_paise, compare_price_paise, stock, low_stock_threshold, active)
    VALUES (?, ?, ?, '250g', 25000, 30000, 50, 5, 1),
           (?, ?, ?, '500g', 48000, 55000, 40, 5, 1)
")->execute([$initialVarId1, $testProdId, $sku1, $initialVarId2, $testProdId, $sku2]);

// Update product variants including preserving initialVarId1, updating price, and adding a new variant
$variantsPayload = [
    [
        'id' => $initialVarId1,
        'sku' => $sku1Up,
        'label' => '250g Super',
        'price_paise' => 27000,
        'compare_price_paise' => 32000,
        'stock' => 50,
        'low_stock_threshold' => 5,
        'active' => 1
    ],
    [
        'id' => '', // New variant, no ID yet
        'sku' => $sku3,
        'label' => '1kg Jumbo',
        'price_paise' => 90000,
        'compare_price_paise' => 100000,
        'stock' => 25,
        'low_stock_threshold' => 5,
        'active' => 1
    ]
];

Models::saveProductVariants($testProdId, $variantsPayload);

// Verify original variant ID 1 is still present and updated in place
$checkVar1 = $pdo->prepare("SELECT * FROM variants WHERE id = ?");
$checkVar1->execute([$initialVarId1]);
$var1Row = $checkVar1->fetch();

TestSuite::assert(
    !empty($var1Row) && $var1Row['sku'] === $sku1Up && (int)$var1Row['price_paise'] === 27000,
    'Existing variant ID preserved in-place without deletion or ID regeneration'
);

TestSuite::section('9. Checkout Inventory Deduction & Idempotent Stock Restoration');

// Record initial stock
$initStock = (int)$var1Row['stock']; // 50
$qtyOrdered = 5;

// Place a test order
$testOrderNum = 'SVG-TEST-' . bin2hex(random_bytes(4));
$orderData = [
    'order_number' => $testOrderNum,
    'tracking_token' => bin2hex(random_bytes(16)),
    'customer_name' => 'Tester',
    'customer_phone' => '9876543210',
    'customer_email' => 'test@example.com',
    'shipping_address' => ['street' => '123 Test St', 'city' => 'Hyderabad', 'state' => 'Telangana', 'pincode' => '500001'],
    'subtotal_paise' => 27000 * $qtyOrdered,
    'shipping_paise' => 0,
    'discount_paise' => 0,
    'total_paise' => 27000 * $qtyOrdered,
    'payment_method' => 'online',
    'payment_status' => 'pending',
    'fulfillment_status' => 'pending'
];

$orderItems = [
    [
        'product_id' => $testProdId,
        'variant_id' => $initialVarId1,
        'product_name' => 'Test Inventory Product',
        'variant_label' => '250g Super',
        'sku' => 'SKU-TEST-1-UPDATED',
        'quantity' => $qtyOrdered,
        'unit_price_paise' => 27000
    ]
];

Models::createOrder($orderData, $orderItems);

// Check stock was deducted
$stmtStockAfterOrder = $pdo->prepare("SELECT stock FROM variants WHERE id = ?");
$stmtStockAfterOrder->execute([$initialVarId1]);
$stockAfterOrder = (int)$stmtStockAfterOrder->fetchColumn();

TestSuite::assert(
    $stockAfterOrder === ($initStock - $qtyOrdered),
    "Inventory deducted on order checkout: {$initStock} - {$qtyOrdered} = {$stockAfterOrder}"
);

// Cancel order and restore stock
$restoredFirst = Models::restoreOrderStock($testOrderNum);
$stmtStockAfterRestore = $pdo->prepare("SELECT stock FROM variants WHERE id = ?");
$stmtStockAfterRestore->execute([$initialVarId1]);
$stockAfterRestore = (int)$stmtStockAfterRestore->fetchColumn();

TestSuite::assert(
    $restoredFirst === true && $stockAfterRestore === $initStock,
    "Stock accurately restored on order cancellation/refund: back to {$stockAfterRestore}"
);

// Test idempotency: Calling restoreOrderStock a second time must NOT add stock again
$restoredSecond = Models::restoreOrderStock($testOrderNum);
$stmtStockAfterSecondRestore = $pdo->prepare("SELECT stock FROM variants WHERE id = ?");
$stmtStockAfterSecondRestore->execute([$initialVarId1]);
$stockAfterSecond = (int)$stmtStockAfterSecondRestore->fetchColumn();

TestSuite::assert(
    $restoredSecond === false && $stockAfterSecond === $initStock,
    'restoreOrderStock() is strictly idempotent (does not double-restore stock on repeated calls)'
);

// Clean up test order and product
$pdo->prepare("DELETE FROM order_items WHERE order_id IN (SELECT id FROM orders WHERE order_number = ?)")->execute([$testOrderNum]);
$pdo->prepare("DELETE FROM orders WHERE order_number = ?")->execute([$testOrderNum]);
$pdo->prepare("DELETE FROM variants WHERE product_id = ?")->execute([$testProdId]);
$pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$testProdId]);
