<?php
/**
 * SVG Dry Fruits - Product Detail Page (PDP)
 * Faithfully matches Next.js src/components/ProductDetailView.tsx
 */
declare(strict_types=1);
require_once ROOT_PATH . '/src/Helpers.php';
require_once ROOT_PATH . '/src/Models.php';
require_once ROOT_PATH . '/src/Icon.php';

$slug = $slug ?? '';
$product = Models::getProductBySlug($slug);

if (!$product) {
    http_response_code(404);
    echo "<div style='text-align:center; padding:100px 20px; font-family:sans-serif;'>";
    echo "<h1 style='font-size:36px; color:#203c32;'>Product Not Found</h1>";
    echo "<p><a href='" . Helpers::url('shop') . "'>Return to Shop &rarr;</a></p>";
    echo "</div>";
    exit;
}

$variants = $product['variants'] ?? [];
$firstVar = !empty($variants) ? $variants[0] : null;

$pageTitle = $product['name'] . ' (Premium Grade) — SVG Dry Fruits';
$activePage = 'shop';

// Image galleries
$defaultImg = !empty($product['image_url']) ? Helpers::asset($product['image_url']) : Helpers::asset('pdp-hero-almonds.jpg');
$galleryImages = [
    $defaultImg,
    Helpers::asset('pdp-thumb-1.jpg'),
    Helpers::asset('pdp-thumb-2.jpg'),
    Helpers::asset('pdp-thumb-3.jpg'),
];

$pricePaise = $firstVar ? (int)$firstVar['price_paise'] : 129000;
$comparePaise = ($firstVar && !empty($firstVar['compare_price_paise'])) ? (int)$firstVar['compare_price_paise'] : (int)round($pricePaise * 1.15);
$discountPercent = $comparePaise > $pricePaise ? (int)round((($comparePaise - $pricePaise) / $comparePaise) * 100) : 13;

$badgeText = ["PREMIUM", "SELECTED", strtoupper(explode(' ', $product['name'])[0] ?? 'GRADE')];

$faqs = [
    [
        'q' => 'Where are these sourced from?',
        'a' => 'Our dry fruits are sourced directly from verified, certified farms renowned for their ideal climate, nutrient-rich soils, and sustainable harvesting practices.'
    ],
    [
        'q' => 'Are these raw or roasted?',
        'a' => 'These are 100% natural, premium grade dry fruits with no added oils, preservatives, or artificial flavours.'
    ],
    [
        'q' => 'How should I store them?',
        'a' => 'To maintain maximum crunch, natural oils, and freshness, store them in an airtight container in a cool, dry place away from direct sunlight.'
    ],
    [
        'q' => 'Do you offer bulk orders?',
        'a' => 'Yes! We supply cafes, bakeries, corporate gifting programs, and families with bulk quantities. Please reach out via our Bulk Orders page or contact our team directly.'
    ],
];

require __DIR__ . '/layouts/header.php';
?>

