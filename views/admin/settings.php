<?php
/**
 * SVG Dry Fruits - Store & Shipping Settings
 * 1:1 match of Node.js src/app/admin/settings/page.tsx
 */
declare(strict_types=1);
require_once ROOT_PATH . '/src/Helpers.php';
require_once ROOT_PATH . '/src/Models.php';

$pdo = Database::getConnection();

// Handle saving settings
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

  <div class="admin-two-cols">
    <!-- Shipping Rules Form -->
    <div class="admin-card">
      <h2>Shipping Rules</h2>
      <form class="admin-form" method="POST">
        <div class="form-checkbox-row" style="margin-bottom: 12px;">
          <label class="checkbox-label" style="display: flex; gap: 10px; align-items: center; cursor: pointer;">
            <input
              type="checkbox"
              name="active"
              value="1"
              <?= !empty($shipping['active']) ? 'checked' : '' ?>
            >
            <span><strong>Enable shipping charge calculation</strong></span>
          </label>
        </div>
        <p class="admin-card-subtitle">
          When disabled, shipping is calculated as Free for all orders.
        </p>

        <div class="form-group">
          <label for="flat">Standard Flat Delivery Fee (₹)</label>
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
            placeholder="e.g. 999 (Orders above this qualify for free shipping)"
          >
          <span class="field-hint" style="display: block; font-size: 12px; color: #7C7265; margin-top: 4px;">
            Orders with subtotal above this amount get free shipping automatically, and a progress bar appears in the customer bag.
          </span>
        </div>

        <button type="submit" class="button button-primary">
          Save Shipping Rules
        </button>
      </form>
    </div>

    <!-- Store Contact & Support Settings Card -->
    <div class="admin-card">
      <h2>Store Contact &amp; Support Details</h2>
      <p class="admin-card-subtitle">
        Manage customer support mobile, WhatsApp number, email, and dispatch address displayed on the <a href="<?= Helpers::url('contact') ?>" target="_blank" style="color: #073D26; font-weight: 600;">/contact</a> page and site footer.
      </p>

      <form class="admin-form" method="POST">
        <input type="hidden" name="action" value="save_contact">

        <div class="form-group">
          <label for="contact_phone">Customer Care Mobile / Phone</label>
          <input
            id="contact_phone"
            type="text"
            name="contact_phone"
            value="<?= htmlspecialchars($settings['contact_phone']) ?>"
            placeholder="e.g. 919059123456"
            required
          >
          <span class="field-hint" style="display: block; font-size: 12px; color: #7C7265; margin-top: 4px;">
            Displayed on Contact page for customer calls &amp; inquiries.
          </span>
        </div>

        <div class="form-group">
          <label for="whatsapp_number">Official WhatsApp Number (with country code)</label>
          <input
            id="whatsapp_number"
            type="text"
            name="whatsapp_number"
            value="<?= htmlspecialchars($settings['whatsapp_number']) ?>"
            placeholder="e.g. 919059123456"
            required
          >
          <span class="field-hint" style="display: block; font-size: 12px; color: #7C7265; margin-top: 4px;">
            Powers the bottom-right floating WhatsApp chat button and instant order support links.
          </span>
        </div>

        <div class="form-group">
          <label for="contact_email">Support &amp; Inquiries Email Address</label>
          <input
            id="contact_email"
            type="email"
            name="contact_email"
            value="<?= htmlspecialchars($settings['contact_email']) ?>"
            placeholder="svgdryfruits@gmail.com"
            required
          >
        </div>

        <div class="form-group">
          <label for="store_address">Warehouse / Dispatch Address &amp; Operating Hours</label>
          <textarea
            id="store_address"
            name="store_address"
            rows="3"
            placeholder="Address shown on Contact page"
          ><?= htmlspecialchars($settings['store_address']) ?></textarea>
        </div>

        <button type="submit" class="button button-primary">
          Save Contact Information
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
