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
        foreach ($_SESSION['cart'] as $q) {
            $sum += (int)(is_array($q) ? ($q['quantity'] ?? 1) : $q);
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

    public static function getEligibleShippingOptions(int $subtotalPaise): array {
        $all = Models::getShippingOptions(true);
        $eligible = [];
        foreach ($all as $opt) {
            if ($subtotalPaise >= (int)($opt['min_subtotal_paise'] ?? 0)) {
                $isFree = ($opt['free_above_paise'] !== null && $subtotalPaise >= (int)$opt['free_above_paise']);
                $opt['effective_paise'] = $isFree ? 0 : (int)$opt['rate_paise'];
                $opt['is_free'] = $isFree;
                $eligible[] = $opt;
            }
        }
        return $eligible;
    }

    public static function getDetails(?string $shippingOptionId = null, ?string $state = null): array {
        self::init();
        $items = [];
        $subtotalPaise = 0;

        foreach ($_SESSION['cart'] as $variantId => $qty) {
            $quantity = (int)(is_array($qty) ? ($qty['quantity'] ?? 1) : $qty);
            if ($quantity <= 0) {
                continue;
            }

            $variant = Models::getVariantById((string)$variantId);
            if (!$variant || !$variant['active']) {
                continue;
            }

            $lineTotalPaise = $variant['price_paise'] * $quantity;
            $subtotalPaise += $lineTotalPaise;

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

        // State-based & multi-condition shipping resolution
        $eligibleShipping = self::getEligibleShippingOptions($subtotalPaise);
        $targetState = $state ?? ($_SESSION['customer_state'] ?? null);

        $selectedOption = null;
        if ($targetState) {
            $selectedOption = Models::getShippingOptionForState($targetState, $subtotalPaise);
        } elseif ($shippingOptionId) {
            foreach ($eligibleShipping as $opt) {
                if ($opt['id'] === $shippingOptionId) {
                    $selectedOption = $opt;
                    break;
                }
            }
        }

        if (!$selectedOption && !empty($eligibleShipping)) {
            $selectedOption = Models::getShippingOptionForState('Telangana', $subtotalPaise);
        }

        $shippingPaise = 0;
        if ($appliedCoupon && $appliedCoupon['type'] === 'free_shipping') {
            $shippingPaise = 0;
        } elseif ($selectedOption) {
            $shippingPaise = (int)$selectedOption['effective_paise'];
        } else {
            // Fallback to legacy shipping_settings if no shipping_options exist
            $shipping = Models::getShippingSettings();
            if (!empty($shipping['active'])) {
                if ($shipping['free_threshold_paise'] !== null && $subtotalPaise >= $shipping['free_threshold_paise']) {
                    $shippingPaise = 0;
                } else {
                    $shippingPaise = (int)$shipping['flat_charge_paise'];
                }
            }
        }

        $totalPaise = max(0, $subtotalPaise - $discountPaise + $shippingPaise);

        return [
            'items' => $items,
            'item_count' => self::count(),
            'subtotal_paise' => $subtotalPaise,
            'discount_paise' => $discountPaise,
            'shipping_paise' => $shippingPaise,
            'total_paise' => $totalPaise,
            'applied_coupon' => $appliedCoupon,
            'eligible_shipping' => $eligibleShipping,
            'selected_shipping' => $selectedOption,
        ];
    }
}
