<?php
/**
 * SVG Dry Fruits - Shadowfax Logistics & Store Settings
 * Full Shadowfax Developer Platform Integration & Free Shipping Management
 */
declare(strict_types=1);
require_once ROOT_PATH . '/src/Helpers.php';
require_once ROOT_PATH . '/src/Models.php';
require_once ROOT_PATH . '/src/Shadowfax.php';
require_once ROOT_PATH . '/src/Auth.php';

Auth::requireAuth();

$pdo = Database::getConnection();

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Helpers::requireCsrf();
    $action = $_POST['action'] ?? 'save_shadowfax';

    if ($action === 'save_shadowfax') {
        $data = [
            'api_key' => trim($_POST['api_key'] ?? ''),
            'environment' => strtolower(trim($_POST['environment'] ?? 'staging')),
            'base_url' => trim($_POST['base_url'] ?? ''),
            'pickup_name' => trim($_POST['pickup_name'] ?? 'SVG Dry Fruits'),
            'pickup_phone' => trim($_POST['pickup_phone'] ?? '918340956469'),
            'pickup_address' => trim($_POST['pickup_address'] ?? 'SVG Dry Fruits, Trunk Road'),
            'pickup_city' => trim($_POST['pickup_city'] ?? 'Kavali'),
            'pickup_state' => trim($_POST['pickup_state'] ?? 'Andhra Pradesh'),
            'pickup_pincode' => trim($_POST['pickup_pincode'] ?? '524201'),
        ];

        if (Shadowfax::saveConfig($data)) {
            Helpers::setFlash('success', '✓ Shadowfax Logistics configuration saved successfully.');
        } else {
            Helpers::setFlash('error', 'Failed to save Shadowfax settings.');
        }
        Helpers::redirect(Helpers::url('admin/settings'));
    } elseif ($action === 'save_contact') {
        Models::saveStoreSettings([
            'contact_phone' => trim($_POST['contact_phone'] ?? ''),
            'contact_email' => trim($_POST['contact_email'] ?? ''),
            'whatsapp_number' => trim($_POST['whatsapp_number'] ?? ''),
            'store_address' => trim($_POST['store_address'] ?? ''),
        ]);
        Helpers::setFlash('success', '✓ Store contact information updated successfully! Changes are live on /contact and the footer.');
        Helpers::redirect(Helpers::url('admin/settings'));
    } elseif ($action === 'test_connection') {
        $result = Shadowfax::testConnection();
        if ($result['success']) {
            Helpers::setFlash('success', $result['message']);
        } else {
            Helpers::setFlash('warning', $result['message']);
        }
        Helpers::redirect(Helpers::url('admin/settings'));
    } elseif ($action === 'check_pincode') {
        $testPin = trim($_POST['test_pincode'] ?? '');
        if ($testPin) {
            $result = Shadowfax::checkServiceability($testPin);
            if ($result['serviceable']) {
                Helpers::setFlash('success', "✓ PIN {$testPin} Serviceable: " . $result['message']);
            } else {
                Helpers::setFlash('warning', "PIN {$testPin} Check: " . $result['message']);
            }
        }
        Helpers::redirect(Helpers::url('admin/settings'));
    }
}

$sfxConfig = Shadowfax::getConfig();
$isConfigured = Shadowfax::isConfigured();
$settings = Models::getStoreSettings();
$cashfreeConfigured = !empty(CASHFREE_APP_ID) && !empty(CASHFREE_SECRET_KEY);

