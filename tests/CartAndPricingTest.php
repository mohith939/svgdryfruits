<?php
/**
 * SVG Dry Fruits - Cart & Pricing Logic Verification Tests
 */
declare(strict_types=1);

TestSuite::section('6. Cart Subtotal & Item Calculations');

$pdo = Database::getConnection();

// Create test product and variants for cart testing
$cartProdId = 'prod_cart_test_' . bin2hex(random_bytes(4));
$cartVarId1 = 'var_cart_1_' . bin2hex(random_bytes(4));
$cartVarId2 = 'var_cart_2_' . bin2hex(random_bytes(4));

$cartProdSlug = 'test-cart-product-' . bin2hex(random_bytes(4));
$pdo->prepare("
    INSERT INTO products (id, slug, name, description, category_id, active)
    VALUES (?, ?, 'Test Cart Product', 'Desc', NULL, 1)
")->execute([$cartProdId, $cartProdSlug]);

$pdo->prepare("
    INSERT INTO variants (id, product_id, sku, label, price_paise, compare_price_paise, stock, low_stock_threshold, active)
    VALUES (?, ?, 'SKU-CART-1', '500g', 45000, 50000, 50, 5, 1),
           (?, ?, 'SKU-CART-2', '250g', 30000, 35000, 50, 5, 1)
")->execute([$cartVarId1, $cartProdId, $cartVarId2, $cartProdId]);

Cart::clear();
TestSuite::assert(Cart::count() === 0, 'Cart starts empty after clear()');

// Add item 1: Variant with 500g @ ₹450 (45000 paise), quantity 2
Cart::add($cartVarId1, 2);

$cart = Cart::getDetails();
TestSuite::assert(
    $cart['subtotal_paise'] === 90000,
    'Cart subtotal calculated accurately: 2 * 45000 = 90000 paise (₹900.00)'
);
TestSuite::assert(Cart::count() === 2, 'Cart item count matches added quantities');

// Add item 2: Variant with 250g @ ₹300 (30000 paise), quantity 1
Cart::add($cartVarId2, 1);

$cart = Cart::getDetails();
TestSuite::assert(
    $cart['subtotal_paise'] === 120000,
    'Subtotal updates correctly on multiple items: 90000 + 30000 = 120000 paise (₹1200.00)'
);

// Quantity update
Cart::update($cartVarId1, 3);
$cart = Cart::getDetails();
TestSuite::assert(
    $cart['subtotal_paise'] === 165000,
    'Subtotal updates on quantity change: 3 * 45000 + 30000 = 165000 paise (₹1650.00)'
);

TestSuite::section('7. Coupon Discounts & Free Shipping Handling');

// Coupon 1: Percentage 10% with min subtotal ₹500
$pdo->prepare("DELETE FROM coupons WHERE code IN ('TEST10', 'TEST50FLAT', 'TESTFREE')")->execute();
$pdo->prepare("
    INSERT INTO coupons (id, code, name, type, value, min_subtotal_paise, active, times_used)
    VALUES ('cpn_t1', 'TEST10', '10% Off', 'percentage', 10, 50000, 1, 0)
")->execute();

// Coupon 2: Fixed amount ₹50 (5000 paise) with min subtotal ₹500
$pdo->prepare("
    INSERT INTO coupons (id, code, name, type, value, min_subtotal_paise, active, times_used)
    VALUES ('cpn_t2', 'TEST50FLAT', 'Flat ₹50 Off', 'fixed', 5000, 50000, 1, 0)
")->execute();

// Coupon 3: Free shipping coupon
$pdo->prepare("
    INSERT INTO coupons (id, code, name, type, value, min_subtotal_paise, active, times_used)
    VALUES ('cpn_t3', 'TESTFREE', 'Free Shipping', 'free_shipping', 0, 50000, 1, 0)
")->execute();

// Test percentage coupon application
$res1 = Cart::applyCoupon('TEST10');
TestSuite::assert($res1['success'] === true, 'Percentage coupon TEST10 applies successfully');
$cart = Cart::getDetails();
TestSuite::assert(
    $cart['discount_paise'] === 16500, // 10% of 165000 = 16500 paise
    'Percentage discount calculated correctly: 10% of ₹1650 = ₹165 (16500 paise)'
);

// Test fixed amount coupon application
$res2 = Cart::applyCoupon('TEST50FLAT');
TestSuite::assert($res2['success'] === true, 'Fixed amount coupon TEST50FLAT applies successfully');
$cart = Cart::getDetails();
TestSuite::assert(
    $cart['discount_paise'] === 5000,
    'Fixed amount discount calculated correctly: ₹50.00 (5000 paise)'
);

// Test free shipping coupon application
$res3 = Cart::applyCoupon('TESTFREE');
TestSuite::assert($res3['success'] === true, 'Free shipping coupon TESTFREE applies successfully');
$cart = Cart::getDetails();
TestSuite::assert(
    $cart['shipping_paise'] === 0,
    'Free shipping coupon sets shipping charge strictly to 0 paise'
);

// Test coupon minimum order rejection
Cart::clear();
// Create small item
$cheapVarId = 'var_cheap_' . bin2hex(random_bytes(4));
$pdo->prepare("
    INSERT INTO variants (id, product_id, sku, label, price_paise, compare_price_paise, stock, low_stock_threshold, active)
    VALUES (?, ?, 'SKU-CHEAP', '50g', 10000, 15000, 50, 5, 1)
")->execute([$cheapVarId, $cartProdId]);

Cart::add($cheapVarId, 1); // 10000 paise (₹100), less than ₹500
$resMin = Cart::applyCoupon('TEST10');
TestSuite::assert(
    $resMin['success'] === false && str_contains(strtolower($resMin['message'] ?? ''), 'minimum'),
    'Coupon is rejected when cart subtotal is below minimum order value'
);

// Section 7B: State-Based Shipping Rate Auto-Resolution
TestSuite::section('7B. State-Based Shipping Rate Auto-Resolution');

// Temporarily deactivate non-test shipping options to test exact rule resolution
$pdo->exec("UPDATE shipping_options SET active = 0");
$pdo->exec("DELETE FROM shipping_options WHERE id LIKE 'test_ship_%'");
$pdo->prepare("
    INSERT INTO shipping_options (id, name, target_states, description, estimated_days, rate_paise, free_above_paise, min_subtotal_paise, active, sort_order)
    VALUES 
    ('test_ship_ts_ap', 'Telangana & AP Delivery', 'Telangana, Andhra Pradesh', 'Fast regional', '1-2 Days', 4900, 99900, 0, 1, 1),
    ('test_ship_south', 'South India', 'Karnataka, Tamil Nadu, Kerala', 'South zonal', '2-3 Days', 6900, 149900, 0, 1, 2),
    ('test_ship_roi', 'Rest of India', 'ALL', 'National delivery', '3-5 Days', 8900, 199900, 0, 1, 3)
")->execute();

// Test 1: Match Telangana below free threshold (₹450 order)
$optTs = Models::getShippingOptionForState('Telangana', 45000);
TestSuite::assert($optTs['id'] === 'test_ship_ts_ap', 'Correctly matches Telangana state rule (test_ship_ts_ap)');
TestSuite::assert($optTs['effective_paise'] === 4900, 'Applies exact ₹49 (4900 paise) rate for Telangana when below ₹999');

// Test 2: Match Telangana above free threshold (₹1200 order)
$optTsFree = Models::getShippingOptionForState('Telangana', 120000);
TestSuite::assert($optTsFree['effective_paise'] === 0, 'Automatically unlocks FREE delivery (0 paise) when cart >= ₹999 threshold');

// Test 3: Match Karnataka (South India)
$optKa = Models::getShippingOptionForState('Karnataka', 50000);
TestSuite::assert($optKa['id'] === 'test_ship_south', 'Correctly matches Karnataka to South India shipping rule');
TestSuite::assert($optKa['effective_paise'] === 6900, 'Applies ₹69 (6900 paise) rate for Karnataka');

// Test 4: Match Delhi (Falls back to Rest of India 'ALL')
$optDel = Models::getShippingOptionForState('Delhi', 50000);
TestSuite::assert($optDel['id'] === 'test_ship_roi', 'Correctly falls back to ALL (Rest of India) for unlisted states like Delhi');
TestSuite::assert($optDel['effective_paise'] === 8900, 'Applies ₹89 (8900 paise) rate for Rest of India');

// Section 7C: Weight Calculation & Tiered State Shipping Verification
TestSuite::section('7C. Weight-Based Tiered Shipping & Coupon Updates');

// 1. Weight extraction from variant label strings
TestSuite::assert(Cart::parseWeightGrams('250 g') === 250, 'parseWeightGrams extracts 250g from "250 g"');
TestSuite::assert(Cart::parseWeightGrams('500g') === 500, 'parseWeightGrams extracts 500g from "500g"');
TestSuite::assert(Cart::parseWeightGrams('1 kg') === 1000, 'parseWeightGrams extracts 1000g from "1 kg"');
TestSuite::assert(Cart::parseWeightGrams('1.5 kg') === 1500, 'parseWeightGrams extracts 1500g from "1.5 kg"');
TestSuite::assert(Cart::parseWeightGrams('2 kg') === 2000, 'parseWeightGrams extracts 2000g from "2 kg"');
TestSuite::assert(Cart::parseWeightGrams('1 kg Deluxe Hamper') === 1000, 'parseWeightGrams extracts 1000g from "1 kg Deluxe Hamper"');

// 2. Setup user's requested rates for Telangana & Rest of India
$pdo->exec("DELETE FROM shipping_options WHERE id LIKE 'test_%'");
$pdo->prepare("
    INSERT INTO shipping_options (id, name, target_states, description, estimated_days, rate_paise, rate_1_2kg_paise, rate_2kg_above_paise, free_above_paise, min_subtotal_paise, active, sort_order)
    VALUES 
    ('test_tiered_ts', 'Telangana & AP Tiered', 'Telangana, Andhra Pradesh', 'Express TS/AP', '1-2 Days', 9000, 13000, 15000, NULL, 0, 1, 1),
    ('test_tiered_roi', 'Rest of India Tiered', 'ALL', 'National Express', '3-5 Days', 10000, 15000, 21000, NULL, 0, 1, 2)
")->execute();

// Telangana Tier 1: Below 1kg (< 1000g) => ₹90 (9000 paise)
$tsTier1 = Models::getShippingOptionForState('Telangana', 40000, 500);
TestSuite::assert($tsTier1['effective_paise'] === 9000, 'Telangana < 1kg resolves to ₹90 (9000 paise)');
TestSuite::assert($tsTier1['active_tier'] === 'below_1kg', 'Telangana < 1kg sets active_tier to below_1kg');

// Telangana Tier 2: 1kg - 2kg (1500g) => ₹130 (13000 paise)
$tsTier2 = Models::getShippingOptionForState('Telangana', 40000, 1500);
TestSuite::assert($tsTier2['effective_paise'] === 13000, 'Telangana 1-2kg resolves to ₹130 (13000 paise)');
TestSuite::assert($tsTier2['active_tier'] === '1_2kg', 'Telangana 1-2kg sets active_tier to 1_2kg');

// Telangana Tier 3: 2kg & Above (2500g) => ₹150 (15000 paise)
$tsTier3 = Models::getShippingOptionForState('Telangana', 40000, 2500);
TestSuite::assert($tsTier3['effective_paise'] === 15000, 'Telangana >= 2kg resolves to ₹150 (15000 paise)');
TestSuite::assert($tsTier3['active_tier'] === '2kg_above', 'Telangana >= 2kg sets active_tier to 2kg_above');

// Rest of India Tier 1: Below 1kg (< 1000g) => ₹100 (10000 paise)
$roiTier1 = Models::getShippingOptionForState('Delhi', 40000, 750);
TestSuite::assert($roiTier1['effective_paise'] === 10000, 'Rest of India < 1kg resolves to ₹100 (10000 paise)');

// Rest of India Tier 2: 1kg - 2kg (1800g) => ₹150 (15000 paise)
$roiTier2 = Models::getShippingOptionForState('Maharashtra', 40000, 1800);
TestSuite::assert($roiTier2['effective_paise'] === 15000, 'Rest of India 1-2kg resolves to ₹150 (15000 paise)');

// Rest of India Tier 3: 2kg & Above (3000g) => ₹210 (21000 paise)
$roiTier3 = Models::getShippingOptionForState('Punjab', 40000, 3000);
TestSuite::assert($roiTier3['effective_paise'] === 21000, 'Rest of India >= 2kg resolves to ₹210 (21000 paise)');

// 3. Test Coupon Update and Type Mutation
$testCpnId = 'cpn_test_edit_' . bin2hex(random_bytes(4));
$pdo->prepare("
    INSERT INTO coupons (id, code, name, description, type, value, min_subtotal_paise, active, times_used)
    VALUES (?, 'TESTEDIT', 'Initial Percentage', '10% off', 'percentage', 10, 49900, 1, 0)
")->execute([$testCpnId]);

$cpn1 = Models::getCouponByCode('TESTEDIT');
TestSuite::assert($cpn1['type'] === 'percentage' && (int)$cpn1['value'] === 10, 'Coupon created with initial percentage type');

// Update coupon to Fixed Amount ₹150
$pdo->prepare("
    UPDATE coupons 
    SET name = 'Updated Flat ₹150', type = 'fixed', value = 15000, min_subtotal_paise = 99900
    WHERE id = ?
")->execute([$testCpnId]);

$cpn2 = Models::getCouponByCode('TESTEDIT');
TestSuite::assert($cpn2['type'] === 'fixed' && (int)$cpn2['value'] === 15000, 'Coupon successfully updated to fixed amount (₹150)');

// Update coupon to Free Shipping
$pdo->prepare("
    UPDATE coupons 
    SET name = 'Updated Free Shipping', type = 'free_shipping', value = 0
    WHERE id = ?
")->execute([$testCpnId]);

$cpn3 = Models::getCouponByCode('TESTEDIT');
TestSuite::assert($cpn3['type'] === 'free_shipping' && (int)$cpn3['value'] === 0, 'Coupon successfully updated to free shipping');

$pdo->prepare("DELETE FROM coupons WHERE id = ?")->execute([$testCpnId]);
$pdo->exec("DELETE FROM shipping_options WHERE id IN ('test_tiered_ts', 'test_tiered_roi')");

// Cleanup test shipping options & restore active status
$pdo->exec("DELETE FROM shipping_options WHERE id LIKE 'test_ship_%'");
$pdo->exec("UPDATE shipping_options SET active = 1");

// Cleanup test records
$pdo->prepare("DELETE FROM coupons WHERE code IN ('TEST10', 'TEST50FLAT', 'TESTFREE')")->execute();
$pdo->prepare("DELETE FROM variants WHERE product_id = ?")->execute([$cartProdId]);
$pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$cartProdId]);
Cart::clear();
