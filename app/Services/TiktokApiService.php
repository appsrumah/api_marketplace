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

        // Handle error cases — in particular multiple warehouses detected
        if (($data['code'] ?? -1) !== 0) {
            Log::warning('TikTok updateInventory failed (initial attempt)', [
                'product_id' => $productId,
                'sku_id'     => $skuId,
                'quantity'   => $quantity,
                'response'   => $data,
            ]);

            // Specific error: multiple warehouses detected — attempt to retry
            // with explicit per-warehouse inventory entries if possible.
            $errorCode = $data['code'] ?? null;
            if ($errorCode == 12019028) {
                // Try to extract warehouses from API response
                $warehouses = [];

                // Common response shapes to inspect
                if (!empty($data['data']['warehouses'])) {
                    $warehouses = $data['data']['warehouses'];
                } elseif (!empty($data['data']['warehouse_list'])) {
                    $warehouses = $data['data']['warehouse_list'];
                } elseif (!empty($data['extra']['warehouses'])) {
                    $warehouses = $data['extra']['warehouses'];
                }

                // Fallback: fetch product detail to obtain warehouse info
                if (empty($warehouses)) {
                    try {
                        $detail = $this->getProductDetail($accessToken, $shopCipher, $productId);
                        // product detail may contain warehouses under skus -> inventory or warehouse_info
                        if (!empty($detail['skus'])) {
                            foreach ($detail['skus'] as $s) {
                                if (!empty($s['warehouses'])) {
                                    $warehouses = $s['warehouses'];
                                    break;
                                }
                                if (!empty($s['inventory']) && is_array($s['inventory'])) {
                                    // inventory entries may have warehouse_id
                                    $warehouses = array_map(function ($inv) {
                                        return $inv;
                                    }, $s['inventory']);
                                    break;
                                }
                            }
                        }
                        if (empty($warehouses) && !empty($detail['warehouse_info'])) {
                            $warehouses = $detail['warehouse_info'];
                        }
                    } catch (\Throwable $e) {
                        Log::warning('Failed to fetch product detail for warehouse info', ['error' => $e->getMessage()]);
                    }
                }

                // Normalize warehouse IDs and retry if we found anything
                $warehouseIds = [];
                if (!empty($warehouses)) {
                    foreach ($warehouses as $w) {
                        if (is_array($w)) {
                            if (!empty($w['warehouse_id'])) {
                                $warehouseIds[] = $w['warehouse_id'];
                            } elseif (!empty($w['id'])) {
                                $warehouseIds[] = $w['id'];
                            } elseif (!empty($w['warehouseCode'])) {
                                $warehouseIds[] = $w['warehouseCode'];
                            }
                        } elseif (is_string($w) || is_numeric($w)) {
                            $warehouseIds[] = (string) $w;
                        }
                    }
                }

                $warehouseIds = array_values(array_unique(array_filter($warehouseIds)));

                if (!empty($warehouseIds)) {
                    // Build inventory entries per warehouse with the same requested quantity
                    $inventories = [];
                    foreach ($warehouseIds as $wid) {
                        $inventories[] = [
                            'warehouse_id' => $wid,
                            'quantity'     => $quantity,
                        ];
                    }

                    $retryBody = [
                        'skus' => [
                            [
                                'id'        => $skuId,
                                'inventory' => $inventories,
                            ],
                        ],
                    ];

                    $retryJson = json_encode($retryBody);
                    $retrySign = $this->buildSign($path, $queryParams, $retryJson);
                    $queryParams['sign'] = $retrySign;
                    $queryParams['access_token'] = $accessToken;
                    $retryUrl = $this->apiBase . $path . '?' . http_build_query($queryParams);

                    Log::info('Retrying TikTok updateInventory with per-warehouse entries', [
                        'product_id'   => $productId,
                        'sku_id'       => $skuId,
                        'warehouse_ids'=> $warehouseIds,
                        'quantity'     => $quantity,
                    ]);

                    $retryResp = Http::timeout(30)
                        ->withHeaders([
                            'Content-Type'       => 'application/json',
                            'x-tts-access-token' => $accessToken,
                        ])
                        ->withBody($retryJson, 'application/json')
                        ->post($retryUrl);

                    $retryData = $retryResp->json();
                    if (($retryData['code'] ?? -1) === 0) {
                        Log::info('TikTok updateInventory retry success', [
                            'product_id' => $productId,
                            'sku_id'     => $skuId,
                            'quantity'   => $quantity,
                            'warehouses' => $warehouseIds,
                        ]);
                        return $retryData['data'] ?? [];
                    }

                    Log::error('TikTok updateInventory retry failed', [
                        'product_id' => $productId,
                        'sku_id'     => $skuId,
                        'quantity'   => $quantity,
                        'response'   => $retryData,
                    ]);

                    throw new \RuntimeException(
                        'TikTok Update Inventory Error [' . ($retryData['code'] ?? '?') . ']: '
                            . ($retryData['message'] ?? 'Unknown error')
                    );
                }
            }

            // Generic error fallback
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
     *  AUTH URL — redirect user to TikTok OAuth
     * =================================================================== */
    public function getAuthUrl(): string
    {
        return "https://services.tiktokshop.com/open/authorize?service_id={$this->serviceId}";
    }
}
