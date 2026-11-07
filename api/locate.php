<?php
/**
 * SVG Dry Fruits - IP Geolocation API Fallback
 */
declare(strict_types=1);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Helpers.php';

header('Content-Type: application/json');

$ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
if (strpos($ip, ',') !== false) {
    $ip = trim(explode(',', $ip)[0]);
}

// For localhost / local IP, fetch public IP info or default to SVG headquarters
if ($ip === '127.0.0.1' || $ip === '::1' || strpos($ip, '192.168.') === 0 || strpos($ip, '10.') === 0) {
    $ch = curl_init('https://ipapi.co/json/');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    curl_setopt($ch, CURLOPT_USERAGENT, 'SVG-Dry-Fruits-Web');
    $res = curl_exec($ch);
    curl_close($ch);
    if ($res && ($data = json_decode((string)$res, true)) && !empty($data['region'])) {
        echo $res;
        exit;
    }
    echo json_encode([
        'city' => 'Hyderabad',
        'region' => 'Telangana',
        'postal' => '500001',
        'country_name' => 'India'
    ]);
    exit;
}

$ch = curl_init("https://ipapi.co/{$ip}/json/");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 3);
curl_setopt($ch, CURLOPT_USERAGENT, 'SVG-Dry-Fruits-Web');
$res = curl_exec($ch);
curl_close($ch);

if ($res && ($data = json_decode((string)$res, true)) && !empty($data['region'])) {
    echo $res;
} else {
    echo json_encode([
        'city' => 'Hyderabad',
        'region' => 'Telangana',
        'postal' => '500001',
        'country_name' => 'India'
    ]);
}
