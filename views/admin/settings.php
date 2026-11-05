<?php
/**
 * SVG Dry Fruits - Store & Shipping Settings
 * 1:1 match of Node.js src/app/admin/settings/page.tsx
 */
declare(strict_types=1);
require_once ROOT_PATH . '/src/Helpers.php';
require_once ROOT_PATH . '/src/Models.php';
require_once ROOT_PATH . '/src/Auth.php';

Auth::requireAuth();

$pdo = Database::getConnection();

// Handle saving settings
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Helpers::requireCsrf();
    $action = $_POST['action'] ?? 'save_shipping';

    if ($action === 'save_contact') {
        Models::saveStoreSettings([
            'contact_phone' => trim($_POST['contact_phone'] ?? ''),
            'contact_email' => trim($_POST['contact_email'] ?? ''),
            'whatsapp_number' => trim($_POST['whatsapp_number'] ?? ''),
            'store_address' => trim($_POST['store_address'] ?? ''),
        ]);
        Helpers::setFlash('success', 'Store contact information updated successfully! Changes are live on /contact and the footer.');
    } else {
        $active = isset($_POST['active']) ? 1 : 0;
        $flatINR = (float)($_POST['flat_charge'] ?? 0);
        $freeINR = trim($_POST['free_threshold'] ?? '');

        $flatPaise = (int)round($flatINR * 100);
        $freePaise = ($freeINR !== '') ? (int)round((float)$freeINR * 100) : null;

        $stmt = $pdo->prepare("
            UPDATE shipping_settings 
            SET flat_charge_paise = ?, free_threshold_paise = ?, active = ?
            WHERE id = 1
        ");
        $stmt->execute([$flatPaise, $freePaise, $active]);
        Helpers::setFlash('success', 'Shipping rules saved successfully.');
    }

    Helpers::redirect(Helpers::url('admin/settings'));
}

$settings = Models::getStoreSettings();
$flatINR = round($settings['flat_charge_paise'] / 100);
$freeINR = $settings['free_threshold_paise'] ? round($settings['free_threshold_paise'] / 100) : '';

$cashfreeConfigured = !empty(CASHFREE_APP_ID) && !empty(CASHFREE_SECRET_KEY);

$pageTitle = 'Store & Shipping Settings';
$activeNav = 'settings';
require __DIR__ . '/../layouts/admin_layout.php';
?>

