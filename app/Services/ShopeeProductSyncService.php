<?php

namespace App\Services;

use App\Models\AccountShopShopee;
use App\Models\MarketplaceChannel;
use App\Models\ProdukSaya;
use Illuminate\Support\Facades\Log;

/**
 * ShopeeProductSyncService — Sinkronisasi daftar produk dari Shopee API ke tabel produk_saya.
 *
 * Alur syncForAccount():
 *   1. Refresh access token jika expired
 *   2. getItemList (paginated) dari Shopee API
 *   3. getItemBaseInfo (batch per 50) untuk detail (harga, SKU, stok)
 *   4. Upsert ke tabel produk_saya (by account_id + sku_id + platform)
 *   5. Update last_sync_at di account
 */
class ShopeeProductSyncService
{
    public function __construct(
        private ShopeeApiService $shopeeService
    ) {}

    /**
     * @return array{saved: int, pages: int, skipped: int, error: string|null}
     */
    public function syncForAccount(AccountShopShopee $account): array
    {
        try {
            // 1. Refresh token jika expired
            $this->ensureFreshToken($account);

            $shopId      = (int) $account->shop_id;
            $accessToken = $account->access_token;

            if (!$shopId || !$accessToken) {
                return ['saved' => 0, 'pages' => 0, 'skipped' => 0, 'error' => 'shop_id atau access_token kosong'];
            }

            // 2. Fetch all items (paginated)
            $allItemIds = [];
            $offset     = 0;
            $pageSize   = 100;
            $totalPages = 0;

            do {
                $totalPages++;
                $result   = $this->shopeeService->getItemList($accessToken, $shopId, $offset, $pageSize);
                $response = $result['response'] ?? $result;
                $items    = $response['item'] ?? [];
                $hasNext  = $response['has_next_page'] ?? false;
                $offset   = $response['next_offset'] ?? ($offset + $pageSize);

                foreach ($items as $item) {
                    $allItemIds[] = $item['item_id'];
                }

                if ($hasNext) {
                    usleep(200_000); // 200ms rate limiting
                }
            } while ($hasNext && $totalPages < 50);

            if (empty($allItemIds)) {
                $account->update(['last_sync_at' => now()]);
                return ['saved' => 0, 'pages' => $totalPages, 'skipped' => 0, 'error' => null];
            }

            // 3. Get detailed info per batch of 50
            $allProducts = [];
            $chunks      = array_chunk($allItemIds, 50);
            $skipped     = 0;

            foreach ($chunks as $chunk) {
                $detailResult = $this->shopeeService->getItemBaseInfo($accessToken, $shopId, $chunk);
                $detailItems  = $detailResult['response']['item_list'] ?? [];

                foreach ($detailItems as $item) {
                    $hasModel = (bool) ($item['has_model'] ?? false);

                    if ($hasModel) {
                        // get_item_base_info TIDAK menyertakan data varian;
                        // panggil get_model_list secara terpisah untuk setiap item
                        $modelResult = $this->shopeeService->getModelList(
                            $accessToken, $shopId, (int) ($item['item_id'] ?? 0)
                        );
                        $models = $modelResult['response']['model'] ?? [];

                        if (!empty($models)) {
                            foreach ($models as $model) {
                                $allProducts[] = $this->mapToProduct($item, $model);
                            }
                        } else {
                            // has_model=true tapi model list kosong → simpan sebagai produk tunggal
                            $allProducts[] = $this->mapToProduct($item, null);
                        }

                        usleep(200_000); // rate limit per panggilan getModelList
                    } else {
                        // Tidak ada varian — satu baris produk
                        $allProducts[] = $this->mapToProduct($item, null);
                    }
                }

                usleep(200_000); // 200ms rate limiting per batch getItemBaseInfo
            }

            // 4. Upsert to produk_saya
            $saved = $this->saveProducts($account, $allProducts);

            // 5. Mark last sync
            $account->update(['last_sync_at' => now()]);

            Log::info("ShopeeProductSyncService: sync selesai [{$account->seller_name}] — {$saved} produk, {$totalPages} halaman");

            return [
                'saved'   => $saved,
                'pages'   => $totalPages,
                'skipped' => $skipped,
                'error'   => null,
            ];
        } catch (\Throwable $e) {
            Log::error("ShopeeProductSyncService::syncForAccount gagal [account_id={$account->id}]: " . $e->getMessage());

            return [
                'saved'   => 0,
                'pages'   => 0,
                'skipped' => 0,
                'error'   => $e->getMessage(),
            ];
        }
    }

