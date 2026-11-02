<?php
/**
 * SVG Dry Fruits - Privacy Policy
 */
declare(strict_types=1);
require_once ROOT_PATH . '/src/Helpers.php';
$pageTitle = 'Privacy Policy';
require __DIR__ . '/../layouts/header.php';
?>
<div style="max-width:800px; margin:50px auto 80px; padding:0 20px;">
  <h1 style="font-family:var(--serif, serif); font-size:42px; color:var(--pine); margin:0 0 24px 0;">Privacy Policy</h1>
  <div style="background:var(--white); border:1px solid var(--line); border-radius:8px; padding:40px; font-size:15px; line-height:1.8; color:var(--ink);">
    <h3 style="font-family:var(--serif, serif); font-size:22px; color:var(--pine); margin-top:0;">Information We Collect</h3>
    <p>When you place an order on SVG Dry Fruits, we collect your name, shipping address, mobile phone number, and email solely to fulfill your delivery, process payments, and provide tracking alerts.</p>

    <h3 style="font-family:var(--serif, serif); font-size:22px; color:var(--pine);">Payment Security</h3>
    <p>We do not collect, view, or store credit card details or UPI PINs. All payment transactions are encrypted and processed by RBI-licensed payment aggregator <strong>Cashfree Payments</strong>.</p>

    <h3 style="font-family:var(--serif, serif); font-size:22px; color:var(--pine);">No Sharing of Data</h3>
    <p>We never sell, rent, or trade your personal information with third-party marketers. Your data is shared only with our logistics partners strictly to fulfill physical delivery.</p>
  </div>
</div>
<?php require __DIR__ . '/../layouts/footer.php'; ?>
