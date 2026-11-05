<?php
/**
 * SVG Dry Fruits - Storefront Homepage View
 * Faithfully matches Next.js src/app/page.tsx and all child components
 */
declare(strict_types=1);
require_once ROOT_PATH . '/src/Helpers.php';
require_once ROOT_PATH . '/src/Models.php';
require_once ROOT_PATH . '/src/Icon.php';

$pageTitle = 'SVG Dry Fruits — Naturally Superior Dry Fruits & Nuts';
$activePage = 'home';

$categories = Models::getCategories();
$products = Models::getProductsWithVariants(null, true);

$marqueeItems = ["Almonds", "Cashews", "Pistachios", "Walnuts", "Dates", "Raisins", "Figs", "Apricots", "Combos"];

$faqs = [
    [
        'q' => 'Do I need an account to order?',
        'a' => 'No. SVG Dry Fruits uses guest checkout, so you can order with just your delivery details.'
    ],
    [
        'q' => 'Which pack sizes are available?',
        'a' => 'Products are sold in fixed 250 g, 500 g and 1 kg packs. Not every product is offered in every size; each product page shows what is available.'
    ],
    [
        'q' => 'How are shipping charges decided?',
        'a' => 'Shipping is set by SVG Dry Fruits and shown clearly in your bag and at checkout before you pay.'
    ],
    [
        'q' => 'How can I pay?',
        'a' => 'Online through Cashfree. Cash on delivery is not offered.'
    ],
    [
        'q' => 'Where can I read the returns policy?',
        'a' => 'See the Returns page linked in the footer. Questions are welcome through the Contact page.'
    ],
];

$wa = preg_replace('/\D/', '', WHATSAPP_NUMBER);

require __DIR__ . '/layouts/header.php';
?>

<!-- =====================================================================
     HERO SECTION (Matching AnimatedHero.tsx)
     ===================================================================== -->
<section class="hero-section-v1" id="top" aria-labelledby="hero-title">
  <div class="hero-stage">
    <!-- Desktop High-Res Background & Rotating Bowl -->
    <div class="hero-bg-layer" aria-hidden="true">
      <img src="<?= Helpers::asset('section-1-bg.png') ?>" alt="" class="hero-bg-image" />

      <!-- Desktop Rotating Bowl (Animates via CSS keyframes) -->
      <div class="hero-rotating-bowl-anchor">
        <img src="<?= Helpers::asset('mixed dry fruits.png') ?>" alt="Finest mixed dry fruits and nuts" class="hero-rotating-bowl-img" />
      </div>
    </div>

    <!-- Content layer: Left side brand headline and actions -->
    <div class="hero-content-layer">
      <div class="hero-content-wrap">
        <p class="hero-kicker">WELCOME TO SVG DRY FRUITS</p>
        <h1 id="hero-title" class="hero-title">
          GOOD THINGS,<br />
          KEPT CLOSE.
        </h1>
        <p class="hero-description">
          Wholesome California almonds, buttery cashews, festive dates, and curated combos. Sourced with care, packed in transparent pantry sizes, delivered fresh to your doorstep.
        </p>
        <div class="hero-actions">
          <a class="hero-shop-btn" href="<?= Helpers::url('shop') ?>">
            SHOP NOW <span aria-hidden="true">↗</span>
          </a>
          <a class="hero-sub-link" href="<?= Helpers::url('combos') ?>">
            EXPLORE GIFT COMBOS
          </a>
        </div>
      </div>
    </div>

    <!-- Mobile/Tablet Visual Fallback -->
    <div class="hero-mobile-visual" aria-hidden="true">
      <div class="hero-mobile-focal">
        <img src="<?= Helpers::asset('hero-leaves-rosette-clean.png') ?>" alt="" class="hero-mobile-rosette" />
        <div class="hero-mobile-rotating-bowl">
          <img src="<?= Helpers::asset('mixed dry fruits.png') ?>" alt="Finest mixed dry fruits and nuts" class="hero-mobile-bowl-img" />
        </div>
      </div>
    </div>

    <!-- Overlapping White 3-Badge Bar (Bottom Center) -->
    <div class="hero-badge-bar-wrapper">
      <div class="hero-badge-bar-card">
        <!-- Badge 1: Premium Quality -->
        <div class="hero-badge-bar-item">
          <div class="hero-badge-bar-icon" aria-hidden="true">
            <?= Icon::render('almonds', ['size' => 32]) ?>
          </div>
          <span class="hero-badge-bar-label">Premium Quality</span>
        </div>

        <div class="hero-badge-bar-sep" aria-hidden="true"></div>

        <!-- Badge 2: Prepared In House -->
        <div class="hero-badge-bar-item">
          <div class="hero-badge-bar-icon" aria-hidden="true">
            <?= Icon::render('home-craft', ['size' => 30]) ?>
          </div>
          <span class="hero-badge-bar-label">Prepared In House</span>
        </div>

        <div class="hero-badge-bar-sep" aria-hidden="true"></div>

        <!-- Badge 3: Conveniently Packed -->
        <div class="hero-badge-bar-item">
          <div class="hero-badge-bar-icon" aria-hidden="true">
            <?= Icon::render('pouch', ['size' => 30]) ?>
          </div>
          <span class="hero-badge-bar-label">Conveniently Packed</span>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- =====================================================================
     COLLECTION SECTION (Matching CollectionSection.tsx)
     ===================================================================== -->
