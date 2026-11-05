<?php
/**
 * SVG Dry Fruits - 1-Click Database Installer & Seeder
 * Run this by visiting: yourdomain.com/database/install.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Helpers.php';

// Installation Security Lock Check
$lockFile = __DIR__ . '/.installed';
$isLocked = file_exists($lockFile);
$isAdmin = Auth::check();
$setupToken = env('INSTALL_TOKEN', '');
$providedToken = trim($_GET['token'] ?? ($_POST['token'] ?? ''));
$isAuthorized = $isAdmin || (!$isLocked) || ($setupToken !== '' && hash_equals($setupToken, $providedToken));

if ($isLocked && !$isAuthorized) {
    http_response_code(403);
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Installer Locked</title><style>body{font-family:-apple-system,BlinkMacSystemFont,sans-serif;background:#fbfaf7;display:flex;align-items:center;justify-content:center;min-height:80vh;margin:0;}.box{max-width:540px;background:#fff;padding:36px;border-radius:10px;border:1px solid #e0dad0;text-align:center;box-shadow:0 10px 25px rgba(0,0,0,0.05);}h2{color:#073D26;margin:0 0 12px;font-size:24px;}p{color:#666;line-height:1.6;font-size:14.5px;}.btn{display:inline-block;margin-top:16px;padding:12px 24px;background:#073D26;color:#E2C376;text-decoration:none;border-radius:6px;font-weight:700;font-size:14px;}</style></head><body><div class='box'><h2>🔒 Database Installer Locked</h2><p>The database has already been initialized and is locked for security against unauthorized resets or configuration modifications.</p><p style='color:#888;font-size:13px;'>To access this tool again, please login as an administrator first, or remove <code>database/.installed</code>.</p><a href='" . Helpers::url('admin/login') . "' class='btn'>Login to Admin Dashboard &rarr;</a></div></body></html>";
    exit;
}

$message = '';
$error = '';
$installed = false;

// 1. Check for submitted credentials update
$envUpdatedMsg = '';
$envFile = __DIR__ . '/../.env';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_env') {
    $subHost = trim($_POST['db_host'] ?? '');
    $subPort = (int)($_POST['db_port'] ?? 3306);
    $subName = trim($_POST['db_name'] ?? '');
    $subUser = trim($_POST['db_user'] ?? '');
    $subPass = trim($_POST['db_pass'] ?? '');

    // Test connection with these credentials
    try {
        $testDsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $subHost, $subPort, $subName);
        $multi = defined('PDO::MYSQL_ATTR_MULTI_STATEMENTS') ? PDO::MYSQL_ATTR_MULTI_STATEMENTS : 1000;
        $testPdo = new PDO($testDsn, $subUser, $subPass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            $multi                       => true
        ]);

        // Connection worked! Now save to .env
        $envContent = file_exists($envFile) ? file_get_contents($envFile) : '';
        if ($envContent === '') {
            $exampleFile = __DIR__ . '/../.env.example';
            $envContent = file_exists($exampleFile) ? file_get_contents($exampleFile) : '';
        }

        $replacements = [
            'DB_HOST' => $subHost,
            'DB_PORT' => $subPort,
            'DB_NAME' => $subName,
            'DB_USER' => $subUser,
            'DB_PASS' => $subPass,
        ];

        foreach ($replacements as $k => $v) {
            if (preg_match("/^{$k}=.*$/m", $envContent)) {
                $envContent = preg_replace("/^{$k}=.*$/m", "{$k}={$v}", $envContent);
            } else {
                $envContent .= "\n{$k}={$v}";
            }
        }

        $saved = @file_put_contents($envFile, $envContent);
        if ($saved !== false) {
            $envUpdatedMsg = "Credentials verified and saved to .env successfully!";
            // Reload into constants/ENV
            putenv("DB_HOST={$subHost}");
            putenv("DB_PORT={$subPort}");
            putenv("DB_NAME={$subName}");
            putenv("DB_USER={$subUser}");
            putenv("DB_PASS={$subPass}");
            $_ENV['DB_HOST'] = $subHost;
            $_ENV['DB_PORT'] = $subPort;
            $_ENV['DB_NAME'] = $subName;
            $_ENV['DB_USER'] = $subUser;
            $_ENV['DB_PASS'] = $subPass;
        } else {
            $envUpdatedMsg = "Connection successful! However, .env is not writable by PHP. Please update .env manually in File Manager.";
        }
    } catch (Throwable $e) {
        $error = "Connection test failed: " . $e->getMessage();
    }
}

// 2. Live MySQL Connection Test
$activeHost = env('DB_HOST', DB_HOST);
$activePort = (int)env('DB_PORT', DB_PORT);
$activeName = env('DB_NAME', DB_NAME);
$activeUser = env('DB_USER', DB_USER);
$activePass = env('DB_PASS', DB_PASS);

$mysqlConnected = false;
$mysqlError = '';
$isPlaceholder = (
    strpos($activeName, 'cpaneluser') !== false ||
    strpos($activeUser, 'cpaneluser') !== false ||
    $activePass === 'YourDatabasePasswordHere' ||
    $activePass === ''
);

$pdo = null;
try {
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $activeHost, $activePort, $activeName);
    $multi = defined('PDO::MYSQL_ATTR_MULTI_STATEMENTS') ? PDO::MYSQL_ATTR_MULTI_STATEMENTS : 1000;
    $pdo = new PDO($dsn, $activeUser, $activePass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        $multi                       => true
    ]);
    $mysqlConnected = true;
} catch (Throwable $e) {
    $mysqlError = $e->getMessage();
}

// 3. Handle 1-Click Installation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (!isset($_POST['action']) || $_POST['action'] === 'install')) {
    if (!$mysqlConnected || !$pdo) {
        $error = "Cannot install: MySQL connection failed. " . $mysqlError;
    } else {
        try {
            $pdo->exec("SET NAMES utf8mb4;");
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

            $resetTables = !empty($_POST['reset_tables']);
            if ($resetTables) {
                $tablesToDrop = [
                    'refunds', 'combo_items', 'order_items', 'orders',
                    'variants', 'products', 'categories', 'coupons',
                    'shipping_options', 'shipping_settings', 'admins'
                ];
                foreach ($tablesToDrop as $t) {
                    try {
                        $pdo->exec("DROP TABLE IF EXISTS `{$t}`");
                    } catch (Throwable $ignore) {}
                }
            }

            // Run Schema
            $schemaSql = file_get_contents(__DIR__ . '/schema.sql');
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
            $pdo->exec($schemaSql);

            // Run Seed
            $seedSql = file_get_contents(__DIR__ . '/seed.sql');
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
            $pdo->exec($seedSql);

            // Ensure Admin Account
            $adminEmail = strtolower(trim(ADMIN_EMAIL));
            $adminPass = trim(ADMIN_PASSWORD);
            $passHash = password_hash($adminPass, PASSWORD_BCRYPT);

            $stmt = $pdo->prepare("
                INSERT INTO admins (id, email, password_hash)
                VALUES ('e0000001-0001-4000-8000-000000000001', ?, ?)
                ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash)
            ");
            $stmt->execute([$adminEmail, $passHash]);

            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

            $installed = true;
            @file_put_contents($lockFile, "Installed on " . date('Y-m-d H:i:s') . "\n");
            $message = "MySQL database successfully initialized and catalog seeded with categories, pack size variants, coupons, shipping options, and admin login!";
        } catch (Throwable $e) {
            try { $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;"); } catch (Throwable $ignore) {}
            $error = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>SVG Dry Fruits - Database Setup</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../css/style.css">
  <style>
    .install-box {
      max-width: 680px;
      margin: 50px auto 80px;
      padding: 40px;
      background: #fbfaf7;
      border: 1px solid #dfdbd1;
      border-radius: 8px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.05);
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    }
    .btn-install {
      display: inline-block;
      width: 100%;
      background: #203c32;
      color: #fff;
      padding: 15px 20px;
      border: none;
      border-radius: 6px;
      font-size: 16px;
      font-weight: 700;
      cursor: pointer;
      margin-top: 15px;
      transition: background 0.2s;
    }
    .btn-install:hover:not(:disabled) {
      background: #142821;
    }
    .btn-install:disabled {
      background: #9aa5a1;
      cursor: not-allowed;
    }
    .alert-success {
      background: #e6f4ea;
      color: #137333;
      padding: 16px 20px;
      border-radius: 6px;
      margin-bottom: 20px;
      border: 1px solid #ceead6;
    }
    .alert-error {
      background: #fce8e6;
      color: #c5221f;
      padding: 16px 20px;
      border-radius: 6px;
      margin-bottom: 20px;
      border: 1px solid #fad2cf;
    }
    .alert-warning {
      background: #fef7e0;
      color: #b06000;
      padding: 16px 20px;
      border-radius: 6px;
      margin-bottom: 20px;
      border: 1px solid #feefc3;
      line-height: 1.5;
    }
    .diag-table {
      width: 100%;
      background: #fff;
      border: 1px solid #e5e0d3;
      border-radius: 6px;
      margin: 20px 0;
      border-collapse: collapse;
      font-size: 14px;
    }
    .diag-table td {
      padding: 10px 14px;
      border-bottom: 1px solid #f0ece3;
    }
    .diag-table tr:last-child td {
      border-bottom: none;
    }
    .badge {
      display: inline-block;
      padding: 4px 10px;
      border-radius: 4px;
      font-size: 12px;
      font-weight: 700;
      text-transform: uppercase;
    }
    .badge-ok { background: #e6f4ea; color: #137333; }
    .badge-err { background: #fce8e6; color: #c5221f; }
    .guide-box {
      background: #f4f8fa;
      border: 1px solid #d4e6f1;
      border-radius: 6px;
      padding: 18px 20px;
      margin: 20px 0;
      font-size: 13.5px;
      line-height: 1.6;
      color: #2c3e50;
    }
    .guide-box ol { margin: 8px 0 0 18px; padding: 0; }
    .guide-box li { margin-bottom: 6px; }
    code {
      background: #eaeded;
      padding: 2px 6px;
      border-radius: 3px;
      font-family: monospace;
      color: #b03a2e;
    }
  </style>
</head>
<body>
  <div class="install-box">
    <h2 style="font-family: Georgia, serif; font-size: 30px; color: #203c32; margin: 0 0 8px 0;">
      SVG Dry Fruits — Database Setup
    </h2>
    <p style="color: #666; font-size: 14px; margin-bottom: 20px;">
      This utility initializes the MySQL database tables, pack size variants (250g, 500g, 1kg), initial product catalog, and administrator credentials.
    </p>

    <?php if ($message): ?>
      <div class="alert-success">
        <strong>✓ Installation Complete:</strong> <?= htmlspecialchars($message) ?>
        <p style="margin: 14px 0 0 0;">
          <a href="../" style="font-weight: 700; color: #137333; text-decoration: underline;">&rarr; Open Storefront</a> &nbsp;|&nbsp;
          <a href="../admin/login" style="font-weight: 700; color: #137333; text-decoration: underline;">Go to Admin Login &rarr;</a>
        </p>
      </div>
    <?php endif; ?>

    <?php if ($error): ?>
      <div class="alert-error">
        <strong>Setup Error:</strong> <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <?php if ($envUpdatedMsg): ?>
      <div class="alert-success">
        <strong>✓ <?= htmlspecialchars($envUpdatedMsg) ?></strong>
      </div>
    <?php endif; ?>

    <?php if (strpos($mysqlError, '2002') !== false || strpos($mysqlError, 'No such file or directory') !== false): ?>
      <div class="alert-warning" style="background: #fff8e1; border: 1px solid #ffe082;">
        <strong style="color: #c43800; font-size: 15px;">👉 StackCP / Sunlight Host Notice:</strong><br>
        StackCP does <strong>not</strong> run MySQL locally on <code>localhost</code>. In your StackCP panel, your database server hostname is:
        <div style="margin: 8px 0; font-size: 15px;">
          <code>DB_HOST=sdb-81.hosting.stackcp.net</code>
        </div>
        Please update your Host to <code>sdb-81.hosting.stackcp.net</code> below or in your <code>.env</code> file.
      </div>
    <?php endif; ?>

    <?php if ($isPlaceholder && !$installed): ?>
      <div class="alert-warning">
        <strong>⚠️ Placeholder Credentials Detected:</strong><br>
        Your <code>.env</code> file currently contains example placeholders.
      </div>
    <?php endif; ?>

    <!-- Live Diagnostics Table -->
    <table class="diag-table">
      <tr>
        <td style="width: 35%; color: #777;">MySQL Connection</td>
        <td>
          <?php if ($mysqlConnected): ?>
            <span class="badge badge-ok">✓ Connected</span>
          <?php else: ?>
            <span class="badge badge-err">✗ Failed</span>
          <?php endif; ?>
        </td>
      </tr>
      <tr>
        <td style="color: #777;">MySQL Host</td>
        <td><code><?= htmlspecialchars($activeHost) ?>:<?= $activePort ?></code></td>
      </tr>
      <tr>
        <td style="color: #777;">Target Database</td>
        <td><code><?= htmlspecialchars($activeName) ?></code></td>
      </tr>
      <tr>
        <td style="color: #777;">Database User</td>
        <td><code><?= htmlspecialchars($activeUser) ?></code></td>
      </tr>
      <tr>
        <td style="color: #777;">Admin Email</td>
        <td><strong><?= htmlspecialchars(ADMIN_EMAIL) ?></strong></td>
      </tr>
      <?php if (!$mysqlConnected && $mysqlError): ?>
      <tr>
        <td style="color: #c5221f;">Connection Error</td>
        <td style="color: #c5221f; font-size: 13px;"><code><?= htmlspecialchars($mysqlError) ?></code></td>
      </tr>
      <?php endif; ?>
    </table>

    <?php if (!$mysqlConnected && !$installed): ?>
      <div style="background: #ffffff; border: 1px solid #d4ded9; border-radius: 8px; padding: 22px; margin: 25px 0;">
        <h3 style="margin: 0 0 12px 0; color: #203c32; font-size: 17px;">
          ⚙️ Update Credentials Directly
        </h3>
        <p style="font-size: 13px; color: #666; margin-bottom: 16px;">
          Enter your StackCP database settings below to test the connection and save them to <code>.env</code>:
        </p>
        <form method="POST">
          <input type="hidden" name="action" value="save_env">
          <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 10px; margin-bottom: 12px;">
            <div>
              <label style="font-size: 12px; font-weight: 600; color: #555; display: block; margin-bottom: 4px;">DB Host</label>
              <input type="text" name="db_host" value="<?= htmlspecialchars($activeHost === 'localhost' ? 'sdb-81.hosting.stackcp.net' : $activeHost) ?>" style="width: 100%; box-sizing: border-box; padding: 8px 10px; border: 1px solid #ccc; border-radius: 4px; font-family: monospace;">
            </div>
            <div>
              <label style="font-size: 12px; font-weight: 600; color: #555; display: block; margin-bottom: 4px;">Port</label>
              <input type="number" name="db_port" value="<?= htmlspecialchars((string)$activePort) ?>" style="width: 100%; box-sizing: border-box; padding: 8px 10px; border: 1px solid #ccc; border-radius: 4px; font-family: monospace;">
            </div>
          </div>
          <div style="margin-bottom: 12px;">
            <label style="font-size: 12px; font-weight: 600; color: #555; display: block; margin-bottom: 4px;">Database Name</label>
            <input type="text" name="db_name" value="<?= htmlspecialchars($activeName) ?>" style="width: 100%; box-sizing: border-box; padding: 8px 10px; border: 1px solid #ccc; border-radius: 4px; font-family: monospace;">
          </div>
          <div style="margin-bottom: 12px;">
            <label style="font-size: 12px; font-weight: 600; color: #555; display: block; margin-bottom: 4px;">Database User</label>
            <input type="text" name="db_user" value="<?= htmlspecialchars($activeUser) ?>" style="width: 100%; box-sizing: border-box; padding: 8px 10px; border: 1px solid #ccc; border-radius: 4px; font-family: monospace;">
          </div>
          <div style="margin-bottom: 16px;">
            <label style="font-size: 12px; font-weight: 600; color: #555; display: block; margin-bottom: 4px;">
              Database Password
              <span style="font-weight: normal; color: #888;">(Make sure you set &amp; saved this in StackCP)</span>
            </label>
            <input type="password" name="db_pass" placeholder="Enter password for <?= htmlspecialchars($activeUser) ?>" style="width: 100%; box-sizing: border-box; padding: 8px 10px; border: 1px solid #ccc; border-radius: 4px; font-family: monospace;">
          </div>
          <button type="submit" style="background: #2b5948; color: #fff; padding: 10px 18px; border: none; border-radius: 5px; font-weight: 600; cursor: pointer;">
            Save &amp; Test Connection
          </button>
        </form>
      </div>

      <div class="guide-box">
        <strong>StackCP Checklist:</strong>
        <ol>
          <li>In your StackCP <strong>Manage MySQL Databases</strong>:
            <br>Verify your <strong>Database Server Host</strong> is <code>sdb-81.hosting.stackcp.net</code>.
          </li>
          <li>For user <strong><?= htmlspecialchars($activeUser) ?></strong>:
            <br>If it says <em>"Last Changed: Never"</em>, enter a password in the <strong>New password</strong> box and click the green <strong>Save</strong> button.
          </li>
          <li>Ensure <strong><?= htmlspecialchars($activeUser) ?></strong> is granted permissions on database <strong><?= htmlspecialchars($activeName) ?></strong>.</li>
        </ol>
      </div>
    <?php endif; ?>

    <?php if (!$installed): ?>
      <form method="POST">
        <input type="hidden" name="action" value="install">
        <label style="display: flex; gap: 10px; align-items: flex-start; margin-bottom: 15px; font-size: 13.5px; color: #333; cursor: pointer; background: #fff; padding: 12px 14px; border: 1px solid #d4ded9; border-radius: 6px;">
          <input type="checkbox" name="reset_tables" value="1" checked style="margin-top: 3px;">
          <div>
            <strong>Clean Fresh Installation (Recommended)</strong>
            <div style="font-size: 12px; color: #777; margin-top: 2px;">
              Replaces existing broken/outdated schema tables and seeds all 13 authentic dry fruits products, categories, pack sizes (250g, 500g, 1kg), coupons, and shipping rules fresh.
            </div>
          </div>
        </label>
        <button type="submit" class="btn-install" <?= !$mysqlConnected ? 'disabled' : '' ?>>
          <?= $mysqlConnected ? 'Initialize Database &amp; Seed Catalog' : 'Connect MySQL to Continue' ?>
        </button>
      </form>
    <?php endif; ?>
  </div>
</body>
</html>
