<?php
/**
 * SVG Dry Fruits - Shipping Policy
 */
declare(strict_types=1);
require_once ROOT_PATH . '/src/Helpers.php';
$pageTitle = 'Shipping Policy';
require __DIR__ . '/../layouts/header.php';
?>
<div style="max-width:800px; margin:50px auto 80px; padding:0 20px;">
  <h1 style="font-family:var(--serif, serif); font-size:42px; color:var(--pine); margin:0 0 24px 0;">Shipping &amp; Delivery Policy</h1>
  <div style="background:var(--white); border:1px solid var(--line); border-radius:8px; padding:40px; font-size:15px; line-height:1.8; color:var(--ink);">
    <h3 style="font-family:var(--serif, serif); font-size:22px; color:var(--pine); margin-top:0;">Pan-India Delivery</h3>
    <p>SVG Dry Fruits ships across all serviceable pin codes throughout India through reputed courier partners (BlueDart, Delhivery, DTDC, Xpressbees).</p>

    <h3 style="font-family:var(--serif, serif); font-size:22px; color:var(--pine);">Shipping Charges</h3>
    <p>
      &bull; <strong>Free Shipping:</strong> On all orders above ₹999.<br>
      &bull; <strong>Standard Flat Charge:</strong> A nominal ₹49 fee applies for orders below ₹999.
    </p>

    <h3 style="font-family:var(--serif, serif); font-size:22px; color:var(--pine);">Dispatch Timeline</h3>
    <p>Orders are packed in airtight, vacuum-sealed stand-up pouches and dispatched within 24 to 48 hours. Typical delivery takes 3 to 6 business days depending on destination.</p>

    <h3 style="font-family:var(--serif, serif); font-size:22px; color:var(--pine);">Tracking</h3>
    <p>Once dispatched, a live tracking number and courier details are updated on your order page. You can track your shipment anytime on our <a href="<?= Helpers::url('track') ?>" style="color:var(--clay); text-decoration:underline;">Track Order</a> page.</p>
  </div>
</div>
<?php require __DIR__ . '/../layouts/footer.php'; ?>
