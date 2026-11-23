<?php
/**
 * SVG Dry Fruits - Dynamic Shipping Rate & Serviceability API
 * Calculates exact courier freight via Shadowfax Rate Card based on destination PIN code and package weight
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Helpers.php';
require_once __DIR__ . '/../src/Cart.php';
require_once __DIR__ . '/../src/Shadowfax.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$pincode = '';

if ($method === 'POST') {
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true) ?: [];
    $pincode = (string)($input['pincode'] ?? '');
} else {
    $pincode = (string)($_GET['pincode'] ?? '');
}

$cleanPin = preg_replace('/\D/', '', $pincode);

if (strlen($cleanPin) !== 6) {
    Helpers::json([
        'success' => false,
        'serviceable' => false,
        'message' => 'Please enter a valid 6-digit Indian PIN code.'
    ], 400);
}

// Get current cart details to determine total package weight
$cart = Cart::getDetails();
$cartWeightKg = (float)($cart['total_weight_kg'] ?? 0.5);
if ($cartWeightKg <= 0) {
    $cartWeightKg = 0.5;
}

// 1. Check Forward Serviceability via Shadowfax
$serviceability = Shadowfax::checkFullServiceability($cleanPin);
$isServiceable = !empty($serviceability['forward']['serviceable']);

// 2. Calculate Shadowfax Rate Card Courier Cost
$rate = Shadowfax::calculateShippingRate($cleanPin, $cartWeightKg, 'prepaid', 'forward');
$shippingPaise = (int)round(($rate['total_cost'] ?? 0) * 100);

Helpers::json([
    'success' => true,
    'pincode' => $cleanPin,
    'serviceable' => $isServiceable,
    'service_message' => $isServiceable 
        ? "Serviceable by Shadowfax ({$rate['estimated_timeline']})" 
        : "Delivery might be delayed to PIN {$cleanPin}",
    'weight_kg' => $rate['weight_kg'],
    'billed_weight_kg' => $rate['billed_weight_kg'],
    'zone' => $rate['zone'],
    'base_rate' => $rate['base_rate'],
    'base_freight' => $rate['base_freight'],
    'fuel_surcharge' => $rate['fuel_surcharge'],
    'gst_18' => $rate['gst_18'],
    'shipping_cost' => $rate['total_cost'],
    'shipping_paise' => $shippingPaise,
    'shipping_formatted' => '₹' . number_format($rate['total_cost'], 2),
    'estimated_timeline' => $rate['estimated_timeline']
]);
