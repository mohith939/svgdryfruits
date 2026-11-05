<?php
/**
 * SVG Dry Fruits - Categories Management
 * 1:1 match of Node.js src/app/admin/categories/page.tsx
 */
declare(strict_types=1);
require_once ROOT_PATH . '/src/Helpers.php';
require_once ROOT_PATH . '/src/Models.php';

$pdo = Database::getConnection();

// Handle form actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $imageUrl = trim($_POST['image_url'] ?? '/assets/cat-hd-nuts.png');
        $sortOrder = (int)($_POST['sort_order'] ?? 0);

        if (!empty($_FILES['image_file']['tmp_name'])) {
            $uploaded = Helpers::handleUpload($_FILES['image_file'], 'cat_');
            if ($uploaded) {
                $imageUrl = $uploaded;
            }
        }

        if ($name && $slug) {
            $id = 'cat_' . bin2hex(random_bytes(8));
            $stmt = $pdo->prepare("
                INSERT INTO categories (id, slug, name, description, image_url, sort_order, active)
                VALUES (?, ?, ?, ?, ?, ?, 1)
            ");
            $stmt->execute([$id, $slug, $name, $description ?: null, $imageUrl ?: null, $sortOrder]);
            Helpers::setFlash('success', "Category \"{$name}\" created successfully!");
        } else {
            Helpers::setFlash('error', "Category name and slug are required.");
        }
    } elseif ($action === 'update') {
        $id = trim($_POST['id'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $imageUrl = trim($_POST['image_url'] ?? '');
        $sortOrder = (int)($_POST['sort_order'] ?? 0);

        if (!empty($_FILES['image_file']['tmp_name'])) {
            $uploaded = Helpers::handleUpload($_FILES['image_file'], 'cat_');
            if ($uploaded) {
                $imageUrl = $uploaded;
            }
        }

        if ($id && $name && $slug) {
            $stmt = $pdo->prepare("
                UPDATE categories 
                SET name = ?, slug = ?, description = ?, image_url = ?, sort_order = ?
                WHERE id = ?
            ");
            $stmt->execute([$name, $slug, $description ?: null, $imageUrl ?: null, $sortOrder, $id]);
            Helpers::setFlash('success', "Category updated successfully!");
        }
    } elseif ($action === 'toggle_active') {
        $id = trim($_POST['id'] ?? '');
        $stmt = $pdo->prepare("UPDATE categories SET active = 1 - active WHERE id = ?");
        $stmt->execute([$id]);
        Helpers::setFlash('success', "Category visibility updated!");
    } elseif ($action === 'delete') {
        $id = trim($_POST['id'] ?? '');
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        Helpers::setFlash('success', "Category deleted.");
    }

    Helpers::redirect(Helpers::url('admin/categories'));
}

// Fetch categories with product counts
$categories = $pdo->query("
    SELECT c.*, COUNT(p.id) AS product_count 
    FROM categories c 
    LEFT JOIN products p ON p.category_id = c.id 
    GROUP BY c.id 
    ORDER BY c.sort_order ASC, c.name ASC
")->fetchAll();

$pageTitle = 'Catalog Categories';
$activeNav = 'categories';
require __DIR__ . '/../layouts/admin_layout.php';
?>

<div class="admin-content">
  <div class="admin-page-header">
    <div>
      <div class="breadcrumbs">
        <a href="<?= Helpers::url('admin') ?>">Dashboard</a>
        <span aria-hidden="true">/</span>
        <span>Categories</span>
      </div>
      <h1>Catalog <em>Categories</em></h1>
      <p>Create and customize product categories and icon images displayed in the storefront and filter bar.</p>
    </div>
  </div>

  <div class="admin-two-cols">
    <!-- Create Category Form -->
    <div class="admin-card">
      <h2>+ Add New Category</h2>
      <form class="admin-form" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="create">

        <div class="form-group-row">
          <div class="form-group">
            <label for="cat-name">Category Name *</label>
            <input
              id="cat-name"
              name="name"
              required
              placeholder="e.g. Organic Berries"
              oninput="document.getElementById('cat-slug').value = this.value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '')"
            >
          </div>

          <div class="form-group">
            <label for="cat-slug">URL Slug *</label>
            <input
              id="cat-slug"
              name="slug"
              required
              placeholder="organic-berries"
            >
          </div>
        </div>

        <div class="form-group">
          <label for="cat-desc">Description (optional)</label>
          <textarea
            id="cat-desc"
            name="description"
            rows="2"
            placeholder="Short subtitle for the category"
          ></textarea>
        </div>

        <div class="form-group" style="background: #FDFBF7; border: 1px solid #E5DFD5; border-radius: 8px; padding: 14px;">
          <label style="font-size: 13px; font-weight: 700; color: #073D26; margin-bottom: 6px; display: block;">
            Category Icon / Image (Displayed in Shop Tabs)
          </label>

          <div style="display: flex; gap: 14px; align-items: center; margin-bottom: 10px;">
            <div style="width: 52px; height: 52px; border-radius: 6px; overflow: hidden; border: 1px solid #DFD7CA; background: #fff; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
              <img
                id="cat_preview"
                src="<?= Helpers::asset('cat-hd-nuts.png') ?>"
                alt=""
                style="width: 100%; height: 100%; object-fit: contain;"
              >
            </div>
            <div>
              <input
                type="file"
                id="cat_file_input"
                name="image_file"
                accept="image/*"
                style="display: none;"
                onchange="handleCatFileSelect(this)"
              >
              <button
                type="button"
                class="button button-primary"
                onclick="document.getElementById('cat_file_input').click()"
                style="padding: 6px 14px; font-size: 12px; font-weight: 600; cursor: pointer;"
              >
                📁 Upload Category Icon
              </button>
              <div id="cat_upload_status" style="font-size: 11px; color: #777; margin-top: 3px;">
                PNG, WEBP, or JPG image
              </div>
            </div>
          </div>

          <label for="cat-img" style="font-size: 11px; color: #7C7265; font-weight: 600; display: block; margin-bottom: 3px;">
            Or Icon Asset Path:
          </label>
          <input
            id="cat-img"
            name="image_url"
            value="/assets/cat-hd-nuts.png"
            placeholder="/assets/cat-hd-nuts.png"
            oninput="updateCatPreview(this.value)"
            style="font-family: monospace; font-size: 12px; background: #fff;"
          >
          <div style="display: flex; gap: 6px; flex-wrap: wrap; margin-top: 8px;">
            <button type="button" class="btn-action" onclick="selectCatPreset('/assets/cat-hd-nuts.png')">Nuts</button>
            <button type="button" class="btn-action" onclick="selectCatPreset('/assets/cat-hd-dried-fruits.png')">Dried Fruits</button>
            <button type="button" class="btn-action" onclick="selectCatPreset('/assets/cat-hd-seeds.png')">Seeds</button>
            <button type="button" class="btn-action" onclick="selectCatPreset('/assets/cat-hd-healthy-mix.png')">Healthy Mix</button>
            <button type="button" class="btn-action" onclick="selectCatPreset('/assets/cat-hd-gift-boxes.png')">Combos</button>
            <button type="button" class="btn-action" onclick="selectCatPreset('/assets/cat-hd-all-products.png')">All Products</button>
          </div>
        </div>

        <div class="form-group" style="max-width: 180px;">
          <label for="cat-sort">Display Sort Order</label>
          <input
            id="cat-sort"
            type="number"
            name="sort_order"
            min="0"
            value="1"
          >
        </div>

        <button type="submit" class="button button-primary">
          Add Category
        </button>
      </form>
    </div>

    <!-- Categories List -->
    <div class="admin-card">
      <h2>All Categories (<?= count($categories) ?>)</h2>
      <?php if (empty($categories)): ?>
        <p class="admin-empty-text">No categories found.</p>
      <?php else: ?>
        <div class="admin-table-wrap">
          <table class="admin-table">
            <thead>
              <tr>
                <th>Icon</th>
                <th>Category</th>
                <th>Products</th>
                <th>Order</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($categories as $c): ?>
                <tr>
                  <td>
                    <?php if (!empty($c['image_url'])): ?>
                      <img
                        src="<?= Helpers::asset(ltrim($c['image_url'], '/')) ?>"
                        alt=""
                        style="width: 44px; height: 44px; object-fit: contain; border-radius: 6px; background: #f6f3eb; padding: 2px;"
                      >
                    <?php else: ?>
                      <span style="font-size: 11px; color: #888;">No icon</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <strong><?= htmlspecialchars($c['name']) ?></strong>
                    <div style="font-size: 11px; color: #8A7E6F;">/<?= htmlspecialchars($c['slug']) ?></div>
                  </td>
                  <td>
                    <span class="badge badge-neutral"><?= (int)$c['product_count'] ?> products</span>
                  </td>
                  <td><?= (int)$c['sort_order'] ?></td>
                  <td>
                    <form method="POST" style="display:inline;">
                      <input type="hidden" name="action" value="toggle_active">
                      <input type="hidden" name="id" value="<?= $c['id'] ?>">
                      <button
                        type="submit"
                        class="badge-toggle <?= $c['active'] ? 'badge-success' : 'badge-danger' ?>"
                        title="Click to toggle active state"
                      >
                        <?= $c['active'] ? 'Active' : 'Hidden' ?>
                      </button>
                    </form>
                  </td>
                  <td>
                    <div style="display: flex; gap: 10px; align-items: center;">
                      <a href="<?= Helpers::url('shop?category=' . $c['slug']) ?>" target="_blank" class="admin-action-link">
                        View ↗
                      </a>
                      <form method="POST" style="display: inline;" onsubmit="return confirm('Delete category <?= htmlspecialchars(addslashes($c['name'])) ?>?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $c['id'] ?>">
                        <button type="submit" class="admin-remove-btn">
                          Delete
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
  function updateCatPreview(src) {
    const preview = document.getElementById('cat_preview');
    if (!src) return;
    if (src.startsWith('data:') || src.startsWith('blob:') || src.startsWith('http')) {
      preview.src = src;
    } else {
      let clean = src.replace(/^\/?assets\//, '');
      preview.src = '<?= Helpers::url('assets') ?>/' + clean;
    }
  }

  function selectCatPreset(path) {
    document.getElementById('cat-img').value = path;
    updateCatPreview(path);
  }

  function handleCatFileSelect(input) {
    const file = input.files && input.files[0];
    if (!file) return;

    if (file.size > 10 * 1024 * 1024) {
      alert('Selected file exceeds 10MB limit.');
      input.value = '';
      return;
    }

    const reader = new FileReader();
    reader.onload = function(e) {
      document.getElementById('cat_preview').src = e.target.result;
      document.getElementById('cat_upload_status').innerHTML = '<strong>' + file.name + '</strong> selected';
    };
    reader.readAsDataURL(file);

    // Async upload to api/upload.php
    const formData = new FormData();
    formData.append('image', file);
    formData.append('prefix', 'cat');

    fetch('<?= Helpers::url('api/upload.php') ?>', {
      method: 'POST',
      body: formData
    })
    .then(r => r.json())
    .then(res => {
      if (res.success && res.url) {
        document.getElementById('cat-img').value = res.url;
        document.getElementById('cat_upload_status').innerHTML = '✓ Uploaded: <strong>' + res.url + '</strong>';
      }
    })
    .catch(() => {});
  }
</script>

<?php require __DIR__ . '/../layouts/admin_layout_bottom.php'; ?>