<section class="coll-section" id="collection" aria-labelledby="coll-title">
  <!-- 4 Animated Floating Corner Ingredients -->
  <div class="coll-floating-bg" aria-hidden="true">
    <div class="coll-float-corner coll-float-tl">
      <img src="<?= Helpers::asset('sec2-ingredient-top-left.png') ?>" alt="" />
    </div>
    <div class="coll-float-corner coll-float-tr">
      <img src="<?= Helpers::asset('sec2-ingredient-top-right.png') ?>" alt="" />
    </div>
    <div class="coll-float-corner coll-float-bl">
      <img src="<?= Helpers::asset('sec2-ingredient-bottom-left.png') ?>" alt="" />
    </div>
    <div class="coll-float-corner coll-float-br">
      <img src="<?= Helpers::asset('sec2-ingredient-bottom-right.png') ?>" alt="" />
    </div>
  </div>

  <div class="coll-inner">
    <!-- Header -->
    <div class="coll-header">
      <p class="coll-kicker">— OUR COLLECTION —</p>
      <h2 id="coll-title" class="coll-title">
        Premium Dry Fruits <em>for Every Occasion</em>
      </h2>
    </div>

    <!-- Category Filter Tabs -->
    <?php if (!empty($categories)): ?>
      <div class="coll-tabs" role="tablist" aria-label="Product categories">
        <button type="button" role="tab" aria-selected="true" class="coll-tab-btn is-active" data-category="all">
          <span class="coll-tab-label">All Products</span>
          <div class="coll-tab-indicator"></div>
        </button>
        <?php foreach ($categories as $cat): ?>
          <button type="button" role="tab" aria-selected="false" class="coll-tab-btn" data-category="<?= htmlspecialchars($cat['slug']) ?>">
            <?php if (!empty($cat['image_url'])): ?>
              <div class="coll-tab-icon-wrap">
                <img src="<?= Helpers::asset($cat['image_url']) ?>" alt="" class="coll-tab-icon-img" />
              </div>
            <?php endif; ?>
            <span class="coll-tab-label"><?= htmlspecialchars($cat['name']) ?></span>
            <div class="coll-tab-indicator" style="display:none;"></div>
          </button>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <!-- Product Cards Grid -->
    <div class="coll-grid" id="home-coll-grid">
      <?php foreach ($products as $idx => $prod): 
        $variants = !empty($prod['variants']) ? $prod['variants'] : [];
        $firstVar = !empty($variants) ? $variants[0] : null;
        $prodImg = !empty($prod['image_url']) ? Helpers::asset($prod['image_url']) : Helpers::asset('pdp-hero-almonds.jpg');
      ?>
        <div class="coll-card" data-category="<?= htmlspecialchars($prod['category_slug'] ?? '') ?>" data-slug="<?= htmlspecialchars($prod['slug']) ?>">
          <div class="coll-card-media">
            <a href="<?= Helpers::url('products/' . $prod['slug']) ?>" class="coll-card-media-link" style="display: block; width: 100%; height: 100%; text-decoration: none;" aria-label="View <?= htmlspecialchars($prod['name']) ?>">
              <img src="<?= $prodImg ?>" alt="<?= htmlspecialchars($prod['name']) ?>" class="coll-card-img" loading="lazy" />
            </a>
            <button type="button" class="coll-card-fav-btn" aria-label="Save <?= htmlspecialchars($prod['name']) ?> to wishlist" onclick="this.classList.toggle('is-favorited')">
              <?= Icon::render('heart', ['size' => 16]) ?>
            </button>
          </div>

          <div class="coll-card-body">
            <h3 class="coll-card-title">
              <a href="<?= Helpers::url('products/' . $prod['slug']) ?>"><?= htmlspecialchars($prod['name']) ?></a>
            </h3>

            <!-- Pack size pills -->
            <div class="coll-card-sizes" role="radiogroup" aria-label="Pack sizes">
              <?php foreach ($variants as $vIdx => $v): ?>
                <button type="button" 
                        role="radio" 
                        aria-checked="<?= $vIdx === 0 ? 'true' : 'false' ?>" 
                        class="coll-size-pill<?= $vIdx === 0 ? ' is-active' : '' ?>"
                        data-variant-id="<?= htmlspecialchars($v['id']) ?>"
                        data-price="<?= htmlspecialchars(Helpers::formatPaise((int)$v['price_paise'])) ?>"
                        data-compare="<?= (!empty($v['compare_price_paise']) && $v['compare_price_paise'] > $v['price_paise']) ? htmlspecialchars(Helpers::formatPaise((int)$v['compare_price_paise'])) : '' ?>"
                        data-label="<?= htmlspecialchars($v['label']) ?>"
                        onclick="selectCardSize(this)">
                  <?= htmlspecialchars($v['label']) ?>
                </button>
              <?php endforeach; ?>
            </div>

            <!-- Price & Quantity Stepper Row -->
            <div class="coll-card-price-row" style="display: flex; justify-content: space-between; align-items: center; margin: 10px 0;">
              <div>
                <span class="coll-card-price"><?= $firstVar ? Helpers::formatPaise((int)$firstVar['price_paise']) : '' ?></span>
                <?php if ($firstVar && !empty($firstVar['compare_price_paise']) && $firstVar['compare_price_paise'] > $firstVar['price_paise']): ?>
                  <s class="coll-card-compare"><?= Helpers::formatPaise((int)$firstVar['compare_price_paise']) ?></s>
                <?php endif; ?>
              </div>

              <!-- Compact - / + Quantity Stepper -->
              <div class="coll-card-qty-stepper" style="display: inline-flex; align-items: center; border: 1.5px solid #073D26; border-radius: 6px; overflow: hidden; background: #fff;">
                <button type="button" onclick="adjustCardQty(this, -1)" style="border: none; background: #FAF8F5; color: #073D26; width: 26px; height: 26px; display: inline-flex; align-items: center; justify-content: center; font-weight: 800; cursor: pointer; font-size: 15px; padding: 0;" aria-label="Decrease quantity">−</button>
                <span class="coll-card-qty-val" style="min-width: 24px; text-align: center; font-size: 13px; font-weight: 700; color: #073D26; padding: 0 4px;">1</span>
                <button type="button" onclick="adjustCardQty(this, 1)" style="border: none; background: #FAF8F5; color: #073D26; width: 26px; height: 26px; display: inline-flex; align-items: center; justify-content: center; font-weight: 800; cursor: pointer; font-size: 15px; padding: 0;" aria-label="Increase quantity">+</button>
              </div>
            </div>

            <!-- Add to Bag Button -->
            <button type="button" 
                    class="coll-card-add-btn" 
                    data-product-name="<?= htmlspecialchars($prod['name']) ?>"
                    data-image-url="<?= $prodImg ?>"
                    onclick="handleCardAdd(this)">
              <?= Icon::render('bag', ['size' => 18]) ?>
              <span>Add to Bag</span>
            </button>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- View All CTA -->
    <div class="coll-footer-cta">
      <a href="<?= Helpers::url('shop') ?>" class="coll-view-all-btn">
        View All Products <span aria-hidden="true">→</span>
      </a>
    </div>
  </div>
