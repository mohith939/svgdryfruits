<?php
/**
 * SVG Dry Fruits - Product Create & Edit Form
 * 1:1 match of Node.js src/app/admin/products/new and products/[id]
 */
declare(strict_types=1);
require_once ROOT_PATH . '/src/Helpers.php';
require_once ROOT_PATH . '/src/Models.php';
require_once ROOT_PATH . '/src/Auth.php';

Auth::requireAuth();

$pdo = Database::getConnection();

$id = trim($_GET['id'] ?? '');
$isEdit = !empty($id);

$product = null;
$variants = [];

if ($isEdit) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch();
    if (!$product) {
        Helpers::setFlash('error', 'Product not found.');
        Helpers::redirect(Helpers::url('admin/products'));
    }
    $varStmt = $pdo->prepare("SELECT * FROM variants WHERE product_id = ? ORDER BY price_paise ASC");
    $varStmt->execute([$id]);
    $variants = $varStmt->fetchAll();
} else {
    $variants = [
        ['label' => '250 g', 'sku' => '', 'price_paise' => 29900, 'compare_price_paise' => 34900, 'stock' => 25, 'low_stock_threshold' => 5, 'active' => 1],
        ['label' => '500 g', 'sku' => '', 'price_paise' => 56900, 'compare_price_paise' => 64900, 'stock' => 15, 'low_stock_threshold' => 5, 'active' => 1],
        ['label' => '1 kg', 'sku' => '', 'price_paise' => 109900, 'compare_price_paise' => 124900, 'stock' => 10, 'low_stock_threshold' => 3, 'active' => 1],
    ];
}

$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Helpers::requireCsrf();
    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $categoryId = trim($_POST['category_id'] ?? '') ?: null;
    $description = trim($_POST['description'] ?? '');
    $imageUrl = trim($_POST['image_url'] ?? '/assets/mixed-nuts-reference.jpg');

    // Handle direct file upload from device
    if (!empty($_FILES['image_file']['tmp_name'])) {
        $uploaded = Helpers::handleUpload($_FILES['image_file'], 'prod_');
        if ($uploaded) {
            $imageUrl = $uploaded;
        }
    }

    $isCombo = isset($_POST['is_combo']) ? 1 : 0;
    $active = isset($_POST['active']) ? 1 : 0;

    if (!$name || !$slug) {
        Helpers::setFlash('error', 'Name and URL Slug are required.');
    } else {
        if ($isEdit) {
            $stmt = $pdo->prepare("
                UPDATE products 
                SET name = ?, slug = ?, category_id = ?, description = ?, image_url = ?, is_combo = ?, active = ?
                WHERE id = ?
            ");
            $stmt->execute([$name, $slug, $categoryId, $description, $imageUrl, $isCombo, $active, $id]);
            $productId = $id;
        } else {
            $productId = 'prod_' . bin2hex(random_bytes(8));
            $stmt = $pdo->prepare("
                INSERT INTO products (id, name, slug, category_id, description, image_url, is_combo, active)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$productId, $name, $slug, $categoryId, $description, $imageUrl, $isCombo, $active]);
        }

        // Process variants preserving existing IDs
        $varIds = $_POST['variant_id'] ?? [];
        $labels = $_POST['variant_label'] ?? [];
        $skus = $_POST['variant_sku'] ?? [];
        $prices = $_POST['variant_price'] ?? [];
        $compares = $_POST['variant_compare'] ?? [];
        $stocks = $_POST['variant_stock'] ?? [];
        $thresholds = $_POST['variant_threshold'] ?? [];

        $variantsToSave = [];
        for ($i = 0; $i < count($labels); $i++) {
            $lbl = trim($labels[$i] ?? '');
            $prc = (float)($prices[$i] ?? 0);
            if ($lbl && $prc > 0) {
                $sku = trim($skus[$i] ?? '') ?: strtoupper($slug) . '-' . preg_replace('/\s+/', '', $lbl);
                $pricePaise = (int)round($prc * 100);
                $cmp = (float)($compares[$i] ?? 0);
                $comparePaise = $cmp > 0 ? (int)round($cmp * 100) : null;
                $stk = max(0, (int)($stocks[$i] ?? 10));
                $thold = max(0, (int)($thresholds[$i] ?? 5));
                $vId = !empty($varIds[$i]) ? trim($varIds[$i]) : null;

                $variantsToSave[] = [
                    'id' => $vId,
                    'sku' => $sku,
                    'label' => $lbl,
                    'price_paise' => $pricePaise,
                    'compare_price_paise' => $comparePaise,
                    'stock' => $stk,
                    'low_stock_threshold' => $thold,
                ];
            }
        }

        Models::saveProductVariants($productId, $variantsToSave);

        Helpers::setFlash('success', $isEdit ? 'Product updated successfully!' : 'Product created successfully!');
        Helpers::redirect(Helpers::url('admin/products'));
    }
}


