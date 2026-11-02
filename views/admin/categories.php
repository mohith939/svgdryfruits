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
      <form class="admin-form" method="POST">
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

        <div class="form-group">
          <label for="cat-img">Category Icon / Image (Displayed in Shop Tabs)</label>
          <input
            id="cat-img"
            name="image_url"
            value="/assets/cat-hd-nuts.png"
            placeholder="/assets/cat-hd-nuts.png"
          >
          <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: 8px;">
            <button type="button" class="btn-action" onclick="document.getElementById('cat-img').value='/assets/cat-hd-nuts.png'">Nuts</button>
            <button type="button" class="btn-action" onclick="document.getElementById('cat-img').value='/assets/cat-hd-dried-fruits.png'">Dried Fruits</button>
            <button type="button" class="btn-action" onclick="document.getElementById('cat-img').value='/assets/cat-hd-seeds.png'">Seeds</button>
            <button type="button" class="btn-action" onclick="document.getElementById('cat-img').value='/assets/cat-hd-healthy-mix.png'">Healthy Mix</button>
            <button type="button" class="btn-action" onclick="document.getElementById('cat-img').value='/assets/cat-hd-gift-boxes.png'">Combos</button>
            <button type="button" class="btn-action" onclick="document.getElementById('cat-img').value='/assets/cat-hd-all-products.png'">All Products</button>
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

<?php require __DIR__ . '/../layouts/admin_layout_bottom.php'; ?>
