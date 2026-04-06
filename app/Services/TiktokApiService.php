<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TiktokApiService
{
    private string $appKey;
    private string $appSecret;
    private string $apiBase;
    private string $authBase;
    private string $serviceId = '';

    public function __construct()
    {
        $this->appKey    = config('services.tiktok.app_key', '');
        $this->appSecret = config('services.tiktok.app_secret', '');
        $this->apiBase   = config('services.tiktok.api_base', '');
        $this->authBase  = config('services.tiktok.auth_base', '');
        $this->serviceId = config('services.tiktok.service_id', '');
    }

    /* ===================================================================
     *  SIGNATURE  — HMAC-SHA256
     *  Format: secret + path + sorted(params excl. sign & access_token)
     *          + body_json + secret
     * =================================================================== */
    public function buildSign(string $path, array $queryParams, string $bodyJson = ''): string
    {
        // Remove sign & access_token from params used for signing
        $signable = array_filter($queryParams, function ($key) {
            return !in_array($key, ['sign', 'access_token']);
        }, ARRAY_FILTER_USE_KEY);

        ksort($signable);

        $paramString = '';
        foreach ($signable as $key => $value) {
            $paramString .= $key . $value;
        }

        $baseString = $this->appSecret . $path . $paramString . $bodyJson . $this->appSecret;

        return hash_hmac('sha256', $baseString, $this->appSecret);
    }

    /* ===================================================================
     *  GET ACCESS TOKEN  — exchange auth_code for tokens
     * =================================================================== */
    public function getAccessToken(string $authCode): array
    {
        $url = $this->authBase . '/api/v2/token/get';

        $response = Http::get($url, [
            'app_key'    => $this->appKey,
            'app_secret' => $this->appSecret,
            'auth_code'  => $authCode,
            'grant_type' => 'authorized_code',
        ]);

        $data = $response->json();

        if (($data['code'] ?? -1) !== 0) {
            Log::error('TikTok getAccessToken failed', $data);
            throw new \RuntimeException('Token exchange failed: ' . ($data['message'] ?? 'Unknown error'));
        }

        return $data['data'] ?? [];
    }

    /* ===================================================================
     *  REFRESH ACCESS TOKEN
     * =================================================================== */
    public function refreshAccessToken(string $refreshToken): array
    {
        $url = $this->authBase . '/api/v2/token/refresh';

        $response = Http::get($url, [
            'app_key'       => $this->appKey,
            'app_secret'    => $this->appSecret,
            'refresh_token' => $refreshToken,
            'grant_type'    => 'refresh_token',
        ]);

        $data = $response->json();

        if (($data['code'] ?? -1) !== 0) {
            Log::error('TikTok refreshAccessToken failed', $data);
            throw new \RuntimeException('Token refresh failed: ' . ($data['message'] ?? 'Unknown error'));
        }

        return $data['data'] ?? [];
    }

    /* ===================================================================
     *  GET AUTH SHOP  — /authorization/202309/shops
     * =================================================================== */
    public function getAuthShop(string $accessToken): array
    {
        $path = '/authorization/202309/shops';
        $timestamp = time();

        $queryParams = [
            'app_key'   => $this->appKey,
            'timestamp' => $timestamp,
        ];

        $sign = $this->buildSign($path, $queryParams);

        $queryParams['sign']         = $sign;
        $queryParams['access_token'] = $accessToken;

        $url = $this->apiBase . $path . '?' . http_build_query($queryParams);

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'x-tts-access-token' => $accessToken,
        ])->get($url);

        $data = $response->json();

        if (($data['code'] ?? -1) !== 0) {
            Log::error('TikTok getAuthShop failed', $data);
            throw new \RuntimeException('Get Auth Shop failed: ' . ($data['message'] ?? 'Unknown error'));
        }

        return $data['data']['shops'] ?? [];
    }

    /* ===================================================================
     *  SEARCH PRODUCTS  — POST /product/202502/products/search
     *  page_size & page_token are URL query params, body is for filters
     * =================================================================== */
    public function searchProducts(
        string $accessToken,
        string $shopCipher,
        int $pageSize = 100,
        ?string $pageToken = null
    ): array {
        $path = '/product/202502/products/search';
        $timestamp = time();

        $queryParams = [
            'app_key'     => $this->appKey,
            'timestamp'   => $timestamp,
            'shop_cipher' => $shopCipher,
            'page_size'   => $pageSize,
        ];

        if ($pageToken) {
            $queryParams['page_token'] = $pageToken;
        }

        $bodyJson = '{}';

        $sign = $this->buildSign($path, $queryParams, $bodyJson);

        $queryParams['sign']         = $sign;
        $queryParams['access_token'] = $accessToken;

        $url = $this->apiBase . $path . '?' . http_build_query($queryParams);

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'x-tts-access-token' => $accessToken,
        ])->withBody($bodyJson, 'application/json')->post($url);

        $data = $response->json();

        if (($data['code'] ?? -1) !== 0) {
            Log::error('TikTok searchProducts failed', $data);
            throw new \RuntimeException('Search Products failed: ' . ($data['message'] ?? 'Unknown error'));
        }

        return $data['data'] ?? [];
    }

    /* ===================================================================
     *  FETCH ALL PRODUCTS  — handles pagination + dedup by sku_id+platform
     * =================================================================== */
    public function fetchAllProducts(string $accessToken, string $shopCipher): array
    {
        $allProducts  = [];
        $seenSkuKeys  = []; // sku_id + platform as dedup key
        $pageToken    = null;
        $pageNumber   = 0;
        $totalSkipped = 0;

        do {
            $pageNumber++;

            $result = $this->searchProducts($accessToken, $shopCipher, 100, $pageToken);

            $products  = $result['products'] ?? [];
            $pageToken = $result['next_page_token'] ?? null;

            foreach ($products as $product) {
                $skus = $product['skus'] ?? [];
                $platforms = $this->extractPlatforms($product);

                foreach ($skus as $sku) {
                    foreach ($platforms as $platform) {
                        $dedupeKey = ($sku['id'] ?? '') . '|' . $platform;

                        if (isset($seenSkuKeys[$dedupeKey])) {
                            $totalSkipped++;
                            continue;
                        }

                        $seenSkuKeys[$dedupeKey] = true;

                        $allProducts[] = [
                            'product_id'     => $product['id'] ?? '',
                            'sku_id'         => $sku['id'] ?? '',
                            'platform'       => $platform,
                            'title'          => $product['title'] ?? '',
                            'product_status' => $product['status'] ?? '',
                            'quantity'       => $sku['inventory'][0]['quantity'] ?? 0,
                            'price'          => $sku['price']['tax_exclusive_price'] ?? '0',
                            'seller_sku'     => $sku['seller_sku'] ?? '',
                            'status_info'    => $sku['status_info']['status'] ?? '',
                            'current_status' => $product['status'] ?? '',
                        ];
                    }
                }
            }

            Log::info("Page {$pageNumber}: got " . count($products) . " products, total unique SKUs: " . count($allProducts) . ", skipped: {$totalSkipped}");

            // Small delay to avoid rate limiting
            if ($pageToken) {
                usleep(200000); // 200ms
            }
        } while ($pageToken);

        return [
            'products'     => $allProducts,
            'total_unique' => count($allProducts),
            'total_pages'  => $pageNumber,
            'total_skipped' => $totalSkipped,
        ];
    }

    /* ---------- Extract platforms from integrated_platform_statuses ---------- */
    private function extractPlatforms(array $product): array
    {
        $platforms = [];
        $statuses  = $product['integrated_platform_statuses'] ?? [];

        foreach ($statuses as $status) {
            $platform = $status['platform'] ?? null;
            if ($platform) {
                $platforms[] = strtoupper($platform);
            }
        }

        // Fallback: if no platform info, assume TIKTOK
        return empty($platforms) ? ['TIKTOK'] : array_unique($platforms);
    }

    /* ===================================================================
     *  UPDATE INVENTORY
     *  PUT /product/202309/products/{product_id}/inventory/update
     *
     *  Header : Content-Type: application/json
     *           x-tts-access-token: {access_token}
     *  Params : app_key, sign, timestamp, shop_cipher
     *  Body   : { "skus": [{ "id": "...", "inventory": [{ "quantity": N }] }] }
     * =================================================================== */
    public function updateInventory(
        string $accessToken,
        string $shopCipher,
        string $productId,
        string $skuId,
        int    $quantity
    ): array {
        $path      = "/product/202309/products/{$productId}/inventory/update";
        $timestamp = time();

        $queryParams = [
            'app_key'     => $this->appKey,
            'timestamp'   => $timestamp,
            'shop_cipher' => $shopCipher,
        ];

        $body = [
            'skus' => [
                [
                    'id'        => $skuId,
                    'inventory' => [['quantity' => $quantity]],
                ],
            ],
        ];
        $bodyJson = json_encode($body);

        $sign = $this->buildSign($path, $queryParams, $bodyJson);

        $queryParams['sign']         = $sign;
        $queryParams['access_token'] = $accessToken;

        $url = $this->apiBase . $path . '?' . http_build_query($queryParams);

        $response = Http::timeout(30)
            ->withHeaders([
                'Content-Type'       => 'application/json',
                'x-tts-access-token' => $accessToken,
            ])
            ->withBody($bodyJson, 'application/json')
            ->post($url); // ← GANTI dari put() ke post()

        $data = $response->json();

        if (($data['code'] ?? -1) !== 0) {
            Log::error('TikTok updateInventory failed', [
                'product_id' => $productId,
                'sku_id'     => $skuId,
                'quantity'   => $quantity,
                'response'   => $data,
            ]);
            throw new \RuntimeException(
                'TikTok Update Inventory Error [' . ($data['code'] ?? '?') . ']: '
                    . ($data['message'] ?? 'Unknown error')
            );
        }

        Log::info('TikTok updateInventory success', [
            'product_id' => $productId,
            'sku_id'     => $skuId,
            'quantity'   => $quantity,
        ]);

        return $data['data'] ?? [];
    }

    /* ===================================================================
     *  AUTH URL — redirect user to TikTok OAuth
     * =================================================================== */
    public function getAuthUrl(): string
    {
        return "https://services.tiktokshop.com/open/authorize?service_id={$this->serviceId}";
    }
}