$pageTitle = 'Shadowfax Logistics & Store Settings';
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
      <h1>Shadowfax Logistics &amp; <em>Store Settings</em></h1>
      <p>Manage Shadowfax courier integration, API tokens, warehouse pickup coordinates, contact details, and platform diagnostics.</p>
    </div>
  </div>

  <div class="admin-two-cols">
    <!-- Left Column: Shadowfax Configuration -->
    <div class="admin-card">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; border-bottom:1px solid #EBE4DA; padding-bottom:12px;">
        <div>
          <h2 style="margin:0 0 4px; font-size:18px; color:#073D26;">🚚 Shadowfax Logistics Partner</h2>
          <span style="font-size:12px; color:#6B6255;">Forward delivery dispatch, automated waybill generation &amp; real-time tracking</span>
        </div>
        <span class="badge <?= $isConfigured ? ($sfxConfig['environment'] === 'production' ? 'badge-success' : 'badge-warning') : 'badge-neutral' ?>">
          <?= $isConfigured ? (strtoupper($sfxConfig['environment']) . ' ACTIVE') : 'SIMULATED MODE' ?>
        </span>
      </div>

      <form class="admin-form" method="POST">
        <?= Helpers::csrfField() ?>
        <input type="hidden" name="action" value="save_shadowfax">

        <div class="form-group">
          <label for="sfx_env">Operating Environment</label>
          <select id="sfx_env" name="environment" onchange="updateDefaultUrl(this.value)">
            <option value="staging" <?= $sfxConfig['environment'] === 'staging' ? 'selected' : '' ?>>
              Staging Sandbox (dale.staging.shadowfax.in)
            </option>
            <option value="production" <?= $sfxConfig['environment'] === 'production' ? 'selected' : '' ?>>
              Production Live (dale.shadowfax.in)
            </option>
          </select>
          <span class="field-hint" style="display:block; font-size:11.5px; color:#7C7265; margin-top:4px;">
            Staging allows safe testing and manifest simulation without sending real delivery vans.
          </span>
        </div>

        <div class="form-group">
          <label for="sfx_key">Shadowfax API Token</label>
          <input
            id="sfx_key"
            type="password"
            name="api_key"
            value="<?= htmlspecialchars($sfxConfig['api_key']) ?>"
            placeholder="e.g. 749a98c76bf... (from SFX 360 Partner Portal)"
            autocomplete="off"
          >
          <span class="field-hint" style="display:block; font-size:11.5px; color:#7C7265; margin-top:4px;">
            Obtain your static token from the <a href="https://shadowfax360.in/" target="_blank" rel="noreferrer" style="color:#073D26; text-decoration:underline; font-weight:600;">SFX 360 Partner Portal</a>.
          </span>
        </div>

        <div class="form-group">
          <label for="sfx_url">Gateway API Base URL</label>
          <input
            id="sfx_url"
            type="text"
            name="base_url"
            value="<?= htmlspecialchars($sfxConfig['base_url']) ?>"
            placeholder="https://dale.staging.shadowfax.in/api"
          >
        </div>

        <h3 style="font-size:14px; font-weight:700; color:#073D26; margin:20px 0 10px; border-top:1px solid #EBE4DA; padding-top:14px; text-transform:uppercase; letter-spacing:0.04em;">
          📦 Warehouse / Pickup Location
        </h3>

        <div class="form-group">
          <label for="sfx_pname">Warehouse / Store Name</label>
          <input
            id="sfx_pname"
            type="text"
            name="pickup_name"
            value="<?= htmlspecialchars($sfxConfig['pickup_name']) ?>"
            required
          >
        </div>

        <div class="form-group">
          <label for="sfx_pphone">Warehouse Dispatch Phone Number</label>
          <input
            id="sfx_pphone"
            type="text"
            name="pickup_phone"
            value="<?= htmlspecialchars($sfxConfig['pickup_phone']) ?>"
            required
            placeholder="10-digit mobile number"
          >
        </div>

        <div class="form-group">
          <label for="sfx_paddr">Pickup Street Address</label>
          <input
            id="sfx_paddr"
            type="text"
            name="pickup_address"
            value="<?= htmlspecialchars($sfxConfig['pickup_address']) ?>"
            required
            placeholder="Building, street name, landmark"
          >
        </div>

        <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:10px;">
          <div class="form-group">
            <label for="sfx_pcity">City</label>
            <input
              id="sfx_pcity"
              type="text"
              name="pickup_city"
              value="<?= htmlspecialchars($sfxConfig['pickup_city']) ?>"
              required
            >
          </div>
          <div class="form-group">
            <label for="sfx_pstate">State</label>
            <input
              id="sfx_pstate"
              type="text"
              name="pickup_state"
              value="<?= htmlspecialchars($sfxConfig['pickup_state']) ?>"
              required
            >
          </div>
          <div class="form-group">
            <label for="sfx_ppin">Pincode</label>
            <input
              id="sfx_ppin"
              type="text"
              name="pickup_pincode"
              value="<?= htmlspecialchars($sfxConfig['pickup_pincode']) ?>"
              required
              maxlength="6"
            >
          </div>
        </div>

        <div style="margin-top:20px; display:flex; gap:10px;">
          <button type="submit" class="button button-primary" style="background:#073D26; color:#E2C376;">
            Save Shadowfax Settings
          </button>
        </div>
      </form>

      <!-- Connection & Pincode Tester -->
      <div style="margin-top:24px; padding:16px; background:#FAF7F2; border:1px solid #D6C7B2; border-radius:6px;">
        <h4 style="margin:0 0 8px; font-size:13px; color:#073D26; font-weight:700; text-transform:uppercase;">
          ⚡ Connection &amp; Pincode Diagnostic
        </h4>
        <div style="display:flex; flex-wrap:wrap; gap:10px; align-items:center;">
          <form method="POST" style="margin:0;">
            <?= Helpers::csrfField() ?>
            <input type="hidden" name="action" value="test_connection">
            <button type="submit" class="button button-outline" style="font-size:12px; padding:6px 14px; background:#fff;">
              🔍 Test API Connection
            </button>
          </form>

          <form method="POST" style="display:flex; gap:6px; margin:0; flex:1;">
            <?= Helpers::csrfField() ?>
            <input type="hidden" name="action" value="check_pincode">
            <input
              type="text"
              name="test_pincode"
              placeholder="Test PIN (e.g. 560034)"
              maxlength="6"
              style="padding:6px 10px; font-size:12px; border:1px solid #ccc; border-radius:4px; width:140px;"
              required
            >
            <button type="submit" class="button button-outline" style="font-size:12px; padding:6px 12px; background:#fff;">
              Check Serviceability
            </button>
          </form>
        </div>
      </div>
    </div>

    <!-- Right Column: 100% Free Shipping Banner & Store Contact Settings -->
    <div>
      <!-- 100% Free Shipping Card -->
      <div style="background:#E6F4EA; border:1px solid #A8DAB5; border-radius:8px; padding:18px; margin-bottom:24px;">
        <div style="display:flex; align-items:flex-start; gap:10px;">
          <span style="font-size:24px; line-height:1;">🎉</span>
          <div>
            <h3 style="margin:0 0 4px; font-size:15px; color:#137333; font-weight:700;">100% Free Shipping Active Storewide</h3>
            <p style="margin:0; font-size:13px; color:#2E6930; line-height:1.5;">
              All shipping charge calculations, flat fees, and minimum spend thresholds have been permanently removed. Customers enjoy <strong>Free Delivery (₹0)</strong> across India on every order.
            </p>
          </div>
        </div>
      </div>

      <!-- Store Contact Information Form -->
      <div class="admin-card" style="margin-bottom: 24px;">
        <h2 style="margin: 0 0 4px; font-size: 18px; color: #073D26;">📞 Store Contact &amp; Support Info</h2>
        <p class="admin-card-subtitle" style="font-size: 13px; color: #6B6255; margin-bottom: 16px;">
          These details are shown dynamically across the storefront footer, /contact page, and order confirmations.
        </p>

        <form class="admin-form" method="POST">
          <?= Helpers::csrfField() ?>
          <input type="hidden" name="action" value="save_contact">

          <div class="form-group">
            <label for="contact_phone" style="font-weight: 700; color: #073D26;">Support Phone Number</label>
            <input
              id="contact_phone"
              type="text"
              name="contact_phone"
              value="<?= htmlspecialchars($settings['contact_phone']) ?>"
              placeholder="919059123456"
              required
            >
          </div>

          <div class="form-group">
            <label for="whatsapp_number" style="font-weight: 700; color: #073D26;">WhatsApp Customer Care Number</label>
            <input
              id="whatsapp_number"
              type="text"
              name="whatsapp_number"
              value="<?= htmlspecialchars($settings['whatsapp_number']) ?>"
              placeholder="919059123456"
              required
            >
          </div>

          <div class="form-group">
            <label for="contact_email" style="font-weight: 700; color: #073D26;">Official Support Email</label>
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
            <label for="store_address" style="font-weight: 700; color: #073D26;">Store Dispatch &amp; Warehouse Address</label>
            <textarea
              id="store_address"
              name="store_address"
              rows="3"
              placeholder="Address shown on Contact page"
            ><?= htmlspecialchars($settings['store_address']) ?></textarea>
          </div>

          <button type="submit" class="button button-primary" style="background:#073D26; color:#fff;">
            Save Store Contact Details
          </button>
        </form>
      </div>

      <!-- Environment & Integrations Card -->
      <div class="admin-card">
        <h2>Environment &amp; Integrations</h2>
        <div class="admin-details-grid" style="display: flex; flex-direction: column; gap: 16px;">
          <div>
            <span class="admin-detail-label" style="display: block; font-size: 11px; text-transform: uppercase; color: #7C7265; font-weight: 700; margin-bottom: 4px;">Shadowfax Webhook URL</span>
            <code style="word-break:break-all; font-size:12px;"><?= htmlspecialchars(APP_URL . '/api/shadowfax-webhook.php') ?></code>
            <span class="field-hint" style="display:block; font-size:11px; color:#7C7265; margin-top:2px;">
              Copy &amp; paste this webhook into SFX 360 Partner Portal to receive automated delivery updates.
            </span>
          </div>

          <div>
            <span class="admin-detail-label" style="display: block; font-size: 11px; text-transform: uppercase; color: #7C7265; font-weight: 700; margin-bottom: 4px;">Cashfree Payments Gateway</span>
            <span class="badge <?= $cashfreeConfigured ? 'badge-success' : 'badge-warning' ?>">
              <?= $cashfreeConfigured ? 'Active (' . CASHFREE_ENV . ')' : 'Simulated Dev Mode' ?>
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
        </div>
      </div>
    </div>
  </div>
</div>

<script>
function updateDefaultUrl(env) {
  var urlInput = document.getElementById('sfx_url');
  if (env === 'production') {
    urlInput.value = 'https://dale.shadowfax.in/api';
  } else {
    urlInput.value = 'https://dale.staging.shadowfax.in/api';
  }
}
</script>

<?php require __DIR__ . '/../layouts/admin_layout_bottom.php'; ?>
