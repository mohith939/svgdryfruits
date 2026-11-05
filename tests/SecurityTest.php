<?php
/**
 * SVG Dry Fruits - Security Verification Tests
 */
declare(strict_types=1);

TestSuite::section('1. CSRF Protection & Token Security');

// Test 1: CSRF token generation
$token1 = Helpers::csrfToken();
TestSuite::assert(
    !empty($token1) && strlen($token1) === 64 && ctype_xdigit($token1),
    'CSRF Token is a cryptographically secure 64-char hex string'
);

// Test 2: Token persistence in session
$token2 = Helpers::csrfToken();
TestSuite::assert(
    $token1 === $token2,
    'CSRF token is consistent within the active session'
);

// Test 3: CSRF verification logic
TestSuite::assert(
    Helpers::verifyCsrf($token1) === true,
    'verifyCsrf() validates authentic token correctly'
);

TestSuite::assert(
    Helpers::verifyCsrf('tampered_token_abc123') === false,
    'verifyCsrf() rejects forged/tampered token'
);

TestSuite::assert(
    Helpers::verifyCsrf('') === false,
    'verifyCsrf() rejects empty token'
);

// Test 4: CSRF hidden field helper
$csrfField = Helpers::csrfField();
TestSuite::assert(
    str_contains($csrfField, 'name="csrf_token"') && str_contains($csrfField, $token1),
    'csrfField() renders correct hidden HTML input element with token'
);

TestSuite::section('2. Rate Limiting & Anti-Brute-Force');

$rateKey = 'test_rate_' . bin2hex(random_bytes(4));
$limit = 3;
$decay = 10;

$r1 = Helpers::checkRateLimit($rateKey, $limit, $decay);
$r2 = Helpers::checkRateLimit($rateKey, $limit, $decay);
$r3 = Helpers::checkRateLimit($rateKey, $limit, $decay);
$r4 = Helpers::checkRateLimit($rateKey, $limit, $decay);

TestSuite::assert($r1 === true, 'Rate limiter allows attempt 1 within quota');
TestSuite::assert($r2 === true, 'Rate limiter allows attempt 2 within quota');
TestSuite::assert($r3 === true, 'Rate limiter allows attempt 3 within quota (at limit)');
TestSuite::assert($r4 === false, 'Rate limiter blocks attempt 4 when quota exceeded');

TestSuite::section('3. File Upload & RCE Protection');

// Test temporary malicious PHP file
$tmpMalicious = tempnam(sys_get_temp_dir(), 'test_php_');
file_put_contents($tmpMalicious, '<?php phpinfo(); ?>');

$fakePhpFile = [
    'name' => 'exploit.php',
    'type' => 'image/png', // Spoofed client MIME type
    'tmp_name' => $tmpMalicious,
    'error' => UPLOAD_ERR_OK,
    'size' => filesize($tmpMalicious)
];

$uploadResult = Helpers::handleUpload($fakePhpFile, 'test_');
TestSuite::assert(
    $uploadResult === null,
    'handleUpload() blocks spoofed PHP file via magic byte inspection (finfo)'
);
@unlink($tmpMalicious);

// Test temporary SVG file with malicious XML/script tag
$tmpSvg = tempnam(sys_get_temp_dir(), 'test_svg_');
file_put_contents($tmpSvg, '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');
$fakeSvgFile = [
    'name' => 'vector.svg',
    'type' => 'image/svg+xml',
    'tmp_name' => $tmpSvg,
    'error' => UPLOAD_ERR_OK,
    'size' => filesize($tmpSvg)
];
$uploadSvgResult = Helpers::handleUpload($fakeSvgFile, 'test_');
TestSuite::assert(
    $uploadSvgResult === null,
    'handleUpload() blocks SVG uploads to prevent Stored XSS'
);
@unlink($tmpSvg);

// Test valid 1x1 GIF image
$tmpGif = tempnam(sys_get_temp_dir(), 'test_gif_');
file_put_contents($tmpGif, base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7'));
$fakeGifFile = [
    'name' => 'valid.gif',
    'type' => 'image/gif',
    'tmp_name' => $tmpGif,
    'error' => UPLOAD_ERR_OK,
    'size' => filesize($tmpGif)
];
$uploadGifResult = Helpers::handleUpload($fakeGifFile, 'test_');
TestSuite::assert(
    $uploadGifResult !== null && str_starts_with($uploadGifResult, '/assets/uploads/'),
    'handleUpload() accepts legitimate image files (GIF/PNG/WEBP/JPG)'
);
if ($uploadGifResult) {
    @unlink(ROOT_PATH . '/public' . $uploadGifResult);
    @unlink(ROOT_PATH . $uploadGifResult);
}
@unlink($tmpGif);

TestSuite::section('4. Uploads Directory Execution Prevention');
$htaccessUploads = file_get_contents(ROOT_PATH . '/assets/uploads/.htaccess');
TestSuite::assert(
    $htaccessUploads !== false && str_contains($htaccessUploads, 'php_flag engine off') && str_contains($htaccessUploads, 'Deny from all'),
    'Uploads .htaccess blocks execution of scripts (php, cgi, pl)'
);

TestSuite::section('5. Installer Lock & Sensitive File Protection');
TestSuite::assert(
    file_exists(ROOT_PATH . '/database/.installed'),
    'Installer lock file (database/.installed) is present in environment'
);
