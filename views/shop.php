<?php
/**
 * SVG Dry Fruits - Storefront Shop Catalog View
 * Faithfully matches Next.js src/components/ShopCatalog.tsx
 */
declare(strict_types=1);
require_once ROOT_PATH . '/src/Helpers.php';
require_once ROOT_PATH . '/src/Models.php';
require_once ROOT_PATH . '/src/Icon.php';

$categorySlug = $_GET['category'] ?? 'all';
$search = trim($_GET['search'] ?? $_GET['q'] ?? '');

$categories = Models::getCategories();
$products = Models::getProductsWithVariants($categorySlug === 'all' ? null : $categorySlug, true, $search ?: null);

$currentCategory = ($categorySlug !== 'all') ? Models::getCategoryBySlug($categorySlug) : null;
$pageTitle = $currentCategory ? $currentCategory['name'] . ' — Collection' : 'Explore Our Collection';
$activePage = ($categorySlug === 'combos') ? 'combos' : 'shop';

$defaultCatIcons = [
    'all' => Helpers::asset('cat-hd-all-products.png'),
    'nuts' => Helpers::asset('cat-hd-nuts.png'),
    'dried-fruits' => Helpers::asset('cat-hd-dried-fruits.png'),
    'seeds' => Helpers::asset('cat-hd-seeds.png'),
    'healthy-mix' => Helpers::asset('cat-hd-healthy-mix.png'),
    'combos' => Helpers::asset('cat-hd-gift-boxes.png'),
];

require __DIR__ . '/layouts/header.php';
?>

