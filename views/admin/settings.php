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
            'pickup_address' => trim($_POST['pickup_address'] ?? 'Anjaneya Nagar, APSP Camp'),
            'pickup_city' => trim($_POST['pickup_city'] ?? 'Kakinada'),
            'pickup_state' => trim($_POST['pickup_state'] ?? 'Andhra Pradesh'),
            'pickup_pincode' => trim($_POST['pickup_pincode'] ?? '533005'),
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
    } elseif ($action === 'check_pincode' || $action === 'calculate_shipping') {
        $testPin = trim($_POST['test_pincode'] ?? $_POST['dest_pincode'] ?? '');
        $weightKg = (float)($_POST['weight_kg'] ?? 0.5);
        if ($weightKg <= 0) $weightKg = 0.5;
        $payMode = trim($_POST['payment_mode'] ?? 'prepaid');
        $direction = trim($_POST['direction'] ?? 'forward');

        if ($testPin) {
            $serviceability = Shadowfax::checkFullServiceability($testPin);
            $rates = Shadowfax::calculateShippingRate($testPin, $weightKg, $payMode, $direction);

            $_SESSION['sfx_diagnostic'] = [
                'pincode' => $testPin,
                'weight_kg' => $weightKg,
                'payment_mode' => $payMode,
                'direction' => $direction,
                'serviceability' => $serviceability,
                'rates' => $rates,
            ];

            if ($serviceability['forward']['serviceable']) {
                Helpers::setFlash('success', "✓ PIN {$testPin} Forward Delivery is AVAILABLE (" . implode(', ', $serviceability['forward']['services']) . ")");
            } else {
                Helpers::setFlash('warning', "PIN {$testPin} Forward Delivery: NO (Not Serviceable). Backward Pickup: " . ($serviceability['backward']['serviceable'] ? "YES (" . implode(', ', $serviceability['backward']['services']) . ")" : "NO"));
            }
        } else {
            Helpers::setFlash('error', "Please enter a valid 6-digit PIN code.");
        }
        Helpers::redirect(Helpers::url('admin/settings#shipping-calculator'));
    }
}

$diagnosticResult = $_SESSION['sfx_diagnostic'] ?? null;
unset($_SESSION['sfx_diagnostic']);

