<?php
/**
 * SVG Dry Fruits - Coupon API Endpoint
 */

declare(strict_types=1);

require_once __DIR__ . '/../src/Helpers.php';
require_once __DIR__ . '/../src/Cart.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = $input['action'] ?? ($_GET['action'] ?? 'apply');

if ($action === 'remove') {
    Cart::removeCoupon();
    Helpers::json([
        'success' => true,
        'message' => 'Coupon removed.',
        'cart' => Cart::getDetails()
    ]);
}

$code = trim($input['code'] ?? '');
if (!$code) {
    Helpers::json(['success' => false, 'error' => 'Please enter a coupon code.'], 400);
}

$result = Cart::applyCoupon($code);
if ($result['success']) {
    Helpers::json([
        'success' => true,
        'message' => $result['message'],
        'cart' => Cart::getDetails()
    ]);
} else {
    Helpers::json([
        'success' => false,
        'error' => $result['message']
    ], 400);
}
