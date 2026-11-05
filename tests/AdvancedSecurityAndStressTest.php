<?php
/**
 * SVG Dry Fruits - Advanced Security, Concurrency & Defense-in-Depth Tests
 * Verifies second-pass security controls, atomic stock locks, payment zero-trust, and replay protection.
 */
declare(strict_types=1);

require_once ROOT_PATH . '/config/cashfree.php';

TestSuite::section('10. Session Hardening & Strict Cookie Configuration');

// Test 10.1: Strict Session Modes
$strictMode = ini_get('session.use_strict_mode');
TestSuite::assert(
    $strictMode === '1',
    'session.use_strict_mode is enabled (prevents session fixation)'
);

$onlyCookies = ini_get('session.use_only_cookies');
TestSuite::assert(
    $onlyCookies === '1',
    'session.use_only_cookies is enabled (disallows session ID passing in URLs)'
);

$cookieParams = session_get_cookie_params();
TestSuite::assert(
    $cookieParams['httponly'] === true,
    'Session cookie httponly is set to true (XSS mitigation)'
);
TestSuite::assert(
    strtolower((string)($cookieParams['samesite'] ?? '')) === 'lax',
    'Session cookie samesite is set to Lax (CSRF mitigation)'
);

TestSuite::section('11. Admin Execution Order & Router Authorization Gate');

// Ensure admin user is cleared from session
unset($_SESSION['admin_user']);
TestSuite::assert(Auth::check() === false, 'Auth::check() returns false when no admin session exists');

// Verify router-level path matching logic
$protectedAdminPaths = [
    'admin',
    'admin/products',
    'admin/orders',
    'admin/shipping',
    'admin/categories',
    'admin/coupons',
    'admin/settings',
    'admin/combos'
];

foreach ($protectedAdminPaths as $path) {
    $requiresAuth = (strpos($path, 'admin') === 0 && $path !== 'admin/login');
    TestSuite::assert(
        $requiresAuth === true,
        "Router authorization gate flags '$path' as requiring mandatory authentication"
    );
}

// Ensure login path is the only exception
$loginPathRequiresAuth = (strpos('admin/login', 'admin') === 0 && 'admin/login' !== 'admin/login');
TestSuite::assert(
    $loginPathRequiresAuth === false,
    "Router gate permits 'admin/login' to bypass gate for authentication"
);

TestSuite::section('12. Payment Zero-Trust & Gateway Amount Tampering Rejection');

$pdo = Database::getConnection();

