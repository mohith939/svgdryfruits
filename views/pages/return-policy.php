<?php
/**
 * SVG Dry Fruits - Returns & Refunds Policy
 */
declare(strict_types=1);
require_once ROOT_PATH . '/src/Helpers.php';
$pageTitle = 'Return & Refund Policy';
require __DIR__ . '/../layouts/header.php';
?>
<div style="max-width:800px; margin:50px auto 80px; padding:0 20px;">
  <h1 style="font-family:var(--serif, serif); font-size:42px; color:var(--pine); margin:0 0 24px 0;">Return &amp; Refund Policy</h1>
  <div style="background:var(--white); border:1px solid var(--line); border-radius:8px; padding:40px; font-size:15px; line-height:1.8; color:var(--ink);">
    <h3 style="font-family:var(--serif, serif); font-size:22px; color:var(--pine); margin-top:0;">Perishable Food Item Assurance</h3>
    <p>Because dry fruits and nuts are edible, perishable food products, we do not accept returns once a package has been opened or unsealed, in accordance with food hygiene standards.</p>

    <h3 style="font-family:var(--serif, serif); font-size:22px; color:var(--pine);">Damaged in Transit / Quality Issues</h3>
    <p>If your package arrives physically damaged, punctured, or with incorrect items, please contact us within <strong>48 hours of delivery</strong> via WhatsApp or email with photos of the package. We will immediately arrange a replacement or full refund.</p>

    <h3 style="font-family:var(--serif, serif); font-size:22px; color:var(--pine);">Refund Processing</h3>
    <p>Approved refunds for online payments are credited directly back to the original payment source (UPI / Bank Account / Card) via Cashfree within 5-7 business days.</p>
  </div>
</div>
<?php require __DIR__ . '/../layouts/footer.php'; ?>
