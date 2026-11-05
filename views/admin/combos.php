<?php
/**
 * SVG Dry Fruits - Gift Combos & Hampers Builder
 * 1:1 match of Node.js src/app/admin/combos/page.tsx
 */
declare(strict_types=1);
require_once ROOT_PATH . '/src/Helpers.php';
require_once ROOT_PATH . '/src/Models.php';
require_once ROOT_PATH . '/src/Auth.php';

Auth::requireAuth();

$pdo = Database::getConnection();

// Handle adding or removing bundle components
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Helpers::requireCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'add_component') {
        $comboVariantId = trim($_POST['combo_variant_id'] ?? '');
        $componentVariantId = trim($_POST['component_variant_id'] ?? '');
        $quantity = max(1, (int)($_POST['quantity'] ?? 1));

        if ($comboVariantId && $componentVariantId) {
            $id = 'ci_' . bin2hex(random_bytes(8));
            $stmt = $pdo->prepare("
                INSERT INTO combo_items (id, combo_variant_id, component_variant_id, quantity)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$id, $comboVariantId, $componentVariantId, $quantity]);
            Helpers::setFlash('success', 'Component added to hamper successfully!');
        } else {
            Helpers::setFlash('error', 'Please select a valid dry fruit variant.');
        }
    } elseif ($action === 'remove_component') {
        $id = trim($_POST['id'] ?? '');
        if ($id) {
            $stmt = $pdo->prepare("DELETE FROM combo_items WHERE id = ?");
            $stmt->execute([$id]);
            Helpers::setFlash('success', 'Component removed from hamper.');
        }
    } elseif ($action === 'update_image') {
        $productId = trim($_POST['product_id'] ?? '');
        $imageUrl = trim($_POST['image_url'] ?? '');

        if (!empty($_FILES['image_file']['tmp_name'])) {
            $uploaded = Helpers::handleUpload($_FILES['image_file'], 'combo_');
            if ($uploaded) {
                $imageUrl = $uploaded;
            }
        }

        if ($productId && $imageUrl) {
            $stmt = $pdo->prepare("UPDATE products SET image_url = ? WHERE id = ?");
            $stmt->execute([$imageUrl, $productId]);
            Helpers::setFlash('success', 'Combo hamper image updated successfully!');
        } else {
            Helpers::setFlash('error', 'Please select an image file or enter an image URL.');
        }
    }

    Helpers::redirect(Helpers::url('admin/combos'));
}

