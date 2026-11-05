<?php
/**
 * SVG Dry Fruits - Product Create & Edit Form
 * 1:1 match of Node.js src/app/admin/products/new and products/[id]
 */
declare(strict_types=1);
require_once ROOT_PATH . '/src/Helpers.php';
require_once ROOT_PATH . '/src/Models.php';

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
    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $categoryId = trim($_POST['category_id'] ?? '') ?: null;
    $description = trim($_POST['description'] ?? '');
    $imageUrl = trim($_POST['image_url'] ?? '/assets/mixed-nuts-reference.jpg');
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

            // Delete old variants and re-insert
            $pdo->prepare("DELETE FROM variants WHERE product_id = ?")->execute([$productId]);
        } else {
            $productId = 'prod_' . bin2hex(random_bytes(8));
            $stmt = $pdo->prepare("
                INSERT INTO products (id, name, slug, category_id, description, image_url, is_combo, active)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$productId, $name, $slug, $categoryId, $description, $imageUrl, $isCombo, $active]);
        }

        // Insert variants
        $labels = $_POST['variant_label'] ?? [];
        $skus = $_POST['variant_sku'] ?? [];
        $prices = $_POST['variant_price'] ?? [];
        $compares = $_POST['variant_compare'] ?? [];
        $stocks = $_POST['variant_stock'] ?? [];
        $thresholds = $_POST['variant_threshold'] ?? [];

        $insVar = $pdo->prepare("
            INSERT INTO variants (id, product_id, sku, label, price_paise, compare_price_paise, stock, low_stock_threshold, active)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)
        ");

        for ($i = 0; $i < count($labels); $i++) {
            $lbl = trim($labels[$i]);
            $prc = (float)($prices[$i] ?? 0);
            if ($lbl && $prc > 0) {
                $sku = trim($skus[$i] ?? '') ?: strtoupper($slug) . '-' . preg_replace('/\s+/', '', $lbl);
                $pricePaise = (int)round($prc * 100);
                $cmp = (float)($compares[$i] ?? 0);
                $comparePaise = $cmp > 0 ? (int)round($cmp * 100) : null;
                $stk = (int)($stocks[$i] ?? 10);
                $thold = (int)($thresholds[$i] ?? 5);
                $varId = 'var_' . bin2hex(random_bytes(8));
                $insVar->execute([$varId, $productId, $sku, $lbl, $pricePaise, $comparePaise, $stk, $thold]);
            }
        }

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
    <div style="display: flex; gap: 10px; align-items: center;">
      <a href="<?= Helpers::url('admin/products') ?>" class="button button-outline" style="background:#fff;">
        ← Back to Products
      </a>
      <?php if ($isEdit && !empty($product)): ?>
        <form method="POST" action="<?= Helpers::url('admin/products') ?>" style="display: inline;" onsubmit="return confirm('Are you sure you want to PERMANENTLY delete \'<?= htmlspecialchars(addslashes($product['name'] ?? 'this product')) ?>\'? This action cannot be undone.');">
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
          <button type="submit" class="button" style="background: #FFF5F5; color: #C53030; border: 1px solid #FEB2B2; padding: 8px 14px; font-weight: 600; cursor: pointer;">
            🗑️ Delete Product
          </button>
        </form>
      <?php endif; ?>
    </div>
  </div>

  <form method="POST" class="admin-form">
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
            <textarea id="prod_desc" name="description" rows="4" placeholder="Rich editorial description of this premium dry fruit..."><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
          </div>

          <div class="form-group">
            <label for="prod_img">Product Image Asset Path</label>
            <input
              id="prod_img"
              name="image_url"
              placeholder="/assets/shop-cashews.jpg"
              value="<?= htmlspecialchars($product['image_url'] ?? '/assets/mixed-nuts-reference.jpg') ?>"
            >
            <div style="margin-top: 8px; display: flex; gap: 8px; align-items: center;">
              <span style="font-size: 12px; color: #7C7265;">Presets:</span>
              <button type="button" class="btn-action" onclick="document.getElementById('prod_img').value='/assets/shop-cashews.jpg'">Cashews</button>
              <button type="button" class="btn-action" onclick="document.getElementById('prod_img').value='/assets/shop-pistachios-hd.jpg'">Pistachios</button>
              <button type="button" class="btn-action" onclick="document.getElementById('prod_img').value='/assets/shop-walnuts.jpg'">Walnuts</button>
              <button type="button" class="btn-action" onclick="document.getElementById('prod_img').value='/assets/cat-hd-nuts.png'">Nuts</button>
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
