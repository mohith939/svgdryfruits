<?php
/**
 * SVG Dry Fruits - Image & Asset Upload Endpoint
 */
declare(strict_types=1);

require_once __DIR__ . '/../src/Helpers.php';
require_once __DIR__ . '/../src/Auth.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Helpers::json(['success' => false, 'error' => 'Method not allowed'], 405);
}

// Require admin authentication for uploading images
if (!Auth::check()) {
    Helpers::json(['success' => false, 'error' => 'Unauthorized. Please login to admin.'], 401);
}

$file = $_FILES['file'] ?? $_FILES['image'] ?? null;
if (!$file) {
    Helpers::json(['success' => false, 'error' => 'No image file uploaded.'], 400);
}

$prefix = preg_replace('/[^a-z0-9_-]/i', '', $_POST['prefix'] ?? 'img') . '_';
$url = Helpers::handleUpload($file, $prefix);

if (!$url) {
    Helpers::json(['success' => false, 'error' => 'Failed to upload image. Ensure it is JPG, PNG, WEBP, or SVG under 10MB.'], 400);
}

Helpers::json([
    'success' => true,
    'url' => $url,
    'full_url' => Helpers::asset(ltrim($url, '/')),
    'message' => 'Image uploaded successfully!',
]);
