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

  <!-- High-Visibility Unmistakable Top Navigation Bar -->
  <div class="admin-top-action-bar" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px; background: #073D26; color: #FFFFFF; padding: 14px 22px; border-radius: 10px; margin-bottom: 22px; box-shadow: 0 4px 16px rgba(7,61,38,0.25);">
    <div style="display: flex; align-items: center; gap: 14px; flex-wrap: wrap;">
      <a href="<?= Helpers::url('admin/products') ?>" style="display: inline-flex; align-items: center; gap: 10px; background: #FFFFFF; color: #073D26; font-weight: 800; font-size: 14px; padding: 10px 20px; border-radius: 8px; text-decoration: none; box-shadow: 0 2px 8px rgba(0,0,0,0.2); transition: all 0.15s;">
        <span style="font-size: 18px; line-height: 1;">&larr;</span>
        <span>Back to Products List</span>
      </a>
      <span style="font-size: 13px; color: rgba(255,255,255,0.85); font-weight: 500;">
        <?= $isEdit ? 'Editing: ' . htmlspecialchars($product['name'] ?? 'Product') : 'Creating New Product' ?>
      </span>
    </div>
    <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
      <?php if ($isEdit && !empty($product)): ?>
        <a href="<?= Helpers::url('products/' . $product['slug']) ?>" target="_blank" style="color: #F8F5EE; font-size: 13px; font-weight: 600; text-decoration: underline; padding: 6px 10px;">
          View Live on Storefront ↗
        </a>
      <?php endif; ?>
      <button type="submit" form="productMainForm" class="button" style="background: #E8B647; color: #073D26; font-weight: 800; font-size: 14px; padding: 11px 24px; border-radius: 8px; border: none; cursor: pointer; box-shadow: 0 2px 8px rgba(0,0,0,0.25);">
        💾 <?= $isEdit ? 'Save Changes' : 'Publish Product' ?>
      </button>
    </div>
  </div>

  <div class="admin-page-header" style="flex-wrap: wrap; gap: 16px; margin-bottom: 20px;">
    <div>
      <div class="breadcrumbs" style="font-size: 13px; color: #8A7E6F; margin-bottom: 6px;">
        <a href="<?= Helpers::url('admin') ?>">Dashboard</a>
        <span aria-hidden="true">/</span>
        <a href="<?= Helpers::url('admin/products') ?>">Products</a>
        <span aria-hidden="true">/</span>
        <span><?= $isEdit ? 'Edit Product' : 'Add New' ?></span>
      </div>
      <h1><?= $isEdit ? 'Edit' : 'Add' ?> <em>Product</em></h1>
      <p>Configure product information, high-resolution photos, pack size variants, selling prices, and stock.</p>
    </div>
    <div style="display: flex; gap: 12px; align-items: center;">
      <a href="<?= Helpers::url('admin/products') ?>" style="display: inline-flex; align-items: center; gap: 8px; background: #FAF7F2; color: #073D26; font-weight: 700; font-size: 13.5px; padding: 10px 18px; border-radius: 8px; border: 1.5px solid #073D26; text-decoration: none;">
        <span>&larr;</span>
        <span>Back to Products</span>
      </a>
      <?php if ($isEdit && !empty($product)): ?>
        <form method="POST" action="<?= Helpers::url('admin/products') ?>" style="display: inline;" onsubmit="return confirm('Are you sure you want to PERMANENTLY delete \'<?= htmlspecialchars(addslashes($product['name'] ?? 'this product')) ?>\'? This action cannot be undone.');">
          <?= Helpers::csrfField() ?>
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
          <button type="submit" class="button" style="background: #FFF5F5; color: #C53030; border: 1px solid #FEB2B2; padding: 10px 16px; font-weight: 600; cursor: pointer; border-radius: 8px;">
            🗑️ Delete
          </button>
        </form>
      <?php endif; ?>
    </div>
  </div>

  <form method="POST" id="productMainForm" class="admin-form" enctype="multipart/form-data">
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
            <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 4px;">
              <label for="prod_cat" style="margin-bottom: 0;">Category</label>
              <a href="<?= Helpers::url('admin/categories') ?>" target="_blank" style="font-size: 12px; color: #073D26; font-weight: 700; text-decoration: underline;">
                🎨 Manage &amp; Add Category Images &rarr;
              </a>
            </div>
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
          <div class="form-group" style="background: #FAF8F5; border: 2px solid #D9D2C5; border-radius: 12px; padding: 20px; margin-top: 18px; box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
              <label style="font-size: 15px; font-weight: 800; color: #073D26; margin: 0; display: flex; align-items: center; gap: 8px;">
                <span>📷 Product Image &amp; Media</span>
                <span style="font-size: 11px; background: #073D26; color: #fff; padding: 2px 8px; border-radius: 10px; font-weight: 600;">Main Photo</span>
              </label>
            </div>

            <div style="display: flex; gap: 20px; align-items: flex-start; flex-wrap: wrap;">
              <!-- Live Preview Box -->
              <div style="width: 130px; height: 130px; border-radius: 12px; overflow: hidden; border: 2px solid #073D26; background: #fff; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(0,0,0,0.08); flex-shrink: 0; position: relative;">
                <img
                  id="prod_preview"
                  src="<?= Helpers::asset(ltrim($product['image_url'] ?? '/assets/mixed-nuts-reference.jpg', '/')) ?>"
                  alt="Product Preview"
                  style="width: 100%; height: 100%; object-fit: cover;"
                  onerror="this.src='<?= Helpers::asset('mixed-nuts-reference.jpg') ?>'"
                >
              </div>

              <!-- Upload & Controls -->
              <div style="flex: 1; min-width: 250px;">
                <div style="display: flex; gap: 10px; align-items: center; margin-bottom: 12px; flex-wrap: wrap;">
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
                    class="button"
                    onclick="document.getElementById('prod_file').click()"
                    style="background: #073D26; color: #FFFFFF; padding: 11px 22px; font-size: 14px; font-weight: 700; display: inline-flex; align-items: center; gap: 8px; cursor: pointer; border-radius: 8px; border: none; box-shadow: 0 2px 8px rgba(7,61,38,0.25);"
                  >
                    📁 Upload Image from Device
                  </button>
                  <span id="upload_status" style="font-size: 12px; color: #555; font-weight: 500;">
                    JPG, PNG, WEBP, or SVG up to 10MB
                  </span>
                </div>

                <div style="margin-top: 10px;">
                  <label for="prod_img" style="font-size: 12px; color: #5D5447; font-weight: 700; display: block; margin-bottom: 4px;">
                    Or specify image path / custom URL:
                  </label>
                  <input
                    id="prod_img"
                    name="image_url"
                    placeholder="/assets/shop-cashews.jpg"
                    value="<?= htmlspecialchars($product['image_url'] ?? '/assets/mixed-nuts-reference.jpg') ?>"
                    oninput="updateImagePreview(this.value)"
                    style="font-family: monospace; font-size: 13px; background: #fff; width: 100%; padding: 8px 12px; border: 1px solid #CCC; border-radius: 6px;"
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
    formData.append('csrf_token', '<?= Helpers::csrfToken() ?>');
    formData.append('image', file);
    formData.append('prefix', 'prod');

    document.getElementById('upload_status').innerHTML = '<em>Uploading image...</em>';

    fetch('<?= Helpers::url('api/upload.php') ?>', {
      method: 'POST',
      body: formData
    })
    .then(r => r.json())
    .then(res => {
      if (res.success && res.url) {
        document.getElementById('prod_img').value = res.url;
        document.getElementById('upload_status').innerHTML = '<span style="color: #2E7D32; font-weight: 700;">✓ Uploaded successfully!</span> Saved to ' + res.url;
      } else {
        document.getElementById('upload_status').innerHTML = '<span style="color: #C53030;">Upload warning: ' + (res.error || 'Server error') + '. Form submit will still save file.</span>';
      }
    })
    .catch(() => {
      document.getElementById('upload_status').innerHTML = '<span style="color: #666;">File selected. Full upload will complete on saving product.</span>';
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
