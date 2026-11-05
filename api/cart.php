<?php
/**
 * SVG Dry Fruits - Cart API Endpoint
 */

declare(strict_types=1);

require_once __DIR__ . '/../src/Helpers.php';
require_once __DIR__ . '/../src/Cart.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? ($_POST['action'] ?? '');

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $action = $input['action'] ?? $action;

    if ($action === 'add') {
        $variantId = $input['variant_id'] ?? '';
        $qty = (int)($input['quantity'] ?? 1);

        if (!$variantId) {
            Helpers::json(['success' => false, 'error' => 'Variant ID is required'], 400);
        }

        Cart::add($variantId, $qty);
        Helpers::json([
            'success' => true,
            'message' => 'Added to bag',
            'count' => Cart::count(),
            'cart' => Cart::getDetails()
        ]);
    }

    if ($action === 'update') {
        $variantId = $input['variant_id'] ?? '';
        $qty = (int)($input['quantity'] ?? 1);

        Cart::update($variantId, $qty);
        Helpers::json([
            'success' => true,
            'count' => Cart::count(),
            'cart' => Cart::getDetails()
        ]);
    }

    if ($action === 'remove') {
        $variantId = $input['variant_id'] ?? '';
        Cart::remove($variantId);
        Helpers::json([
            'success' => true,
            'count' => Cart::count(),
            'cart' => Cart::getDetails()
        ]);
    }

    if ($action === 'clear') {
        Cart::clear();
        Helpers::json(['success' => true, 'count' => 0]);
    }

    if ($action === 'apply_coupon' || $action === 'apply') {
        $code = trim($input['code'] ?? '');
        if (!$code) {
            Helpers::json(['success' => false, 'error' => 'Please enter a coupon code.'], 400);
        }
        $res = Cart::applyCoupon($code);
        if ($res['success']) {
            Helpers::json([
                'success' => true,
                'message' => $res['message'],
                'count' => Cart::count(),
                'cart' => Cart::getDetails()
            ]);
        } else {
            Helpers::json([
                'success' => false,
                'error' => $res['message']
            ], 400);
        }
    }

    if ($action === 'remove_coupon' || $action === 'remove_promo') {
        Cart::removeCoupon();
        Helpers::json([
            'success' => true,
            'message' => 'Coupon removed.',
            'count' => Cart::count(),
            'cart' => Cart::getDetails()
        ]);
    }
}


// GET cart details
Helpers::json([
    'success' => true,
    'count' => Cart::count(),
    'cart' => Cart::getDetails()
]);
