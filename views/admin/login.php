<?php
/**
 * SVG Dry Fruits - Storefront Admin Login View
 * Faithfully matches Next.js src/components/AdminLoginForm.tsx
 */
declare(strict_types=1);
require_once ROOT_PATH . '/src/Helpers.php';
require_once ROOT_PATH . '/src/Auth.php';

if (Auth::check()) {
    Helpers::redirect(Helpers::url('admin'));
}

$error = null;
$email = $_POST['email'] ?? ADMIN_EMAIL;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Helpers::requireCsrf();
    $email = trim($_POST['email'] ?? '');
    $password = (string)($_POST['password'] ?? '');

    try {
        if (Auth::login($email, $password)) {
            Helpers::redirect(Helpers::url('admin'));
        } else {
            $error = 'Invalid email address or password. Please verify your credentials.';
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Login — SVG Dry Fruits</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Caveat:wght@400;500;600;700&family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&family=Instrument+Serif:ital@0;1&family=Playfair+Display:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= Helpers::url('css/style.css') ?>">
</head>
<body>
  <div class="login-v3-wrapper">
    <div class="login-v3-container">
      <!-- Left Editorial Hero -->
      <div class="login-v3-left">
        <a href="<?= Helpers::url('/') ?>" class="login-v3-brand-logo" aria-label="SVG Dry Fruits Homepage">
          <span class="login-v3-logo-title">SVG</span>
          <span class="login-v3-logo-sub">DRY FRUITS</span>
        </a>

        <span class="login-v3-kicker">REAL INGREDIENTS. BRIGHTER DAYS.</span>
        <h1 class="login-v3-heading">
          Good Food<br />
          Builds Brighter<br />
          Tomorrows.
        </h1>

        <div class="login-v3-rule"></div>

        <!-- 3 Feature Pillars -->
        <div class="login-v3-features">
          <div class="login-v3-feature-row">
            <div class="login-v3-feat-circle">
              <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z" />
                <path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12" />
              </svg>
            </div>
            <span class="login-v3-feat-label">Premium Quality</span>
          </div>

          <div class="login-v3-feature-row">
            <div class="login-v3-feat-circle">
              <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 2a10 10 0 0 0-7.07 17.07C7.5 21.64 12 22 12 22s4.5-.36 7.07-2.93A10 10 0 0 0 12 2Z" />
                <path d="M12 6v10" />
                <path d="m8 10 4-4 4 4" />
              </svg>
            </div>
            <span class="login-v3-feat-label">Sustainably Sourced</span>
          </div>

          <div class="login-v3-feature-row">
            <div class="login-v3-feat-circle">
              <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z" />
                <path d="m3.3 7 8.7 5 8.7-5" />
                <path d="M12 22V12" />
              </svg>
            </div>
            <span class="login-v3-feat-label">Packed with Care</span>
          </div>
        </div>

        <!-- Bottom Left Script -->
        <div class="login-v3-left-script">
          <span class="login-v3-script-text">
            Real Ingredients.<br />
            Brighter Days.
          </span>
        </div>
      </div>

      <!-- Right Column: Portal Login Card -->
      <div class="login-v3-card">
        <!-- Emblem Badge -->
        <div class="login-v3-card-emblem-wrap">
          <img src="<?= Helpers::asset('login-emblem-badge.png') ?>" alt="SVG Dry Fruits Emblem" class="login-v3-card-emblem" />
        </div>

        <div class="login-v3-card-head">
          <span class="login-v3-card-kicker">STOREFRONT ADMINISTRATION</span>
          <h2 class="login-v3-card-title">Product Management Portal</h2>
          <p class="login-v3-card-desc">
            Sign in to manage catalog products, stock counts, pack variants &amp; pricing.
          </p>
        </div>

        <!-- Error Notice -->
        <?php if ($error): ?>
          <div class="login-v3-error-box" role="alert" style="margin-bottom: 14px;">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="10" />
              <line x1="12" y1="8" x2="12" y2="12" />
              <line x1="12" y1="16" x2="12.01" y2="16" />
            </svg>
            <span><?= htmlspecialchars($error) ?></span>
          </div>
        <?php endif; ?>

        <!-- Form -->
        <form method="POST" class="login-v3-form">
          <?= Helpers::csrfField() ?>
          <!-- Email Address -->
          <div class="login-v3-group">
            <label for="admin-email" class="login-v3-label">Email Address</label>
            <div class="login-v3-input-box">
              <span class="login-v3-input-icon">
                <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                  <rect x="2" y="4" width="20" height="16" rx="2" />
                  <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7" />
                </svg>
              </span>
              <input id="admin-email" name="email" type="email" required autocomplete="email" value="<?= htmlspecialchars($email) ?>" placeholder="svgdryfruits@gmail.com" class="login-v3-input" />
            </div>
          </div>

          <!-- Password -->
          <div class="login-v3-group">
            <label for="admin-pass" class="login-v3-label">Password</label>
            <div class="login-v3-input-box">
              <span class="login-v3-input-icon">
                <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                  <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
                  <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                </svg>
              </span>
              <input id="admin-pass" name="password" type="password" required autocomplete="current-password" placeholder="••••••••" class="login-v3-input" />
              <button type="button" onclick="togglePassVisibility()" class="login-v3-eye-btn" aria-label="Toggle password visibility">
                <svg id="eye-icon" viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                  <circle cx="12" cy="12" r="3" />
                </svg>
              </button>
            </div>
          </div>

          <!-- Options Row -->
          <div class="login-v3-options-row">
            <label class="login-v3-checkbox-label">
              <input type="checkbox" name="keep_signed_in" checked class="login-v3-checkbox" />
              <span>Keep me signed in</span>
            </label>

            <button type="button" onclick="alert('Please contact your database administrator to reset credentials.')" class="login-v3-forgot-link">
              Forgot password?
            </button>
          </div>

          <!-- Submit Button -->
          <button type="submit" class="login-v3-submit-btn">
            <span>Sign In &amp; Manage Products</span>
            <span aria-hidden="true">→</span>
          </button>
        </form>

        <!-- Security Footer -->
        <div class="login-v3-security-note">
          <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
            <path d="M7 11V7a5 5 0 0 1 10 0v4" />
          </svg>
          <span>256-bit Encrypted Session | Secure Access</span>
        </div>
      </div>
    </div>
  </div>

  <script>
  function togglePassVisibility() {
    var pass = document.getElementById('admin-pass');
    if (!pass) return;
    pass.type = (pass.type === 'password') ? 'text' : 'password';
  }
  </script>
</body>
</html>