$sfxConfig = Shadowfax::getConfig();
$isConfigured = Shadowfax::isConfigured();
$settings = Models::getStoreSettings();

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

      <!-- Connection & Pincode Tester + Shipping Calculator -->
      <div id="shipping-calculator" style="margin-top:24px; padding:20px; background:#FAF7F2; border:1.5px solid #D6C7B2; border-radius:8px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; border-bottom:1px solid #E6DCCD; padding-bottom:10px;">
          <div>
            <h4 style="margin:0 0 4px; font-size:14px; color:#073D26; font-weight:700; text-transform:uppercase; letter-spacing:0.03em;">
              ⚡ Shadowfax Shipping Calculator &amp; Diagnostics
            </h4>
            <span style="font-size:12px; color:#6B6255;">
              Verify live Forward (Customer Delivery) &amp; Backward (Customer Return / Pickup) operations and calculate courier rates.
            </span>
          </div>
        </div>

        <!-- Quick API Connection Test Form -->
        <div style="margin-bottom:18px;">
          <form method="POST" style="margin:0; display:inline-block;">
            <?= Helpers::csrfField() ?>
            <input type="hidden" name="action" value="test_connection">
            <button type="submit" style="display:inline-flex; align-items:center; gap:8px; background:#073D26; color:#E2C376; font-weight:700; font-size:13px; padding:9px 18px; border-radius:6px; border:none; cursor:pointer; box-shadow:0 1px 3px rgba(0,0,0,0.12); transition: background 0.15s ease;">
              <span>🔍</span>
              <span>Test API Connection</span>
            </button>
          </form>
        </div>

        <!-- Full Shipping Calculator Form -->
        <form method="POST" style="margin:0;">
          <?= Helpers::csrfField() ?>
          <input type="hidden" name="action" value="calculate_shipping">
          
          <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap:12px; margin-bottom:16px;">
            <div>
              <label style="display:block; font-size:11.5px; font-weight:700; color:#073D26; margin-bottom:4px; text-transform:uppercase;">
                Destination Pincode *
              </label>
              <input
                type="text"
                name="dest_pincode"
                value="<?= htmlspecialchars($diagnosticResult['pincode'] ?? '560034') ?>"
                placeholder="e.g. 500000, 560034"
                maxlength="6"
                style="width:100%; padding:9px 12px; font-size:13px; font-weight:600; border:1.5px solid #D6C7B2; border-radius:6px; background:#fff; color:#1E2520; box-sizing:border-box;"
                required
              >
            </div>

            <div>
              <label style="display:block; font-size:11.5px; font-weight:700; color:#073D26; margin-bottom:4px; text-transform:uppercase;">
                Package Weight (Kg)
              </label>
              <input
                type="number"
                step="0.1"
                min="0.1"
                max="50"
                name="weight_kg"
                value="<?= htmlspecialchars((string)($diagnosticResult['weight_kg'] ?? 0.5)) ?>"
                style="width:100%; padding:9px 12px; font-size:13px; font-weight:600; border:1.5px solid #D6C7B2; border-radius:6px; background:#fff; color:#1E2520; box-sizing:border-box;"
              >
            </div>

            <div>
              <label style="display:block; font-size:11.5px; font-weight:700; color:#073D26; margin-bottom:4px; text-transform:uppercase;">
                Payment Mode
              </label>
              <select name="payment_mode" style="width:100%; padding:9px 12px; font-size:13px; font-weight:600; border:1.5px solid #D6C7B2; border-radius:6px; background:#fff; color:#1E2520; box-sizing:border-box;">
                <option value="prepaid" <?= (($diagnosticResult['payment_mode'] ?? '') === 'prepaid') ? 'selected' : '' ?>>Prepaid (₹0 COD Fee)</option>
                <option value="cod" <?= (($diagnosticResult['payment_mode'] ?? '') === 'cod') ? 'selected' : '' ?>>Cash On Delivery (COD)</option>
              </select>
            </div>

            <div>
              <label style="display:block; font-size:11.5px; font-weight:700; color:#073D26; margin-bottom:4px; text-transform:uppercase;">
                Logistics Movement
              </label>
              <select name="direction" style="width:100%; padding:9px 12px; font-size:13px; font-weight:600; border:1.5px solid #D6C7B2; border-radius:6px; background:#fff; color:#1E2520; box-sizing:border-box;">
                <option value="forward" <?= (($diagnosticResult['direction'] ?? '') === 'forward') ? 'selected' : '' ?>>Forward (Customer Delivery)</option>
                <option value="backward" <?= (($diagnosticResult['direction'] ?? '') === 'backward') ? 'selected' : '' ?>>Backward (Customer Return / Pickup)</option>
              </select>
            </div>
          </div>

          <div style="display:flex; gap:10px; align-items:center;">
            <button type="submit" style="display:inline-flex; align-items:center; gap:8px; background:#073D26; color:#fff; font-weight:700; font-size:13px; padding:10px 20px; border-radius:6px; border:none; cursor:pointer; box-shadow:0 1px 3px rgba(0,0,0,0.15);">
              <span>🚚</span>
              <span>Calculate Shipping &amp; Check Serviceability</span>
            </button>
          </div>
        </form>

        <?php if (!empty($diagnosticResult)): 
          $diagServ = $diagnosticResult['serviceability'];
          $diagRate = $diagnosticResult['rates'];
          $fwd = $diagServ['forward'] ?? [];
          $bwd = $diagServ['backward'] ?? [];
        ?>
          <div style="margin-top:20px; padding:16px; background:#fff; border-radius:8px; border:1.5px solid #D6C7B2;">
            <h5 style="margin:0 0 12px; font-size:13.5px; color:#073D26; font-weight:700;">
              📊 Results for PIN <?= htmlspecialchars($diagnosticResult['pincode']) ?> (Weight: <?= $diagnosticResult['weight_kg'] ?> kg, <?= strtoupper($diagnosticResult['payment_mode']) ?>)
            </h5>

            <!-- Forward and Backward Serviceability Comparison -->
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px; margin-bottom:16px;">
              <!-- Forward Logistics (Customer Delivery) -->
              <div style="padding:12px; border-radius:6px; border:1px solid <?= !empty($fwd['serviceable']) ? '#A8DAB5' : '#F5C6CB' ?>; background:<?= !empty($fwd['serviceable']) ? '#F2F9F4' : '#FDF7F7' ?>;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                  <span style="font-size:12px; font-weight:700; color:#073D26;">Forward Logistics</span>
                  <span style="padding:2px 8px; border-radius:12px; font-size:11px; font-weight:700; <?= !empty($fwd['serviceable']) ? 'background:#E6F4EA; color:#137333;' : 'background:#FCE8E6; color:#C5221F;' ?>">
                    <?= !empty($fwd['serviceable']) ? '✓ Customer Delivery: YES' : '✕ Customer Delivery: NO' ?>
                  </span>
                </div>
                <div style="font-size:12px; color:#555;">
                  <strong>Status:</strong> <?= !empty($fwd['serviceable']) ? 'Deliverable to customer doorstep' : 'Not deliverable via Shadowfax' ?><br>
                  <?php if (!empty($fwd['services'])): ?>
                    <strong>Available Modes:</strong> <?= implode(', ', array_map('htmlspecialchars', $fwd['services'])) ?>
                  <?php else: ?>
                    <strong>Available Modes:</strong> None (Serviceable: No)
                  <?php endif; ?>
                </div>
              </div>

              <!-- Backward Logistics (Customer Return / Pickup) -->
              <div style="padding:12px; border-radius:6px; border:1px solid <?= !empty($bwd['serviceable']) ? '#A8DAB5' : '#F5C6CB' ?>; background:<?= !empty($bwd['serviceable']) ? '#F2F9F4' : '#FDF7F7' ?>;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                  <span style="font-size:12px; font-weight:700; color:#073D26;">Backward / Reverse Logistics</span>
                  <span style="padding:2px 8px; border-radius:12px; font-size:11px; font-weight:700; <?= !empty($bwd['serviceable']) ? 'background:#E6F4EA; color:#137333;' : 'background:#FCE8E6; color:#C5221F;' ?>">
                    <?= !empty($bwd['serviceable']) ? '✓ Customer Pickup: YES' : '✕ Customer Pickup: NO' ?>
                  </span>
                </div>
                <div style="font-size:12px; color:#555;">
                  <strong>Status:</strong> <?= !empty($bwd['serviceable']) ? 'Available for reverse pickup / return' : 'Not available for reverse pickup' ?><br>
                  <?php if (!empty($bwd['services'])): ?>
                    <strong>Available Modes:</strong> <?= implode(', ', array_map('htmlspecialchars', $bwd['services'])) ?>
                  <?php else: ?>
                    <strong>Available Modes:</strong> None
                  <?php endif; ?>
                </div>
              </div>
            </div>

            <!-- Rate Calculation Table -->
            <div style="background:#FAF8F5; border-radius:6px; padding:12px; border:1px solid #EBE4DA;">
              <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                <span style="font-size:12px; font-weight:700; color:#073D26; text-transform:uppercase;">
                  Estimated Courier Freight Breakdown (Shadowfax Rate Card)
                </span>
                <span style="font-size:11.5px; color:#7C7265;">
                  Route: <?= htmlspecialchars($diagRate['origin_pincode']) ?> ➔ <?= htmlspecialchars($diagRate['dest_pincode']) ?> (<?= htmlspecialchars($diagRate['zone']) ?>)
                </span>
              </div>
              <table style="width:100%; font-size:12px; border-collapse:collapse;">
                <tbody>
                  <tr style="border-bottom:1px solid #EAE3D7;">
                    <td style="padding:5px 0; color:#5C5549;">Billed Weight Slab</td>
                    <td style="padding:5px 0; text-align:right; font-weight:600;"><?= $diagRate['billed_weight_kg'] ?> kg</td>
                  </tr>
                  <tr style="border-bottom:1px solid #EAE3D7;">
                    <td style="padding:5px 0; color:#5C5549;">Base Freight</td>
                    <td style="padding:5px 0; text-align:right; font-weight:600;">₹<?= number_format($diagRate['base_freight'], 2) ?></td>
                  </tr>
                  <tr style="border-bottom:1px solid #EAE3D7;">
                    <td style="padding:5px 0; color:#5C5549;">Fuel Surcharge (10%)</td>
                    <td style="padding:5px 0; text-align:right; font-weight:600;">₹<?= number_format($diagRate['fuel_surcharge'], 2) ?></td>
                  </tr>
                  <?php if ($diagRate['cod_charge'] > 0): ?>
                    <tr style="border-bottom:1px solid #EAE3D7;">
                      <td style="padding:5px 0; color:#5C5549;">COD Collection Surcharge</td>
                      <td style="padding:5px 0; text-align:right; font-weight:600;">₹<?= number_format($diagRate['cod_charge'], 2) ?></td>
                    </tr>
                  <?php endif; ?>
                  <tr style="border-bottom:1px solid #EAE3D7;">
                    <td style="padding:5px 0; color:#5C5549;">GST (18%)</td>
                    <td style="padding:5px 0; text-align:right; font-weight:600;">₹<?= number_format($diagRate['gst_18'], 2) ?></td>
                  </tr>
                  <tr style="font-weight:700; font-size:13.5px; color:#073D26;">
                    <td style="padding:8px 0 2px;">Estimated Total Courier Cost</td>
                    <td style="padding:8px 0 2px; text-align:right; color:#073D26;">₹<?= number_format($diagRate['total_cost'], 2) ?></td>
                  </tr>
                  <tr>
                    <td colspan="2" style="padding-top:4px; font-size:11px; color:#7C7265;">
                      Estimated Transit Time: <strong><?= htmlspecialchars($diagRate['estimated_timeline']) ?></strong>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <p style="margin:10px 0 0; font-size:11.5px; color:#6B6255; line-height:1.4;">
              💡 <em>Storefront Note: Customers receive <strong>100% Free Delivery (₹0)</strong> at checkout storewide. This calculator is for your internal Shadowfax merchant operations.</em>
            </p>
          </div>
        <?php endif; ?>
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
      <div class="admin-card">
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
