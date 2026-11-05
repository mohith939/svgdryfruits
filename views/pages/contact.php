<?php
/**
 * SVG Dry Fruits - Contact Page View
 */
declare(strict_types=1);
require_once ROOT_PATH . '/src/Helpers.php';

require_once ROOT_PATH . '/src/Models.php';

$settings = Models::getStoreSettings();
$phone = $settings['contact_phone'] ?? WHATSAPP_NUMBER;
$wa = preg_replace('/\D/', '', (string)($settings['whatsapp_number'] ?? WHATSAPP_NUMBER));
$email = $settings['contact_email'] ?? CONTACT_EMAIL;
$address = !empty($settings['store_address']) ? $settings['store_address'] : "SVG Dry Fruits\nSpecialist in Premium Dry Fruits, Almonds, Cashews, Walnuts, Dates & Nutrient Seeds.\nDirect sourcing from California, Afghanistan, Kashmir, and coastal groves.";

$pageTitle = 'Contact Us';
$activePage = 'contact';

require __DIR__ . '/../layouts/header.php';
?>

<div style="max-width:860px; margin:50px auto 80px; padding:0 20px;">
  <div style="text-align:center; margin-bottom:40px;">
    <p style="font-size:11px; letter-spacing:0.25em; text-transform:uppercase; color:var(--clay); font-weight:700; margin-bottom:8px;">WE ARE HERE TO HELP</p>
    <h1 style="font-family:var(--serif, serif); font-size:42px; color:var(--pine); margin:0 0 14px 0;">Get In Touch</h1>
    <p style="font-size:15px; color:var(--muted); max-width:520px; margin:0 auto;">
      Have questions about pack sizes, corporate gifting, or wholesale orders? Message our team directly.
    </p>
  </div>

  <div style="background:var(--white); border:1px solid var(--line); border-radius:12px; padding:40px; box-shadow: 0 4px 20px rgba(0,0,0,0.03);">
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:20px; margin-bottom:35px;">
      <!-- WhatsApp Support Card -->
      <div style="background:var(--paper); padding:24px; border-radius:10px; border: 1px solid var(--line);">
        <h4 style="font-family:var(--serif, serif); font-size:20px; color:var(--pine); margin:0 0 8px 0;">💬 WhatsApp Chat</h4>
        <p style="font-size:13px; color:var(--muted); margin:0 0 14px 0;">Instant answers for orders &amp; delivery queries.</p>
        <a href="https://wa.me/<?= $wa ?>" target="_blank" rel="noopener" style="color:#25D366; font-weight:700; font-size:15px; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
          +<?= htmlspecialchars($settings['whatsapp_number']) ?> &rarr;
        </a>
      </div>

      <!-- Phone Support Card -->
      <div style="background:var(--paper); padding:24px; border-radius:10px; border: 1px solid var(--line);">
        <h4 style="font-family:var(--serif, serif); font-size:20px; color:var(--pine); margin:0 0 8px 0;">📞 Phone Call</h4>
        <p style="font-size:13px; color:var(--muted); margin:0 0 14px 0;">Speak directly to our warehouse coordinator.</p>
        <a href="tel:+<?= preg_replace('/\D/', '', $phone) ?>" style="color:var(--pine); font-weight:700; font-size:15px; text-decoration: none;">
          +<?= htmlspecialchars($phone) ?> &rarr;
        </a>
      </div>

      <!-- Email Support Card -->
      <div style="background:var(--paper); padding:24px; border-radius:10px; border: 1px solid var(--line);">
        <h4 style="font-family:var(--serif, serif); font-size:20px; color:var(--pine); margin:0 0 8px 0;">✉️ Email Support</h4>
        <p style="font-size:13px; color:var(--muted); margin:0 0 14px 0;">For bulk business orders or order assistance.</p>
        <a href="mailto:<?= htmlspecialchars($email) ?>" style="color:var(--clay); font-weight:700; font-size:15px; text-decoration: none; word-break: break-all;">
          <?= htmlspecialchars($email) ?> &rarr;
        </a>
      </div>
    </div>

    <div style="font-size:14px; line-height:1.7; color:var(--ink); border-top: 1px solid var(--line); padding-top: 26px;">
      <h3 style="font-family:var(--serif, serif); font-size:22px; color:var(--pine); margin:0 0 10px 0;">Dispatch &amp; Warehouse Location</h3>
      <p style="margin:0 0 10px 0; white-space: pre-line;">
        <?= htmlspecialchars($address) ?>
      </p>
      <p style="color:var(--muted); font-size:13px; margin: 0;">
        Operating Hours: Monday – Saturday, 9:00 AM – 7:00 PM IST.
      </p>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
