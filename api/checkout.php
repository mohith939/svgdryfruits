<?php
/**
 * SVG Dry Fruits - Checkout API Endpoint
 * Handles Order submission, stock reservation, and Cashfree PG session creation.
 */

declare(strict_types=1);

require_once __DIR__ . '/../src/Helpers.php';
require_once __DIR__ . '/../src/Cart.php';
require_once __DIR__ . '/../src/Models.php';
require_once __DIR__ . '/../config/cashfree.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Helpers::json(['success' => false, 'error' => 'Method not allowed'], 405);
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

// Validate customer info (support both naming conventions)
$name = trim($input['name'] ?? $input['customer_name'] ?? '');
$phone = preg_replace('/\D/', '', (string)($input['phone'] ?? $input['customer_phone'] ?? ''));
$email = trim($input['email'] ?? $input['customer_email'] ?? '');
$street = trim($input['street'] ?? $input['shipping_street'] ?? '');
$landmark = trim($input['landmark'] ?? $input['shipping_landmark'] ?? '');
$city = trim($input['city'] ?? $input['shipping_city'] ?? '');
$state = trim($input['state'] ?? $input['shipping_state'] ?? '');
$pincode = trim($input['pincode'] ?? $input['shipping_pincode'] ?? '');
$paymentMethod = $input['payment_method'] ?? 'online'; // 'online' or 'cod'
$shippingOptionId = trim((string)($input['shipping_option_id'] ?? ''));

if (strlen($name) < 2) {
    Helpers::json(['success' => false, 'error' => 'Please enter your full name.'], 400);
}

if (strlen($phone) < 10) {
    Helpers::json(['success' => false, 'error' => 'Please enter a valid 10-digit mobile number.'], 400);
}

if (strlen($street) < 5 || strlen($city) < 2 || strlen($pincode) !== 6) {
    Helpers::json(['success' => false, 'error' => 'Please provide a complete delivery address and 6-digit PIN code.'], 400);
}

$cart = Cart::getDetails(null, $state);
if (empty($cart['items'])) {
    Helpers::json(['success' => false, 'error' => 'Your bag is empty.'], 400);
}

// In-flight submission lock to prevent rapid duplicate clicks / double order creation
$checkoutLockKey = 'checkout_lock_' . md5(session_id() . '_' . $cart['subtotal_paise']);
if (!empty($_SESSION[$checkoutLockKey]) && (time() - (int)$_SESSION[$checkoutLockKey]) < 4) {
    Helpers::json(['success' => false, 'error' => 'Checkout already processing. Please wait a moment.'], 429);
}
$_SESSION[$checkoutLockKey] = time();

// Check stock for each item
foreach ($cart['items'] as $item) {
    if ($item['stock'] < $item['quantity']) {
        Helpers::json([
            'success' => false,
            'error' => "Sorry, {$item['product_name']} ({$item['variant_label']}) only has {$item['stock']} in stock."
        ], 400);
    }
}

// Resolve state-based shipping pricing dynamically from database rules
$shippingOpt = Models::getShippingOptionForState($state, $cart['subtotal_paise']);
$shippingPaise = (int)$shippingOpt['effective_paise'];
if (!empty($cart['applied_coupon']) && $cart['applied_coupon']['type'] === 'free_shipping') {
    $shippingPaise = 0;
}
$discountPaise = (int)$cart['discount_paise'];
$totalPaise = max(0, $cart['subtotal_paise'] - $discountPaise + $shippingPaise);

// Generate Collision-Free High-Entropy Order ID & Token
$datePrefix = date('Ymd');
$randSuffix = strtoupper(bin2hex(random_bytes(3)));
$orderNumber = "SVG-{$datePrefix}-{$randSuffix}";
$trackingToken = bin2hex(random_bytes(16));
$orderId = 'ord_' . bin2hex(random_bytes(12));

$shippingAddress = [
    'street' => $street,
    'city' => $city,
    'state' => $state,
    'pincode' => $pincode,
];

$orderData = [
    'id' => $orderId,
    'order_number' => $orderNumber,
    'tracking_token' => $trackingToken,
    'payment_status' => ($paymentMethod === 'cod') ? 'cod' : 'pending',
    'fulfillment_status' => ($paymentMethod === 'cod') ? 'processing' : 'awaiting_payment',
    'customer_name' => $name,
    'customer_email' => $email,
    'customer_phone' => $phone,
    'shipping_address' => $shippingAddress,
    'subtotal_paise' => $cart['subtotal_paise'],
    'discount_paise' => $discountPaise,
    'shipping_paise' => $shippingPaise,
    'total_paise' => $totalPaise,
    'coupon_code' => $cart['applied_coupon']['code'] ?? null,
    'shipping_option_id' => $shippingOpt['id'] ?? 'ship_default',
    'shipping_option_name' => $shippingOpt['name'] ?? 'State Delivery',
    'cashfree_order_id' => null,
    'payment_session_id' => null,
];

// If Online payment via Cashfree
if ($paymentMethod === 'online') {
    try {
        $cashfree = new CashfreePG();
        $orderAmountRupees = Helpers::paiseToRupees($totalPaise);
        $returnUrl = APP_URL . "/order-success?order_id={$orderNumber}&token={$trackingToken}";

        $cfResponse = $cashfree->createOrder([
            'order_id' => $orderNumber,
            'order_amount' => $orderAmountRupees,
            'customer_phone' => $phone,
            'customer_name' => $name,
            'customer_email' => $email,
            'return_url' => $returnUrl,
            'order_note' => "SVG Dry Fruits Order {$orderNumber}",
        ]);

        $orderData['cashfree_order_id'] = $cfResponse['cf_order_id'] ?? $orderNumber;
        $orderData['payment_session_id'] = $cfResponse['payment_session_id'] ?? null;
    } catch (Throwable $e) {
        error_log("Cashfree Order Create Error: " . $e->getMessage());
        Helpers::json(['success' => false, 'error' => 'Payment gateway error: ' . $e->getMessage()], 500);
    }
}

try {
    Models::createOrder($orderData, $cart['items']);
    $_SESSION['recent_order_id'] = $orderNumber;
    $_SESSION['recent_order_token'] = $trackingToken;
    unset($_SESSION[$checkoutLockKey]);
    // Clear cart on successful creation
    Cart::clear();

    Helpers::json([
        'success' => true,
        'order_number' => $orderNumber,
        'tracking_token' => $trackingToken,
        'payment_method' => $paymentMethod,
        'payment_session_id' => $orderData['payment_session_id'],
        'environment' => CASHFREE_ENV,
        'redirect_url' => Helpers::url("order-success?order_id={$orderNumber}&token={$trackingToken}")
    ]);
} catch (Throwable $e) {
    unset($_SESSION[$checkoutLockKey]);
    error_log("Database Order Insert Error: " . $e->getMessage());
    $isStockError = ($e instanceof RuntimeException && strpos($e->getMessage(), 'Insufficient stock') !== false);
    $statusCode = $isStockError ? 400 : 500;
    $errorMsg = $isStockError ? $e->getMessage() : 'Failed to save order: ' . $e->getMessage();
    Helpers::json(['success' => false, 'error' => $errorMsg], $statusCode);
}