<div class="admin-content">
  <div class="admin-page-header">
    <div>
      <div class="breadcrumbs" style="font-size: 13px; color: #8A7E6F; margin-bottom: 6px;">
        <a href="<?= Helpers::url('admin') ?>">Dashboard</a>
        <span aria-hidden="true">/</span>
        <span>Settings</span>
      </div>
      <h1>Store &amp; <em>Shipping Settings</em></h1>
      <p>Configure delivery fees, free-shipping thresholds, and inspect environment integration.</p>
    </div>
  </div>

  <!-- Prominent Multi-Option Shipping Banner -->
  <div style="background: linear-gradient(135deg, #073D26 0%, #0F5C3B 100%); color: #fff; padding: 18px 24px; border-radius: 12px; margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px; box-shadow: 0 4px 14px rgba(7,61,38,0.2);">
    <div>
      <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 4px;">
        <span style="font-size: 22px;">🚚</span>
        <strong style="font-size: 16.5px; letter-spacing: -0.2px;">Multi-Condition Shipping &amp; Delivery Rates</strong>
        <span style="background: #E8B647; color: #073D26; font-size: 11px; font-weight: 800; padding: 2px 8px; border-radius: 6px;">NEW CRITERIA ENGINE</span>
      </div>
      <p style="margin: 0; font-size: 13.5px; color: rgba(255,255,255,0.85); max-width: 650px;">
        Configure multiple courier delivery tiers (Standard, Express, Same-Day) with custom conditions: free-shipping thresholds, minimum basket subtotal, delivery days, and active status — selectable by customers at checkout just like coupons!
      </p>
    </div>
    <a href="<?= Helpers::url('admin/shipping') ?>" class="button" style="background: #FFFFFF; color: #073D26; font-weight: 800; font-size: 13.5px; padding: 11px 22px; border-radius: 8px; text-decoration: none; box-shadow: 0 2px 8px rgba(0,0,0,0.2); white-space: nowrap;">
      Configure Multi-Option Rates &rarr;
    </a>
  </div>

  <div class="admin-two-cols">
    <!-- Store Contact & Support Settings Card (PRIMARY) -->
    <div class="admin-card" id="contact" style="grid-column: 1 / -1; border-top: 4px solid #073D26;">
      <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 8px;">
        <h2 style="margin: 0; font-size: 20px; color: #073D26; display: flex; align-items: center; gap: 8px;">
          <span>📞 Store Contact &amp; Support Details</span>
        </h2>
        <span class="badge badge-success" style="font-size: 11.5px; padding: 4px 10px;">
          ✓ Live on /contact Page &amp; Storefront Footer
        </span>
      </div>
      <p class="admin-card-subtitle" style="margin-bottom: 20px;">
        Update your store phone number, official WhatsApp, customer support email, and warehouse dispatch address. All changes take effect immediately on <a href="<?= Helpers::url('contact') ?>" target="_blank" style="color: #073D26; font-weight: 700; text-decoration: underline;">/contact ↗</a> and throughout the site footer.
      </p>

      <form class="admin-form" method="POST">
        <?= Helpers::csrfField() ?>
        <input type="hidden" name="action" value="save_contact">

        <div class="form-group-row">
          <div class="form-group" style="flex: 1;">
            <label for="contact_phone" style="font-weight: 700; color: #073D26;">Customer Care Mobile / Calling Number *</label>
            <input
              id="contact_phone"
              type="text"
              name="contact_phone"
              value="<?= htmlspecialchars($settings['contact_phone']) ?>"
              placeholder="e.g. 919059123456"
              required
              style="font-size: 14.5px; font-weight: 600;"
            >
            <span class="field-hint" style="display: block; font-size: 12px; color: #7C7265; margin-top: 4px;">
              Displayed on Contact page for customer voice calls &amp; inquiries.
            </span>
          </div>

          <div class="form-group" style="flex: 1;">
            <label for="whatsapp_number" style="font-weight: 700; color: #073D26;">Official WhatsApp Support Number (with country code) *</label>
            <input
              id="whatsapp_number"
              type="text"
              name="whatsapp_number"
              value="<?= htmlspecialchars($settings['whatsapp_number']) ?>"
              placeholder="e.g. 919059123456"
              required
              style="font-size: 14.5px; font-weight: 600;"
            >
            <span class="field-hint" style="display: block; font-size: 12px; color: #7C7265; margin-top: 4px;">
              Powers the floating WhatsApp button and order support links.
            </span>
          </div>
        </div>

        <div class="form-group">
          <label for="contact_email" style="font-weight: 700; color: #073D26;">Official Customer Inquiries &amp; Support Email *</label>
          <input
            id="contact_email"
            type="email"
            name="contact_email"
            value="<?= htmlspecialchars($settings['contact_email']) ?>"
            placeholder="svgdryfruits@gmail.com"
            required
            style="font-size: 14.5px; font-weight: 600;"
          >
          <span class="field-hint" style="display: block; font-size: 12px; color: #7C7265; margin-top: 4px;">
            Customers use this email to write to customer service and order tracking inquiries.
          </span>
        </div>

        <div class="form-group">
          <label for="store_address" style="font-weight: 700; color: #073D26;">Store Dispatch &amp; Warehouse Address</label>
          <textarea
            id="store_address"
            name="store_address"
            rows="3"
            placeholder="Address shown on Contact page"
            style="font-size: 14px;"
          ><?= htmlspecialchars($settings['store_address']) ?></textarea>
        </div>

        <button type="submit" class="button" style="background: #073D26; color: #FFFFFF; font-weight: 800; font-size: 14px; padding: 12px 28px; border-radius: 8px; border: none; cursor: pointer; box-shadow: 0 2px 8px rgba(7,61,38,0.25);">
          💾 Save Store Contact Details
        </button>
      </form>
    </div>

    <!-- Default Fallback Shipping Rules Form -->
    <div class="admin-card">
      <h2>Default Shipping Fallback</h2>
      <p class="admin-card-subtitle">
        Standard baseline fee used if no multi-condition options are configured.
      </p>
      <form class="admin-form" method="POST">
        <?= Helpers::csrfField() ?>
        <div class="form-checkbox-row" style="margin-bottom: 12px;">
          <label class="checkbox-label" style="display: flex; gap: 10px; align-items: center; cursor: pointer;">
            <input
              type="checkbox"
              name="active"
              value="1"
              <?= !empty($settings['shipping_active']) ? 'checked' : '' ?>
            >
            <span><strong>Enable default shipping fee calculation</strong></span>
          </label>
        </div>

        <div class="form-group">
          <label for="flat">Fallback Delivery Fee (₹)</label>
          <input
            id="flat"
            type="number"
            min="0"
            name="flat_charge"
            value="<?= $flatINR ?>"
          >
        </div>

        <div class="form-group">
          <label for="free">Free Shipping Threshold (₹, optional)</label>
          <input
            id="free"
            type="number"
            min="0"
            name="free_threshold"
            value="<?= $freeINR ?>"
            placeholder="e.g. 999"
          >
        </div>

        <button type="submit" class="button button-outline" style="border-color: #073D26; color: #073D26; font-weight: 700;">
          Save Fallback Rules
        </button>
      </form>
    </div>

    <!-- Integration & Environment Status Box -->
    <div class="admin-card" style="grid-column: 1 / -1;">
      <h2>Integration &amp; Environment Status</h2>
      <div class="admin-details-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px;">
        <div>
          <span class="admin-detail-label" style="display: block; font-size: 11px; text-transform: uppercase; color: #7C7265; font-weight: 700; margin-bottom: 4px;">Cashfree Payments Gateway</span>
          <span class="badge <?= $cashfreeConfigured ? 'badge-success' : 'badge-warning' ?>">
            <?= $cashfreeConfigured ? 'Sandbox / Production Active' : 'Simulated Dev Mode' ?>
          </span>
        </div>
        <div>
          <span class="admin-detail-label" style="display: block; font-size: 11px; text-transform: uppercase; color: #7C7265; font-weight: 700; margin-bottom: 4px;">Storefront Base URL</span>
          <code><?= htmlspecialchars(APP_URL) ?></code>
        </div>
        <div>
          <span class="admin-detail-label" style="display: block; font-size: 11px; text-transform: uppercase; color: #7C7265; font-weight: 700; margin-bottom: 4px;">Database Connection</span>
          <span class="badge badge-success"><?= strtoupper(Database::getDriver()) ?> Connected</span>
        </div>
        <div>
          <span class="admin-detail-label" style="display: block; font-size: 11px; text-transform: uppercase; color: #7C7265; font-weight: 700; margin-bottom: 4px;">Active Support WhatsApp</span>
          <a href="https://wa.me/<?= preg_replace('/\D/', '', $settings['whatsapp_number']) ?>" target="_blank" style="color: #25D366; font-weight: 600;">
            +<?= htmlspecialchars($settings['whatsapp_number']) ?> &rarr;
          </a>
        </div>
        <div>
          <span class="admin-detail-label" style="display: block; font-size: 11px; text-transform: uppercase; color: #7C7265; font-weight: 700; margin-bottom: 4px;">Active Contact Email</span>
          <a href="mailto:<?= htmlspecialchars($settings['contact_email']) ?>" style="color: #073D26; font-weight: 600;">
            <?= htmlspecialchars($settings['contact_email']) ?>
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../layouts/admin_layout_bottom.php'; ?>
