<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ShopeeApiService
{
    private int    $partnerId;
    private string $partnerKey;
    private string $apiBase;
    private string $redirectUrl;

    public function __construct()
    {
        $this->partnerId   = (int) (config('services.shopee.partner_id') ?? 0);
        $this->partnerKey  = (string) (config('services.shopee.partner_key') ?? '');
        $this->apiBase     = rtrim((string) (config('services.shopee.api_base') ?? 'https://partner.shopeemobile.com'), '/');
        $this->redirectUrl = (string) (config('services.shopee.redirect_url') ?? '');
    }

    /* ===================================================================
     *  SIGNATURE — untuk endpoint AUTH (tanpa access_token & shop_id)
     *  Format: partner_id + path + timestamp
     * =================================================================== */
    public function buildAuthSign(string $path, int $timestamp): string
    {
        $base = $this->partnerId . $path . $timestamp;
        return hash_hmac('sha256', $base, $this->partnerKey);
    }

    /* ===================================================================
     *  SIGNATURE — untuk endpoint SHOP (dengan access_token + shop_id)
     *  Format: partner_id + path + timestamp + access_token + shop_id
     * =================================================================== */
    public function buildShopSign(string $path, int $timestamp, string $accessToken, int $shopId): string
    {
        $base = $this->partnerId . $path . $timestamp . $accessToken . $shopId;
        return hash_hmac('sha256', $base, $this->partnerKey);
    }

    /* ===================================================================
     *  AUTH URL — arahkan user ke halaman otorisasi Shopee
     *  GET /api/v2/shop/auth_partner
     * =================================================================== */
    public function getAuthUrl(): string
    {
        $path      = '/api/v2/shop/auth_partner';
        $timestamp = time();
        $sign      = $this->buildAuthSign($path, $timestamp);

        return $this->apiBase . $path . '?' . http_build_query([
            'partner_id' => $this->partnerId,
            'timestamp'  => $timestamp,
            'sign'       => $sign,
            'redirect'   => $this->redirectUrl,
        ]);
    }

    /* ===================================================================
     *  GET ACCESS TOKEN — tukar code + shop_id → access_token
     *  POST /api/v2/auth/token/get
     *
     *  Response:
     *    access_token, refresh_token, expire_in, refresh_token_expire_in,
     *    shop_id, merchant_id_list, error, message
     * =================================================================== */
    public function getAccessToken(string $code, int $shopId): array
    {
        $path      = '/api/v2/auth/token/get';
        $timestamp = time();
        $sign      = $this->buildAuthSign($path, $timestamp);

        $response = Http::post(
            $this->apiBase . $path . '?' . http_build_query([
                'partner_id' => $this->partnerId,
                'timestamp'  => $timestamp,
                'sign'       => $sign,
            ]),
            [
                'code'       => $code,
                'shop_id'    => $shopId,
                'partner_id' => $this->partnerId,
            ]
        );

        $data = $response->json();
        Log::info('Shopee getAccessToken', ['shop_id' => $shopId, 'status' => $response->status(), 'has_token' => !empty($data['access_token'])]);

        if (!empty($data['error']) && $data['error'] !== '') {
            throw new \RuntimeException('Shopee error [' . $data['error'] . ']: ' . ($data['message'] ?? 'unknown'));
        }

        return $data;
    }

    /* ===================================================================
     *  REFRESH ACCESS TOKEN
     *  POST /api/v2/auth/access_token/get
     * =================================================================== */
    public function refreshAccessToken(string $refreshToken, int $shopId): array
    {
        $path      = '/api/v2/auth/access_token/get';
        $timestamp = time();
        $sign      = $this->buildAuthSign($path, $timestamp);

        $response = Http::post(
            $this->apiBase . $path . '?' . http_build_query([
                'partner_id' => $this->partnerId,
                'timestamp'  => $timestamp,
                'sign'       => $sign,
            ]),
            [
                'shop_id'       => $shopId,
                'refresh_token' => $refreshToken,
                'partner_id'    => $this->partnerId,
            ]
        );

        $data = $response->json();
        Log::info('Shopee refreshToken', ['shop_id' => $shopId, 'status' => $response->status(), 'has_token' => !empty($data['access_token'])]);

        if (!empty($data['error']) && $data['error'] !== '') {
            throw new \RuntimeException('Shopee refresh error [' . $data['error'] . ']: ' . ($data['message'] ?? 'unknown'));
        }

        return $data;
    }

    /* ===================================================================
     *  GET SHOP INFO
     *  GET /api/v2/shop/get_shop_info
     * =================================================================== */
    public function getShopInfo(string $accessToken, int $shopId): array
    {
        $path      = '/api/v2/shop/get_shop_info';
        $timestamp = time();
        $sign      = $this->buildShopSign($path, $timestamp, $accessToken, $shopId);

        $response = Http::get($this->apiBase . $path, [
            'partner_id'   => $this->partnerId,
            'timestamp'    => $timestamp,
            'sign'         => $sign,
            'access_token' => $accessToken,
            'shop_id'      => $shopId,
        ]);

        return $response->json();
    }

    /* ===================================================================
     *  GET ITEM LIST (Produk)
     *  GET /api/v2/product/get_item_list
     * =================================================================== */
    public function getItemList(string $accessToken, int $shopId, int $offset = 0, int $pageSize = 100, string $itemStatus = 'NORMAL'): array
    {
        $path      = '/api/v2/product/get_item_list';
        $timestamp = time();
        $sign      = $this->buildShopSign($path, $timestamp, $accessToken, $shopId);

        $response = Http::get($this->apiBase . $path, [
            'partner_id'   => $this->partnerId,
            'timestamp'    => $timestamp,
            'sign'         => $sign,
            'access_token' => $accessToken,
            'shop_id'      => $shopId,
            'offset'       => $offset,
            'page_size'    => $pageSize,
            'item_status'  => $itemStatus,
        ]);

        return $response->json();
    }

    /* ===================================================================
     *  GET ITEM BASE INFO (Detail Produk)
     *  GET /api/v2/product/get_item_base_info
     * =================================================================== */
    public function getItemBaseInfo(string $accessToken, int $shopId, array $itemIds): array
    {
        $path      = '/api/v2/product/get_item_base_info';
        $timestamp = time();
        $sign      = $this->buildShopSign($path, $timestamp, $accessToken, $shopId);

        $response = Http::get($this->apiBase . $path, [
            'partner_id'   => $this->partnerId,
            'timestamp'    => $timestamp,
            'sign'         => $sign,
            'access_token' => $accessToken,
            'shop_id'      => $shopId,
            'item_id_list' => implode(',', $itemIds),
        ]);

        return $response->json();
    }

    /* ===================================================================
     *  GET ORDER LIST
     *  GET /api/v2/order/get_order_list
     * =================================================================== */
    public function getOrderList(
        string $accessToken,
        int    $shopId,
        int    $timeFrom,
        int    $timeTo,
        int    $pageSize = 50,
        string $cursor   = '',
        string $orderStatus = 'ALL'
    ): array {
        $path      = '/api/v2/order/get_order_list';
        $timestamp = time();
        $sign      = $this->buildShopSign($path, $timestamp, $accessToken, $shopId);

        $params = [
            'partner_id'   => $this->partnerId,
            'timestamp'    => $timestamp,
            'sign'         => $sign,
            'access_token' => $accessToken,
            'shop_id'      => $shopId,
            'time_range_field' => 'create_time',
            'time_from'    => $timeFrom,
            'time_to'      => $timeTo,
            'page_size'    => $pageSize,
            'response_optional_fields' => 'order_status',
        ];

        if ($cursor) {
            $params['cursor'] = $cursor;
        }

        $response = Http::get($this->apiBase . $path, $params);
        return $response->json();
    }

    /* ===================================================================
     *  UPDATE STOCK (update_stock)
     *  POST /api/v2/product/update_stock
     * =================================================================== */
    public function updateStock(string $accessToken, int $shopId, int $itemId, array $stockList): array
    {
        $path      = '/api/v2/product/update_stock';
        $timestamp = time();
        $sign      = $this->buildShopSign($path, $timestamp, $accessToken, $shopId);

        $response = Http::post(
            $this->apiBase . $path . '?' . http_build_query([
                'partner_id'   => $this->partnerId,
                'timestamp'    => $timestamp,
                'sign'         => $sign,
                'access_token' => $accessToken,
                'shop_id'      => $shopId,
            ]),
            [
                'item_id'    => $itemId,
                'stock_list' => $stockList,
            ]
        );

        return $response->json();
    }

    /* ===================================================================
     *  Helper: apakah credentials sudah di-set?
     * =================================================================== */
    public function isConfigured(): bool
    {
        return $this->partnerId > 0 && !empty($this->partnerKey) && !empty($this->redirectUrl);
    }
}