<div class="eoc-page">
  <!-- Heading: Explore Our Collection -->
  <div class="eoc-heading-wrap">
    <span class="eoc-leaf-icon" aria-hidden="true">
      <svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M14 3C14 3 6 8 6 16C6 20.4183 9.58172 24 14 24C18.4183 24 22 20.4183 22 16C22 8 14 3 14 3Z" fill="#A77052" opacity="0.85"/>
        <path d="M14 3L14 24" stroke="#0E3622" stroke-width="1.5" stroke-linecap="round"/>
        <path d="M14 12C14 12 10 14 8 18" stroke="#0E3622" stroke-width="1.2" stroke-linecap="round" opacity="0.6"/>
      </svg>
    </span>
    <h1 class="eoc-heading">
      <span class="eoc-heading-dark">Explore Our </span>
      <span class="eoc-heading-orange">Collection</span>
    </h1>
  </div>

  <!-- Draggable Category Tabs Row -->
  <div class="eoc-tabs-outer" style="position: relative; max-width: 1240px; margin: 0 auto 40px; padding: 0 16px;">
    <button type="button" onclick="scrollShopTabs(-300)" aria-label="Scroll categories left" class="eoc-tabs-arrow eoc-tabs-arrow-left" style="position: absolute; left: 2px; top: 50%; transform: translateY(-50%); z-index: 10; width: 38px; height: 38px; border-radius: 50%; background: #FFFFFF; border: 1.5px solid #D6C8B5; box-shadow: 0 4px 14px rgba(0,0,0,0.12); color: #073D26; font-size: 20px; font-weight: 700; display: flex; align-items: center; justify-content: center; cursor: pointer;">
      ‹
    </button>

    <div id="shop-tabs-container" class="eoc-tabs-wrap" style="padding: 8px 24px 18px; overflow-x: auto; scroll-behavior: smooth;">
      <div class="eoc-tabs-row" role="tablist" aria-label="Product categories">
        <!-- All Products Tab -->
        <a href="<?= Helpers::url('shop') ?>" class="eoc-tab-card<?= ($categorySlug === 'all') ? ' is-active' : '' ?>">
          <img src="<?= $defaultCatIcons['all'] ?>" alt="All Products" class="eoc-tab-card-img" draggable="false" style="pointer-events: none;" />
          <span class="eoc-tab-card-label" style="pointer-events: none;">All Products</span>
          <?php if ($categorySlug === 'all'): ?>
            <span class="eoc-tab-active-bar"></span>
          <?php endif; ?>
        </a>

        <!-- Dynamic Category Tabs -->
        <?php foreach ($categories as $cat): 
          $isActive = ($categorySlug === $cat['slug']);
          $icon = !empty($cat['image_url']) ? Helpers::asset($cat['image_url']) : ($defaultCatIcons[$cat['slug']] ?? $defaultCatIcons['nuts']);
        ?>
          <a href="<?= Helpers::url('shop?category=' . $cat['slug']) ?>" class="eoc-tab-card<?= $isActive ? ' is-active' : '' ?>">
            <img src="<?= $icon ?>" alt="<?= htmlspecialchars($cat['name']) ?>" class="eoc-tab-card-img" draggable="false" style="pointer-events: none;" />
            <span class="eoc-tab-card-label" style="pointer-events: none;"><?= htmlspecialchars($cat['name']) ?></span>
            <?php if ($isActive): ?>
              <span class="eoc-tab-active-bar"></span>
            <?php endif; ?>
          </a>
        <?php endforeach; ?>
      </div>
    </div>

    <button type="button" onclick="scrollShopTabs(300)" aria-label="Scroll categories right" class="eoc-tabs-arrow eoc-tabs-arrow-right" style="position: absolute; right: 2px; top: 50%; transform: translateY(-50%); z-index: 10; width: 38px; height: 38px; border-radius: 50%; background: #FFFFFF; border: 1.5px solid #D6C8B5; box-shadow: 0 4px 14px rgba(0,0,0,0.12); color: #073D26; font-size: 20px; font-weight: 700; display: flex; align-items: center; justify-content: center; cursor: pointer;">
      ›
    </button>
  </div>

  <!-- Product Cards Grid -->
  <main class="eoc-grid-wrap">
    <?php if (empty($products)): ?>
      <div style="text-align: center; padding: 80px 20px; color: #665C50;">
        <h3 style="font-family: var(--serif, serif); font-size: 28px; color: var(--pine); margin-bottom: 12px;">No products found</h3>
        <p style="font-size: 15px; color: #7C7265; margin-bottom: 24px;">Try selecting another category or clearing search filters.</p>
        <a href="<?= Helpers::url('shop') ?>" class="coll-view-all-btn">View All Products →</a>
      </div>
    <?php else: ?>
      <div class="eoc-cards-grid">
        <?php foreach ($products as $p): 
          $variants = !empty($p['variants']) ? $p['variants'] : [];
          $firstVar = !empty($variants) ? $variants[0] : null;
          $prodImg = !empty($p['image_url']) ? Helpers::asset($p['image_url']) : Helpers::asset('shop-pistachios-hd.jpg');
        ?>
          <article class="eoc-card" data-slug="<?= htmlspecialchars($p['slug']) ?>">
            <div class="eoc-card-img-wrap">
              <a href="<?= Helpers::url('products/' . $p['slug']) ?>" class="eoc-card-img-link" aria-label="View <?= htmlspecialchars($p['name']) ?>">
                <img src="<?= $prodImg ?>" alt="<?= htmlspecialchars($p['name']) ?>" class="eoc-card-img" loading="lazy" />
              </a>
              <button type="button" class="eoc-wishlist-btn" aria-label="Wishlist" onclick="this.classList.toggle('is-wishlisted')">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
                </svg>
              </button>
            </div>

            <div class="eoc-card-body">
              <h2 class="eoc-card-name">
                <a href="<?= Helpers::url('products/' . $p['slug']) ?>"><?= htmlspecialchars($p['name']) ?></a>
              </h2>

              <!-- Size Pills -->
              <div class="eoc-card-sizes" role="group" aria-label="Select weight for <?= htmlspecialchars($p['name']) ?>">
                <?php foreach ($variants as $vIdx => $v): ?>
                  <button type="button" 
                          class="eoc-size-pill<?= $vIdx === 0 ? ' is-selected' : '' ?>"
                          data-variant-id="<?= htmlspecialchars($v['id']) ?>"
                          data-price="<?= htmlspecialchars(Helpers::formatPaise((int)$v['price_paise'])) ?>"
                          data-label="<?= htmlspecialchars($v['label']) ?>"
                          onclick="selectEocCardSize(this)">
                    <?= htmlspecialchars($v['label']) ?>
                  </button>
                <?php endforeach; ?>
              </div>

              <!-- Footer: Price + Quantity Stepper + Cart Action -->
              <div class="eoc-card-footer" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
                <div style="display: flex; align-items: center; gap: 10px;">
                  <span class="eoc-card-price">
                    <?= $firstVar ? Helpers::formatPaise((int)$firstVar['price_paise']) : '₹—' ?>
                  </span>

                  <!-- Compact - / + Quantity Stepper -->
                  <div class="eoc-card-qty-stepper" style="display: inline-flex; align-items: center; border: 1.5px solid #073D26; border-radius: 6px; overflow: hidden; background: #fff;">
                    <button type="button" onclick="adjustCardQty(this, -1)" style="border: none; background: #FAF8F5; color: #073D26; width: 24px; height: 24px; display: inline-flex; align-items: center; justify-content: center; font-weight: 800; cursor: pointer; font-size: 14px; padding: 0;" aria-label="Decrease quantity">−</button>
                    <span class="eoc-card-qty-val" style="min-width: 22px; text-align: center; font-size: 12px; font-weight: 700; color: #073D26; padding: 0 2px;">1</span>
                    <button type="button" onclick="adjustCardQty(this, 1)" style="border: none; background: #FAF8F5; color: #073D26; width: 24px; height: 24px; display: inline-flex; align-items: center; justify-content: center; font-weight: 800; cursor: pointer; font-size: 14px; padding: 0;" aria-label="Increase quantity">+</button>
                  </div>
                </div>

                <div class="eoc-cart-action">
                  <button type="button" 
                          class="eoc-add-btn" 
                          data-product-name="<?= htmlspecialchars($p['name']) ?>"
                          data-image-url="<?= $prodImg ?>"
                          onclick="handleEocCardAdd(this)">
                    <?= Icon::render('cart', ['size' => 15]) ?>
                    <span>Add to Cart</span>
                  </button>
                </div>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </main>
</div>

<script>
function scrollShopTabs(offset) {
  var el = document.getElementById('shop-tabs-container');
  if (el) el.scrollBy({ left: offset, behavior: 'smooth' });
}
</script>

<?php require __DIR__ . '/layouts/footer.php'; ?>