<div class="pdp-v2-page">
  <!-- SECTION 1: HERO PDP (GALLERY + BUY BOX) -->
  <section class="pdp-v2-hero">
    <div class="pdp-v2-container pdp-v2-hero-grid">
      <!-- Left Column: Vertical Thumbnails + Main Image -->
      <div class="pdp-v2-gallery">
        <div class="pdp-v2-thumbs-col">
          <button type="button" class="pdp-v2-thumb-arrow" aria-label="Previous thumbnail" onclick="cyclePdpThumb(-1)">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="18 15 12 9 6 15"></polyline>
            </svg>
          </button>

          <div class="pdp-v2-thumbs-list" id="pdp-thumbs-list">
            <?php foreach ($galleryImages as $tIdx => $tImg): ?>
              <button type="button" 
                      class="pdp-v2-thumb-btn<?= $tIdx === 0 ? ' is-active' : '' ?>" 
                      aria-label="View photo <?= $tIdx + 1 ?>"
                      onclick="selectPdpImage('<?= $tImg ?>', this)">
                <img src="<?= $tImg ?>" alt="" class="pdp-v2-thumb-img" />
              </button>
            <?php endforeach; ?>
          </div>

          <button type="button" class="pdp-v2-thumb-arrow" aria-label="Next thumbnail" onclick="cyclePdpThumb(1)">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="6 9 12 15 18 9"></polyline>
            </svg>
          </button>
        </div>

        <!-- Main Hero Photo with circular badge overlay -->
        <div class="pdp-v2-main-image-wrap">
          <img id="pdp-main-img" src="<?= $defaultImg ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="pdp-v2-main-img" />
          <div class="pdp-v2-circle-badge" aria-hidden="true">
            <?php foreach ($badgeText as $bLine): ?>
              <span class="pdp-v2-circle-line"><?= htmlspecialchars($bLine) ?></span>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- Right Column: Product Info & Actions -->
      <div class="pdp-v2-info">
        <span class="pdp-v2-kicker"><?= htmlspecialchars(strtoupper($product['category_name'] ?? 'DRY FRUITS')) ?></span>
        <h1 class="pdp-v2-title"><?= htmlspecialchars($product['name']) ?> (Premium Grade)</h1>

        <!-- Price Row -->
        <div class="pdp-v2-price-row">
          <span class="pdp-v2-price-current" id="pdp-price-text"><?= Helpers::formatPaise($pricePaise) ?></span>
          <span class="pdp-v2-price-compare" id="pdp-compare-text" <?= ($comparePaise > $pricePaise) ? '' : 'style="display:none;"' ?>><?= Helpers::formatPaise($comparePaise) ?></span>
          <span class="pdp-v2-save-pill" id="pdp-save-pill" <?= ($discountPercent > 0) ? '' : 'style="display:none;"' ?>>Save <?= $discountPercent ?>%</span>
        </div>
        <p class="pdp-v2-tax-subtext">Inclusive of all taxes</p>

        <!-- Lede -->
        <p class="pdp-v2-lede">
          <?= htmlspecialchars($product['description'] ?: "Naturally nutritious, crunchy and pure — our {$product['name']} are carefully sourced from trusted growers, selected for exceptional taste and quality.") ?>
        </p>

        <!-- Weight / Variant Selection -->
        <div class="pdp-v2-variants-block">
          <span class="pdp-v2-variants-label">Weight</span>
          <div class="pdp-v2-variants-row" role="radiogroup" aria-label="Select weight">
            <?php foreach ($variants as $vIdx => $v): ?>
              <button type="button" 
                      role="radio" 
                      aria-checked="<?= $vIdx === 0 ? 'true' : 'false' ?>" 
                      class="pdp-v2-weight-pill<?= $vIdx === 0 ? ' is-active' : '' ?>"
                      data-variant-id="<?= htmlspecialchars($v['id']) ?>"
                      data-price-paise="<?= (int)$v['price_paise'] ?>"
                      data-price-formatted="<?= htmlspecialchars(Helpers::formatPaise((int)$v['price_paise'])) ?>"
                      data-compare-paise="<?= (int)($v['compare_price_paise'] ?? 0) ?>"
                      data-compare-formatted="<?= (!empty($v['compare_price_paise'])) ? htmlspecialchars(Helpers::formatPaise((int)$v['compare_price_paise'])) : '' ?>"
                      data-label="<?= htmlspecialchars($v['label']) ?>"
                      onclick="selectPdpVariant(this)">
                <?= htmlspecialchars($v['label']) ?>
              </button>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Quantity & Add to Cart Action -->
        <div class="pdp-v2-actions-row">
          <div class="pdp-v2-qty-box" aria-label="Select quantity">
            <button type="button" class="pdp-v2-qty-btn" aria-label="Decrease quantity" onclick="adjustPdpQty(-1)">−</button>
            <span class="pdp-v2-qty-display" id="pdp-qty-display" aria-live="polite">1</span>
            <button type="button" class="pdp-v2-qty-btn" aria-label="Increase quantity" onclick="adjustPdpQty(1)">+</button>
          </div>

          <button type="button" 
                  class="pdp-v2-add-btn" 
                  id="pdp-add-btn" 
                  data-product-name="<?= htmlspecialchars($product['name']) ?>"
                  data-image-url="<?= $defaultImg ?>"
                  onclick="handlePdpAddToCart()">
            Add to Cart
          </button>
        </div>

        <!-- 4 Trust Badges in a Horizontal Row -->
        <div class="pdp-v2-trust-grid">
          <div class="pdp-v2-trust-item">
            <div class="pdp-v2-trust-icon-box">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#0E3622" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                <path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z"></path>
                <path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"></path>
              </svg>
            </div>
            <span class="pdp-v2-trust-label">100% Natural</span>
          </div>

          <div class="pdp-v2-trust-item">
            <div class="pdp-v2-trust-icon-box">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#0E3622" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                <polyline points="9 12 11 14 15 10"></polyline>
              </svg>
            </div>
            <span class="pdp-v2-trust-label">Premium Grade</span>
          </div>

          <div class="pdp-v2-trust-item">
            <div class="pdp-v2-trust-icon-box">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#0E3622" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                <rect x="1" y="3" width="15" height="13"></rect>
                <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon>
                <circle cx="5.5" cy="18.5" r="2.5"></circle>
                <circle cx="18.5" cy="18.5" r="2.5"></circle>
              </svg>
            </div>
            <span class="pdp-v2-trust-label">Fast Delivery</span>
          </div>

          <div class="pdp-v2-trust-item">
            <div class="pdp-v2-trust-icon-box">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#0E3622" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"></path>
              </svg>
            </div>
            <span class="pdp-v2-trust-label">No Preservatives</span>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- SECTION 2: GOODNESS IN EVERY BITE (BANNER) -->
  <section class="pdp-v2-banner-sec">
    <div class="pdp-v2-container pdp-v2-banner-grid">
      <div class="pdp-v2-banner-content">
        <span class="pdp-v2-banner-kicker">GOODNESS IN EVERY BITE</span>
        <h2 class="pdp-v2-banner-title">Pure. Wholesome. Naturally Better.</h2>
        <p class="pdp-v2-banner-desc">Sourced from the finest growers, our <?= strtolower(htmlspecialchars($product['name'])) ?> are rich in natural nutrients, wholesome and perfect for your everyday vitality.</p>

        <div class="pdp-v2-banner-badges">
          <div class="pdp-v2-feat-item">
            <div class="pdp-v2-feat-circle">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#0E3622" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 2L15 8L21 9L17 14L18 20L12 17L6 20L7 14L3 9L9 8L12 2Z"></path>
              </svg>
            </div>
            <span class="pdp-v2-feat-text">Rich in Nutrients</span>
          </div>

          <div class="pdp-v2-feat-item">
            <div class="pdp-v2-feat-circle">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#0E3622" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
              </svg>
            </div>
            <span class="pdp-v2-feat-text">Supports Heart Health</span>
          </div>

          <div class="pdp-v2-feat-item">
            <div class="pdp-v2-feat-circle">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#0E3622" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <polyline points="12 6 12 12 16 14"></polyline>
              </svg>
            </div>
            <span class="pdp-v2-feat-text">Great for Daily Snacking</span>
          </div>
        </div>
      </div>

      <div class="pdp-v2-banner-media">
        <img src="<?= Helpers::asset('pdp-banner-hd.jpg') ?>" alt="Scattered nuts close-up" class="pdp-v2-banner-img" loading="lazy" />
      </div>
    </div>
  </section>

  <!-- SECTION 3: FROM NATURE'S FINEST ORCHARDS -->
  <section class="pdp-v2-orchard-sec">
    <div class="pdp-v2-container pdp-v2-orchard-grid">
      <div class="pdp-v2-orchard-media">
        <img src="<?= Helpers::asset('pdp-orchard-hd.jpg') ?>" alt="Orchard trees with fresh harvest" class="pdp-v2-orchard-img" loading="lazy" />
      </div>

      <div class="pdp-v2-orchard-content">
        <h2 class="pdp-v2-orchard-title">FROM NATURE'S FINEST ORCHARDS</h2>
        <p class="pdp-v2-orchard-desc">Our <?= strtolower(htmlspecialchars($product['name'])) ?> are sourced directly from trusted growers, carefully selected, cleaned, and sealed to ensure peak freshness in every pack.</p>
        <div class="pdp-v2-orchard-line"></div>
        <div class="pdp-v2-orchard-pillars">
          <div class="pdp-v2-pillar-item"><span class="pdp-v2-pillar-label">PREMIUM FARMS</span></div>
          <div class="pdp-v2-pillar-item"><span class="pdp-v2-pillar-label">CAREFULLY SELECTED</span></div>
          <div class="pdp-v2-pillar-item"><span class="pdp-v2-pillar-label">PACKED FOR FRESHNESS</span></div>
        </div>
      </div>
    </div>
  </section>

  <!-- SECTION 4: WAYS TO ENJOY -->
  <section class="pdp-v2-ways-sec">
    <div class="pdp-v2-container pdp-v2-ways-grid">
      <div class="pdp-v2-ways-header">
        <span class="pdp-v2-ways-kicker">WAYS TO ENJOY</span>
        <h2 class="pdp-v2-ways-title">Simple Ingredients.<br />Endless Possibilities.</h2>
      </div>

      <div class="pdp-v2-ways-cards">
        <div class="pdp-v2-way-card">
          <div class="pdp-v2-way-img-wrap">
            <img src="<?= Helpers::asset('pdp-way-snack-hd.jpg') ?>" alt="Snack on the go" class="pdp-v2-way-img" loading="lazy" />
          </div>
          <span class="pdp-v2-way-caption">Snack on the go</span>
        </div>
        <div class="pdp-v2-way-card">
          <div class="pdp-v2-way-img-wrap">
            <img src="<?= Helpers::asset('pdp-way-smoothie-hd.jpg') ?>" alt="Add to your smoothies" class="pdp-v2-way-img" loading="lazy" />
          </div>
          <span class="pdp-v2-way-caption">Add to your smoothies</span>
        </div>
        <div class="pdp-v2-way-card">
          <div class="pdp-v2-way-img-wrap">
            <img src="<?= Helpers::asset('pdp-way-cookies-hd.jpg') ?>" alt="Use in your favourite recipes" class="pdp-v2-way-img" loading="lazy" />
          </div>
          <span class="pdp-v2-way-caption">Use in your favourite recipes</span>
        </div>
      </div>
    </div>
  </section>

  <!-- SECTION 5: NUTRITION INFORMATION -->
  <section class="pdp-v2-nutrition-sec">
    <div class="pdp-v2-container pdp-v2-nutrition-grid">
      <div class="pdp-v2-nutrition-media">
        <img src="<?= Helpers::asset('pdp-nutrition-hd.jpg') ?>" alt="Nutrient-dense natural dry fruits" class="pdp-v2-nutrition-img" loading="lazy" />
      </div>

      <div class="pdp-v2-nutrition-content">
        <span class="pdp-v2-nutrition-kicker">NUTRITION INFORMATION</span>
        <h2 class="pdp-v2-nutrition-title">A Natural Source of<br />Good Nutrition</h2>
        <p class="pdp-v2-nutrition-desc"><?= htmlspecialchars($product['name']) ?> are packed with essential vitamins and clean nutrients that support overall vitality.</p>
        <div class="pdp-v2-nutrition-line"></div>

        <div class="pdp-v2-nutrients-grid">
          <div class="pdp-v2-nutrient-item">
            <div class="pdp-v2-nutrient-icon">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#0E3622" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z"></path>
                <path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"></path>
              </svg>
            </div>
            <div class="pdp-v2-nutrient-text">
              <span class="pdp-v2-nutrient-name">Essential Nutrients</span>
              <span class="pdp-v2-nutrient-sub">Clean cellular energy</span>
            </div>
          </div>

          <div class="pdp-v2-nutrient-item">
            <div class="pdp-v2-nutrient-icon">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#0E3622" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
              </svg>
            </div>
            <div class="pdp-v2-nutrient-text">
              <span class="pdp-v2-nutrient-name">Healthy Natural Fats</span>
              <span class="pdp-v2-nutrient-sub">Promotes heart health</span>
            </div>
          </div>

          <div class="pdp-v2-nutrient-item">
            <div class="pdp-v2-nutrient-icon">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#0E3622" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M6 5v14M18 5v14M2 9v6M22 9v6M6 12h12"></path>
              </svg>
            </div>
            <div class="pdp-v2-nutrient-text">
              <span class="pdp-v2-nutrient-name">Natural Plant Protein</span>
              <span class="pdp-v2-nutrient-sub">Supports muscle recovery</span>
            </div>
          </div>

          <div class="pdp-v2-nutrient-item">
            <div class="pdp-v2-nutrient-icon">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#0E3622" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M2 22 12 12M12 2v20M8 6l4 4M16 6l-4 4M8 12l4 4M16 12l-4 4"></path>
              </svg>
            </div>
            <div class="pdp-v2-nutrient-text">
              <span class="pdp-v2-nutrient-name">Dietary Fibre</span>
              <span class="pdp-v2-nutrient-sub">Supports healthy digestion</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- SECTION 6: FAQS ACCORDION -->
  <section class="pdp-v2-faq-sec">
    <div class="pdp-v2-container pdp-v2-faq-wrap">
      <div class="pdp-v2-faq-header">
        <span class="pdp-v2-faq-kicker">FREQUENTLY ASKED QUESTIONS</span>
        <h2 class="pdp-v2-faq-title">Everything You Need to Know</h2>
        <div class="pdp-v2-faq-line"></div>
      </div>

      <div class="pdp-v2-faq-list">
        <?php foreach ($faqs as $fIdx => $faq): ?>
          <div class="pdp-v2-faq-item">
            <button type="button" class="pdp-v2-faq-question" aria-expanded="<?= $fIdx === 0 ? 'true' : 'false' ?>" onclick="togglePdpFaq(this)">
              <span><?= htmlspecialchars($faq['q']) ?></span>
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="pdp-v2-faq-chevron<?= $fIdx === 0 ? ' is-open' : '' ?>">
                <polyline points="6 9 12 15 18 9"></polyline>
              </svg>
            </button>
            <div class="pdp-v2-faq-answer" style="<?= $fIdx === 0 ? 'display:block;' : 'display:none;' ?>">
              <p><?= htmlspecialchars($faq['a']) ?></p>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