    /**
     * Map Shopee item+model to the produk_saya format
     */
    private function mapToProduct(array $item, ?array $model): array
    {
        $itemId  = (string) ($item['item_id'] ?? 0);
        $modelId = $model ? (string) ($model['model_id'] ?? 0) : '0';
        $skuId   = $model ? "{$itemId}_{$modelId}" : "{$itemId}_0";

        // Status: untuk varian gunakan model_status, untuk produk utama gunakan item_status
        $rawStatus = $model
            ? strtoupper($model['model_status'] ?? $item['item_status'] ?? 'NORMAL')
            : strtoupper($item['item_status'] ?? 'NORMAL');

        $statusMap = [
            'NORMAL'              => 'ACTIVATE',
            'MODEL_NORMAL'        => 'ACTIVATE',
            'BANNED'              => 'PLATFORM_DEACTIVATED',
            'MODEL_BANNED'        => 'PLATFORM_DEACTIVATED',
            'DELETED'             => 'DELETED',
            'UNLIST'              => 'SELLER_DEACTIVATED',
            'MODEL_DELETED'       => 'DELETED',
        ];

        // Stock: stock_info_v2 -> seller_stock[0] -> stock
        $stock = $model
            ? (int) ($model['stock_info_v2']['seller_stock'][0]['stock'] ?? 0)
            : (int) ($item['stock_info_v2']['seller_stock'][0]['stock'] ?? 0);

        // Price: price_info[0].current_price
        $price = $model
            ? (float) ($model['price_info'][0]['current_price'] ?? $model['price_info'][0]['original_price'] ?? 0)
            : (float) ($item['price_info'][0]['current_price'] ?? $item['price_info'][0]['original_price'] ?? 0);

        // Seller SKU: model_sku untuk varian, item_sku untuk produk tanpa varian
        $sellerSku = $model ? ($model['model_sku'] ?? '') : ($item['item_sku'] ?? '');

        return [
            'product_id'     => $itemId,
            'sku_id'         => $skuId,
            'platform'       => 'SHOPEE',
            'title'          => $item['item_name'] ?? '',
            'product_status' => $statusMap[$rawStatus] ?? $rawStatus,
            'quantity'       => $stock,
            'price'          => $price,
            'seller_sku'     => $sellerSku,
            'status_info'    => $rawStatus,
            'current_status' => $statusMap[$rawStatus] ?? $rawStatus,
        ];
    }

    /**
     * Upsert products to produk_saya table for a Shopee account.
     */
    public function saveProducts(AccountShopShopee $account, array $products): int
    {
        if (empty($products)) return 0;

        $channelId = $account->channel_id
            ?? MarketplaceChannel::where('code', MarketplaceChannel::SHOPEE)->value('id');

        $saved  = 0;
        $chunks = array_chunk($products, 100);

        foreach ($chunks as $chunk) {
            $rows = array_map(function ($p) use ($account, $channelId) {
                return [
                    'account_id'     => $account->id,
                    'channel_id'     => $channelId,
                    'product_id'     => $p['product_id'],
                    'sku_id'         => $p['sku_id'],
                    'platform'       => 'SHOPEE',
                    'title'          => $p['title'],
                    'product_status' => $p['product_status'],
                    'quantity'       => $p['quantity'],
                    'price'          => $p['price'],
                    'seller_sku'     => $p['seller_sku'],
                    'status_info'    => $p['status_info'],
                    'current_status' => $p['current_status'],
                    'updated_at'     => now(),
                    'created_at'     => now(),
                ];
            }, $chunk);

            ProdukSaya::upsert(
                $rows,
                ['account_id', 'sku_id', 'platform'],
                ['title', 'product_status', 'quantity', 'price', 'seller_sku', 'status_info', 'current_status', 'channel_id', 'updated_at']
            );

            $saved += count($chunk);
        }

        return $saved;
    }

    /**
     * Ensure access token is still valid, refresh if expired.
     */
    private function ensureFreshToken(AccountShopShopee $account): void
    {
        if (!$account->isTokenExpired()) return;

        Log::info("ShopeeProductSyncService: refresh token untuk [{$account->id}] {$account->seller_name}");

        $shopId    = (int) $account->shop_id;
        $tokenData = $this->shopeeService->refreshAccessToken($account->refresh_token, $shopId);

        $expireIn        = (int) ($tokenData['expire_in'] ?? 14400);
        $refreshExpireIn = (int) ($tokenData['refresh_token_expire_in'] ?? 2592000);

        $account->update([
            'access_token'            => $tokenData['access_token'],
            'access_token_expire_in'  => now()->addSeconds($expireIn),
            'refresh_token'           => $tokenData['refresh_token'] ?? $account->refresh_token,
            'refresh_token_expire_in' => now()->addSeconds($refreshExpireIn),
            'token_obtained_at'       => now(),
            'status'                  => 'active',
        ]);

        $account->refresh();
    }
}
