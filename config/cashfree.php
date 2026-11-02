<?php
/**
 * SVG Dry Fruits - Cashfree PG Integration Class
 * Handles Order Session creation, payment status verification, and webhook signature validation.
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';

class CashfreePG {
    private string $clientId;
    private string $clientSecret;
    private string $baseUrl;
    private string $apiVersion;

    public function __construct() {
        $this->clientId = CASHFREE_CLIENT_ID;
        $this->clientSecret = CASHFREE_CLIENT_SECRET;
        $this->apiVersion = CASHFREE_API_VERSION;

        if (CASHFREE_ENV === 'PRODUCTION') {
            $this->baseUrl = 'https://api.cashfree.com/pg';
        } else {
            $this->baseUrl = 'https://sandbox.cashfree.com/pg';
        }
    }

    public function isConfigured(): bool {
        return !empty($this->clientId) && !empty($this->clientSecret);
    }

    /**
     * Create Cashfree Order and obtain payment_session_id
     */
    public function createOrder(array $params): array {
        if (!$this->isConfigured()) {
            // Return simulation / test fallback if secret is not set yet
            return [
                'order_id' => $params['order_id'],
                'payment_session_id' => 'session_mock_' . bin2hex(random_bytes(8)),
                'cf_order_id' => 'cf_mock_' . time(),
                'is_mock' => true,
            ];
        }

        $phone = preg_replace('/\D/', '', $params['customer_phone'] ?? '9999999999');
        if (strlen($phone) > 10) {
            $phone = substr($phone, -10);
        }
        $customerId = 'cust_' . substr($phone, -6) . '_' . substr(md5($params['customer_name'] ?? 'guest'), 0, 6);

        $siteUrl = (defined('APP_URL') && str_starts_with(APP_URL, 'https://')) ? APP_URL : 'https://www.svgdryfruits.com';
        $returnUrl = $params['return_url'] ?? '';
        if (!str_starts_with($returnUrl, 'https://')) {
            $returnUrl = $siteUrl . '/order-success?order_id=' . urlencode((string)$params['order_id']);
        }
        $notifyUrl = $params['notify_url'] ?? ($siteUrl . '/api/webhook.php');
        if (!str_starts_with($notifyUrl, 'https://')) {
            $notifyUrl = $siteUrl . '/api/webhook.php';
        }

        $payload = [
            'order_id' => (string)$params['order_id'],
            'order_amount' => (float)$params['order_amount'], // In Rupees (e.g. 799.00)
            'order_currency' => 'INR',
            'customer_details' => [
                'customer_id' => $customerId,
                'customer_phone' => $phone,
                'customer_name' => $params['customer_name'] ?? 'SVG Customer',
                'customer_email' => !empty($params['customer_email']) ? $params['customer_email'] : 'customer@svgdryfruits.com',
            ],
            'order_meta' => [
                'return_url' => $returnUrl,
                'notify_url' => $notifyUrl,
            ],
            'order_note' => $params['order_note'] ?? 'Order ' . $params['order_id'],
        ];

        $response = $this->request('POST', '/orders', $payload);
        return $response;
    }

    /**
     * Fetch order details directly from Cashfree to verify payment status
     */
    public function getOrder(string $orderId): array {
        if (!$this->isConfigured()) {
            throw new Exception("Cashfree credentials are not configured.");
        }

        return $this->request('GET', '/orders/' . urlencode($orderId));
    }

    /**
     * Verify Webhook HMAC Signature with 5-minute replay protection
     */
    public function verifyWebhookSignature(string $rawBody, string $signature, string $timestamp): bool {
        if (empty($this->clientSecret) || empty($signature) || empty($timestamp)) {
            return false;
        }

        // 5-minute replay attack window check
        $currentTime = time();
        $ts = is_numeric($timestamp) ? (int)$timestamp : strtotime($timestamp);
        if ($ts > 0 && abs($currentTime - $ts) > 300) {
            error_log("Cashfree webhook timestamp expired (replay protection): diff=" . abs($currentTime - $ts));
            return false;
        }

        $expectedData = $timestamp . $rawBody;
        $computedSignature = base64_encode(hash_hmac('sha256', $expectedData, $this->clientSecret, true));
        return hash_equals($computedSignature, $signature);
    }

    public static function isLocal(): bool {
        $host = $_SERVER['HTTP_HOST'] ?? '';
        $addr = $_SERVER['REMOTE_ADDR'] ?? '';
        return strpos($host, 'localhost') !== false 
            || strpos($host, '127.0.0.1') !== false 
            || in_array($addr, ['127.0.0.1', '::1'])
            || php_sapi_name() === 'cli'
            || php_sapi_name() === 'cli-server';
    }

    private function request(string $method, string $endpoint, ?array $body = null): array {
        $url = $this->baseUrl . $endpoint;
        $headers = [
            'x-api-version: ' . $this->apiVersion,
            'x-client-id: ' . $this->clientId,
            'x-client-secret: ' . $this->clientSecret,
            'Content-Type: application/json',
            'Accept: application/json',
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        // Auto-detect CA bundle across macOS and Linux/cPanel
        $caBundlePaths = [
            '/etc/ssl/cert.pem',
            '/opt/homebrew/etc/ca-certificates/cert.pem',
            '/opt/homebrew/etc/openssl@3/cert.pem',
            '/etc/pki/tls/certs/ca-bundle.crt',
            '/etc/ssl/certs/ca-certificates.crt',
        ];
        foreach ($caBundlePaths as $ca) {
            if (file_exists($ca)) {
                curl_setopt($ch, CURLOPT_CAINFO, $ca);
                break;
            }
        }

        // On local environments or macOS without local CA certs, disable strict peer verification
        if (self::isLocal()) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        } else {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        }

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }

        $rawResponse = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);

        if ($rawResponse === false) {
            throw new Exception("Cashfree connection error: " . $curlError);
        }

        $decoded = json_decode($rawResponse, true);
        if ($httpCode >= 400) {
            $msg = $decoded['message'] ?? 'Payment gateway error (' . $httpCode . ')';
            throw new Exception($msg);
        }

        return $decoded ?? [];
    }
}
