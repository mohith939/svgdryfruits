<?php
/**
 * SVG Dry Fruits - Shopping Cart Manager
 * Session-based cart with real-time database stock & price resolution.
 */

declare(strict_types=1);

require_once __DIR__ . '/Models.php';

class Cart {
    public static function init(): void {
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = []; // [ variantId => quantity ]
        }
    }

    public static function add(string|int $variantId, int $quantity = 1): void {
        self::init();
        if ($quantity <= 0) return;
        $variantId = (string)$variantId;

        if (isset($_SESSION['cart'][$variantId])) {
            $_SESSION['cart'][$variantId] += $quantity;
        } else {
            $_SESSION['cart'][$variantId] = $quantity;
        }

        // Cap at 20 per item
        if ($_SESSION['cart'][$variantId] > 20) {
            $_SESSION['cart'][$variantId] = 20;
        }
    }

    public static function update(string|int $variantId, int $quantity): void {
        self::init();
        $variantId = (string)$variantId;
        if ($quantity <= 0) {
            self::remove($variantId);
        } else {
            $_SESSION['cart'][$variantId] = min(20, $quantity);
        }
    }

    public static function remove(string|int $variantId): void {
        self::init();
        $variantId = (string)$variantId;
        unset($_SESSION['cart'][$variantId]);
    }

    public static function clear(): void {
        $_SESSION['cart'] = [];
        unset($_SESSION['applied_coupon']);
    }

    public static function count(): int {
        self::init();
        $sum = 0;
        foreach ($_SESSION['cart'] as $variantId => $q) {
            $qty = (int)(is_array($q) ? ($q['quantity'] ?? 1) : $q);
            if ($qty <= 0) {
                unset($_SESSION['cart'][$variantId]);
                continue;
            }
            $variant = Models::getVariantById((string)$variantId);
            if (!$variant || empty($variant['active'])) {
                unset($_SESSION['cart'][$variantId]);
                continue;
            }
            $sum += $qty;
        }
        return $sum;
    }



    public static function applyCoupon(string $code): array {
        $coupon = Models::getCouponByCode($code);
        if (!$coupon) {
            return ['success' => false, 'message' => 'Invalid or expired coupon code.'];
        }

        $details = self::getDetails();
        if ($details['subtotal_paise'] < $coupon['min_subtotal_paise']) {
            $minRupees = $coupon['min_subtotal_paise'] / 100;
            return [
                'success' => false, 
                'message' => "Coupon requires minimum order of ₹{$minRupees}."
            ];
        }

        $_SESSION['applied_coupon'] = $coupon['code'];
        return ['success' => true, 'coupon' => $coupon, 'message' => 'Coupon applied successfully!'];
    }

    public static function removeCoupon(): void {
        unset($_SESSION['applied_coupon']);
    }

    public static function setShippingOption(string $id): void {
        self::init();
        $_SESSION['selected_shipping_id'] = $id;
    }

    public static function parseWeightGrams(string $label, ?int $explicitGrams = null): int {
        if ($explicitGrams !== null && $explicitGrams > 0) {
            return $explicitGrams;
        }
        $label = strtolower(trim($label));
        if (preg_match('/(\d+(?:\.\d+)?)\s*(kg|kilo|kilogram)s?/i', $label, $m)) {
            return (int)round(((float)$m[1]) * 1000);
        }
        if (preg_match('/(\d+(?:\.\d+)?)\s*(g|gm|gms|gram)s?/i', $label, $m)) {
            return (int)round((float)$m[1]);
        }
        return 500;
    }

    public static function getCartWeightGrams(): int {
        $details = self::getDetails();
        return (int)($details['total_weight_grams'] ?? 0);
    }

    public static function getEligibleShippingOptions(int $subtotalPaise): array {
        return [];
    }

    public static function getDetails(?string $shippingOptionId = null, ?string $state = null): array {
        self::init();
        $items = [];
        $subtotalPaise = 0;
        $totalWeightGrams = 0;
        $itemCount = 0;

        foreach ($_SESSION['cart'] as $variantId => $qty) {
            $quantity = (int)(is_array($qty) ? ($qty['quantity'] ?? 1) : $qty);
            if ($quantity <= 0) {
                unset($_SESSION['cart'][$variantId]);
                continue;
            }

            $variant = Models::getVariantById((string)$variantId);
            if (!$variant || empty($variant['active'])) {
                unset($_SESSION['cart'][$variantId]);
                continue;
            }

            $lineTotalPaise = $variant['price_paise'] * $quantity;
            $subtotalPaise += $lineTotalPaise;
            $itemCount += $quantity;

            $weightGrams = self::parseWeightGrams((string)($variant['label'] ?? ''));
            $lineWeightGrams = $weightGrams * $quantity;
            $totalWeightGrams += $lineWeightGrams;

            $items[] = [
                'variant_id' => $variant['id'],
                'product_name' => $variant['product_name'],
                'product_slug' => $variant['product_slug'],
                'variant_label' => $variant['label'],
                'sku' => $variant['sku'],
                'price_paise' => $variant['price_paise'],
                'compare_price_paise' => $variant['compare_price_paise'],
                'image_url' => $variant['product_image'],
                'quantity' => $quantity,
                'stock' => $variant['stock'],
                'line_total_paise' => $lineTotalPaise,
                'weight_grams' => $weightGrams,
                'line_weight_grams' => $lineWeightGrams,
            ];
        }

        // Calculate discount
        $discountPaise = 0;
        $appliedCoupon = null;
        if (!empty($_SESSION['applied_coupon'])) {
            $coupon = Models::getCouponByCode($_SESSION['applied_coupon']);
            if ($coupon && $subtotalPaise >= $coupon['min_subtotal_paise']) {
                $appliedCoupon = $coupon;
                if ($coupon['type'] === 'percentage') {
                    $discountPaise = (int)round(($subtotalPaise * $coupon['value']) / 100);
                } elseif ($coupon['type'] === 'free_shipping') {
                    $discountPaise = 0;
                } else {
                    // 'flat' or 'fixed'
                    $discountPaise = (int)$coupon['value'];
                }

                if (!empty($coupon['max_discount_paise']) && $discountPaise > $coupon['max_discount_paise']) {
                    $discountPaise = (int)$coupon['max_discount_paise'];
                }

                if ($discountPaise > $subtotalPaise) {
                    $discountPaise = $subtotalPaise;
                }
            } else {
                unset($_SESSION['applied_coupon']);
            }
        }

        // All orders enjoy 100% Free Shipping storewide
        $shippingPaise = 0;
        $totalPaise = max(0, $subtotalPaise - $discountPaise);

        return [
            'items' => $items,
            'item_count' => $itemCount,
            'subtotal_paise' => $subtotalPaise,
            'total_weight_grams' => $totalWeightGrams,
            'total_weight_kg' => round($totalWeightGrams / 1000, 2),
            'discount_paise' => $discountPaise,
            'shipping_paise' => 0,
            'total_paise' => $totalPaise,
            'applied_coupon' => $appliedCoupon,
            'eligible_shipping' => [],
            'selected_shipping' => null,
        ];
    }
}