</section>

<!-- =====================================================================
     WHY CHOOSE SECTION (Matching WhyChooseSection.tsx)
     ===================================================================== -->
<section class="why-choose-section" id="why-us" aria-labelledby="why-choose-title">
  <div class="why-choose-container">
    <div class="why-choose-intro">
      <p class="why-choose-kicker">— WHY CHOOSE SVG DRY FRUITS —</p>
      <h2 id="why-choose-title" class="why-choose-headline">
        PURE GOODNESS<br />IN EVERY BITE.
      </h2>
      <p class="why-choose-desc">
        We go beyond just dry fruits — we bring you a promise of quality, care and a healthier tomorrow.
      </p>
      <a href="<?= Helpers::url('shop') ?>" class="why-choose-cta">
        EXPLORE COLLECTION <span aria-hidden="true">→</span>
      </a>
    </div>

    <div class="why-choose-pillars" role="list">
      <div class="why-choose-pillar-item" role="listitem">
        <div class="why-choose-icon-badge">
          <?= Icon::render('leaf', ['size' => 28]) ?>
        </div>
        <h3 class="why-choose-pillar-title">Premium Quality</h3>
        <p class="why-choose-pillar-desc">Carefully selected for the best taste and freshness.</p>
      </div>

      <div class="why-choose-pillar-item" role="listitem">
        <div class="why-choose-icon-badge">
          <?= Icon::render('globe', ['size' => 28]) ?>
        </div>
        <h3 class="why-choose-pillar-title">Ethically Sourced</h3>
        <p class="why-choose-pillar-desc">From trusted farms across the globe.</p>
      </div>

      <div class="why-choose-pillar-item" role="listitem">
        <div class="why-choose-icon-badge">
          <?= Icon::render('package-sealed', ['size' => 28]) ?>
        </div>
        <h3 class="why-choose-pillar-title">Hygienically Packed</h3>
        <p class="why-choose-pillar-desc">Sealed with care to retain natural goodness.</p>
      </div>

      <div class="why-choose-pillar-item" role="listitem">
        <div class="why-choose-icon-badge">
          <?= Icon::render('truck', ['size' => 28]) ?>
        </div>
        <h3 class="why-choose-pillar-title">Fast &amp; Reliable Delivery</h3>
        <p class="why-choose-pillar-desc">Your favourite dry fruits delivered to your doorstep.</p>
      </div>
    </div>
  </div>
