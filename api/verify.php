<?php
/**
 * SVG Dry Fruits - Payment Verification Endpoint
 * Checks order payment status with Cashfree and marks order as paid.
 */

declare(strict_types=1);

require_once __DIR__ . '/../src/Helpers.php';
require_once __DIR__ . '/../src/Models.php';
require_once __DIR__ . '/../config/cashfree.php';

header('Content-Type: application/json');

$orderNumber = $_GET['order_id'] ?? ($_POST['order_id'] ?? '');

if (!$orderNumber) {
    Helpers::json(['success' => false, 'error' => 'Order ID is required'], 400);
}

// Rate limit: max 10 status checks per minute per order
if (!Helpers::checkRateLimit('verify_order_' . $orderNumber, 10, 60)) {
    Helpers::json(['success' => false, 'error' => 'Too many verification requests. Please wait a moment.'], 429);
}

$order = Models::getOrderByNumber($orderNumber);
if (!$order) {
    Helpers::json(['success' => false, 'error' => 'Order not found'], 404);
}


// If already paid, return success immediately
if ($order['payment_status'] === 'paid') {
    Helpers::json([
        'success' => true,
        'payment_status' => 'paid',
        'fulfillment_status' => $order['fulfillment_status']
    ]);
}

try {
    $cashfree = new CashfreePG();
    $cfOrder = $cashfree->getOrder($orderNumber);
    $status = strtoupper($cfOrder['order_status'] ?? '');
    if ($status === 'PAID') {
        $cfAmount = (float)($cfOrder['order_amount'] ?? 0);
        $expectedAmount = Helpers::paiseToRupees((int)$order['total_paise']);
        if (abs($cfAmount - $expectedAmount) > 0.01) {
            error_log("CRITICAL PAYMENT TAMPERING DETECTED for order {$orderNumber}: Expected ₹{$expectedAmount}, Gateway paid ₹{$cfAmount}");
            Helpers::json([
                'success' => false,
                'error' => 'Payment amount mismatch detected. Order cannot be verified automatically.'
            ], 400);
        }

        Models::updateOrderPayment($orderNumber, 'paid', $cfOrder['cf_order_id'] ?? null);
        Helpers::json([
            'success' => true,
            'payment_status' => 'paid',
            'order_status' => 'PAID'
        ]);
    } else {
        Helpers::json([
            'success' => false,
            'payment_status' => strtolower($status),
            'order_status' => $status,
            'message' => 'Payment has not been completed yet.'
        ]);
    }
} catch (Throwable $e) {
    error_log("Verification error: " . $e->getMessage());
    Helpers::json(['success' => false, 'error' => $e->getMessage()], 500);
}
