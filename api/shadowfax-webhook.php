<?php
/**
 * SVG Dry Fruits - Shadowfax Webhook Callback Endpoint
 * Receives real-time fulfillment and delivery status callbacks from Shadowfax:
 * dispatched, in_transit, out_for_delivery, delivered, rto_initiated, rto_delivered, cancelled
 */

declare(strict_types=1);

require_once __DIR__ . '/../src/Helpers.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Models.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Helpers::json(['success' => false, 'error' => 'Method not allowed'], 405);
}

$raw = file_get_contents('php://input');
$payload = json_decode($raw, true);

if (!$payload) {
    Helpers::json(['success' => false, 'error' => 'Invalid JSON'], 400);
}

// Log incoming webhook for auditing
error_log("Shadowfax Webhook received: " . substr($raw, 0, 300));

// Extract identifier (client_order_id, order_id, or awb_number)
$orderNumber = $payload['client_order_id'] ?? $payload['order_id'] ?? null;
$awb = $payload['awb_number'] ?? $payload['awb'] ?? null;
$status = strtolower(trim((string)($payload['status'] ?? $payload['event'] ?? '')));

if (!$orderNumber && !$awb) {
    Helpers::json(['success' => false, 'error' => 'Missing order reference'], 400);
}

try {
    $pdo = Database::getConnection();

    // Find order
    if ($orderNumber) {
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_number = ? LIMIT 1");
        $stmt->execute([$orderNumber]);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE tracking_number = ? LIMIT 1");
        $stmt->execute([$awb]);
    }
    $order = $stmt->fetch();

    if (!$order) {
        Helpers::json(['success' => false, 'error' => 'Order not found'], 404);
    }

    // Map Shadowfax status to internal fulfillment_status
    $mappedStatus = null;
    if (in_array($status, ['delivered', 'delivery_complete', 'closed'])) {
        $mappedStatus = 'delivered';
    } elseif (in_array($status, ['out_for_delivery', 'reached_destination', 'in_transit', 'dispatched', 'pickup_complete', 'shipped'])) {
        $mappedStatus = 'shipped';
    } elseif (in_array($status, ['cancelled', 'rto', 'rto_delivered'])) {
        $mappedStatus = 'cancelled';
    }

    if ($mappedStatus) {
        $upStmt = $pdo->prepare("
            UPDATE orders 
            SET fulfillment_status = ?, courier = 'Shadowfax Express', tracking_number = COALESCE(?, tracking_number)
            WHERE id = ?
        ");
        $upStmt->execute([$mappedStatus, $awb, $order['id']]);
    }

    Helpers::json([
        'success' => true,
        'message' => 'Shadowfax callback processed successfully',
        'order_number' => $order['order_number'],
        'fulfillment_status' => $mappedStatus ?? $order['fulfillment_status']
    ]);
} catch (Throwable $e) {
    error_log("Shadowfax Webhook error: " . $e->getMessage());
    Helpers::json(['success' => false, 'error' => 'Server error'], 500);
}