</section>

<!-- =====================================================================
     MARQUEE STRIP
     ===================================================================== -->
<div class="marquee" aria-hidden="true">
  <div class="marquee-track">
    <?php for ($rep = 0; $rep < 2; $rep++): ?>
      <?php foreach ($marqueeItems as $m): ?>
        <span><?= htmlspecialchars($m) ?><i>✦</i></span>
      <?php endforeach; ?>
    <?php endfor; ?>
  </div>
</div>

<!-- =====================================================================
     THREE SIZES SECTION (Matching ThreeSizesSection.tsx)
     ===================================================================== -->
<section class="sizes-section-v2" aria-labelledby="sizes-title">
  <div class="sizes-v2-container">
    <!-- Left Column: Headline, Story & 3 Pillars -->
    <div class="sizes-v2-left">
      <div class="sizes-v2-kicker">
        <span class="sizes-kicker-dash" aria-hidden="true"></span>
        <span>SIMPLE BY DESIGN</span>
      </div>

      <h2 id="sizes-title" class="sizes-v2-headline">
        Three sizes.<br />
        <em class="sizes-headline-italic">No guesswork.</em>
      </h2>

      <p class="sizes-v2-desc">
        Every pack comes in a fixed weight, so you always know exactly what you’re ordering and what it costs.
      </p>

      <div class="sizes-v2-pillars-row" role="list">
        <div class="sizes-pillar-wrap" role="listitem">
          <div class="sizes-pillar-item">
            <div class="sizes-pillar-icon-badge">
              <?= Icon::render('leaf', ['size' => 24]) ?>
            </div>
            <span class="sizes-pillar-label">SAME<br />GREAT QUALITY</span>
          </div>
          <div class="sizes-pillar-divider" aria-hidden="true"></div>
        </div>

        <div class="sizes-pillar-wrap" role="listitem">
          <div class="sizes-pillar-item">
            <div class="sizes-pillar-icon-badge">
              <?= Icon::render('scale', ['size' => 24]) ?>
            </div>
            <span class="sizes-pillar-label">CLEAR<br />WEIGHTS</span>
          </div>
          <div class="sizes-pillar-divider" aria-hidden="true"></div>
        </div>

        <div class="sizes-pillar-wrap" role="listitem">
          <div class="sizes-pillar-item">
            <div class="sizes-pillar-icon-badge">
              <?= Icon::render('heart', ['size' => 24]) ?>
            </div>
            <span class="sizes-pillar-label">PERFECT<br />FOR EVERY NEED</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Right Column: 3 Size Rows with Photos -->
    <div class="sizes-v2-right" role="list">
      <!-- 250g Row -->
      <div>
        <a href="#collection" class="sizes-v2-row" role="listitem">
          <div class="sizes-v2-photo-wrap">
            <img src="<?= Helpers::asset('size-photo-250g.jpg') ?>" alt="250g bowl of premium almonds" loading="lazy" class="sizes-v2-photo" />
          </div>
          <span class="sizes-v2-weight">250 g</span>
          <div class="sizes-v2-row-divider" aria-hidden="true"></div>
          <p class="sizes-v2-note">A first taste, or a small top-up for the jar.</p>
          <div class="sizes-v2-arrow-badge" aria-hidden="true">
            <span class="sizes-v2-arrow">→</span>
          </div>
        </a>
        <div class="sizes-v2-horizontal-divider" aria-hidden="true"></div>
      </div>

      <!-- 500g Row -->
      <div>
        <a href="#collection" class="sizes-v2-row" role="listitem">
          <div class="sizes-v2-photo-wrap">
            <img src="<?= Helpers::asset('size-photo-500g.jpg') ?>" alt="500g bowl of whole roasted cashews" loading="lazy" class="sizes-v2-photo" />
          </div>
          <span class="sizes-v2-weight">500 g</span>
          <div class="sizes-v2-row-divider" aria-hidden="true"></div>
          <p class="sizes-v2-note">The everyday size for a household pantry.</p>
          <div class="sizes-v2-arrow-badge" aria-hidden="true">
            <span class="sizes-v2-arrow">→</span>
          </div>
        </a>
        <div class="sizes-v2-horizontal-divider" aria-hidden="true"></div>
      </div>

      <!-- 1kg Row -->
      <div>
        <a href="#collection" class="sizes-v2-row" role="listitem">
          <div class="sizes-v2-photo-wrap">
            <img src="<?= Helpers::asset('size-photo-1kg.jpg') ?>" alt="1kg bowl of rich mixed dry fruits" loading="lazy" class="sizes-v2-photo" />
          </div>
          <span class="sizes-v2-weight">1 kg</span>
          <div class="sizes-v2-row-divider" aria-hidden="true"></div>
          <p class="sizes-v2-note">For the family that finishes a jar quickly.</p>
          <div class="sizes-v2-arrow-badge" aria-hidden="true">
            <span class="sizes-v2-arrow">→</span>
          </div>
        </a>
      </div>
    </div>
  </div>
