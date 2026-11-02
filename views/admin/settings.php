<?php
/**
 * SVG Dry Fruits - Store & Shipping Settings
 * 1:1 match of Node.js src/app/admin/settings/page.tsx
 */
declare(strict_types=1);
require_once ROOT_PATH . '/src/Helpers.php';
require_once ROOT_PATH . '/src/Models.php';

$pdo = Database::getConnection();

// Handle saving shipping settings
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
    Helpers::redirect(Helpers::url('admin/settings'));
}

$shipping = Models::getShippingSettings();
$flatINR = round($shipping['flat_charge_paise'] / 100);
$freeINR = $shipping['free_threshold_paise'] ? round($shipping['free_threshold_paise'] / 100) : '';

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

    <!-- Integration & Environment Status Box -->
    <div class="admin-card">
      <h2>Integration &amp; Environment Status</h2>
      <div class="admin-details-grid" style="display: flex; flex-direction: column; gap: 16px;">
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
          <span class="admin-detail-label" style="display: block; font-size: 11px; text-transform: uppercase; color: #7C7265; font-weight: 700; margin-bottom: 4px;">Support WhatsApp Number</span>
          <span>+91 <?= htmlspecialchars(WHATSAPP_NUMBER) ?></span>
        </div>
        <div>
          <span class="admin-detail-label" style="display: block; font-size: 11px; text-transform: uppercase; color: #7C7265; font-weight: 700; margin-bottom: 4px;">Contact Email</span>
          <span><?= htmlspecialchars(CONTACT_EMAIL) ?></span>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../layouts/admin_layout_bottom.php'; ?>