// 1. Fetch all combo products (is_combo = 1 or slug like '%combo%' or '%hamper%')
$combos = $pdo->query("
    SELECT * FROM products 
    WHERE is_combo = 1 OR slug LIKE '%combo%' OR slug LIKE '%hamper%' 
    ORDER BY name ASC
")->fetchAll();

// 2. Fetch variants and linked combo items for each combo
$comboData = [];
$varStmt = $pdo->prepare("SELECT * FROM variants WHERE product_id = ? ORDER BY price_paise ASC");
$compStmt = null;
try {
    $compStmt = $pdo->prepare("
        SELECT ci.id, ci.quantity, ci.component_variant_id, 
               v.sku as component_sku, v.label as component_label, v.stock as component_stock,
               p.name as component_product_name
        FROM combo_items ci
        INNER JOIN variants v ON v.id = ci.component_variant_id
        INNER JOIN products p ON p.id = v.product_id
        WHERE ci.combo_variant_id = ?
    ");
} catch (Throwable $e) {
    $compStmt = null;
}

foreach ($combos as $c) {
    $varStmt->execute([$c['id']]);
    $variants = $varStmt->fetchAll();
    $variantGroups = [];
    foreach ($variants as $v) {
        $components = [];
        if ($compStmt) {
            try {
                $compStmt->execute([$v['id']]);
                $components = $compStmt->fetchAll() ?: [];
            } catch (Throwable $e) {
                $components = [];
            }
        }
        $variantGroups[] = [
            'variant' => $v,
            'components' => $components,
        ];
    }
    $comboData[] = [
        'product' => $c,
        'variants' => $variantGroups,
    ];
}

// 3. Fetch all available standard components (non-combo products)
$availableComponents = $pdo->query("
    SELECT v.id, v.sku, v.label, v.stock, p.name as product_name
    FROM variants v
    INNER JOIN products p ON p.id = v.product_id
    WHERE v.active = 1 AND p.is_combo = 0
    ORDER BY p.name ASC, v.price_paise ASC
")->fetchAll();

function calculateMaxPackable(array $components): ?int {
    if (empty($components)) return null;
    $minUnits = PHP_INT_MAX;
    foreach ($components as $comp) {
        $qty = max(1, (int)$comp['quantity']);
        $count = (int)floor(((int)$comp['component_stock']) / $qty);
        if ($count < $minUnits) $minUnits = $count;
    }
    return $minUnits === PHP_INT_MAX ? 0 : $minUnits;
}

$pageTitle = 'Gift Combos & Hampers';
$activeNav = 'combos';
require __DIR__ . '/../layouts/admin_layout.php';
?>

<div class="admin-content">
  <div class="admin-page-header">
    <div>
      <div class="breadcrumbs">
        <a href="<?= Helpers::url('admin') ?>">Dashboard</a>
        <span aria-hidden="true">/</span>
        <span>Gift Combos &amp; Hampers</span>
      </div>
      <h1>Gift Combos &amp; <em>Hampers Builder</em></h1>
      <p>Bundle multiple dry fruits into luxury gift packs. Stock automatically syncs with component inventory.</p>
    </div>
    <a href="<?= Helpers::url('admin/products/new') ?>" class="button button-primary">
      + Create New Combo / Hamper
    </a>
  </div>

  <!-- Educational Concept Guide matching Node.js -->
  <div
    class="admin-card"
    style="background: linear-gradient(135deg, #FAF7F2 0%, #F5EFE6 100%); border: 1px solid #D6C7B2; border-radius: 12px; padding: 20px 24px; margin-bottom: 24px;"
  >
    <div style="display: flex; align-items: flex-start; gap: 14px;">
      <div style="font-size: 28px; line-height: 1; background: #073D26; color: #E2C376; padding: 10px; border-radius: 10px;">
        🎁
      </div>
      <div style="flex: 1;">
        <h2 style="font-size: 16px; margin: 0 0 6px; color: #073D26;">
          How SVG Gift Combos &amp; Hampers Work
        </h2>
        <p style="margin: 0 0 10px; fontSize: 13.5px; color: #4A4031; line-height: 1.5;">
          Combos (like <strong>Festive Gift Boxes</strong> or <strong>Wellness Packs</strong>) do not need separate inventory in your warehouse. Instead, you link individual dry fruits to each hamper.
        </p>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; margin-top: 10px;">
          <div style="background: #fff; padding: 10px 14px; border-radius: 8px; border: 1px solid #E4DCCF; font-size: 12.5px;">
            <strong style="display: block; color: #073D26; margin-bottom: 2px;">1. Bundle Products</strong>
            Add 1× Almonds 250g, 1× Cashews 250g, 1× Pistachios 250g to create a 3-in-1 Hamper.
          </div>
          <div style="background: #fff; padding: 10px 14px; border-radius: 8px; border: 1px solid #E4DCCF; font-size: 12.5px;">
            <strong style="display: block; color: #073D26; margin-bottom: 2px;">2. Automatic Deduction</strong>
            When an order arrives, the system automatically deducts stock from each component dry fruit.
          </div>
          <div style="background: #fff; padding: 10px 14px; border-radius: 8px; border: 1px solid #E4DCCF; font-size: 12.5px;">
            <strong style="display: block; color: #073D26; margin-bottom: 2px;">3. Live Fulfillable Count</strong>
            The "Max Packable" badge below shows how many complete gift boxes you can fulfill right now based on warehouse inventory.
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Combos List -->
  <?php if (empty($comboData)): ?>
    <div class="admin-card" style="text-align: center; padding: 40px;">
      <h3 style="margin: 0 0 8px; color: #073D26;">No combo gift boxes configured</h3>
      <p class="admin-muted" style="max-width: 460px; margin: 0 auto 16px;">
        Create your first combo product (e.g., "Royal Festive Dry Fruits Hamper") and select "Is Combo / Gifting Hamper".
      </p>
      <a href="<?= Helpers::url('admin/products/new') ?>" class="button button-primary">
        + Create New Hamper
      </a>
    </div>
  <?php else: ?>
    <?php foreach ($comboData as $c): 
      $prod = $c['product'];
    ?>
      <div class="admin-card" style="margin-bottom: 24px;">
        <!-- Combo Header -->
        <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #ECE7DE; padding-bottom: 14px; margin-bottom: 16px; flex-wrap: wrap; gap: 12px;">
          <div style="display: flex; align-items: center; gap: 16px;">
            <div style="position: relative; width: 64px; height: 64px; border-radius: 8px; overflow: hidden; border: 2px solid #DFD7CA; background: #fff; flex-shrink: 0; box-shadow: 0 2px 6px rgba(0,0,0,0.06);">
              <img
                src="<?= Helpers::asset(ltrim($prod['image_url'] ?? '/assets/mixed-fruit-reference.jpg', '/')) ?>"
                alt="<?= htmlspecialchars($prod['name']) ?>"
                style="width: 100%; height: 100%; object-fit: cover;"
                onerror="this.src='<?= Helpers::asset('mixed-fruit-reference.jpg') ?>'"
              >
            </div>
            <div>
              <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                <h2 style="margin: 0; font-size: 19px; color: #073D26;">
                  <?= htmlspecialchars($prod['name']) ?>
                </h2>
                <button
                  type="button"
                  onclick="openComboImageModal('<?= $prod['id'] ?>', '<?= htmlspecialchars(addslashes($prod['name'])) ?>', '<?= htmlspecialchars(addslashes($prod['image_url'] ?? '/assets/mixed-fruit-reference.jpg')) ?>')"
                  class="button"
                  style="font-size: 12.5px; padding: 6px 14px; background: #073D26; border: 1px solid #073D26; border-radius: 6px; color: #FFFFFF; cursor: pointer; font-weight: 700; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 2px 6px rgba(7,61,38,0.25);"
                >
                  📷 Upload / Change Hamper Photo
                </button>
              </div>
              <span style="font-size: 12px; color: #776F62; display: block; margin-top: 3px;">
                /<?= htmlspecialchars($prod['slug']) ?> &bull; Gift Hamper Product
              </span>
            </div>
          </div>
          <div style="display: flex; gap: 8px;">
            <a
              href="<?= Helpers::url('products/' . $prod['slug']) ?>"
              target="_blank"
              class="button button-outline"
              style="font-size: 12px; padding: 6px 12px; background: #fff; color: #073D26; border-radius: 6px;"
            >
              View on Storefront ↗
            </a>
            <a
              href="<?= Helpers::url('admin/products/edit?id=' . $prod['id']) ?>"
              class="button button-outline"
              style="font-size: 12px; padding: 6px 12px; background: #fff; color: #073D26; border-radius: 6px;"
            >
              Edit Product Details
            </a>
          </div>
        </div>

        <!-- Variants for this combo -->
        <?php foreach ($c['variants'] as $vg): 
          $v = $vg['variant'];
          $components = $vg['components'];
          $maxPackable = calculateMaxPackable($components);
        ?>
          <div style="background: #FBF9F5; border: 1px solid #EAE3D5; border-radius: 10px; padding: 16px; margin-bottom: 16px;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
              <div>
                <strong style="font-size: 15px; color: #073D26;">
                  Pack: <?= htmlspecialchars($v['label']) ?>
                </strong>
                <span style="margin-left: 10px; font-size: 13px; color: #665C4E;">
                  Selling Price: <strong><?= Helpers::formatPaise((int)$v['price_paise']) ?></strong>
                </span>
              </div>

              <!-- Stock Status Badge -->
              <div>
                <?php if ($maxPackable === null): ?>
                  <span class="badge badge-neutral">No items linked</span>
                <?php elseif ($maxPackable === 0): ?>
                  <span class="badge badge-danger">Out of Stock (0 complete boxes packable)</span>
                <?php elseif ($maxPackable <= 5): ?>
                  <span class="badge badge-warning">Low Stock: Can pack <?= $maxPackable ?> hampers today</span>
                <?php else: ?>
                  <span class="badge badge-success">✓ Can pack <?= $maxPackable ?> hampers right now</span>
                <?php endif; ?>
              </div>
            </div>

            <!-- Components List -->
            <?php if (empty($components)): ?>
              <div style="padding: 16px 20px; background: #fff; border-radius: 8px; border: 1px dashed #D6C8B5; text-align: center;">
                <p style="margin: 0 0 8px; font-size: 13px; color: #666;">
                  No dry fruit items have been added to this hamper yet.
                </p>
              </div>
            <?php else: ?>
              <div class="admin-table-wrap" style="background: #fff; border-radius: 8px;">
                <table class="admin-table" style="margin: 0;">
                  <thead>
                    <tr>
                      <th>Included Dry Fruit</th>
                      <th style="width: 140px;">Quantity in Hamper</th>
                      <th style="width: 160px;">Live Warehouse Stock</th>
                      <th style="width: 180px;">Fulfillment Limit</th>
                      <th style="width: 80px; text-align: right;">Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($components as $comp): 
                      $canMake = (int)floor(((int)$comp['component_stock']) / max(1, (int)$comp['quantity']));
                      $isBottleneck = ($maxPackable !== null && $canMake === $maxPackable);
                    ?>
                      <tr>
                        <td>
                          <strong><?= htmlspecialchars($comp['component_product_name']) ?></strong>
                          <span style="display: block; font-size: 11px; color: #777;">
                            Pack size: <?= htmlspecialchars($comp['component_label']) ?> &bull; SKU: <?= htmlspecialchars($comp['component_sku']) ?>
                          </span>
                        </td>
                        <td>
                          <span style="display: inline-block; padding: 2px 8px; background: #F4EEDF; border-radius: 4px; font-weight: 600; font-size: 12px;">
                            <?= $comp['quantity'] ?>&times; per hamper
                          </span>
                        </td>
                        <td>
                          <span style="font-weight: 600; color: <?= (int)$comp['component_stock'] <= 5 ? '#C45B28' : '#073D26' ?>;">
                            <?= $comp['component_stock'] ?> units
                          </span>
                        </td>
                        <td>
                          <span style="font-size: 12px; color: <?= $isBottleneck ? '#C45B28' : '#555' ?>; font-weight: <?= $isBottleneck ? '700' : '400' ?>;">
                            Supports <?= $canMake ?> hampers <?= $isBottleneck ? '(Bottleneck)' : '' ?>
                          </span>
                        </td>
                        <td style="text-align: right;">
                          <form method="POST" style="display: inline;" onsubmit="return confirm('Remove this dry fruit from the bundle?');">
                            <?= Helpers::csrfField() ?>
                            <input type="hidden" name="action" value="remove_component">
                            <input type="hidden" name="id" value="<?= $comp['id'] ?>">
                            <button
                              type="submit"
                              style="background: none; border: none; color: #A73A2A; cursor: pointer; font-size: 12px; text-decoration: underline;"
                            >
                              Remove
                            </button>
                          </form>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>

            <!-- Add Component Row -->
            <div style="margin-top: 12px;">
              <form method="POST" style="background: #fff; padding: 14px 16px; border-radius: 8px; border: 1px solid #C5A059; margin-top: 8px;">
                <?= Helpers::csrfField() ?>
                <input type="hidden" name="action" value="add_component">
                <input type="hidden" name="combo_variant_id" value="<?= $v['id'] ?>">

                <h4 style="margin: 0 0 10px; font-size: 13px; color: #073D26;">
                  Choose Dry Fruit to bundle in this Hamper
                </h4>
                <div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: flex-end;">
                  <div style="flex: 1; min-width: 260px;">
                    <label style="display: block; font-size: 12px; margin-bottom: 4px; font-weight: 600;">
                      Select Dry Fruit &amp; Size
                    </label>
                    <select
                      name="component_variant_id"
                      required
                      style="width: 100%; padding: 8px 10px; border-radius: 6px; border: 1px solid #CCC; font-size: 13px;"
                    >
                      <option value="">— Select item from catalog —</option>
                      <?php foreach ($availableComponents as $ac): ?>
                        <option value="<?= $ac['id'] ?>">
                          <?= htmlspecialchars($ac['product_name']) ?> — <?= htmlspecialchars($ac['label']) ?> (<?= $ac['stock'] ?> in stock)
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </div>

                  <div style="width: 100px;">
                    <label style="display: block; font-size: 12px; margin-bottom: 4px; font-weight: 600;">
                      Qty / Box
                    </label>
                    <input
                      type="number"
                      name="quantity"
                      min="1"
                      max="10"
                      value="1"
                      required
                      style="width: 100%; padding: 8px 10px; border-radius: 6px; border: 1px solid #CCC; font-size: 13px;"
                    >
                  </div>

                  <button type="submit" class="button button-primary" style="font-size: 13px; padding: 8px 16px;">
                    Add to Box
                  </button>
                </div>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<!-- Modal: Change Combo Hamper Image -->
<div id="comboImageModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.55); z-index: 9999; align-items: center; justify-content: center; padding: 20px;">
  <div style="background: #fff; border-radius: 12px; max-width: 520px; width: 100%; box-shadow: 0 20px 40px rgba(0,0,0,0.25); overflow: hidden; border: 1px solid #DFD7CA;">
    <div style="background: #073D26; color: #fff; padding: 18px 24px; display: flex; align-items: center; justify-content: space-between;">
      <h3 style="margin: 0; font-size: 18px; color: #E2C376; font-family: Georgia, serif;">
        📷 Update Combo Photo
      </h3>
      <button type="button" onclick="closeComboImageModal()" style="background: none; border: none; color: #fff; font-size: 24px; cursor: pointer; line-height: 1;">&times;</button>
    </div>

    <form method="POST" enctype="multipart/form-data" style="padding: 24px;">
      <?= Helpers::csrfField() ?>
      <input type="hidden" name="action" value="update_image">
      <input type="hidden" id="modal_combo_product_id" name="product_id" value="">

      <p style="margin: 0 0 16px; font-size: 14px; color: #555;">
        Updating image for: <strong id="modal_combo_product_name" style="color: #073D26;">Combo Hamper</strong>
      </p>

      <div style="display: flex; gap: 16px; align-items: flex-start; margin-bottom: 18px;">
        <!-- Preview Box -->
        <div style="width: 100px; height: 100px; border-radius: 8px; overflow: hidden; border: 2px solid #DFD7CA; background: #FAF7F2; flex-shrink: 0; display: flex; align-items: center; justify-content: center;">
          <img
            id="modal_combo_preview"
            src="<?= Helpers::asset('mixed-fruit-reference.jpg') ?>"
            alt="Preview"
            style="width: 100%; height: 100%; object-fit: cover;"
          >
        </div>

        <div style="flex: 1;">
          <input
            type="file"
            id="combo_file_input"
            name="image_file"
            accept="image/*"
            style="display: none;"
            onchange="handleComboFileChange(this)"
          >
          <button
            type="button"
            class="button button-primary"
            onclick="document.getElementById('combo_file_input').click()"
            style="padding: 8px 16px; font-size: 13px; font-weight: 600; cursor: pointer; margin-bottom: 6px;"
          >
            📁 Choose Image from Device
          </button>
          <div id="combo_upload_status" style="font-size: 12px; color: #777;">
            PNG, JPG, WEBP, or SVG under 10MB
          </div>
        </div>
      </div>

      <div style="margin-bottom: 16px;">
        <label for="modal_combo_url" style="display: block; font-size: 12px; font-weight: 700; color: #555; margin-bottom: 4px;">
          Or Image URL / Path:
        </label>
        <input
          type="text"
          id="modal_combo_url"
          name="image_url"
          style="width: 100%; box-sizing: border-box; padding: 8px 12px; border-radius: 6px; border: 1px solid #CCC; font-family: monospace; font-size: 13px;"
          oninput="updateModalPreview(this.value)"
        >
      </div>

      <div style="margin-bottom: 20px;">
        <span style="display: block; font-size: 12px; font-weight: 700; color: #776F62; margin-bottom: 8px;">
          Hamper Presets:
        </span>
        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
          <?php 
          $comboPresets = [
            ['name' => 'Festive Wooden Box', 'path' => '/assets/mixed-fruit-reference.jpg'],
            ['name' => 'Dry Fruit Assortment', 'path' => '/assets/mixed dry fruits.png'],
            ['name' => 'Gift Hampers', 'path' => '/assets/cat-hd-gift-boxes.png'],
            ['name' => 'Royal Nut Mix', 'path' => '/assets/shop-mixed-nuts.jpg'],
            ['name' => 'Deluxe Gift Set', 'path' => '/assets/pdp-hero-main.jpg'],
          ];
          foreach ($comboPresets as $cp):
          ?>
            <button
              type="button"
              class="btn-action"
              style="display: inline-flex; align-items: center; gap: 6px; padding: 5px 10px; border-radius: 6px; background: #FAF7F2; border: 1px solid #DFD7CA; font-size: 11.5px; cursor: pointer;"
              onclick="selectComboPreset('<?= $cp['path'] ?>')"
            >
              <img src="<?= Helpers::asset(ltrim($cp['path'], '/')) ?>" alt="" style="width: 16px; height: 16px; border-radius: 3px; object-fit: cover;">
              <span><?= $cp['name'] ?></span>
            </button>
          <?php endforeach; ?>
        </div>
      </div>

      <div style="display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid #ECE7DE; padding-top: 16px;">
        <button
          type="button"
          class="button button-outline"
          onclick="closeComboImageModal()"
          style="background: #fff; padding: 8px 16px;"
        >
          Cancel
        </button>
        <button
          type="submit"
          class="button button-primary"
          style="padding: 8px 20px; font-weight: 700;"
        >
          Save Photo
        </button>
      </div>
    </form>
  </div>
</div>

<script>
  function openComboImageModal(id, name, currentImg) {
    document.getElementById('modal_combo_product_id').value = id;
    document.getElementById('modal_combo_product_name').textContent = name;
    document.getElementById('modal_combo_url').value = currentImg;
    updateModalPreview(currentImg);
    document.getElementById('combo_upload_status').textContent = 'PNG, JPG, WEBP, or SVG under 10MB';
    const modal = document.getElementById('comboImageModal');
    modal.style.display = 'flex';
  }

  function closeComboImageModal() {
    document.getElementById('comboImageModal').style.display = 'none';
  }

  function updateModalPreview(src) {
    const preview = document.getElementById('modal_combo_preview');
    if (!src) return;
    if (src.startsWith('data:') || src.startsWith('blob:') || src.startsWith('http')) {
      preview.src = src;
    } else {
      let clean = src.replace(/^\/?assets\//, '');
      preview.src = '<?= Helpers::url('assets') ?>/' + clean;
    }
  }

  function selectComboPreset(path) {
    document.getElementById('modal_combo_url').value = path;
    updateModalPreview(path);
  }

  function handleComboFileChange(input) {
    const file = input.files && input.files[0];
    if (!file) return;

    if (file.size > 10 * 1024 * 1024) {
      alert('Selected file exceeds 10MB limit.');
      input.value = '';
      return;
    }

    const reader = new FileReader();
    reader.onload = function(e) {
      document.getElementById('modal_combo_preview').src = e.target.result;
      document.getElementById('combo_upload_status').innerHTML = '<strong>' + file.name + '</strong> (' + Math.round(file.size / 1024) + ' KB) selected';
    };
    reader.readAsDataURL(file);

    // Instant async upload to server
    const formData = new FormData();
    formData.append('csrf_token', '<?= Helpers::csrfToken() ?>');
    formData.append('image', file);
    formData.append('prefix', 'combo');

    document.getElementById('combo_upload_status').innerHTML = '<em>Uploading image...</em>';

    fetch('<?= Helpers::url('api/upload.php') ?>', {
      method: 'POST',
      body: formData
    })
    .then(r => r.json())
    .then(res => {
      if (res.success && res.url) {
        document.getElementById('modal_combo_url').value = res.url;
        document.getElementById('combo_upload_status').innerHTML = '<span style="color: #2E7D32; font-weight: 700;">✓ Uploaded successfully!</span> Saved to ' + res.url;
      } else {
        document.getElementById('combo_upload_status').innerHTML = '<span style="color: #C53030;">Upload warning: ' + (res.error || 'Server error') + '. Form submit will still save photo.</span>';
      }
    })
    .catch(() => {
      document.getElementById('combo_upload_status').innerHTML = '<span style="color: #666;">File selected. Full upload will complete on clicking Save Photo.</span>';
    });
  }
</script>

<?php require __DIR__ . '/../layouts/admin_layout_bottom.php'; ?>