</section>

<!-- =====================================================================
     GIFTING SECTION
     ===================================================================== -->
<section class="gifting-section" id="gifting" aria-labelledby="gifting-title">
  <div class="gifting-photo">
    <img src="<?= Helpers::asset('dates-reference.jpg') ?>" alt="Dates arranged in a small glass dish" loading="lazy" />
    <span>FOR THE HOST, THE VISIT, THE LITTLE OCCASION</span>
  </div>
  <div class="gifting-copy">
    <p class="section-kicker">A THOUGHT TO TAKE ALONG</p>
    <h2 id="gifting-title">A small gesture.<br /><em>A good one.</em></h2>
    <p>A gift can be simple and still feel considered. Browse boxes and combinations that are a pleasure to give, for festivals, thank-yous and everything in between.</p>
    <a class="underlined-link" href="<?= Helpers::url('combos') ?>">Explore gifting <span aria-hidden="true">↗</span></a>
  </div>
</section>

<!-- =====================================================================
     HOW IT WORKS SECTION (Matching HowItWorks.tsx)
     ===================================================================== -->
<section class="steps-section" id="how-it-works" aria-labelledby="steps-title">
  <div class="steps-inner-container">
    <div class="steps-heading-wrap">
      <p class="steps-kicker">
        <span class="kicker-line" aria-hidden="true"></span>
        <span>A BETTER WAY TO SHOP</span>
        <span class="kicker-line" aria-hidden="true"></span>
      </p>
      <h2 id="steps-title" class="steps-headline">
        From pantry to <em class="headline-doorstep">doorstep.</em>
      </h2>
    </div>

    <!-- Wave Timeline & Columns -->
    <div class="steps-journey-container">
      <div class="steps-svg-wave" aria-hidden="true">
        <svg viewBox="0 0 1024 100" fill="none" preserveAspectRatio="none">
          <path d="M 146 35 C 200 40, 240 50, 272 54 C 310 58, 350 78, 392 90 C 440 96, 475 60, 513 52 C 555 45, 595 40, 633 44 C 680 50, 720 78, 759 86 C 800 92, 840 76, 874 65" stroke="#164332" stroke-width="1.5" />
          <circle cx="272" cy="54" r="4" fill="#C9A34E" stroke="#F6F0E7" stroke-width="2" class="step-wave-bead"></circle>
          <circle cx="513" cy="52" r="5.5" fill="#C9A34E" stroke="#F6F0E7" stroke-width="2" class="step-wave-bead"></circle>
          <circle cx="759" cy="86" r="4" fill="#C9A34E" stroke="#F6F0E7" stroke-width="2" class="step-wave-bead"></circle>
        </svg>
      </div>

      <div class="steps-columns-grid" role="tablist" aria-label="Order process steps">
        <!-- Step 1 -->
        <div class="step-column-item step-col-1" onclick="switchStep(0)">
          <button type="button" class="step-badge-wrap" title="Step 01: Choose your size">
            <img src="<?= Helpers::asset('step-badge-1.png') ?>" alt="Step 01" class="step-badge-img" />
          </button>
          <div class="step-content-card">
            <span class="step-num-serif">01</span>
            <h3 class="step-col-title">Choose your size</h3>
            <p class="step-col-body">Pick a product and a pack size of 250 g, 500 g or 1 kg.</p>
            <div class="step-action-row" style="display:none;">
              <a href="#collection" class="step-operate-btn"><span>Explore Sizes</span> <span aria-hidden="true">→</span></a>
            </div>
          </div>
          <div class="step-col-divider" aria-hidden="true"></div>
        </div>

        <!-- Step 2 -->
        <div class="step-column-item step-col-2" onclick="switchStep(1)">
          <button type="button" class="step-badge-wrap" title="Step 02: Add to your bag">
            <img src="<?= Helpers::asset('step-badge-2.png') ?>" alt="Step 02" class="step-badge-img" />
          </button>
          <div class="step-content-card">
            <span class="step-num-serif">02</span>
            <h3 class="step-col-title">Add to your bag</h3>
            <p class="step-col-body">No account needed. Your bag stays with you as you browse.</p>
            <div class="step-action-row" style="display:none;">
              <a href="<?= Helpers::url('cart') ?>" class="step-operate-btn"><span>View Bag</span> <span aria-hidden="true">→</span></a>
            </div>
          </div>
          <div class="step-col-divider" aria-hidden="true"></div>
        </div>

        <!-- Step 3 (Active by default) -->
        <div class="step-column-item step-col-3 step-active-col" onclick="switchStep(2)">
          <button type="button" class="step-badge-wrap badge-active" title="Step 03: Pay securely">
            <img src="<?= Helpers::asset('step-badge-3.png') ?>" alt="Step 03" class="step-badge-img" />
            <span class="badge-active-glow"></span>
          </button>
          <div class="step-content-card card-highlighted">
            <div class="step-card-connector" aria-hidden="true"></div>
            <span class="step-num-serif">03</span>
            <h3 class="step-col-title">Pay securely</h3>
            <p class="step-col-body">Checkout with Cashfree. Prices and stock are confirmed on our server.</p>
            <div class="step-action-row">
              <a href="<?= Helpers::url('checkout') ?>" class="step-operate-btn"><span>Secure Checkout</span> <span aria-hidden="true">→</span></a>
            </div>
          </div>
          <div class="step-col-divider" aria-hidden="true"></div>
        </div>

        <!-- Step 4 -->
        <div class="step-column-item step-col-4" onclick="switchStep(3)">
          <button type="button" class="step-badge-wrap" title="Step 04: At your doorstep">
            <img src="<?= Helpers::asset('step-badge-4.png') ?>" alt="Step 04" class="step-badge-img" />
          </button>
          <div class="step-content-card">
            <span class="step-num-serif">04</span>
            <h3 class="step-col-title">At your doorstep</h3>
            <p class="step-col-body">Follow your order with its order number and private tracking link.</p>
            <div class="step-action-row" style="display:none;">
              <a href="<?= Helpers::url('track') ?>" class="step-operate-btn"><span>Track Order</span> <span aria-hidden="true">→</span></a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- =====================================================================
     STORY SECTION (Matching StorySection.tsx)
     ===================================================================== -->
