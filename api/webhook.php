<?php
/**
 * SVG Dry Fruits - Cashfree Webhook Handler
 * Processes asynchronous payment notifications securely.
 */

declare(strict_types=1);

require_once __DIR__ . '/../src/Helpers.php';
require_once __DIR__ . '/../src/Models.php';
require_once __DIR__ . '/../config/cashfree.php';

$rawBody = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'] ?? '';
$timestamp = $_SERVER['HTTP_X_WEBHOOK_TIMESTAMP'] ?? '';

$cashfree = new CashfreePG();

// Mandatory signature verification in production
if (CASHFREE_ENV === 'PRODUCTION') {
    if (empty($signature) || empty($timestamp) || !$cashfree->isConfigured()) {
        http_response_code(401);
        echo json_encode(['error' => 'Missing webhook signature or credentials in production']);
        exit;
    }
    if (!$cashfree->verifyWebhookSignature($rawBody, $signature, $timestamp)) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid webhook signature']);
        exit;
    }
} elseif (!empty($signature) && $cashfree->isConfigured()) {
    if (!$cashfree->verifyWebhookSignature($rawBody, $signature, $timestamp)) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid webhook signature']);
        exit;
    }
}

$payload = json_decode($rawBody, true);
if (!$payload) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON body']);
    exit;
}

$type = $payload['type'] ?? '';
$orderData = $payload['data']['order'] ?? [];
$orderNumber = $orderData['order_id'] ?? null;

if ($orderNumber) {
    $existing = Models::getOrderByNumber($orderNumber);
    if ($existing && $existing['payment_status'] === 'paid') {
        // Idempotency: duplicate notification already handled
        http_response_code(200);
        echo json_encode(['received' => true, 'idempotent' => true]);
        exit;
    }

    if (in_array($type, ['PAYMENT_SUCCESS_WEBHOOK', 'ORDER_PAID_WEBHOOK', 'ORDER_SUCCESS'], true)) {
        if ($existing) {
            $paidAmount = (float)($orderData['order_amount'] ?? 0);
            $expectedAmount = Helpers::paiseToRupees((int)$existing['total_paise']);
            if (abs($paidAmount - $expectedAmount) > 0.01) {
                error_log("CRITICAL PAYMENT TAMPERING in webhook for order {$orderNumber}: expected ₹{$expectedAmount}, got ₹{$paidAmount}");
                http_response_code(400);
                echo json_encode(['error' => 'Payment amount mismatch']);
                exit;
            }
        }
        Models::updateOrderPayment($orderNumber, 'paid', $orderData['cf_order_id'] ?? null);
    } elseif (in_array($type, ['PAYMENT_FAILED_WEBHOOK', 'ORDER_FAILED'], true)) {
        Models::updateOrderPayment($orderNumber, 'failed', $orderData['cf_order_id'] ?? null);
    }
}

http_response_code(200);
echo json_encode(['received' => true]);
