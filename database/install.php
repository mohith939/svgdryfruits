<?php
/**
 * SVG Dry Fruits - 1-Click Database Installer & Seeder
 * Run this by visiting: yourdomain.com/database/install.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$message = '';
$error = '';
$installed = false;

// 1. Live MySQL Connection Test
$mysqlConnected = false;
$mysqlError = '';
$isPlaceholder = (
    strpos(DB_NAME, 'cpaneluser') !== false ||
    strpos(DB_USER, 'cpaneluser') !== false ||
    DB_PASS === 'YourDatabasePasswordHere' ||
    DB_PASS === ''
);

$pdo = null;
try {
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);
    $multi = defined('PDO::MYSQL_ATTR_MULTI_STATEMENTS') ? PDO::MYSQL_ATTR_MULTI_STATEMENTS : 1000;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        $multi                       => true
    ]);
    $mysqlConnected = true;
} catch (Throwable $e) {
    $mysqlError = $e->getMessage();
}

// 2. Handle 1-Click Installation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$mysqlConnected || !$pdo) {
        $error = "Cannot install: MySQL connection failed. " . $mysqlError;
    } else {
        try {
            // Run Schema
            $schemaSql = file_get_contents(__DIR__ . '/schema.sql');
            $pdo->exec($schemaSql);

            // Run Seed
            $seedSql = file_get_contents(__DIR__ . '/seed.sql');
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

            $installed = true;
            $message = "MySQL database successfully initialized and catalog seeded with categories, pack size variants, coupons, shipping settings, and admin login!";
        } catch (Throwable $e) {
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

    <?php if ($isPlaceholder && !$installed): ?>
      <div class="alert-warning">
        <strong>⚠️ Placeholder Credentials Detected:</strong><br>
        Your <code>.env</code> file currently contains the example placeholders:
        <code>cpaneluser_svgdryfruits</code> or <code>YourDatabasePasswordHere</code>.<br>
        In cPanel, your database name and username must start with your <strong>actual cPanel account username</strong> (e.g. <code>svgdryfr_...</code>).
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
        <td style="color: #777;">Target Database</td>
        <td><code><?= htmlspecialchars(DB_NAME) ?></code></td>
      </tr>
      <tr>
        <td style="color: #777;">Database User</td>
        <td><code><?= htmlspecialchars(DB_USER) ?></code></td>
      </tr>
      <tr>
        <td style="color: #777;">MySQL Host</td>
        <td><code><?= htmlspecialchars(DB_HOST) ?>:<?= DB_PORT ?></code></td>
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
      <div class="guide-box">
        <strong>How to fix this in Sunlight Host cPanel:</strong>
        <ol>
          <li>Open your <strong>cPanel</strong> dashboard.</li>
          <li>Under <strong>Databases</strong>, click <strong>MySQL® Databases</strong>.</li>
          <li>Look at <strong>Current Databases</strong>: your actual database name looks like <code>prefix_svgdryfruits</code> (where <code>prefix</code> is your cPanel username, NOT <code>cpaneluser</code>).</li>
          <li>Under <strong>Current Users</strong>: find or create your database user (e.g. <code>prefix_svguser</code>).</li>
          <li>Under <strong>Add User To Database</strong>: select that user and database, click <strong>Add</strong>, and check <strong>ALL PRIVILEGES</strong>.</li>
          <li>Open cPanel <strong>File Manager</strong> &rarr; <code>public_html/.env</code>, and enter your actual values:
            <pre style="background: #fff; padding: 10px; border-radius: 4px; margin: 8px 0; font-size: 12px; border: 1px solid #e1e8ed;">DB_HOST=localhost
DB_PORT=3306
DB_NAME=yourprefix_svgdryfruits
DB_USER=yourprefix_svguser
DB_PASS=YourActualPasswordSetInCpanel</pre>
          </li>
          <li>Save the <code>.env</code> file and refresh this page!</li>
        </ol>
      </div>
    <?php endif; ?>

    <?php if (!$installed): ?>
      <form method="POST">
        <button type="submit" class="btn-install" <?= !$mysqlConnected ? 'disabled' : '' ?>>
          <?= $mysqlConnected ? 'Initialize Database &amp; Seed Catalog' : 'Fix .env Credentials to Continue' ?>
        </button>
      </form>
    <?php endif; ?>
  </div>
</body>
</html>
