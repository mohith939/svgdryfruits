<?php
/**
 * Fallback handler for /api/order-success
 * Redirects to the storefront order confirmation view
 */
declare(strict_types=1);
require_once __DIR__ . '/../src/Helpers.php';

$qs = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
Helpers::redirect(Helpers::url('order-success' . $qs));
