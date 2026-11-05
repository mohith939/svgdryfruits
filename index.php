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

try {
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

        // Database Installer Shortcuts
        case $path === 'install.php' || $path === 'install' || $path === 'database/install.php' || $path === 'database/install':
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
} catch (Throwable $e) {
    if (ob_get_length()) {
        ob_clean();
    }
    http_response_code(500);
    $msg = $e->getMessage();
    $isDbError = (
        strpos($msg, 'MySQL Connection') !== false ||
        strpos($msg, 'SQLSTATE') !== false ||
        strpos($msg, 'database') !== false ||
        strpos($msg, 'Access denied') !== false ||
        strpos($msg, 'No such file') !== false
    );
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
      <meta charset="UTF-8">
      <title>SVG Dry Fruits — Configuration Notice</title>
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #fbfaf7; color: #203c32; margin: 0; padding: 40px 20px; display: flex; align-items: center; justify-content: center; min-height: 80vh; }
        .box { max-width: 640px; width: 100%; background: #fff; padding: 36px; border-radius: 12px; border: 1px solid #e2ddd3; box-shadow: 0 10px 30px rgba(0,0,0,0.06); text-align: center; }
        h1 { font-family: Georgia, serif; font-size: 26px; margin: 0 0 12px; color: #073D26; }
        p { color: #555; line-height: 1.6; font-size: 14.5px; margin: 0 0 18px; }
        .diag { background: #fdf2f2; border: 1px solid #f8b4b4; color: #9b1c1c; padding: 12px 16px; border-radius: 6px; font-size: 13px; font-family: monospace; word-break: break-all; margin: 16px 0 24px; text-align: left; line-height: 1.4; }
        .btn { display: inline-block; background: #073D26; color: #E2C376; text-decoration: none; padding: 13px 26px; border-radius: 6px; font-weight: 700; font-size: 15px; }
        .btn:hover { background: #13271e; }
      </style>
    </head>
    <body>
      <div class="box">
        <h1>SVG Dry Fruits</h1>
        <?php if ($isDbError): ?>
          <p>The application is loaded, but the MySQL database connection needs to be verified or initialized.</p>
          <div class="diag">
            <strong>Diagnostic Error:</strong><br><?= htmlspecialchars($msg) ?>
          </div>
          <p style="font-size: 13px; color: #777;">
            Click below to open the 1-Click Database Setup &amp; Seeder utility:
          </p>
          <a href="<?= Helpers::url('database/install.php') ?>" class="btn">
            Open Database Setup &amp; Seeder &rarr;
          </a>
        <?php else: ?>
          <p>An unexpected server error occurred while processing your request.</p>
          <div class="diag">
            <strong>Error:</strong><br><?= htmlspecialchars($msg) ?>
          </div>
          <a href="<?= Helpers::url('database/install.php') ?>" class="btn">
            Open Setup &rarr;
          </a>
        <?php endif; ?>
      </div>
    </body>
    </html>
    <?php
    exit;
}
