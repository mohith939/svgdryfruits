<?php
/**
 * SVG Dry Fruits - Front Controller & Clean URL Router
 */

declare(strict_types=1);

if (!ob_get_level()) {
    ob_start();
}

// Built-in PHP server static file handling (must be at top)
if (php_sapi_name() === 'cli-server') {
    $rawUri = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));
    $cleanUri = preg_replace('#^(/assets)+#', '/assets', $rawUri);
    $staticFile = __DIR__ . $cleanUri;
    if (!is_file($staticFile) && is_file(__DIR__ . '/assets' . $rawUri)) {
        $staticFile = __DIR__ . '/assets' . $rawUri;
    }
    if (!is_file($staticFile) && is_file(__DIR__ . '/public' . $cleanUri)) {
        $staticFile = __DIR__ . '/public' . $cleanUri;
    }
    if ($rawUri !== '/' && is_file($staticFile)) {
        $ext = strtolower(pathinfo($staticFile, PATHINFO_EXTENSION));
        $mimes = [
            'css'   => 'text/css; charset=utf-8',
            'js'    => 'application/javascript; charset=utf-8',
            'jpg'   => 'image/jpeg',
            'jpeg'  => 'image/jpeg',
            'png'   => 'image/png',
            'gif'   => 'image/gif',
            'svg'   => 'image/svg+xml',
            'webp'  => 'image/webp',
            'woff'  => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf'   => 'font/ttf',
            'ico'   => 'image/x-icon',
        ];
        if (isset($mimes[$ext])) {
            header('Content-Type: ' . $mimes[$ext]);
            header('Content-Length: ' . filesize($staticFile));
            header('Cache-Control: public, max-age=86400');
            readfile($staticFile);
            exit;
        }
        return false;
    }
}

if (file_exists(__DIR__ . '/config/config.php')) {
    require_once __DIR__ . '/config/config.php';
    require_once __DIR__ . '/src/Helpers.php';
    require_once __DIR__ . '/src/Auth.php';
} else {
    require_once __DIR__ . '/../config/config.php';
    require_once __DIR__ . '/../src/Helpers.php';
    require_once __DIR__ . '/../src/Auth.php';
}

// Parse requested URI relative to project base path
$requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');

// Normalize URI
if ($scriptDir !== '' && strpos($requestUri, $scriptDir) === 0) {
    $path = substr($requestUri, strlen($scriptDir));
} else {
    $path = $requestUri;
}
$path = trim($path, '/');

// Router Dispatch
switch (true) {
    // Storefront Pages
    case $path === '' || $path === 'index.php':
        Helpers::render('home');
        break;

    case $path === 'shop':
        Helpers::render('shop');
        break;

    case preg_match('#^products/([a-zA-Z0-9_-]+)$#', $path, $matches):
        Helpers::render('product', ['slug' => $matches[1]]);
        break;

    case preg_match('#^categories/([a-zA-Z0-9_-]+)$#', $path, $matches):
        $_GET['category'] = $matches[1];
        Helpers::render('shop');
        break;

    case $path === 'combos':
        $_GET['category'] = 'combos';
        Helpers::render('shop');
        break;

    case $path === 'cart':
        Helpers::render('cart');
        break;

    case $path === 'checkout':
        Helpers::render('checkout');
        break;

    case $path === 'order-success' || $path === 'order/success':
        Helpers::render('order_success');
        break;

    case $path === 'track':
        Helpers::render('track');
        break;

    case $path === 'contact':
        Helpers::render('pages/contact');
        break;

    case $path === 'terms':
        Helpers::render('pages/terms');
        break;

    case $path === 'privacy-policy':
        Helpers::render('pages/privacy');
        break;

    case $path === 'shipping-policy':
        Helpers::render('pages/shipping-policy');
        break;

    case $path === 'return-policy':
        Helpers::render('pages/return-policy');
        break;

    // Admin Routes
    case $path === 'admin':
        Helpers::render('admin/dashboard');
        break;

    case $path === 'admin/login':
        Helpers::render('admin/login');
        break;

    case $path === 'admin/logout':
        Auth::logout();
        Helpers::redirect(Helpers::url('admin/login'));
        break;

    case $path === 'admin/orders':
        Helpers::render('admin/orders');
        break;

    case $path === 'admin/orders/detail':
        Helpers::render('admin/order_detail');
        break;

    case $path === 'admin/products':
        Helpers::render('admin/products');
        break;

    case $path === 'admin/products/new' || $path === 'admin/products/edit':
        Helpers::render('admin/product_form');
        break;

    case $path === 'admin/categories':
        Helpers::render('admin/categories');
        break;

    case $path === 'admin/combos':
        Helpers::render('admin/combos');
        break;

    case $path === 'admin/coupons':
        Helpers::render('admin/coupons');
        break;

    case $path === 'admin/settings':
        Helpers::render('admin/settings');
        break;

    // Database Installer Shortcut
    case $path === 'install.php' || $path === 'install':
        require ROOT_PATH . '/database/install.php';
        break;

    // API Routes dispatch
    case preg_match('#^api/([a-zA-Z0-9_-]+)(\.php)?$#', $path, $matches):
        $apiFile = ROOT_PATH . '/api/' . $matches[1] . '.php';
        if (file_exists($apiFile)) {
            require $apiFile;
        } else {
            Helpers::json(['error' => 'API endpoint not found'], 404);
        }
        break;

    default:
        http_response_code(404);
        echo "<div style='text-align:center; padding:100px 20px; font-family:sans-serif;'>";
        echo "<h1 style='font-size:48px; color:#203c32;'>404</h1>";
        echo "<p style='color:#666;'>The requested page does not exist.</p>";
        echo "<a href='" . Helpers::url('/') . "' style='color:#a66247; font-weight:600;'>Return Home &rarr;</a>";
        echo "</div>";
        break;
}
