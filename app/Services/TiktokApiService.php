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
    private string $serviceId;

    public function __construct()
    {
        $this->appKey    = config('services.tiktok.app_key');
        $this->appSecret = config('services.tiktok.app_secret');
        $this->apiBase   = config('services.tiktok.api_base');
        $this->authBase  = config('services.tiktok.auth_base');
        $this->serviceId = config('services.tiktok.service_id');
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
<<<<<<< Updated upstream
=======
     *  SEARCH ORDERS  — POST /order/202309/orders/search
     *
     *  Query  : app_key, sign, timestamp, page_size, page_token, shop_cipher
     *  Header : x-tts-access-token, Content-Type: application/json
     *  Body   : { order_status, create_time_ge, create_time_lt, ... }
     * =================================================================== */
    public function searchOrders(
        string  $accessToken,
        string  $shopCipher,
        int     $pageSize = 20,
        ?string $pageToken = null,
        array   $filters = []
    ): array {
        $path      = '/order/202309/orders/search';
        $timestamp = time();

        $queryParams = [
            'app_key'     => $this->appKey,
            'timestamp'   => $timestamp,
            'shop_cipher' => $shopCipher,
            'page_size'   => min($pageSize, 100),
        ];

        if ($pageToken) {
            $queryParams['page_token'] = $pageToken;
        }

        $bodyJson = !empty($filters) ? json_encode($filters) : '{}';

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
            ->post($url);

        $data = $response->json();

        if (($data['code'] ?? -1) !== 0) {
            Log::error('TikTok searchOrders failed', [
                'filters'  => $filters,
                'response' => $data,
            ]);
            throw new \RuntimeException(
                'TikTok Search Orders Error [' . ($data['code'] ?? '?') . ']: '
                    . ($data['message'] ?? 'Unknown error')
            );
        }

        return $data['data'] ?? [];
    }

    /* ===================================================================
     *  GET ORDER DETAIL  — GET /order/202309/orders
     *
     *  Query  : app_key, sign, timestamp, ids (comma-sep, max 50), shop_cipher
     *  Header : x-tts-access-token, Content-Type: application/json
     * =================================================================== */
    public function getOrderDetail(
        string $accessToken,
        string $shopCipher,
        array  $orderIds
    ): array {
        $path      = '/order/202309/orders';
        $timestamp = time();

        $queryParams = [
            'app_key'     => $this->appKey,
            'timestamp'   => $timestamp,
            'shop_cipher' => $shopCipher,
            'ids'         => implode(',', array_slice($orderIds, 0, 50)),
        ];

        $sign = $this->buildSign($path, $queryParams);

        $queryParams['sign']         = $sign;
        $queryParams['access_token'] = $accessToken;

        $url = $this->apiBase . $path . '?' . http_build_query($queryParams);

        $response = Http::timeout(30)
            ->withHeaders([
                'Content-Type'       => 'application/json',
                'x-tts-access-token' => $accessToken,
            ])
            ->get($url);

        $data = $response->json();

        if (($data['code'] ?? -1) !== 0) {
            Log::error('TikTok getOrderDetail failed', [
                'order_ids' => $orderIds,
                'response'  => $data,
            ]);
            throw new \RuntimeException(
                'TikTok Get Order Detail Error [' . ($data['code'] ?? '?') . ']: '
                    . ($data['message'] ?? 'Unknown error')
            );
        }

        return $data['data'] ?? [];
    }

    /* ===================================================================
     *  GET PRODUCT DETAIL  — GET /product/202309/products/{product_id}
     *
     *  Query  : app_key, sign, timestamp, shop_cipher
     *  Header : x-tts-access-token, Content-Type: application/json
     * =================================================================== */
    public function getProductDetail(
        string $accessToken,
        string $shopCipher,
        string $productId
    ): array {
        $path      = "/product/202309/products/{$productId}";
        $timestamp = time();

        $queryParams = [
            'app_key'     => $this->appKey,
            'timestamp'   => $timestamp,
            'shop_cipher' => $shopCipher,
        ];

        $sign = $this->buildSign($path, $queryParams);

        $queryParams['sign']         = $sign;
        $queryParams['access_token'] = $accessToken;

        $url = $this->apiBase . $path . '?' . http_build_query($queryParams);

        $response = Http::timeout(30)
            ->withHeaders([
                'Content-Type'       => 'application/json',
                'x-tts-access-token' => $accessToken,
            ])
            ->get($url);

        $data = $response->json();

        if (($data['code'] ?? -1) !== 0) {
            Log::error('TikTok getProductDetail failed', [
                'product_id' => $productId,
                'response'   => $data,
            ]);
            throw new \RuntimeException(
                'TikTok Get Product Detail Error [' . ($data['code'] ?? '?') . ']: '
                    . ($data['message'] ?? 'Unknown error')
            );
        }

        return $data['data'] ?? [];
    }

    /* ===================================================================
     *  EDIT PRODUCT  — PUT /product/202509/products/{product_id}
     *
     *  Query  : app_key, sign, timestamp, shop_cipher
     *  Header : x-tts-access-token, Content-Type: application/json
     *  Body   : { title, description, skus, ... }
     * =================================================================== */
    public function editProduct(
        string $accessToken,
        string $shopCipher,
        string $productId,
        array  $body
    ): array {
        $path      = "/product/202509/products/{$productId}";
        $timestamp = time();

        $queryParams = [
            'app_key'     => $this->appKey,
            'timestamp'   => $timestamp,
            'shop_cipher' => $shopCipher,
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
            ->put($url);

        $data = $response->json();

        if (($data['code'] ?? -1) !== 0) {
            Log::error('TikTok editProduct failed', [
                'product_id' => $productId,
                'body'       => $body,
                'response'   => $data,
            ]);
            throw new \RuntimeException(
                'TikTok Edit Product Error [' . ($data['code'] ?? '?') . ']: '
                    . ($data['message'] ?? 'Unknown error')
            );
        }

        Log::info('TikTok editProduct success', ['product_id' => $productId]);

        return $data['data'] ?? [];
    }

    /* ===================================================================
     *  UPDATE SHOP WEBHOOK — PUT /event/202309/webhooks
     *  Mendaftarkan webhook URL untuk event tertentu
     * =================================================================== */
    public function updateShopWebhook(
        string $accessToken,
        string $shopCipher,
        string $address,
        string $eventType
    ): array {
        $path      = '/event/202309/webhooks';
        $timestamp = time();

        $queryParams = [
            'app_key'     => $this->appKey,
            'timestamp'   => $timestamp,
            'shop_cipher' => $shopCipher,
        ];

        $bodyArray = [
            'address'    => $address,
            'event_type' => $eventType,
        ];

        $bodyJson = json_encode($bodyArray);

        $sign = $this->buildSign($path, $queryParams, $bodyJson);

        $queryParams['sign']         = $sign;
        $queryParams['access_token'] = $accessToken;

        $url = $this->apiBase . $path . '?' . http_build_query($queryParams);

        $response = Http::withHeaders([
            'Content-Type'       => 'application/json',
            'x-tts-access-token' => $accessToken,
        ])->withBody($bodyJson, 'application/json')->put($url);

        $data = $response->json();

        if (($data['code'] ?? -1) !== 0) {
            Log::error('TikTok updateShopWebhook failed', [
                'event_type' => $eventType,
                'address'    => $address,
                'response'   => $data,
            ]);
            throw new \RuntimeException(
                'Update Webhook Error [' . ($data['code'] ?? '?') . ']: '
                    . ($data['message'] ?? 'Unknown error')
            );
        }

        return $data['data'] ?? [];
    }

    /* ===================================================================
     *  GET SHOP WEBHOOKS — GET /event/202309/webhooks
     *  Lihat semua webhook yang sudah terdaftar di shop
     * =================================================================== */
    public function getShopWebhooks(string $accessToken, string $shopCipher): array
    {
        $path      = '/event/202309/webhooks';
        $timestamp = time();

        $queryParams = [
            'app_key'     => $this->appKey,
            'timestamp'   => $timestamp,
            'shop_cipher' => $shopCipher,
        ];

        $sign = $this->buildSign($path, $queryParams);

        $queryParams['sign']         = $sign;
        $queryParams['access_token'] = $accessToken;

        $url = $this->apiBase . $path . '?' . http_build_query($queryParams);

        $response = Http::withHeaders([
            'Content-Type'       => 'application/json',
            'x-tts-access-token' => $accessToken,
        ])->get($url);

        $data = $response->json();

        if (($data['code'] ?? -1) !== 0) {
            Log::error('TikTok getShopWebhooks failed', $data);
            throw new \RuntimeException(
                'Get Webhooks Error [' . ($data['code'] ?? '?') . ']: '
                    . ($data['message'] ?? 'Unknown error')
            );
        }

        return $data['data'] ?? [];
    }

    /* ===================================================================
>>>>>>> Stashed changes
     *  AUTH URL — redirect user to TikTok OAuth
     * =================================================================== */
    public function getAuthUrl(): string
    {
        return "https://services.tiktokshop.com/open/authorize?service_id={$this->serviceId}";
    }
}
