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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = Database::getConnection();

        // 1. Run Schema
        $schemaSql = file_get_contents(__DIR__ . '/schema.sql');
        $pdo->exec($schemaSql);

        // 2. Run Seed
        $seedSql = file_get_contents(__DIR__ . '/seed.sql');
        $pdo->exec($seedSql);

        // 3. Ensure Admin account password hash is correct
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
        $message = "Database tables created and catalog successfully seeded with 14+ authentic dry fruits, pack variants, and admin account!";
    } catch (Throwable $e) {
        $error = $e->getMessage();
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
      max-width: 620px;
      margin: 60px auto;
      padding: 40px;
      background: #fbfaf7;
      border: 1px solid #dfdbd1;
      border-radius: 8px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.04);
    }
    .btn-install {
      display: inline-block;
      width: 100%;
      background: var(--pine, #203c32);
      color: #fff;
      padding: 14px 20px;
      border: none;
      border-radius: 4px;
      font-size: 16px;
      font-weight: 600;
      cursor: pointer;
      margin-top: 20px;
    }
    .alert-success {
      background: #e6f4ea;
      color: #137333;
      padding: 16px;
      border-radius: 4px;
      margin-bottom: 20px;
    }
    .alert-error {
      background: #fce8e6;
      color: #c5221f;
      padding: 16px;
      border-radius: 4px;
      margin-bottom: 20px;
    }
  </style>
</head>
<body>
  <div class="install-box">
    <h2 style="font-family: var(--serif, serif); font-size: 32px; color: var(--pine, #203c32); margin-top: 0;">
      SVG Dry Fruits — Database Setup
    </h2>
    <p style="color: #666; font-size: 14px;">
      This utility initializes the MySQL database tables, seeds all categories, pack sizes (250g, 500g, 1kg), and sets up the admin credentials.
    </p>

    <?php if ($message): ?>
      <div class="alert-success">
        <strong>Success:</strong> <?= htmlspecialchars($message) ?>
        <p style="margin: 12px 0 0 0;">
          <a href="../" style="font-weight: 600; text-decoration: underline;">&rarr; Go to Storefront</a> &nbsp;|&nbsp;
          <a href="../admin/login" style="font-weight: 600; text-decoration: underline;">Go to Admin Login &rarr;</a>
        </p>
      </div>
    <?php endif; ?>

    <?php if ($error): ?>
      <div class="alert-error">
        <strong>Setup Error:</strong> <?= htmlspecialchars($error) ?>
        <p style="margin: 8px 0 0 0; font-size: 13px;">
          Make sure your MySQL credentials in <code>config/config.php</code> or <code>.env</code> match your cPanel database details.
        </p>
      </div>
    <?php endif; ?>

    <div style="background: #f5f2eb; padding: 15px; border-radius: 4px; font-size: 13px; margin: 20px 0;">
      <strong>Target Database:</strong> <?= htmlspecialchars(DB_NAME) ?><br>
      <strong>Host:</strong> <?= htmlspecialchars(DB_HOST) ?>:<?= DB_PORT ?><br>
      <strong>Admin Email:</strong> <?= htmlspecialchars(ADMIN_EMAIL) ?>
    </div>

    <?php if (!$installed): ?>
      <form method="POST">
        <button type="submit" class="btn-install">
          Initialize Database &amp; Seed Catalog
        </button>
      </form>
    <?php endif; ?>
  </div>
</body>
</html>