</div>

<script>
var pdpCurrentQty = 1;
var pdpGallery = <?= json_encode($galleryImages) ?>;
var pdpCurrentImgIdx = 0;

function selectPdpImage(src, btn) {
  var main = document.getElementById('pdp-main-img');
  if (main) main.src = src;
  var buttons = document.querySelectorAll('.pdp-v2-thumb-btn');
  buttons.forEach(function(b) { b.classList.remove('is-active'); });
  if (btn) btn.classList.add('is-active');
}

function cyclePdpThumb(dir) {
  pdpCurrentImgIdx = (pdpCurrentImgIdx + dir + pdpGallery.length) % pdpGallery.length;
  var buttons = document.querySelectorAll('.pdp-v2-thumb-btn');
  if (buttons[pdpCurrentImgIdx]) {
    buttons[pdpCurrentImgIdx].click();
  }
}

function selectPdpVariant(btn) {
  var pills = document.querySelectorAll('.pdp-v2-weight-pill');
  pills.forEach(function(p) { 
    p.classList.remove('is-active'); 
    p.setAttribute('aria-checked', 'false');
  });
  btn.classList.add('is-active');
  btn.setAttribute('aria-checked', 'true');

  var priceText = document.getElementById('pdp-price-text');
  if (priceText) priceText.textContent = btn.getAttribute('data-price-formatted');

  var compareText = document.getElementById('pdp-compare-text');
  var compareFormatted = btn.getAttribute('data-compare-formatted');
  if (compareText) {
    if (compareFormatted) {
      compareText.textContent = compareFormatted;
      compareText.style.display = 'inline';
    } else {
      compareText.style.display = 'none';
    }
  }

  pdpCurrentQty = 1;
  var qtyDisplay = document.getElementById('pdp-qty-display');
  if (qtyDisplay) qtyDisplay.textContent = '1';
}