<section class="story-section-v2" id="story" aria-labelledby="story-title">
  <div class="story-content-wrap">
    <div class="story-copy-card">
      <p class="story-kicker">
        <span class="story-kicker-line" aria-hidden="true"></span>
        <span>A NOTE FROM SVG DRY FRUITS</span>
        <span class="story-kicker-line" aria-hidden="true"></span>
      </p>

      <h2 id="story-title" class="story-headline">
        For the jar by the kettle.<br />
        For the friend at the door.<br />
        <em class="story-headline-italic">For keeping close.</em>
      </h2>

      <p class="story-desc">
        Our dry fruits are more than a snack — they’re a part of life’s simple, meaningful moments. Whether it’s a quiet evening, a quick energy boost, or sharing with someone special, we’re here for every occasion.
      </p>

      <a href="<?= Helpers::url('shop') ?>" class="story-cta-link">
        <span>Explore our harvest</span>
        <span class="story-cta-arrow" aria-hidden="true">↗</span>
      </a>
    </div>
  </div>
</section>

<!-- =====================================================================
     FAQ SECTION (Matching page.tsx)
     ===================================================================== -->
<section class="faq-section" id="faq" aria-labelledby="faq-title">
  <div class="faq-head">
    <p class="section-kicker">GOOD TO KNOW</p>
    <h2 id="faq-title">Questions,<br /><em>answered.</em></h2>
  </div>
  <div class="faq-list">
    <?php foreach ($faqs as $f): ?>
      <details>
        <summary><?= htmlspecialchars($f['q']) ?></summary>
        <p><?= htmlspecialchars($f['a']) ?></p>
      </details>
    <?php endforeach; ?>
  </div>
</section>

<!-- =====================================================================
     CTA BAND SECTION
     ===================================================================== -->
<section class="cta-band" aria-labelledby="cta-title">
  <h2 id="cta-title">Something you’d like <em>to know?</em></h2>
  <div class="cta-actions">
    <a class="button button-light" href="<?= Helpers::url('contact') ?>">Contact us <span aria-hidden="true">↗</span></a>
    <a class="button button-outline" href="https://wa.me/<?= $wa ?>"><?= Icon::render('whatsapp', ['size' => 20]) ?> WhatsApp</a>
  </div>
</section>

<?php require __DIR__ . '/layouts/footer.php'; ?>