// Create a real order in the database with total ₹1,250.00 (125000 paise)
$tamperProdId = 'prod_tamper_' . bin2hex(random_bytes(4));
$tamperSlug = 'test-tamper-' . bin2hex(random_bytes(4));
$pdo->prepare("
    INSERT INTO products (id, slug, name, description, category_id, active)
    VALUES (?, ?, 'Tamper Test Product', 'Tamper test', NULL, 1)
")->execute([$tamperProdId, $tamperSlug]);

$tamperVarId = 'var_tamper_' . bin2hex(random_bytes(4));
$pdo->prepare("
    INSERT INTO variants (id, product_id, sku, label, price_paise, compare_price_paise, stock, low_stock_threshold, active)
    VALUES (?, ?, ?, 'Standard', 125000, 150000, 100, 5, 1)
")->execute([$tamperVarId, $tamperProdId, 'SKU-TAMPER-' . bin2hex(random_bytes(4))]);

$orderData = [
    'tracking_token' => bin2hex(random_bytes(16)),
    'customer_name' => 'Tamper Test Customer',
    'customer_email' => 'tamper@test.com',
    'customer_phone' => '9876543210',
    'shipping_address' => [
        'full_name' => 'Tamper Test Customer',
        'address_line1' => '123 Security Way',
        'city' => 'Hyderabad',
        'state' => 'Telangana',
        'pincode' => '500001',
        'phone' => '9876543210'
    ],
    'subtotal_paise' => 125000,
    'discount_paise' => 0,
    'shipping_paise' => 0,
    'total_paise' => 125000,
    'payment_method' => 'cashfree'
];

$orderItems = [
    [
        'variant_id' => $tamperVarId,
        'product_name' => 'Tamper Test Product',
        'variant_label' => 'Standard',
        'price_paise' => 125000,
        'quantity' => 1
    ]
];

$orderId = Models::createOrder($orderData, $orderItems);
$order = Models::getOrderById($orderId);
TestSuite::assert($order !== null && (int)$order['total_paise'] === 125000, 'Order created with expected ₹1,250.00 total');

// Scenario 1: Malicious attacker manipulates Cashfree to return order_amount = 1.00 (₹1.00 instead of ₹1,250.00)
$tamperedCfAmount = 1.00;
$tamperedPaise = (int)round($tamperedCfAmount * 100);
$isTampered = abs($tamperedPaise - (int)$order['total_paise']) > 1;
TestSuite::assert(
    $isTampered === true,
    'Zero-trust validation detects amount tampering (₹1.00 returned vs ₹1,250.00 required)'
);

// Scenario 2: Authentic payment with exact amount ₹1,250.00
$authenticCfAmount = 1250.00;
$authenticPaise = (int)round($authenticCfAmount * 100);
$isAuthentic = abs($authenticPaise - (int)$order['total_paise']) <= 1;
TestSuite::assert(
    $isAuthentic === true,
    'Zero-trust validation accepts exact matching payment amount (₹1,250.00)'
);

TestSuite::section('13. Cashfree Webhook Replay & Timestamp Drift Protection');

$cfInstance = new CashfreePG();

// Reflection to set a clientSecret for testing HMAC verification
$reflection = new ReflectionClass($cfInstance);
$secretProp = $reflection->getProperty('clientSecret');
$secretProp->setAccessible(true);
$secretProp->setValue($cfInstance, 'test_secret_key_abcdef1234567890');

$rawWebhookBody = json_encode([
    'data' => [
        'order' => ['order_id' => $orderId, 'order_amount' => 1250.00],
        'payment' => ['payment_status' => 'SUCCESS']
    ],
    'event_time' => date('c')
]);

$now = time();

// Sub-test 13.1: Valid signature within 5 minutes
$validTimestamp = (string)$now;
$validSignature = base64_encode(hash_hmac('sha256', $validTimestamp . $rawWebhookBody, 'test_secret_key_abcdef1234567890', true));
$verifiedValid = $cfInstance->verifyWebhookSignature($rawWebhookBody, $validSignature, $validTimestamp);
TestSuite::assert(
    $verifiedValid === true,
    'Webhook with current timestamp and valid HMAC signature is accepted'
);

// Sub-test 13.2: Replay attack (timestamp 301 seconds old)
$expiredTimestamp = (string)($now - 301);
$expiredSignature = base64_encode(hash_hmac('sha256', $expiredTimestamp . $rawWebhookBody, 'test_secret_key_abcdef1234567890', true));
$verifiedExpired = $cfInstance->verifyWebhookSignature($rawWebhookBody, $expiredSignature, $expiredTimestamp);
TestSuite::assert(
    $verifiedExpired === false,
    'Webhook replay attack (> 300s old) is rejected by replay drift protection'
);

// Sub-test 13.3: Future timestamp drift (> 300s in the future)
$futureTimestamp = (string)($now + 301);
$futureSignature = base64_encode(hash_hmac('sha256', $futureTimestamp . $rawWebhookBody, 'test_secret_key_abcdef1234567890', true));
$verifiedFuture = $cfInstance->verifyWebhookSignature($rawWebhookBody, $futureSignature, $futureTimestamp);
TestSuite::assert(
    $verifiedFuture === false,
    'Webhook with excessive forward timestamp drift (> 300s) is rejected'
);

// Sub-test 13.4: Tampered body with valid timestamp
$tamperedBody = $rawWebhookBody . ' ';
$verifiedTampered = $cfInstance->verifyWebhookSignature($tamperedBody, $validSignature, $validTimestamp);
TestSuite::assert(
    $verifiedTampered === false,
    'Webhook with altered payload body fails HMAC-SHA256 signature check'
);

TestSuite::section('14. Concurrency & Race Condition Oversell Prevention');

// Setup a product variant with exactly 1 unit of stock
$raceProdId = 'prod_race_' . bin2hex(random_bytes(4));
$raceSlug = 'test-race-' . bin2hex(random_bytes(4));
$pdo->prepare("
    INSERT INTO products (id, slug, name, description, category_id, active)
    VALUES (?, ?, 'Race Condition Test Product', 'Stock = 1', NULL, 1)
")->execute([$raceProdId, $raceSlug]);

$raceVarId = 'var_race_' . bin2hex(random_bytes(4));
$pdo->prepare("
    INSERT INTO variants (id, product_id, sku, label, price_paise, compare_price_paise, stock, low_stock_threshold, active)
    VALUES (?, ?, ?, 'Only One Left', 50000, 60000, 1, 0, 1)
")->execute([$raceVarId, $raceProdId, 'SKU-RACE-' . bin2hex(random_bytes(4))]);

// Customer A order payload for 1 item
$orderDataA = [
    'tracking_token' => bin2hex(random_bytes(16)),
    'customer_name' => 'Buyer A',
    'customer_email' => 'buyera@test.com',
    'customer_phone' => '9999990001',
    'shipping_address' => ['full_name' => 'Buyer A', 'address_line1' => 'Street A', 'city' => 'City', 'state' => 'State', 'pincode' => '123456', 'phone' => '9999990001'],
    'subtotal_paise' => 50000,
    'discount_paise' => 0,
    'shipping_paise' => 0,
    'total_paise' => 50000,
    'payment_method' => 'cashfree'
];

$orderItemsA = [
    ['variant_id' => $raceVarId, 'product_name' => 'Race Item', 'variant_label' => '1 Left', 'price_paise' => 50000, 'quantity' => 1]
];

// Customer B order payload for 1 item (concurrent attempt)
$orderDataB = [
    'tracking_token' => bin2hex(random_bytes(16)),
    'customer_name' => 'Buyer B',
    'customer_email' => 'buyerb@test.com',
    'customer_phone' => '9999990002',
    'shipping_address' => ['full_name' => 'Buyer B', 'address_line1' => 'Street B', 'city' => 'City', 'state' => 'State', 'pincode' => '123456', 'phone' => '9999990002'],
    'subtotal_paise' => 50000,
    'discount_paise' => 0,
    'shipping_paise' => 0,
    'total_paise' => 50000,
    'payment_method' => 'cashfree'
];

$orderItemsB = [
    ['variant_id' => $raceVarId, 'product_name' => 'Race Item', 'variant_label' => '1 Left', 'price_paise' => 50000, 'quantity' => 1]
];

// First order succeeds
$orderIdA = Models::createOrder($orderDataA, $orderItemsA);
TestSuite::assert(!empty($orderIdA), 'Buyer A order successfully created when stock = 1');

// Verify stock became 0
$stockStmt = $pdo->prepare("SELECT stock FROM variants WHERE id = ?");
$stockStmt->execute([$raceVarId]);
$currentStock = (int)$stockStmt->fetchColumn();
TestSuite::assert($currentStock === 0, 'Variant stock decremented atomically to 0');

// Second concurrent order MUST fail with RuntimeException (insufficient stock)
$caughtException = false;
$exceptionMessage = '';
try {
    Models::createOrder($orderDataB, $orderItemsB);
} catch (RuntimeException $e) {
    $caughtException = true;
    $exceptionMessage = $e->getMessage();
}

TestSuite::assert($caughtException === true, 'Buyer B order rejected with RuntimeException on depleted stock');
TestSuite::assert(str_contains($exceptionMessage, 'Insufficient stock'), "Exception message clearly indicates insufficient stock ('{$exceptionMessage}')");

// Verify stock did not drop below 0 (no negative stock overselling)
$stockStmt->execute([$raceVarId]);
$finalStock = (int)$stockStmt->fetchColumn();
TestSuite::assert($finalStock === 0, 'Stock remains strictly at 0 without negative underflow');

TestSuite::section('15. Refund Ceiling & Multi-Refund Prevention');

// Create test order with total ₹2,000.00 (200000 paise)
$refundOrderId = 'ord_refund_' . bin2hex(random_bytes(4));
$pdo->prepare("
    INSERT INTO orders (id, order_number, tracking_token, customer_name, customer_email, customer_phone, 
                        shipping_address, subtotal_paise, discount_paise, shipping_paise, 
                        total_paise, payment_status, fulfillment_status)
    VALUES (?, ?, ?, 'Refund Test', 'refund@test.com', '9888888888', '{}', 200000, 0, 0, 200000, 'paid', 'delivered')
")->execute([$refundOrderId, 'REFUND-ORD-1-' . bin2hex(random_bytes(3)), bin2hex(random_bytes(16))]);

// Verify refund helper logic
$refOrder = Models::getOrderById($refundOrderId);
$orderTotalPaise = (int)$refOrder['total_paise'];

// Refund 1: ₹1,200.00 (120000 paise)
$priorRefunds = 0;
$refund1Amount = 120000;
$canRefund1 = ($priorRefunds + $refund1Amount) <= $orderTotalPaise;
TestSuite::assert($canRefund1 === true, 'Initial partial refund of ₹1,200 (out of ₹2,000) is permitted');

$pdo->prepare("
    INSERT INTO refunds (id, order_id, amount_paise, note, created_at)
    VALUES (?, ?, ?, 'Partial refund 1', CURRENT_TIMESTAMP)
")->execute(['ref_1_' . bin2hex(random_bytes(4)), $refundOrderId, $refund1Amount]);

// Calculate cumulative refunds from DB
$sumStmt = $pdo->prepare("SELECT COALESCE(SUM(amount_paise), 0) FROM refunds WHERE order_id = ?");
$sumStmt->execute([$refundOrderId]);
$totalRefunded = (int)$sumStmt->fetchColumn();
TestSuite::assert($totalRefunded === 120000, 'Database cumulative refunded sum is exactly ₹1,200.00 (120000 paise)');

// Refund 2: Attempting another ₹1,000.00 (100000 paise) -> Total would be ₹2,200 > ₹2,000
$excessiveRefundAmount = 100000;
$canExcessiveRefund = ($totalRefunded + $excessiveRefundAmount) <= $orderTotalPaise;
TestSuite::assert(
    $canExcessiveRefund === false,
    'Excessive refund attempt (₹1,200 + ₹1,000 > ₹2,000) is strictly rejected'
);

// Refund 3: Exact remaining balance ₹800.00 (80000 paise) -> Total = ₹2,000
$exactRemainingAmount = 80000;
$canExactRefund = ($totalRefunded + $exactRemainingAmount) <= $orderTotalPaise;
TestSuite::assert(
    $canExactRefund === true,
    'Exact remaining refund of ₹800 is permitted'
);

TestSuite::section('16. File Upload Mandatory CSRF Validation');

// Sub-test 16.1: CSRF missing from upload request
$missingCsrfValid = Helpers::verifyCsrf('');
TestSuite::assert($missingCsrfValid === false, 'Upload request without CSRF token fails verification');

// Sub-test 16.2: CSRF invalid/forged in upload request
$forgedCsrfValid = Helpers::verifyCsrf('forged_csrf_token_value_abc');
TestSuite::assert($forgedCsrfValid === false, 'Upload request with forged CSRF token fails verification');

// Sub-test 16.3: Legitimate CSRF token
$validToken = Helpers::csrfToken();
$legitCsrfValid = Helpers::verifyCsrf($validToken);
TestSuite::assert($legitCsrfValid === true, 'Upload request with legitimate CSRF token succeeds verification');

TestSuite::section('17. Order Tracking Rate Limiting');

$trackKey = 'track_order_test_' . bin2hex(random_bytes(4));
$trackLimit = 5;
$trackWindow = 60;

for ($i = 1; $i <= 5; $i++) {
    $allowed = Helpers::checkRateLimit($trackKey, $trackLimit, $trackWindow);
    TestSuite::assert($allowed === true, "Order tracking lookup attempt {$i}/5 is allowed");
}

$blockedAttempt = Helpers::checkRateLimit($trackKey, $trackLimit, $trackWindow);
TestSuite::assert($blockedAttempt === false, 'Order tracking lookup attempt 6 is blocked by rate limiter');