function adjustPdpQty(diff) {
  pdpCurrentQty = Math.max(1, pdpCurrentQty + diff);
  var qtyDisplay = document.getElementById('pdp-qty-display');
  if (qtyDisplay) qtyDisplay.textContent = pdpCurrentQty;
}

function handlePdpAddToCart() {
  var activePill = document.querySelector('.pdp-v2-weight-pill.is-active');
  if (!activePill) return;
  var variantId = activePill.getAttribute('data-variant-id');
  var variantLabel = activePill.getAttribute('data-label');
  var btn = document.getElementById('pdp-add-btn');
  var prodName = btn ? btn.getAttribute('data-product-name') : '';
  
  if (typeof addToCart === 'function') {
    addToCart(variantId, pdpCurrentQty, {
      title: prodName,
      size: variantLabel,
      qty: pdpCurrentQty
    });
  }
}

function togglePdpFaq(btn) {
  var ans = btn.nextElementSibling;
  var chevron = btn.querySelector('.pdp-v2-faq-chevron');
  var isExpanded = btn.getAttribute('aria-expanded') === 'true';
  btn.setAttribute('aria-expanded', isExpanded ? 'false' : 'true');
  if (ans) ans.style.display = isExpanded ? 'none' : 'block';
  if (chevron) chevron.classList.toggle('is-open', !isExpanded);
}
</script>

<?php require __DIR__ . '/layouts/footer.php'; ?>