$pageTitle = $isEdit ? 'Edit Product' : 'Add New Product';
$activeNav = 'products';
require __DIR__ . '/../layouts/admin_layout.php';
?>

<div class="admin-content" style="max-width: 1080px; margin: 0 auto;">
  <div class="admin-page-header">
    <div>
      <div class="breadcrumbs" style="font-size: 13px; color: #8A7E6F; margin-bottom: 6px;">
        <a href="<?= Helpers::url('admin') ?>">Dashboard</a>
        <span aria-hidden="true">/</span>
        <a href="<?= Helpers::url('admin/products') ?>">Products</a>
        <span aria-hidden="true">/</span>
        <span><?= $isEdit ? 'Edit Product' : 'Add New' ?></span>
      </div>
      <h1><?= $isEdit ? 'Edit' : 'Add' ?> <em>Product</em></h1>
      <p>Configure product information, pack size variants, selling prices in INR, and inventory.</p>
    </div>
    <div style="display: flex; gap: 12px; align-items: center;">
      <a href="<?= Helpers::url('admin/products') ?>" style="display: inline-flex; align-items: center; gap: 8px; background: #073D26; color: #FFFFFF; font-weight: 700; font-size: 14px; padding: 11px 20px; border-radius: 8px; border: 1px solid #135538; box-shadow: 0 4px 12px rgba(7, 61, 38, 0.25); text-decoration: none; transition: transform 0.15s, background 0.15s;">
        <span style="font-size: 18px; line-height: 1;">&larr;</span>
        <span>Back to Products</span>
      </a>
      <?php if ($isEdit && !empty($product)): ?>
        <form method="POST" action="<?= Helpers::url('admin/products') ?>" style="display: inline;" onsubmit="return confirm('Are you sure you want to PERMANENTLY delete \'<?= htmlspecialchars(addslashes($product['name'] ?? 'this product')) ?>\'? This action cannot be undone.');">
          <?= Helpers::csrfField() ?>
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
          <button type="submit" class="button" style="background: #FFF5F5; color: #C53030; border: 1px solid #FEB2B2; padding: 10px 16px; font-weight: 600; cursor: pointer; border-radius: 8px;">
            🗑️ Delete Product
          </button>
        </form>
      <?php endif; ?>
    </div>
  </div>

  <form method="POST" class="admin-form" enctype="multipart/form-data">
    <?= Helpers::csrfField() ?>
    <div class="admin-two-cols">

      <!-- Left Column: Primary Details -->
      <div class="admin-col">
        <div class="admin-card">
          <h2>Product Overview</h2>
          <div class="form-group-row">
            <div class="form-group" style="flex: 2;">
              <label for="prod_name">Product Name *</label>
              <input
                id="prod_name"
                name="name"
                required
                placeholder="e.g. California Royal Almonds"
                value="<?= htmlspecialchars($product['name'] ?? '') ?>"
                oninput="autoSlug(this.value)"
              >
            </div>
            <div class="form-group" style="flex: 1;">
              <label for="prod_slug">URL Slug *</label>
              <input
                id="prod_slug"
                name="slug"
                required
                placeholder="california-royal-almonds"
                value="<?= htmlspecialchars($product['slug'] ?? '') ?>"
              >
            </div>
          </div>

          <div class="form-group">
            <label for="prod_cat">Category</label>
            <select id="prod_cat" name="category_id">
              <option value="">— Select Category —</option>
              <?php foreach ($categories as $c): ?>
                <option value="<?= $c['id'] ?>" <?= ($product['category_id'] ?? '') === $c['id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($c['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label for="prod_desc">Description</label>
            <textarea id="prod_desc" name="description" rows="3" placeholder="Rich editorial description of this premium dry fruit..."><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
          </div>

          <!-- Product Image & Real File Upload Card Section -->
          <div class="form-group" style="background: #FDFBF7; border: 1px solid #E5DFD5; border-radius: 10px; padding: 18px; margin-top: 15px;">
            <label style="font-size: 14px; font-weight: 700; color: #073D26; margin-bottom: 8px; display: block;">
              📷 Product Image &amp; Media
            </label>

            <div style="display: flex; gap: 18px; align-items: flex-start; flex-wrap: wrap;">
              <!-- Live Preview Box -->
              <div style="width: 110px; height: 110px; border-radius: 10px; overflow: hidden; border: 2px solid #D9D2C5; background: #fff; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(0,0,0,0.06); flex-shrink: 0;">
                <img
                  id="prod_preview"
                  src="<?= Helpers::asset(ltrim($product['image_url'] ?? '/assets/mixed-nuts-reference.jpg', '/')) ?>"
                  alt="Product Preview"
                  style="width: 100%; height: 100%; object-fit: cover;"
                  onerror="this.src='<?= Helpers::asset('mixed-nuts-reference.jpg') ?>'"
                >
              </div>

              <!-- Upload & Controls -->
              <div style="flex: 1; min-width: 240px;">
                <div style="display: flex; gap: 10px; align-items: center; margin-bottom: 10px; flex-wrap: wrap;">
                  <input
                    type="file"
                    id="prod_file"
                    name="image_file"
                    accept="image/png,image/jpeg,image/webp,image/gif,image/svg+xml"
                    style="display: none;"
                    onchange="handleProductFileSelect(this)"
                  >
                  <button
                    type="button"
                    class="button button-primary"
                    onclick="document.getElementById('prod_file').click()"
                    style="padding: 9px 16px; font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; cursor: pointer;"
                  >
                    📁 Upload Image from Device
                  </button>
                  <span id="upload_status" style="font-size: 12px; color: #666;">PNG, JPG, WEBP, or SVG up to 10MB</span>
                </div>

                <div style="margin-top: 8px;">
                  <label for="prod_img" style="font-size: 12px; color: #7C7265; font-weight: 600; display: block; margin-bottom: 4px;">
                    Or specify image path / URL:
                  </label>
                  <input
                    id="prod_img"
                    name="image_url"
                    placeholder="/assets/shop-cashews.jpg"
                    value="<?= htmlspecialchars($product['image_url'] ?? '/assets/mixed-nuts-reference.jpg') ?>"
                    oninput="updateImagePreview(this.value)"
                    style="font-family: monospace; font-size: 13px; background: #fff;"
                  >
                </div>
              </div>
            </div>

            <!-- Visual Presets Picker -->
            <div style="margin-top: 14px; border-top: 1px dashed #E5DFD5; padding-top: 12px;">
              <span style="font-size: 12px; font-weight: 700; color: #7C7265; display: block; margin-bottom: 8px;">
                Quick Catalog Presets:
              </span>
              <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                <?php 
                $presets = [
                  ['name' => 'Almonds', 'path' => '/assets/pdp-hero-almonds.jpg'],
                  ['name' => 'Cashews', 'path' => '/assets/shop-cashews.jpg'],
                  ['name' => 'Pistachios', 'path' => '/assets/shop-pistachios-hd.jpg'],
                  ['name' => 'Walnuts', 'path' => '/assets/shop-walnuts.jpg'],
                  ['name' => 'Dates', 'path' => '/assets/shop-premium-dates.jpg'],
                  ['name' => 'Figs', 'path' => '/assets/shop-dried-figs.jpg'],
                  ['name' => 'Raisins', 'path' => '/assets/shop-golden-raisins.jpg'],
                  ['name' => 'Apricots', 'path' => '/assets/shop-dried-apricots.jpg'],
                  ['name' => 'Pumpkin Seeds', 'path' => '/assets/shop-pumpkin-seeds.jpg'],
                  ['name' => 'Sunflower Seeds', 'path' => '/assets/shop-sunflower-seeds.jpg'],
                  ['name' => 'Healthy Mix', 'path' => '/assets/shop-mixed-nuts.jpg'],
                  ['name' => 'Gift Hamper', 'path' => '/assets/mixed-fruit-reference.jpg'],
                ];
                foreach ($presets as $p):
                ?>
                  <button
                    type="button"
                    class="btn-action"
                    style="display: inline-flex; align-items: center; gap: 6px; padding: 5px 10px; border-radius: 6px; background: #fff; border: 1px solid #D5CEBF; font-size: 12px; cursor: pointer;"
                    onclick="selectPreset('<?= $p['path'] ?>')"
                  >
                    <img src="<?= Helpers::asset(ltrim($p['path'], '/')) ?>" alt="" style="width: 16px; height: 16px; border-radius: 3px; object-fit: cover;">
                    <span><?= $p['name'] ?></span>
                  </button>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
        </div>

        <!-- Variants Card -->
        <div class="admin-card">
          <div class="admin-card-head">
            <h2>Pack Sizes &amp; Pricing (INR)</h2>
            <button type="button" onclick="addVariantRow()" class="button button-secondary" style="font-size: 12px; padding: 4px 10px;">
              + Add Size
            </button>
          </div>

          <div class="admin-table-wrap">
            <table class="admin-table" id="variantsTable">
              <thead>
                <tr>
                  <th>Pack Size</th>
                  <th>SKU</th>
                  <th>Price (₹)</th>
                  <th>MRP / Compare (₹)</th>
                  <th>Stock</th>
                  <th>Min Alert</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($variants as $idx => $v): ?>
                  <tr>
                    <td>
                      <input type="hidden" name="variant_id[]" value="<?= htmlspecialchars($v['id'] ?? '') ?>">
                      <input name="variant_label[]" value="<?= htmlspecialchars($v['label']) ?>" required style="width: 85px;">
                    </td>
                    <td>
                      <input name="variant_sku[]" value="<?= htmlspecialchars($v['sku'] ?? '') ?>" placeholder="AUTO" style="width: 110px;">
                    </td>
                    <td>
                      <input type="number" step="1" min="1" name="variant_price[]" value="<?= round($v['price_paise'] / 100) ?>" required style="width: 85px;">
                    </td>
                    <td>
                      <input type="number" step="1" name="variant_compare[]" value="<?= !empty($v['compare_price_paise']) ? round($v['compare_price_paise'] / 100) : '' ?>" placeholder="—" style="width: 85px;">
                    </td>
                    <td>
                      <input type="number" min="0" name="variant_stock[]" value="<?= $v['stock'] ?>" required style="width: 70px;">
                    </td>
                    <td>
                      <input type="number" min="0" name="variant_threshold[]" value="<?= $v['low_stock_threshold'] ?? 5 ?>" style="width: 60px;">
                    </td>
                    <td>
                      <button type="button" onclick="this.closest('tr').remove()" style="background:none; border:none; color:#c5221f; font-size:16px; cursor:pointer;">×</button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Right Column: Settings & Publish -->
      <div class="admin-col">
        <div class="admin-card">
          <h2>Publishing &amp; Type</h2>
          
          <div class="form-checkbox-row" style="margin-bottom: 16px;">
            <label class="checkbox-label" style="display: flex; gap: 10px; align-items: center; cursor: pointer;">
              <input type="checkbox" name="active" value="1" <?= empty($product) || !empty($product['active']) ? 'checked' : '' ?>>
              <span><strong>Active &amp; Visible in Storefront</strong></span>
            </label>
          </div>

          <div class="form-checkbox-row" style="margin-bottom: 24px;">
            <label class="checkbox-label" style="display: flex; gap: 10px; align-items: center; cursor: pointer;">
              <input type="checkbox" name="is_combo" value="1" <?= !empty($product['is_combo']) ? 'checked' : '' ?>>
              <span><strong>Is Combo / Gifting Hamper</strong><br><small style="color:#7C7265;">Enables automatic bundle stock calculation</small></span>
            </label>
          </div>

          <button type="submit" class="button button-primary" style="width: 100%; justify-content: center; padding: 12px;">
            <?= $isEdit ? 'Save Changes' : 'Create Product' ?>
          </button>
        </div>
      </div>
    </div>
  </form>
</div>

<script>
  function autoSlug(val) {
    const slugInput = document.getElementById('prod_slug');
    if (!slugInput.dataset.manual) {
      slugInput.value = val.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
    }
  }
  document.getElementById('prod_slug').addEventListener('input', function() {
    this.dataset.manual = 'true';
  });

  function updateImagePreview(src) {
    const preview = document.getElementById('prod_preview');
    if (!src) return;
    if (src.startsWith('data:') || src.startsWith('blob:') || src.startsWith('http')) {
      preview.src = src;
    } else {
      let clean = src.replace(/^\/?assets\//, '');
      preview.src = '<?= Helpers::url('assets') ?>/' + clean;
    }
  }

  function selectPreset(path) {
    document.getElementById('prod_img').value = path;
    updateImagePreview(path);
    document.getElementById('upload_status').textContent = 'Selected preset: ' + path;
  }

  function handleProductFileSelect(input) {
    const file = input.files && input.files[0];
    if (!file) return;

    if (file.size > 10 * 1024 * 1024) {
      alert('Selected file exceeds 10MB limit.');
      input.value = '';
      return;
    }

    const reader = new FileReader();
    reader.onload = function(e) {
      document.getElementById('prod_preview').src = e.target.result;
      document.getElementById('upload_status').innerHTML = '<strong>' + file.name + '</strong> (' + Math.round(file.size / 1024) + ' KB) selected';
    };
    reader.readAsDataURL(file);

    // Instant async upload to server
    const formData = new FormData();
    formData.append('image', file);
    formData.append('prefix', 'prod');

    fetch('<?= Helpers::url('api/upload.php') ?>', {
      method: 'POST',
      body: formData
    })
    .then(r => r.json())
    .then(res => {
      if (res.success && res.url) {
        document.getElementById('prod_img').value = res.url;
        document.getElementById('upload_status').innerHTML = '✓ Uploaded &amp; Saved: <strong>' + res.url + '</strong>';
      }
    })
    .catch(() => {
      // Fallback: standard multipart form POST will save it!
    });
  }

  function addVariantRow() {
    const tbody = document.querySelector('#variantsTable tbody');
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td><input name="variant_label[]" value="250 g" required style="width: 85px;"></td>
      <td><input name="variant_sku[]" value="" placeholder="AUTO" style="width: 110px;"></td>
      <td><input type="number" step="1" min="1" name="variant_price[]" value="399" required style="width: 85px;"></td>
      <td><input type="number" step="1" name="variant_compare[]" value="" placeholder="—" style="width: 85px;"></td>
      <td><input type="number" min="0" name="variant_stock[]" value="20" required style="width: 70px;"></td>
      <td><input type="number" min="0" name="variant_threshold[]" value="5" style="width: 60px;"></td>
      <td><button type="button" onclick="this.closest('tr').remove()" style="background:none; border:none; color:#c5221f; font-size:16px; cursor:pointer;">×</button></td>
    `;
    tbody.appendChild(tr);
  }
</script>

<?php require __DIR__ . '/../layouts/admin_layout_bottom.php'; ?>
