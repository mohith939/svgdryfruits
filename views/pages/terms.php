<?php
/**
 * SVG Dry Fruits - Terms of Service
 */
declare(strict_types=1);
require_once ROOT_PATH . '/src/Helpers.php';
$pageTitle = 'Terms of Service';
require __DIR__ . '/../layouts/header.php';
?>
<div style="max-width:800px; margin:50px auto 80px; padding:0 20px;">
  <h1 style="font-family:var(--serif, serif); font-size:42px; color:var(--pine); margin:0 0 24px 0;">Terms &amp; Conditions</h1>
  <div style="background:var(--white); border:1px solid var(--line); border-radius:8px; padding:40px; font-size:15px; line-height:1.8; color:var(--ink);">
    <h3 style="font-family:var(--serif, serif); font-size:22px; color:var(--pine); margin-top:0;">Acceptance of Terms</h3>
    <p>By accessing or purchasing from SVG Dry Fruits, you agree to comply with and be bound by these terms, our shipping policy, and refund rules.</p>

    <h3 style="font-family:var(--serif, serif); font-size:22px; color:var(--pine);">Pricing &amp; Catalog Accuracy</h3>
    <p>All prices are listed in Indian Rupees (₹) and include applicable taxes. We strive for accurate product photography and descriptions; however, natural harvests may exhibit slight variations in kernel size, color shades, or seasonal crunch.</p>

    <h3 style="font-family:var(--serif, serif); font-size:22px; color:var(--pine);">Order Acceptance</h3>
    <p>We reserve the right to refuse or cancel any order in the event of unexpected stock depletion or logistical unserviceability, in which case a 100% refund will be issued immediately.</p>
  </div>
</div>
<?php require __DIR__ . '/../layouts/footer.php'; ?>
