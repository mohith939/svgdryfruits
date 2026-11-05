<?php
/**
 * SVG Dry Fruits - Storefront Footer Layout
 * Faithfully matches Next.js src/components/Footer.tsx, WhatsAppButton.tsx, CartToast.tsx
 */
declare(strict_types=1);
require_once ROOT_PATH . '/src/Helpers.php';
require_once ROOT_PATH . '/src/Models.php';

$storeSettings = Models::getStoreSettings();
$wa = preg_replace('/\D/', '', (string)($storeSettings['whatsapp_number'] ?? WHATSAPP_NUMBER));
?>
  </main>

  <footer class="site-footer-v2" aria-labelledby="footer-brand-title">
    {/* Single High-Res Botanical Footer Background */}
    <div class="footer-bg-layer" aria-hidden="true">
      <img src="<?= Helpers::asset('footer bg.png') ?>" alt="" class="footer-bg-img" />
    </div>

    <div class="footer-main-container">
      <div class="footer-top-grid">
        <!-- Brand Column -->
        <div class="footer-brand-col">
          <div class="footer-brand-header">
            <div class="footer-brand-title-wrap">
              <img src="<?= Helpers::asset('svg-dry-fruits-logo.jpg') ?>" alt="SVG Dry Fruits Logo" class="footer-svg-logo" />
              <span class="footer-brand-kicker">SVG DRY FRUITS</span>
            </div>
          </div>

          <h2 id="footer-brand-title" class="footer-tagline-v2">
            Good Things,<br />
            <em class="footer-tagline-italic">Kept Close.</em>
          </h2>

          <!-- Circular Outlined Social Media Buttons -->
          <div class="footer-social-row" aria-label="Social links">
            <a href="https://instagram.com/svgdryfruits" target="_blank" rel="noopener noreferrer" class="footer-social-btn footer-social-instagram" aria-label="Follow SVG Dry Fruits on Instagram">
              <?= Icon::render('instagram', ['size' => 18]) ?>
            </a>
            <a href="https://facebook.com/svgdryfruits" target="_blank" rel="noopener noreferrer" class="footer-social-btn footer-social-facebook" aria-label="Follow SVG Dry Fruits on Facebook">
              <?= Icon::render('facebook', ['size' => 18]) ?>
            </a>
            <a href="https://youtube.com/@svgdryfruits" target="_blank" rel="noopener noreferrer" class="footer-social-btn footer-social-youtube" aria-label="Subscribe to SVG Dry Fruits on YouTube">
              <?= Icon::render('youtube', ['size' => 18]) ?>
            </a>
            <a href="https://wa.me/<?= $wa ?>" target="_blank" rel="noopener noreferrer" class="footer-social-btn footer-social-whatsapp" aria-label="Chat with SVG Dry Fruits on WhatsApp">
              <?= Icon::render('whatsapp', ['size' => 18]) ?>
            </a>
          </div>
        </div>

        <!-- Nav: SHOP -->
        <nav class="footer-nav-col" aria-label="Shop categories">
          <h3 class="footer-col-heading">SHOP</h3>
          <ul class="footer-links-list">
            <li><a href="<?= Helpers::url('shop?category=nuts') ?>" class="footer-nav-link">Nuts</a></li>
            <li><a href="<?= Helpers::url('shop?category=dried-fruits') ?>" class="footer-nav-link">Dried Fruits</a></li>
            <li><a href="<?= Helpers::url('shop?category=seeds') ?>" class="footer-nav-link">Seeds</a></li>
            <li><a href="<?= Helpers::url('shop?category=healthy-mix') ?>" class="footer-nav-link">Healthy Mix</a></li>
            <li><a href="<?= Helpers::url('combos') ?>" class="footer-nav-link">Combos</a></li>
          </ul>
        </nav>

        <!-- Nav: HELP -->
        <nav class="footer-nav-col" aria-label="Help and support">
          <h3 class="footer-col-heading">HELP</h3>
          <ul class="footer-links-list">
            <li><a href="<?= Helpers::url('track') ?>" class="footer-nav-link">Track Order</a></li>
            <li><a href="<?= Helpers::url('shipping-policy') ?>" class="footer-nav-link">Shipping Info</a></li>
            <li><a href="<?= Helpers::url('return-policy') ?>" class="footer-nav-link">Returns &amp; Refunds</a></li>
            <li><a href="<?= Helpers::url('/#faq') ?>" class="footer-nav-link">FAQs</a></li>
            <li><a href="<?= Helpers::url('contact') ?>" class="footer-nav-link">Contact Us</a></li>
          </ul>
        </nav>

        <!-- Nav: EXPLORE -->
        <nav class="footer-nav-col" aria-label="Explore SVG Dry Fruits">
          <h3 class="footer-col-heading">EXPLORE</h3>
          <ul class="footer-links-list">
            <li><a href="<?= Helpers::url('/#why-us') ?>" class="footer-nav-link">Quality Promise</a></li>
            <li><a href="<?= Helpers::url('/#faq') ?>" class="footer-nav-link">FAQs</a></li>
            <li><a href="<?= Helpers::url('combos') ?>" class="footer-nav-link">Gifting &amp; Combos</a></li>
            <li><a href="<?= Helpers::url('contact') ?>" class="footer-nav-link">Bulk Inquiries</a></li>
            <li><a href="<?= Helpers::url('contact') ?>" class="footer-nav-link">Contact Us</a></li>
          </ul>
        </nav>
      </div>

      <!-- Finstics Spotlight Row -->
      <div class="footer-finstics-spotlight-row">
        <div class="footer-finstics-wrapper">
          <div class="footer-finstics-card">
            <div class="footer-finstics-brand">
              <span class="footer-finstics-label">Designed and Developed by</span>
              <a href="https://finstics.com" target="_blank" rel="noopener noreferrer" class="footer-finstics-logo-link" aria-label="Finstics website">
                <img src="<?= Helpers::asset('finstics logo.png') ?>" alt="Finstics" class="footer-finstics-logo-img" />
              </a>
            </div>

            <div class="footer-finstics-divider" aria-hidden="true"></div>

            <div class="footer-finstics-contact">
              <div class="footer-finstics-support-text">
                <span class="footer-finstics-headset-icon" aria-hidden="true">
                  <?= Icon::render('headset', ['size' => 14]) ?>
                </span>
                <span>For any website related works</span>
              </div>
              <a href="tel:+918340956469" class="footer-finstics-call-btn" aria-label="Call Finstics at +91 8340956469">
                <?= Icon::render('phone', ['size' => 12]) ?>
                <span>+91 8340956469</span>
              </a>
            </div>
          </div>

          <!-- Playful Hand-Drawn Curved Arrow Doodle -->
          <img src="<?= Helpers::asset('footer-arrow-doodle.png') ?>" alt="" class="footer-finstics-arrow" aria-hidden="true" />
        </div>
      </div>

      <!-- Bottom Bar -->
      <div class="footer-bottom-bar">
        <p class="footer-copyright">
          &copy; <?= date('Y') ?> SVG Dry Fruits. All rights reserved.
        </p>

        <div class="footer-legal-links">
          <a href="<?= Helpers::url('privacy-policy') ?>" class="footer-legal-link">Privacy Policy</a>
          <span class="footer-legal-sep" aria-hidden="true">|</span>
          <a href="<?= Helpers::url('terms') ?>" class="footer-legal-link">Terms of Service</a>
          <span class="footer-legal-sep" aria-hidden="true">|</span>
          <a href="<?= Helpers::url('/') ?>" class="footer-legal-link">Sitemap</a>
        </div>
      </div>
    </div>
  </footer>

  <!-- WhatsApp Floating Button with Breathing Glow -->
  <div class="wa-fixed-wrapper">
    <span class="wa-pulse-ring"></span>
    <a href="https://wa.me/<?= $wa ?>?text=Hello%20SVG%20Dry%20Fruits%2C%20I%20have%20an%20enquiry." target="_blank" rel="noopener noreferrer" class="wa-floating-btn" aria-label="Chat with SVG Dry Fruits on WhatsApp" title="Chat with us on WhatsApp">
      <span class="wa-icon-wrap">
        <?= Icon::render('whatsapp', ['size' => 28]) ?>
        <span class="wa-online-dot" aria-hidden="true"></span>
      </span>
      <span class="wa-label-text">Chat with us</span>
    </a>
  </div>

  <!-- Cart Notification Toast Container -->
  <div class="cart-toast-container" id="cart-toast-container" aria-live="polite" aria-atomic="true"></div>

  <!-- Storefront JavaScript -->
  <script src="<?= Helpers::url('js/main.js') ?>"></script>
</body>
</html>
