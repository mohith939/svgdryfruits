<?php
/**
 * SVG Dry Fruits - Contact Page View
 */
declare(strict_types=1);
require_once ROOT_PATH . '/src/Helpers.php';

$pageTitle = 'Contact Us';
$activePage = 'contact';

require __DIR__ . '/../layouts/header.php';
?>

<div style="max-width:800px; margin:50px auto 80px; padding:0 20px;">
  <div style="text-align:center; margin-bottom:40px;">
    <p style="font-size:11px; letter-spacing:0.25em; text-transform:uppercase; color:var(--clay); font-weight:700; margin-bottom:8px;">WE ARE HERE TO HELP</p>
    <h1 style="font-family:var(--serif, serif); font-size:42px; color:var(--pine); margin:0 0 14px 0;">Get In Touch</h1>
    <p style="font-size:15px; color:var(--muted); max-width:500px; margin:0 auto;">
      Have questions about pack sizes, corporate gifting, or wholesale orders? Message our team directly.
    </p>
  </div>

  <div style="background:var(--white); border:1px solid var(--line); border-radius:8px; padding:40px;">
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:30px; margin-bottom:35px;">
      <div style="background:var(--paper); padding:20px; border-radius:6px;">
        <h4 style="font-family:var(--serif, serif); font-size:20px; color:var(--pine); margin:0 0 8px 0;">WhatsApp Support</h4>
        <p style="font-size:13px; color:var(--muted); margin:0 0 12px 0;">Instant answers for orders &amp; delivery queries.</p>
        <a href="https://wa.me/<?= WHATSAPP_NUMBER ?>" target="_blank" rel="noopener" style="color:#25D366; font-weight:700; font-size:16px;">
          +<?= WHATSAPP_NUMBER ?> &rarr;
        </a>
      </div>

      <div style="background:var(--paper); padding:20px; border-radius:6px;">
        <h4 style="font-family:var(--serif, serif); font-size:20px; color:var(--pine); margin:0 0 8px 0;">Email Support</h4>
        <p style="font-size:13px; color:var(--muted); margin:0 0 12px 0;">For bulk business orders or order assistance.</p>
        <a href="mailto:<?= CONTACT_EMAIL ?>" style="color:var(--clay); font-weight:700; font-size:15px;">
          <?= CONTACT_EMAIL ?> &rarr;
        </a>
      </div>
    </div>

    <div style="font-size:14px; line-height:1.7; color:var(--ink);">
      <h3 style="font-family:var(--serif, serif); font-size:24px; color:var(--pine); margin:0 0 10px 0;">Dispatch &amp; Warehouse Location</h3>
      <p style="margin:0 0 10px 0;">
        <strong>SVG Dry Fruits</strong><br>
        Specialist in Premium Dry Fruits, Almonds, Cashews, Walnuts, Dates &amp; Nutrient Seeds.<br>
        Direct sourcing from California, Afghanistan, Kashmir, and coastal groves.
      </p>
      <p style="color:var(--muted); font-size:13px;">
        Operating Hours: Monday – Saturday, 9:00 AM – 7:00 PM IST.
      </p>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
